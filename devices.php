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
   ACTION MESSAGES
   ========================= */

$success_message = '';
$error_message = '';

if (isset($_GET['added']) && $_GET['added'] === '1') {
    $success_message = "Device added successfully.";
}

if (isset($_GET['updated']) && $_GET['updated'] === '1') {
    $success_message = "Device updated successfully.";
}

/* =========================
   HANDLE DEVICE ACTIONS
   - toggle active/inactive
   - delete device
   ========================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';

    if (!verify_csrf_token($csrf_token)) {
        $error_message = "Invalid request. Please refresh and try again.";
    } else {
        // Toggle device active state
        if (isset($_POST['toggle_device'], $_POST['device_id'])) {
            $device_id = (int)$_POST['device_id'];

            $stmt = $conn->prepare("UPDATE devices SET is_active = CASE WHEN is_active = 1 THEN 0 ELSE 1 END WHERE id = ?");
            $stmt->bind_param("i", $device_id);

            if ($stmt->execute()) {
                $success_message = "Device status updated successfully.";
            } else {
                $error_message = "Failed to update device status.";
            }

            $stmt->close();
        }

        // Delete device
        if (isset($_POST['delete_device'], $_POST['device_id'])) {
            $device_id = (int)$_POST['device_id'];

            $stmt = $conn->prepare("DELETE FROM devices WHERE id = ?");
            $stmt->bind_param("i", $device_id);

            if ($stmt->execute()) {
                $success_message = "Device deleted successfully.";
            } else {
                $error_message = "Failed to delete device.";
            }

            $stmt->close();
        }
    }
}

/* =========================
   FETCH DEVICES + LATEST STATUS
   ========================= */

$devices = [];

$sql = "
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
        sl.checked_at AS last_checked
    FROM devices d
    LEFT JOIN (
        SELECT s1.device_id, s1.status, s1.latency_ms, s1.checked_at
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
    ORDER BY d.device_name ASC
";

$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $devices[] = $row;
    }
}

$total_devices = count($devices);
$active_devices = 0;
$inactive_devices = 0;

