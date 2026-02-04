<?php
// Start a new session if one is not already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database connection configuration values
$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'capstonestoredb';

// Create a connection to the MySQL database using mysqli
$conn = mysqli_connect($host, $user, $pass, $db);

// If the connection fails, stop execution and output the error message
if (!$conn) {
    die('Database Connection Error: ' . mysqli_connect_error());
}

// Ensure the connection uses UTF-8 (utf8mb4) character encoding
mysqli_set_charset($conn, 'utf8mb4');
?>
