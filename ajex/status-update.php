<?php
session_start();
include '../include/config-db.php';

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_POST['action'] === 'update' && isset($_POST['user_id'])) {
        $userId = intval($_POST['user_id']);
        $currentTime = date('Y-m-d H:i:s');
        $stmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ?");
        $stmt->bind_param('si', $currentTime, $userId);
        $stmt->execute();
        $stmt->close();
    } elseif ($_POST['action'] === 'fetch' && isset($_POST['user_id'])) {
        $userId = intval($_POST['user_id']);
        $stmt = $conn->prepare("SELECT status FROM users WHERE id = ?");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $stmt->bind_result($lastSeen);
        $stmt->fetch();
        echo $lastSeen ?: 'No Data';
        $stmt->close();
    }
}

$conn->close();
?>
