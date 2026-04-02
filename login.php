<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require 'database.php';
require 'csrf.php';

// If already logged in, go straight to dashboard
if (isset($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit();
}

$errors = [];
$email = '';

// Show timeout message if redirected after inactivity
if (isset($_GET['timeout'])) {
    $errors[] = "Your session expired due to inactivity. Please log in again.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';

    if (!verify_csrf_token($csrf_token)) {
        $errors[] = "Invalid request. Please refresh the page and try again.";
    }

    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email === '') {
        $errors[] = "Email is required.";
    }

    if ($password === '') {
        $errors[] = "Password is required.";
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id, name, email, password FROM admins WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows === 1) {
            $admin = $result->fetch_assoc();

            if (password_verify($password, $admin['password'])) {
                session_regenerate_id(true);

                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_name'] = $admin['name'];
                $_SESSION['admin_email'] = $admin['email'];
                $_SESSION['last_activity'] = time();

                header('Location: dashboard.php');
                exit();
            } else {
                $errors[] = "Incorrect password.";
            }
        } else {
            $errors[] = "No admin account found with that email.";
        }

        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | Secure Network Monitoring Dashboard</title>
    <link rel="icon" type="image/png" href="favicon.png">
    <link rel="stylesheet" href="style.css">
</head>
<body>
        <nav class="navbar">
            <div class="nav-shell">
                <div class="logo">SNM<span>Dashboard</span></div>

                <div class="nav-links">
                    <a href="index.php" class="active-link">Home</a>
                    <a href="dashboard.php">Dashboard</a>
                    <a href="devices.php">Devices</a>
                    <a href="logs.php">Logs</a>
                    <a href="login.php" class="nav-button">Admin Login</a>
                </div>
            </div>
        </nav>

<section class="login-page-alt">
    <div class="login-page-inner">
        <div class="login-heading-block">
            <a href="index.php" class="logo login-logo">SNM<span>Dashboard</span></a>
            <h1 class="login-main-title">Administrator Login</h1>
            <p class="login-main-subtitle">
                Access the private monitoring dashboard and manage approved devices,
                monitoring data, and network health records securely.
            </p>
        </div>

        <div class="login-form-shell">
            <div class="login-form-card">
                <h2 class="login-form-title">Sign In</h2>

                <?php if (!empty($errors)): ?>
                    <div class="error-box">
                        <?php foreach ($errors as $error): ?>
                            <p><?php echo htmlspecialchars($error); ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="login.php">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">

                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            placeholder="Enter your admin email"
                            value="<?php echo htmlspecialchars($email); ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Enter your password"
                            required
                        >
                    </div>

                    <button type="submit" class="btn-primary login-submit-btn">Log In</button>
                </form>
                
                <br>
                <p class="hint">Don't have an account yet? <a href="register.php">Sign up now to securely monitor your networks today!</a></p>
            </div>
        </div>
    </div>
</section>

</body>
</html>