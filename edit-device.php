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

$errors = [];

/* =========================
   VALIDATION HELPERS
   ========================= */

function is_valid_hostname(string $host): bool
{
    if (strlen($host) > 253) {
        return false;
    }

    return (bool) preg_match(
        '/^(?=.{1,253}$)(?!-)([A-Za-z0-9-]{1,63}(?<!-)\.)*[A-Za-z0-9-]{1,63}(?<!-)$/',
        $host
    );
}

/* =========================
   LOAD CURRENT DEVICE
   ========================= */

$stmt = $conn->prepare("
    SELECT id, device_name, ip_address, port, device_type, notes, is_active
    FROM devices
    WHERE id = ?
    LIMIT 1
");
$stmt->bind_param("i", $device_id);
$stmt->execute();
$result = $stmt->get_result();

if (!$result || $result->num_rows !== 1) {
    $stmt->close();
    header('Location: devices.php');
    exit();
}

$device = $result->fetch_assoc();
$stmt->close();

/* =========================
   FORM VALUES
   ========================= */

$device_name = $device['device_name'];
$ip_address = $device['ip_address'];
$port = (string) $device['port'];
$device_type = $device['device_type'] ?? '';
$notes = $device['notes'] ?? '';
$is_active = (int) $device['is_active'];

/* =========================
   HANDLE UPDATE
   ========================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';

    if (!verify_csrf_token($csrf_token)) {
        $errors[] = "Invalid request. Please refresh the page and try again.";
    }

    $device_name = trim($_POST['device_name'] ?? '');
    $ip_address = trim($_POST['ip_address'] ?? '');
    $port = trim($_POST['port'] ?? '');
    $device_type = trim($_POST['device_type'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    /* =========================
       FIELD VALIDATION
       ========================= */

    if ($device_name === '') {
        $errors[] = "Device name is required.";
    } elseif (strlen($device_name) > 100) {
        $errors[] = "Device name must be 100 characters or fewer.";
    }

    if ($ip_address === '') {
        $errors[] = "IP address or hostname is required.";
    } else {
        $is_ip = filter_var($ip_address, FILTER_VALIDATE_IP);
        $is_hostname = is_valid_hostname($ip_address);

        if (!$is_ip && !$is_hostname) {
            $errors[] = "Enter a valid IP address or hostname.";
        }
    }

    if ($port === '') {
        $errors[] = "Port is required.";
    } elseif (!ctype_digit($port)) {
        $errors[] = "Port must be a number.";
    } else {
        $port_number = (int) $port;
        if ($port_number < 1 || $port_number > 65535) {
            $errors[] = "Port must be between 1 and 65535.";
        }
    }

    if ($device_type !== '' && strlen($device_type) > 50) {
        $errors[] = "Device type must be 50 characters or fewer.";
    }

    if (strlen($notes) > 1000) {
        $errors[] = "Notes must be 1000 characters or fewer.";
    }

    /* =========================
       DUPLICATE CHECK
       Prevent another device from using
       the exact same address and port
       ========================= */

    if (empty($errors)) {
        $port_number = (int) $port;

        $check_stmt = $conn->prepare("
            SELECT id
            FROM devices
            WHERE ip_address = ? AND port = ? AND id != ?
            LIMIT 1
        ");
        $check_stmt->bind_param("sii", $ip_address, $port_number, $device_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result && $check_result->num_rows > 0) {
            $errors[] = "Another device with that address and port already exists.";
        }

        $check_stmt->close();
    }

    /* =========================
       UPDATE DEVICE
       ========================= */

    if (empty($errors)) {
        if ($device_type === '') {
            $device_type = 'Unknown';
        }

        $port_number = (int) $port;

        $update_stmt = $conn->prepare("
            UPDATE devices
            SET
                device_name = ?,
                ip_address = ?,
                port = ?,
                device_type = ?,
                notes = ?,
                is_active = ?
            WHERE id = ?
        ");

        $update_stmt->bind_param(
            "ssissii",
            $device_name,
            $ip_address,
            $port_number,
            $device_type,
            $notes,
            $is_active,
            $device_id
        );

        if ($update_stmt->execute()) {
            $update_stmt->close();
            header('Location: devices.php?updated=1');
            exit();
        } else {
            $errors[] = "Failed to update device. Please try again.";
        }

        $update_stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Device | Secure Network Monitoring Dashboard</title>
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

        <div class="dashboard-hero-panel">
            <div class="dashboard-hero-copy">
                <p class="dashboard-tag">Device Management</p>
                <h1>Edit monitored device</h1>
                <p class="dashboard-subtext">
                    Update this approved monitoring target safely without exposing direct public scanning functionality.
                </p>

                <div class="dashboard-hero-meta">
                    <div class="meta-chip">
                        <span class="meta-dot meta-dot-live"></span>
                        <span>Current device: <?php echo htmlspecialchars($device['device_name']); ?></span>
                    </div>

                    <div class="meta-chip">
                        <span class="meta-dot"></span>
                        <span>ID: <?php echo (int)$device_id; ?></span>
                    </div>
                </div>
            </div>

            <div class="dashboard-hero-side">
                <div class="hero-side-card">
                    <span class="hero-side-label">Editing</span>
                    <strong><?php echo htmlspecialchars($device['device_name']); ?></strong>
                    <p>Adjust connection details, notes, and monitoring state for this stored device record.</p>
                </div>
            </div>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="error-box">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="dashboard-panel form-panel">
            <div class="panel-header">
                <h2>Edit Device Details</h2>
                <p>Update the host information used by your backend collector.</p>
            </div>

            <form method="POST" action="edit-device.php?id=<?php echo (int)$device_id; ?>" class="device-form">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">

                <div class="form-grid">
                    <div class="form-group">
                        <label for="device_name">Device Name</label>
                        <input
                            type="text"
                            id="device_name"
                            name="device_name"
                            placeholder="e.g. Internal Web Node"
                            value="<?php echo htmlspecialchars($device_name); ?>"
                            maxlength="100"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="device_type">Device Type</label>
                        <input
                            type="text"
                            id="device_type"
                            name="device_type"
                            placeholder="e.g. Web Server"
                            value="<?php echo htmlspecialchars($device_type); ?>"
                            maxlength="50"
                        >
                    </div>

                    <div class="form-group">
                        <label for="ip_address">IP Address or Hostname</label>
                        <input
                            type="text"
                            id="ip_address"
                            name="ip_address"
                            placeholder="e.g. 127.0.0.1 or localhost"
                            value="<?php echo htmlspecialchars($ip_address); ?>"
                            maxlength="253"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="port">Port</label>
                        <input
                            type="number"
                            id="port"
                            name="port"
                            placeholder="e.g. 5000"
                            value="<?php echo htmlspecialchars($port); ?>"
                            min="1"
                            max="65535"
                            required
                        >
                    </div>
                </div>

                <div class="form-group">
                    <label for="notes">Notes / Description</label>
                    <textarea
                        id="notes"
                        name="notes"
                        class="form-textarea"
                        placeholder="Optional notes about this device..."
                        maxlength="1000"
                    ><?php echo htmlspecialchars($notes); ?></textarea>
                </div>

                <div class="form-check-row">
                    <label class="checkbox-label">
                        <input
                            type="checkbox"
                            name="is_active"
                            <?php echo (int)$is_active === 1 ? 'checked' : ''; ?>
                        >
                        <span>Enable monitoring for this device</span>
                    </label>
                </div>

                <div class="form-actions">
                    <a href="devices.php" class="btn-secondary">Cancel</a>
                    <button type="submit" class="btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</section>

</body>
</html>