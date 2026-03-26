<?php
$host = 'localhost';
$user = 'root';          // your MySQL username
$pass = '';              // your MySQL password
$db   = 'customer_db';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Start session for flash messages
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>