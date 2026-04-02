<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require 'database.php';
require 'csrf.php';

/* =========================
   ACCESS CONTROL
   ========================= */

// Redirect if not logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Session timeout: 15 minutes
$timeout_duration = 900;

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
    session_unset();
    session_destroy();
    header('Location: login.php?timeout=1');
    exit();
}

$_SESSION['last_activity'] = time();

$admin_name = $_SESSION['admin_name'] ?? 'Administrator';

/* =========================
   ACTION MESSAGES
   ========================= */

$success_message = '';
$error_message = '';

/* =========================
   CLEAR LOGS HANDLER
   Handle actions BEFORE loading dashboard data
   so the stats refresh correctly afterward
   ========================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_logs'])) {
    $csrf_token = $_POST['csrf_token'] ?? '';

    if (!verify_csrf_token($csrf_token)) {
        $error_message = "Invalid request. Please try again.";
    } else {
        $delete_sql = "DELETE FROM status_logs";

        if ($conn->query($delete_sql)) {
            $success_message = "All monitoring logs have been cleared.";
        } else {
            $error_message = "Failed to clear logs.";
        }
    }
}

/* =========================
   DASHBOARD DATA
   ========================= */

$total_devices = 0;
$online_devices = 0;
$offline_devices = 0;
$latest_check = 'No checks recorded yet';

// Total devices
$result = $conn->query("SELECT COUNT(*) AS total FROM devices");
if ($result) {
    $row = $result->fetch_assoc();
    $total_devices = (int)$row['total'];
}

// Devices currently online based on latest log per device
$online_sql = "
    SELECT COUNT(*) AS online_total
    FROM (
        SELECT sl.device_id, sl.status
        FROM status_logs sl
        INNER JOIN (
            SELECT device_id, MAX(checked_at) AS latest_checked
            FROM status_logs
            GROUP BY device_id
        ) latest
        ON sl.device_id = latest.device_id
        AND sl.checked_at = latest.latest_checked
        WHERE sl.status = 'online'
    ) AS current_online
";

$result = $conn->query($online_sql);
if ($result) {
    $row = $result->fetch_assoc();
    $online_devices = (int)$row['online_total'];
}

$offline_devices = $total_devices - $online_devices;
if ($offline_devices < 0) {
    $offline_devices = 0;
}

// Latest check time
$result = $conn->query("SELECT MAX(checked_at) AS latest_check FROM status_logs");
if ($result) {
    $row = $result->fetch_assoc();
    if (!empty($row['latest_check'])) {
        $latest_check = date('d M Y, H:i:s', strtotime($row['latest_check']));
    }
}

// Recent logs
$recent_logs = [];
$logs_sql = "
    SELECT d.device_name, d.ip_address, d.port, sl.status, sl.latency_ms, sl.checked_at
    FROM status_logs sl
    INNER JOIN devices d ON sl.device_id = d.id
    ORDER BY sl.checked_at DESC
    LIMIT 6
";
$result = $conn->query($logs_sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $recent_logs[] = $row;
    }
}

/* =========================
   EXTRA DERIVED VALUES
   ========================= */

$system_health = 'Needs attention';
$health_class = 'health-warning';

if ($total_devices > 0 && $offline_devices === 0) {
    $system_health = 'Fully operational';
    $health_class = 'health-good';
} elseif ($online_devices > 0) {
    $system_health = 'Partially online';
    $health_class = 'health-warning';
} elseif ($total_devices > 0 && $online_devices === 0) {
    $system_health = 'All devices offline';
    $health_class = 'health-critical';
}

