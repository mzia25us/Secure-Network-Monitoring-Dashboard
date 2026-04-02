<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require 'database.php';
require 'csrf.php';

/* =========================
   ACCESS CONTROL
   ========================= */

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

$timeout_duration = 900;

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
    session_unset();
    session_destroy();
    header('Location: login.php?timeout=1');
    exit();
}

$_SESSION['last_activity'] = time();

/* =========================
   GET DEVICE ID
   ========================= */

$device_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($device_id <= 0) {
    header('Location: devices.php');
    exit();
}

/* =========================
   LOAD DEVICE DETAILS
   ========================= */

$device_stmt = $conn->prepare("
    SELECT 
        d.id,
        d.device_name,
        d.ip_address,
        d.port,
        d.device_type,
        d.notes,
        d.is_active,
        d.created_at,
        sl.status AS latest_status,
        sl.latency_ms AS latest_latency,
        sl.checked_at AS latest_checked,
        sl.message AS latest_message
    FROM devices d
    LEFT JOIN (
        SELECT s1.device_id, s1.status, s1.latency_ms, s1.checked_at, s1.message
        FROM status_logs s1
        INNER JOIN (
            SELECT device_id, MAX(checked_at) AS max_checked
            FROM status_logs
            GROUP BY device_id
        ) s2
        ON s1.device_id = s2.device_id
        AND s1.checked_at = s2.max_checked
    ) sl
    ON d.id = sl.device_id
    WHERE d.id = ?
    LIMIT 1
");

$device_stmt->bind_param("i", $device_id);
$device_stmt->execute();
$device_result = $device_stmt->get_result();

if (!$device_result || $device_result->num_rows !== 1) {
    $device_stmt->close();
    header('Location: devices.php');
    exit();
}

$device = $device_result->fetch_assoc();
$device_stmt->close();

/* =========================
   RECENT HISTORY FOR DEVICE
   ========================= */

$recent_logs = [];

$history_stmt = $conn->prepare("
    SELECT id, status, latency_ms, message, checked_at
    FROM status_logs
    WHERE device_id = ?
    ORDER BY checked_at DESC
    LIMIT 20
");
$history_stmt->bind_param("i", $device_id);
$history_stmt->execute();
$history_result = $history_stmt->get_result();

if ($history_result) {
    while ($row = $history_result->fetch_assoc()) {
        $recent_logs[] = $row;
    }
}

$history_stmt->close();

/* =========================
   RECENT SUMMARY
   Based on latest fetched logs
   ========================= */

$recent_total = count($recent_logs);
$recent_online = 0;
$recent_offline = 0;

foreach ($recent_logs as $log) {
    if ($log['status'] === 'online') {
        $recent_online++;
    } elseif ($log['status'] === 'offline') {
        $recent_offline++;
    }
}

$recent_uptime_percentage = $recent_total > 0
    ? round(($recent_online / $recent_total) * 100)
    : 0;

/* =========================
   DISPLAY HELPERS
   ========================= */

$latest_status = $device['latest_status'] ?? null;
$latest_latency = $device['latest_latency'];
$latest_checked = $device['latest_checked'];
$latest_message = $device['latest_message'] ?? '';

$is_active = (int) $device['is_active'] === 1;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Device Details | Secure Network Monitoring Dashboard</title>
    <link rel="icon" type="image/png" href="favicon.png">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<section class="dashboard-page dashboard-page-enhanced">
    <div class="dashboard-bg-orb dashboard-bg-orb-one"></div>
    <div class="dashboard-bg-orb dashboard-bg-orb-two"></div>

    <nav class="navbar dashboard-nav">
        <div class="nav-shell">
            <div class="logo">SNM<span>Dashboard</span></div>

            <div class="nav-links">
                <a href="index.php">Home</a>
                <a href="dashboard.php">Dashboard</a>
                <a href="devices.php" class="active-link">Devices</a>
                <a href="logs.php">Logs</a>

                <form method="POST" action="logout.php" class="logout-form">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                    <button type="submit" class="nav-button logout-button">Logout</button>
                </form>
            </div>
        </div>
    </nav>

    <div class="dashboard-container">

        <!-- Header -->
        <div class="dashboard-hero-panel">
            <div class="dashboard-hero-copy">
                <p class="dashboard-tag">Device Details</p>
                <h1><?php echo htmlspecialchars($device['device_name']); ?></h1>
                <p class="dashboard-subtext">
                    Inspect this monitored device’s configuration, latest known status, and recent monitoring history.
                </p>

                <div class="dashboard-hero-meta">
                    <div class="meta-chip">
                        <span class="meta-dot <?php echo $latest_status === 'online' ? 'meta-dot-live' : ''; ?>"></span>
                        <span>
                            <?php
                            if ($latest_status === 'online') {
                                echo 'Currently online';
                            } elseif ($latest_status === 'offline') {
                                echo 'Currently offline';
                            } else {
                                echo 'No status recorded yet';
                            }
                            ?>
                        </span>
                    </div>

                    <div class="meta-chip">
                        <span class="meta-dot"></span>
                        <span><?php echo $is_active ? 'Monitoring enabled' : 'Monitoring disabled'; ?></span>
                    </div>
                </div>
            </div>

            <div class="dashboard-hero-side">
                <div class="hero-side-card">
                    <span class="hero-side-label">Quick Actions</span>
                    <strong>Manage this device</strong>
                    <p>Update connection details, adjust notes, or return to the full device inventory.</p>
                    <div class="hero-side-actions">
                        <a href="edit-device.php?id=<?php echo (int)$device_id; ?>" class="btn-primary btn-small">Edit Device</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Summary cards -->
        <div class="summary-grid summary-grid-enhanced">
            <div class="summary-card summary-card-primary">
                <div class="summary-card-top">
                    <h3>Latest Status</h3>
                    <span class="summary-pill">Current</span>
                </div>
                <div class="summary-small">
                    <?php
                    if ($latest_status === 'online') {
                        echo 'Online';
                    } elseif ($latest_status === 'offline') {
                        echo 'Offline';
                    } else {
                        echo 'No data';
                    }
                    ?>
                </div>
                <p>Most recent monitoring result recorded for this device.</p>
            </div>

            <div class="summary-card">
                <div class="summary-card-top">
                    <h3>Latest Latency</h3>
                    <span class="summary-pill success-pill">Performance</span>
                </div>
                <div class="summary-small">
                    <?php echo $latest_latency !== null ? htmlspecialchars($latest_latency) . ' ms' : 'N/A'; ?>
                </div>
                <p>Most recent measured response time for this device.</p>
            </div>

            <div class="summary-card">
                <div class="summary-card-top">
                    <h3>Last Checked</h3>
                    <span class="summary-pill">Timestamp</span>
                </div>
                <div class="summary-small">
                    <?php echo !empty($latest_checked) ? htmlspecialchars(date('d M Y, H:i:s', strtotime($latest_checked))) : 'Never'; ?>
                </div>
                <p>Latest collector timestamp stored for this device.</p>
            </div>

            <div class="summary-card">
                <div class="summary-card-top">
                    <h3>Recent Uptime</h3>
                    <span class="summary-pill">Last 20</span>
                </div>
                <div class="summary-number"><?php echo $recent_uptime_percentage; ?>%</div>
                <p>Based on the most recent <?php echo $recent_total; ?> recorded checks.</p>
            </div>
        </div>

        <!-- Main content -->
        <div class="dashboard-main-grid">

            <!-- LEFT COLUMN -->
            <div class="dashboard-side-stack">

                <div class="dashboard-panel">
                    <div class="panel-header">
                        <h2>Device Information</h2>
                        <p>Stored configuration and metadata for this monitoring target.</p>
                    </div>

                    <div class="detail-list">
                        <div class="detail-row">
                            <span>Device name</span>
                            <strong><?php echo htmlspecialchars($device['device_name']); ?></strong>
                        </div>

                        <div class="detail-row">
                            <span>Address</span>
                            <strong><?php echo htmlspecialchars($device['ip_address']); ?></strong>
                        </div>

                        <div class="detail-row">
                            <span>Port</span>
                            <strong><?php echo htmlspecialchars($device['port']); ?></strong>
                        </div>

                        <div class="detail-row">
                            <span>Device type</span>
                            <strong><?php echo htmlspecialchars($device['device_type'] ?? 'Unknown'); ?></strong>
                        </div>

                        <div class="detail-row">
                            <span>Monitoring</span>
                            <strong><?php echo $is_active ? 'Enabled' : 'Disabled'; ?></strong>
                        </div>

                        <div class="detail-row">
                            <span>Created</span>
                            <strong><?php echo !empty($device['created_at']) ? htmlspecialchars(date('d M Y, H:i:s', strtotime($device['created_at']))) : 'N/A'; ?></strong>
                        </div>
                    </div>
                </div>

                <div class="dashboard-panel">
                    <div class="panel-header">
                        <h2>Notes</h2>
                        <p>Additional admin description for this device.</p>
                    </div>

                    <div class="notes-box">
                        <?php if (!empty($device['notes'])): ?>
                            <p><?php echo nl2br(htmlspecialchars($device['notes'])); ?></p>
                        <?php else: ?>
                            <p>No notes recorded for this device yet.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="dashboard-panel">
                    <div class="panel-header">
                        <h2>Latest Collector Message</h2>
                        <p>Most recent backend check message stored for this device.</p>
                    </div>

                    <div class="notes-box">
                        <?php if (!empty($latest_message)): ?>
                            <p><?php echo htmlspecialchars($latest_message); ?></p>
                        <?php else: ?>
                            <p>No collector message recorded yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- RIGHT COLUMN -->
            <div class="dashboard-panel dashboard-panel-large">
                <div class="panel-header panel-header-row">
                    <div>
                        <h2>Recent Device History</h2>
                        <p>Latest recorded checks for this device, newest first.</p>
                    </div>

                    <div class="panel-actions">
                        <a href="devices.php" class="btn-secondary btn-small">Back to Devices</a>
                        <a href="edit-device.php?id=<?php echo (int)$device_id; ?>" class="btn-primary btn-small">Edit Device</a>
                    </div>
                </div>

                <?php if (!empty($recent_logs)): ?>
                    <div class="table-wrapper">
                        <table class="dashboard-table">
                            <thead>
                                <tr>
                                    <th>Status</th>
                                    <th>Latency</th>
                                    <th>Message</th>
                                    <th>Checked At</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_logs as $log): ?>
                                    <tr>
                                        <td>
                                            <?php if ($log['status'] === 'online'): ?>
                                                <span class="badge online">ONLINE</span>
                                            <?php else: ?>
                                                <span class="badge offline">OFFLINE</span>
                                            <?php endif; ?>
                                        </td>

                                        <td>
                                            <?php echo $log['latency_ms'] !== null ? htmlspecialchars($log['latency_ms']) . ' ms' : 'N/A'; ?>
                                        </td>

                                        <td>
                                            <span class="log-message">
                                                <?php echo !empty($log['message']) ? htmlspecialchars($log['message']) : 'No message recorded'; ?>
                                            </span>
                                        </td>

                                        <td>
                                            <?php echo htmlspecialchars(date('d M Y, H:i:s', strtotime($log['checked_at']))); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state empty-state-enhanced">
                        <h3>No monitoring history found</h3>
                        <p>This device has not been checked yet, or its logs may have been cleared.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

</body>
</html>