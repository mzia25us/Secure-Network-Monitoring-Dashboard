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

$admin_name = $_SESSION['admin_name'] ?? 'Administrator';

/* =========================
   FILTER INPUTS
   ========================= */

$selected_device = isset($_GET['device_id']) ? (int) $_GET['device_id'] : 0;
$selected_status = trim($_GET['status'] ?? '');

$allowed_statuses = ['online', 'offline'];
if (!in_array($selected_status, $allowed_statuses, true)) {
    $selected_status = '';
}

/* =========================
   LOAD DEVICE FILTER OPTIONS
   ========================= */

$device_options = [];

$device_option_result = $conn->query("
    SELECT id, device_name
    FROM devices
    ORDER BY device_name ASC
");

if ($device_option_result) {
    while ($row = $device_option_result->fetch_assoc()) {
        $device_options[] = $row;
    }
}

/* =========================
   LOG SUMMARY COUNTS
   ========================= */

$total_logs = 0;
$online_log_count = 0;
$offline_log_count = 0;
$latest_log_time = 'No logs recorded yet';

$count_result = $conn->query("SELECT COUNT(*) AS total FROM status_logs");
if ($count_result) {
    $row = $count_result->fetch_assoc();
    $total_logs = (int) $row['total'];
}

$online_count_result = $conn->query("SELECT COUNT(*) AS total FROM status_logs WHERE status = 'online'");
if ($online_count_result) {
    $row = $online_count_result->fetch_assoc();
    $online_log_count = (int) $row['total'];
}

$offline_log_count = $total_logs - $online_log_count;
if ($offline_log_count < 0) {
    $offline_log_count = 0;
}

$latest_result = $conn->query("SELECT MAX(checked_at) AS latest_check FROM status_logs");
if ($latest_result) {
    $row = $latest_result->fetch_assoc();
    if (!empty($row['latest_check'])) {
        $latest_log_time = date('d M Y, H:i:s', strtotime($row['latest_check']));
    }
}

/* =========================
   BUILD FILTERED LOG QUERY
   ========================= */

$sql = "
    SELECT
        sl.id,
        sl.device_id,
        sl.status,
        sl.latency_ms,
        sl.message,
        sl.checked_at,
        d.device_name,
        d.ip_address,
        d.port,
        d.device_type,
        d.is_active
    FROM status_logs sl
    INNER JOIN devices d ON sl.device_id = d.id
    WHERE 1 = 1
";

$types = '';
$params = [];

if ($selected_device > 0) {
    $sql .= " AND sl.device_id = ?";
    $types .= 'i';
    $params[] = $selected_device;
}

if ($selected_status !== '') {
    $sql .= " AND sl.status = ?";
    $types .= 's';
    $params[] = $selected_status;
}

$sql .= " ORDER BY sl.checked_at DESC LIMIT 250";

$logs = [];

$stmt = $conn->prepare($sql);

if ($stmt) {
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $logs[] = $row;
        }
    }

    $stmt->close();
}

/* =========================
   FILTERED RESULT COUNT
   ========================= */

