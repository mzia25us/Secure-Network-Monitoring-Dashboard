<?php
// Database connection configuration
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'network_monitor';

// Create a new MySQLi connection
$conn = new mysqli($host, $user, $password, $database);

// Check if the connection was successful
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>