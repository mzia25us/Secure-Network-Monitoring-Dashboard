<?php
session_start();
require 'csrf.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit();
}

$csrf_token = $_POST['csrf_token'] ?? '';

if (!verify_csrf_token($csrf_token)) {
    die('Invalid request.');
}

session_unset();
session_destroy();

header('Location: index.php');
exit();
?>