foreach ($devices as $device) {
    if ((int)$device['is_active'] === 1) {
        $active_devices++;
    } else {
        $inactive_devices++;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Devices | Secure Network Monitoring Dashboard</title>
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

        <!-- Page header -->
        <div class="dashboard-hero-panel">
            <div class="dashboard-hero-copy">
                <p class="dashboard-tag">Device Management</p>
                <h1>Manage monitored devices</h1>
                <p class="dashboard-subtext">
                    View approved monitoring targets, review their latest known status, and control whether each device is actively monitored.
                </p>

                <div class="dashboard-hero-meta">
                    <div class="meta-chip">
                        <span class="meta-dot meta-dot-live"></span>
                        <span><?php echo $active_devices; ?> active devices</span>
                    </div>

                    <div class="meta-chip">
                        <span class="meta-dot"></span>
                        <span><?php echo $total_devices; ?> total devices</span>
                    </div>
                </div>
            </div>

            <div class="dashboard-hero-side">
                <div class="hero-side-card">
                    <span class="hero-side-label">Quick Actions</span>
                    <strong>Manage your device inventory</strong>
                    <p>Add new approved hosts, disable noisy test devices, or remove old entries safely.</p>
                    <div class="hero-side-actions">
                        <a href="add-device.php" class="btn-primary">Add Device</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Summary cards -->
        <div class="summary-grid summary-grid-enhanced">
            <div class="summary-card summary-card-primary">
                <div class="summary-card-top">
                    <h3>Total Devices</h3>
                    <span class="summary-pill">Inventory</span>
                </div>
                <div class="summary-number"><?php echo $total_devices; ?></div>
                <p>All stored monitoring targets in the database.</p>
            </div>

            <div class="summary-card">
                <div class="summary-card-top">
                    <h3>Active Devices</h3>
                    <span class="summary-pill success-pill">Enabled</span>
                </div>
                <div class="summary-number status-online-text"><?php echo $active_devices; ?></div>
                <p>Devices currently included in collector checks.</p>
            </div>

            <div class="summary-card">
                <div class="summary-card-top">
                    <h3>Inactive Devices</h3>
                    <span class="summary-pill danger-pill">Disabled</span>
                </div>
                <div class="summary-number status-offline-text"><?php echo $inactive_devices; ?></div>
                <p>Devices stored but excluded from monitoring runs.</p>
            </div>

            <div class="summary-card">
                <div class="summary-card-top">
                    <h3>Admin</h3>
                    <span class="summary-pill">Session</span>
                </div>
                <div class="summary-small"><?php echo htmlspecialchars($admin_name); ?></div>
                <p>Authenticated user currently managing this system.</p>
            </div>
        </div>

        <!-- Devices table -->
        <div class="dashboard-panel dashboard-panel-large">
            <div class="panel-header panel-header-row">
                <div>
                    <h2>Approved Devices</h2>
                    <p>Stored monitoring targets and their latest known status.</p>
                </div>

                <div class="panel-actions">
                    <a href="add-device.php" class="btn-primary btn-small">Add Device</a>
                </div>
            </div>

            <?php if (!empty($devices)): ?>
                <div class="table-wrapper">
                    <table class="dashboard-table devices-table">
                        <thead>
                            <tr>
                                <th>Device</th>
                                <th>Address</th>
                                <th>Type</th>
                                <th>Monitoring</th>
                                <th>Latest Status</th>
                                <th>Latency</th>
                                <th>Last Checked</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($devices as $device): ?>
                                <tr>
                                    <td>
                                        <div class="device-name-cell">
                                            <strong>
                                                <a href="device-details.php?id=<?php echo (int)$device['id']; ?>" class="device-link">
                                                    <?php echo htmlspecialchars($device['device_name']); ?>
                                                </a>
                                            </strong>
                                            <?php if (!empty($device['notes'])): ?>
                                                <span><?php echo htmlspecialchars($device['notes']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </td>

                                    <td>
                                        <?php echo htmlspecialchars($device['ip_address']) . ':' . htmlspecialchars($device['port']); ?>
                                    </td>

                                    <td>
                                        <?php echo htmlspecialchars($device['device_type'] ?? 'Unknown'); ?>
                                    </td>

                                    <td>
                                        <?php if ((int)$device['is_active'] === 1): ?>
                                            <span class="badge active-badge">ACTIVE</span>
                                        <?php else: ?>
                                            <span class="badge inactive-badge">INACTIVE</span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?php if ($device['latest_status'] === 'online'): ?>
                                            <span class="badge online">ONLINE</span>
                                        <?php elseif ($device['latest_status'] === 'offline'): ?>
                                            <span class="badge offline">OFFLINE</span>
                                        <?php else: ?>
                                            <span class="badge neutral-badge">NO DATA</span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?php echo $device['latest_latency'] !== null ? htmlspecialchars($device['latest_latency']) . ' ms' : 'N/A'; ?>
                                    </td>

                                    <td>
                                        <?php echo !empty($device['last_checked']) ? htmlspecialchars(date('d M Y, H:i:s', strtotime($device['last_checked']))) : 'Never'; ?>
                                    </td>

                                    <td>
                                        <div class="table-actions">
                                            <a href="edit-device.php?id=<?php echo (int)$device['id']; ?>" class="table-action-link">Edit</a>

                                            <form method="POST" class="inline-form">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                                                <input type="hidden" name="device_id" value="<?php echo (int)$device['id']; ?>">
                                                <button type="submit" name="toggle_device" class="table-action-button">
                                                    <?php echo (int)$device['is_active'] === 1 ? 'Disable' : 'Enable'; ?>
                                                </button>
                                            </form>

                                            <form method="POST" class="inline-form"
                                                  onsubmit="return confirm('Are you sure you want to delete this device? This will also remove its logs.');">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                                                <input type="hidden" name="device_id" value="<?php echo (int)$device['id']; ?>">
                                                <button type="submit" name="delete_device" class="table-action-button danger-action">
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state empty-state-enhanced">
                    <h3>No devices added yet</h3>
                    <p>Add your first approved device to begin monitoring and storing status history.</p>
                    <div class="empty-state-action">
                        <a href="add-device.php" class="btn-primary">Add First Device</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

</body>
</html>