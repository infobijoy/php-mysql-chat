<?php
session_start();

// Redirect to login if the user is not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ./log-in.php');
    exit;
}

// Include the database configuration file
include '../include/config-db.php';

// Get form data
$data = json_decode(file_get_contents('php://input'), true);
$receiverId = $data['receiver_id'] ?? null;
$message = $data['message'] ?? '';

// Validate input
if (empty($receiverId) || empty($message)) {
    echo json_encode(['success' => false, 'message' => 'Receiver ID and message are required.']);
    exit;
}

// Insert the message into the database
try {
    $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
    $stmt->bind_param('iis', $_SESSION['user_id'], $receiverId, $message);
    $stmt->execute();

    echo json_encode(['success' => true, 'message' => 'Message sent successfully.']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to send message.']);
}
?>