$online_percentage = $total_devices > 0 ? round(($online_devices / $total_devices) * 100) : 0;
$offline_percentage = $total_devices > 0 ? round(($offline_devices / $total_devices) * 100) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Secure Network Monitoring Dashboard</title>
    <link rel="icon" type="image/png" href="favicon.png">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<section class="dashboard-page dashboard-page-enhanced">

    <!-- Decorative background -->
    <div class="dashboard-bg-orb dashboard-bg-orb-one"></div>
    <div class="dashboard-bg-orb dashboard-bg-orb-two"></div>

    <nav class="navbar dashboard-nav">
        <div class="nav-shell">
            <div class="logo">SNM<span>Dashboard</span></div>

            <div class="nav-links">
                <a href="index.php">Home</a>
                <a href="dashboard.php" class="active-link">Dashboard</a>
                <a href="devices.php">Devices</a>
                <a href="logs.php">Logs</a>

                <form method="POST" action="logout.php" class="logout-form">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                    <button type="submit" class="nav-button logout-button">Logout</button>
                </form>
            </div>
        </div>
    </nav>

    <div class="dashboard-container">

        <?php if (!empty($success_message)): ?>
            <div class="success-box">
                <p><?php echo htmlspecialchars($success_message); ?></p>
            </div>
        <?php endif; ?>

        <?php if (!empty($error_message)): ?>
            <div class="error-box">
                <p><?php echo htmlspecialchars($error_message); ?></p>
            </div>
        <?php endif; ?>

        <!-- TOP HERO PANEL -->
        <div class="dashboard-hero-panel">
            <div class="dashboard-hero-copy">
                <p class="dashboard-tag">Private Admin Area</p>
                <h1>Welcome back, <?php echo htmlspecialchars($admin_name); ?></h1>
                <p class="dashboard-subtext">
                    Review device availability, recent monitoring activity, and overall network health from one clean administrative view.
                </p>

                <div class="dashboard-hero-meta">
                    <div class="meta-chip">
                        <span class="meta-dot meta-dot-live"></span>
                        <span><?php echo htmlspecialchars($system_health); ?></span>
                    </div>

                    <div class="meta-chip">
                        <span class="meta-dot"></span>
                        <span><?php echo $online_percentage; ?>% online coverage</span>
                    </div>
                </div>
            </div>

            <div class="dashboard-hero-side">
                <div class="hero-side-card">
                    <span class="hero-side-label">Latest Check</span>
                    <strong><?php echo htmlspecialchars($latest_check); ?></strong>
                    <p>Most recent monitoring timestamp recorded by the collector.</p>
                </div>
            </div>
        </div>

        <!-- SUMMARY STATUS CARDS -->
        <div class="summary-grid summary-grid-enhanced">
            <div class="summary-card summary-card-primary">
                <div class="summary-card-top">
                    <h3>Total Devices</h3>
                    <span class="summary-pill">Inventory</span>
                </div>
                <div class="summary-number"><?php echo $total_devices; ?></div>
                <p>All devices currently stored in the monitoring database.</p>
                <div class="summary-progress-track">
                    <div class="summary-progress-fill devices-fill" style="width: 100%;"></div>
                </div>
            </div>

            <div class="summary-card">
                <div class="summary-card-top">
                    <h3>Online Devices</h3>
                    <span class="summary-pill success-pill">Healthy</span>
                </div>
                <div class="summary-number status-online-text"><?php echo $online_devices; ?></div>
                <p>Devices marked online from their most recent status log.</p>
                <div class="summary-progress-track">
                    <div class="summary-progress-fill online-fill" style="width: <?php echo $online_percentage; ?>%;"></div>
                </div>
            </div>

            <div class="summary-card">
                <div class="summary-card-top">
                    <h3>Offline Devices</h3>
                    <span class="summary-pill danger-pill">Attention</span>
                </div>
                <div class="summary-number status-offline-text"><?php echo $offline_devices; ?></div>
                <p>Devices currently unreachable or not responding to checks.</p>
                <div class="summary-progress-track">
                    <div class="summary-progress-fill offline-fill" style="width: <?php echo $offline_percentage; ?>%;"></div>
                </div>
            </div>

            <div class="summary-card">
                <div class="summary-card-top">
                    <h3>System Health</h3>
                    <span class="summary-pill <?php echo $health_class; ?>">Status</span>
                </div>
                <div class="summary-small"><?php echo htmlspecialchars($system_health); ?></div>
                <p>Quick interpretation of the latest known monitoring state.</p>
                <div class="summary-progress-track">
                    <div class="summary-progress-fill health-fill" style="width: <?php echo $online_percentage; ?>%;"></div>
                </div>
            </div>
        </div>

        <!-- MAIN CONTENT GRID -->
        <div class="dashboard-main-grid">

            <!-- LEFT: RECENT ACTIVITY -->
            <div class="dashboard-panel dashboard-panel-large">
                <div class="panel-header panel-header-row">
                    <div>
                        <h2>Recent Monitoring Activity</h2>
                        <p>Latest recorded results from the status logs table.</p>
                    </div>

                    <div class="panel-actions">
                        <a href="logs.php" class="panel-link">View all logs</a>

                        <form method="POST" class="clear-logs-form"
                              onsubmit="return confirm('Are you sure you want to delete all logs? This cannot be undone.');">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                            <button type="submit" name="clear_logs" class="btn-danger">Clear Logs</button>
                        </form>
                    </div>
                </div>

                <?php if (!empty($recent_logs)): ?>
                    <div class="activity-list">
                        <?php foreach ($recent_logs as $log): ?>
                            <div class="activity-item">
                                <div class="activity-left">
                                    <div class="activity-icon <?php echo $log['status'] === 'online' ? 'activity-icon-online' : 'activity-icon-offline'; ?>"></div>

                                    <div class="activity-copy">
                                        <div class="activity-title-row">
                                            <strong><?php echo htmlspecialchars($log['device_name']); ?></strong>

                                            <?php if ($log['status'] === 'online'): ?>
                                                <span class="badge online">ONLINE</span>
                                            <?php else: ?>
                                                <span class="badge offline">OFFLINE</span>
                                            <?php endif; ?>
                                        </div>

                                        <p>
                                            <?php echo htmlspecialchars($log['ip_address']) . ':' . htmlspecialchars($log['port']); ?>
                                            <?php if ($log['latency_ms'] !== null): ?>
                                                • <?php echo htmlspecialchars($log['latency_ms']); ?> ms latency
                                            <?php else: ?>
                                                • No latency value recorded
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                </div>

                                <div class="activity-time">
                                    <?php echo htmlspecialchars(date('d M Y', strtotime($log['checked_at']))); ?><br>
                                    <span><?php echo htmlspecialchars(date('H:i:s', strtotime($log['checked_at']))); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="table-wrapper desktop-table-wrapper">
                        <table class="dashboard-table">
                            <thead>
                                <tr>
                                    <th>Device</th>
                                    <th>Address</th>
                                    <th>Status</th>
                                    <th>Latency</th>
                                    <th>Checked At</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_logs as $log): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($log['device_name']); ?></td>
                                        <td><?php echo htmlspecialchars($log['ip_address']) . ':' . htmlspecialchars($log['port']); ?></td>
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
                                        <td><?php echo htmlspecialchars(date('d M Y, H:i:s', strtotime($log['checked_at']))); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state empty-state-enhanced">
                        <h3>No monitoring logs found yet</h3>
                        <p>Run your collector script to start recording device checks and populating dashboard activity.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- RIGHT: QUICK INSIGHTS -->
            <div class="dashboard-side-stack">

                <div class="dashboard-panel side-panel">
                    <div class="panel-header">
                        <h2>Network Snapshot</h2>
                        <p>Fast status overview across monitored devices.</p>
                    </div>

                    <div class="snapshot-list">
                        <div class="snapshot-row">
                            <span>Tracked devices</span>
                            <strong><?php echo $total_devices; ?></strong>
                        </div>
                        <div class="snapshot-row">
                            <span>Currently online</span>
                            <strong class="status-online-text"><?php echo $online_devices; ?></strong>
                        </div>
                        <div class="snapshot-row">
                            <span>Currently offline</span>
                            <strong class="status-offline-text"><?php echo $offline_devices; ?></strong>
                        </div>
                        <div class="snapshot-row">
                            <span>Coverage</span>
                            <strong><?php echo $online_percentage; ?>%</strong>
                        </div>
                    </div>
                </div>

                <div class="dashboard-panel side-panel">
                    <div class="panel-header">
                        <h2>Admin Guidance</h2>
                        <p>Useful reminders for a cleaner workflow.</p>
                    </div>

                    <div class="guidance-list">
                        <div class="guidance-item">
                            Review offline devices first to catch potential outages quickly.
                        </div>
                        <div class="guidance-item">
                            Use recent logs to identify repeated failures or unstable hosts.
                        </div>
                        <div class="guidance-item">
                            Keep collector activity separate from browser-side actions for safer monitoring.
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</section>

</body>
</html>