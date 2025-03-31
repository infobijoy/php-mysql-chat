<?php
session_start();
include '../include/config-db.php'; // Database connection

if (!isset($_SESSION['user_id'])) {
    header('Location: ./log-in.php');
    exit;
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = intval($_SESSION['user_id']);

    $stmt = $conn->prepare("UPDATE users SET status = NOW() WHERE id = ?");
    $stmt->bind_param('i', $userId);

    if ($stmt->execute()) {
        echo json_encode(['success' => 'Status updated']);
    } else {
        echo json_encode(['error' => 'Failed to update status']);
    }

    $stmt->close();
}

$conn->close();
?>
