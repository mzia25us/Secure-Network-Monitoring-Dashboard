<?php
// Start a session if one hasn't already been started.
// The CSRF token is stored in the session so session access is needed.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Generate and return a CSRF token.
// If a token does not exist, it creates a new secure one.
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verify that the submitted CSRF matches the one stored in the session.
// hash_equals() is used to safely compare the values and reduce timing attack risks.
function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
?>