$filtered_count = count($logs);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logs | Secure Network Monitoring Dashboard</title>
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
                <a href="devices.php">Devices</a>
                <a href="logs.php" class="active-link">Logs</a>

                <form method="POST" action="logout.php" class="logout-form">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                    <button type="submit" class="nav-button logout-button">Logout</button>
                </form>
            </div>
        </div>
    </nav>

    <div class="dashboard-container">

        <!-- Page header -->
        <div class="dashboard-hero-panel">
            <div class="dashboard-hero-copy">
                <p class="dashboard-tag">Monitoring History</p>
                <h1>Review monitoring logs</h1>
                <p class="dashboard-subtext">
                    Inspect recent checks, connection outcomes, latency values, and collector messages across your approved monitoring targets.
                </p>

                <div class="dashboard-hero-meta">
                    <div class="meta-chip">
                        <span class="meta-dot meta-dot-live"></span>
                        <span><?php echo $filtered_count; ?> logs shown</span>
                    </div>

                    <div class="meta-chip">
                        <span class="meta-dot"></span>
                        <span>Latest check: <?php echo htmlspecialchars($latest_log_time); ?></span>
                    </div>
                </div>
            </div>

            <div class="dashboard-hero-side">
                <div class="hero-side-card">
                    <span class="hero-side-label">Activity Scope</span>
                    <strong><?php echo $total_logs; ?> total log entries</strong>
                    <p>Use filters to narrow results by device or by online/offline state.</p>
                </div>
            </div>
        </div>

        <!-- Summary cards -->
        <div class="summary-grid summary-grid-enhanced">
            <div class="summary-card summary-card-primary">
                <div class="summary-card-top">
                    <h3>Total Logs</h3>
                    <span class="summary-pill">History</span>
                </div>
                <div class="summary-number"><?php echo $total_logs; ?></div>
                <p>All stored monitoring events currently in the status log table.</p>
            </div>

            <div class="summary-card">
                <div class="summary-card-top">
                    <h3>Online Logs</h3>
                    <span class="summary-pill success-pill">Healthy</span>
                </div>
                <div class="summary-number status-online-text"><?php echo $online_log_count; ?></div>
                <p>Checks that recorded successful connectivity.</p>
            </div>

            <div class="summary-card">
                <div class="summary-card-top">
                    <h3>Offline Logs</h3>
                    <span class="summary-pill danger-pill">Issues</span>
                </div>
                <div class="summary-number status-offline-text"><?php echo $offline_log_count; ?></div>
                <p>Checks that recorded failed or unreachable services.</p>
            </div>

            <div class="summary-card">
                <div class="summary-card-top">
                    <h3>Visible Results</h3>
                    <span class="summary-pill">Filtered</span>
                </div>
                <div class="summary-number"><?php echo $filtered_count; ?></div>
                <p>Rows matching the current device and status filters.</p>
            </div>
        </div>

        <!-- Filters -->
        <div class="dashboard-panel filter-panel">
            <div class="panel-header">
                <h2>Filter Logs</h2>
                <p>Narrow the log history by device or connectivity result.</p>
            </div>

            <form method="GET" action="logs.php" class="logs-filter-form">
                <div class="filter-grid">
                    <div class="form-group">
                        <label for="device_id">Device</label>
                        <select id="device_id" name="device_id" class="form-select">
                            <option value="0">All devices</option>
                            <?php foreach ($device_options as $device_option): ?>
                                <option
                                    value="<?php echo (int) $device_option['id']; ?>"
                                    <?php echo $selected_device === (int) $device_option['id'] ? 'selected' : ''; ?>
                                >
                                    <?php echo htmlspecialchars($device_option['device_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status" class="form-select">
                            <option value="">All statuses</option>
                            <option value="online" <?php echo $selected_status === 'online' ? 'selected' : ''; ?>>Online</option>
                            <option value="offline" <?php echo $selected_status === 'offline' ? 'selected' : ''; ?>>Offline</option>
                        </select>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-primary">Apply Filters</button>
                    <a href="logs.php" class="btn-secondary">Clear Filters</a>
                </div>
            </form>
        </div>

        <!-- Logs table -->
        <div class="dashboard-panel dashboard-panel-large">
            <div class="panel-header panel-header-row">
                <div>
                    <h2>Monitoring Log History</h2>
                    <p>Latest matching status entries, newest first.</p>
                </div>
            </div>

            <?php if (!empty($logs)): ?>
                <div class="table-wrapper">
                    <table class="dashboard-table logs-table">
                        <thead>
                            <tr>
                                <th>Device</th>
                                <th>Address</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Latency</th>
                                <th>Message</th>
                                <th>Checked At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td>
                                        <div class="device-name-cell">
                                            <strong><?php echo htmlspecialchars($log['device_name']); ?></strong>
                                            <span>
                                                <?php echo (int)$log['is_active'] === 1 ? 'Monitored' : 'Currently inactive'; ?>
                                            </span>
                                        </div>
                                    </td>

                                    <td>
                                        <?php echo htmlspecialchars($log['ip_address']) . ':' . htmlspecialchars($log['port']); ?>
                                    </td>

                                    <td>
                                        <?php echo htmlspecialchars($log['device_type'] ?? 'Unknown'); ?>
                                    </td>

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
                    <h3>No matching logs found</h3>
                    <p>Try changing your filters, or run the collector again to generate fresh monitoring data.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

</body>
</html>