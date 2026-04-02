<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require 'database.php';
require 'csrf.php';

// If already logged in, send admin to dashboard
if (isset($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit();
}

$errors = [];
$success = '';
$name = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';

    if (!verify_csrf_token($csrf_token)) {
        $errors[] = "Invalid request. Please refresh the page and try again.";
    }

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Name validation
    if ($name === '') {
        $errors[] = "Full name is required.";
    } elseif (mb_strlen($name) < 2) {
        $errors[] = "Full name must be at least 2 characters long.";
    } elseif (mb_strlen($name) > 100) {
        $errors[] = "Full name must not be longer than 100 characters.";
    }

    // Email validation
    if ($email === '') {
        $errors[] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    } elseif (mb_strlen($email) > 255) {
        $errors[] = "Email address is too long.";
    }

    // Password validation
    if ($password === '') {
        $errors[] = "Password is required.";
    } else {
        if (strlen($password) < 8) {
            $errors[] = "Password must be at least 8 characters long.";
        }
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = "Password must contain at least one uppercase letter.";
        }
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = "Password must contain at least one lowercase letter.";
        }
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = "Password must contain at least one number.";
        }
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = "Password must contain at least one special character.";
        }
    }

    // Confirm password validation
    if ($confirm_password === '') {
        $errors[] = "Please confirm your password.";
    } elseif ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }

    // Check if email already exists
    if (empty($errors)) {
        $checkStmt = $conn->prepare("SELECT id FROM admins WHERE email = ?");
        $checkStmt->bind_param("s", $email);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();

        if ($checkResult && $checkResult->num_rows > 0) {
            $errors[] = "An admin account with that email already exists.";
        }

        $checkStmt->close();
    }

    // Insert new admin
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $insertStmt = $conn->prepare("INSERT INTO admins (name, email, password, created_at) VALUES (?, ?, ?, NOW())");
        $insertStmt->bind_param("sss", $name, $email, $hashed_password);

        if ($insertStmt->execute()) {
            $success = "Admin account created successfully. You can now log in.";

            // Clear form values after success
            $name = '';
            $email = '';
        } else {
            $errors[] = "Something went wrong while creating the account. Please try again.";
        }

        $insertStmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Register | Secure Network Monitoring Dashboard</title>
    <link rel="icon" type="image/png" href="favicon.png">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<section class="register-page">
    <div class="register-bg-orb register-bg-orb-one"></div>
    <div class="register-bg-orb register-bg-orb-two"></div>
    <div class="register-grid-overlay"></div>

    <header class="main-header">
        <div class="container">
            <div class="topbar-inner">
                <a href="index.php" class="brand-link">
                    <div class="name">SNM<span>Dashboard</span></div>
                </a>

                <nav class="nav">
                    <a href="index.php" class="nav-link">Home</a>
                    <a href="login.php" class="nav-link">Login</a>
                    <a href="register.php" class="nav-button">Create Account</a>
                </nav>
            </div>
        </div>
    </header>

    <main class="register-main">
        <div class="container">
            <div class="register-layout">
                <div class="register-intro-panel">
                    <span class="register-tag">Admin Access Setup</span>
                    <h1 class="page-title">Create a secure administrator account</h1>
                    <p class="page-subtitle">
                        Register a new administrator for the monitoring dashboard with
                        secure credentials, validated email input, and strong password requirements.
                    </p>

                    <div class="register-feature-list">
                        <div class="register-feature-item">
                            <div class="register-feature-icon">✉</div>
                            <div>
                                <strong>Email validation</strong>
                                <p>Only properly formatted email addresses are accepted before account creation.</p>
                            </div>
                        </div>

                        <div class="register-feature-item">
                            <div class="register-feature-icon">🔒</div>
                            <div>
                                <strong>Strong password rules</strong>
                                <p>Passwords must include uppercase, lowercase, numbers, and special characters.</p>
                            </div>
                        </div>

                        <div class="register-feature-item">
                            <div class="register-feature-icon">🛡</div>
                            <div>
                                <strong>Secure password storage</strong>
                                <p>Passwords are never saved as plain text and are hashed securely before insertion.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="register-card">
                    <div class="card-top">
                        <div class="card-kicker">Administrator Registration</div>
                        <h2 class="card-title">Sign Up</h2>
                    </div>

                    <?php if (!empty($errors)): ?>
                        <div class="message-box error-box">
                            <?php foreach ($errors as $error): ?>
                                <p><?php echo htmlspecialchars($error); ?></p>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($success !== ''): ?>
                        <div class="message-box success-box">
                            <p><?php echo htmlspecialchars($success); ?></p>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="register.php" class="register-form">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">

                        <div class="field">
                            <label for="name">Full Name</label>
                            <input
                                type="text"
                                id="name"
                                name="name"
                                placeholder="Enter your full name"
                                value="<?php echo htmlspecialchars($name); ?>"
                                required
                            >
                        </div>

                        <div class="field">
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

                        <div class="grid">
                            <div class="field">
                                <label for="password">Password</label>
                                <input
                                    type="password"
                                    id="password"
                                    name="password"
                                    placeholder="Create a strong password"
                                    required
                                >
                                <p class="hint">
                                    Must be at least 8 characters and include uppercase, lowercase, number, and special character.
                                </p>
                            </div>

                            <div class="field">
                                <label for="confirm_password">Confirm Password</label>
                                <input
                                    type="password"
                                    id="confirm_password"
                                    name="confirm_password"
                                    placeholder="Re-enter your password"
                                    required
                                >
                            </div>
                        </div>

                        <div class="submit">
                            <button type="submit" class="btn">Create Admin Account</button>
                            <p class="hint centered-hint">
                                Already have an account?
                                <a href="login.php" class="text-link">Log in here</a>
                            </p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>
</section>

</body>
</html>