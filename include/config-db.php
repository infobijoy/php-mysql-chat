<?php
$isLocalhost = ($_SERVER['SERVER_NAME'] === 'localhost' || $_SERVER['SERVER_NAME'] === '127.0.0.1');

if ($isLocalhost) {
    // Localhost (XAMPP) settings
    define('DB_HOST', 'localhost');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'chat');
} else {
    // Remote server settings
    define('DB_HOST', 'localhost'); // or your remote host address
    define('DB_USER', 'bijoydev_chat');
    define('DB_PASS', '8;u6^r,tMXs{');
    define('DB_NAME', 'bijoydev_chat');
}
// Create a connection to the database
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set the charset to utf8mb4 for emoji support
$conn->set_charset("utf8mb4");
?>