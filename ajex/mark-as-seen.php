<?php
session_start();
include '../include/config-db.php';

$userId = $_SESSION['user_id'];
$contactId = $_GET['user_id'];

// Mark all messages from this contact as seen
$query = "UPDATE messages SET is_seen = TRUE, seen_at = NOW() 
          WHERE receiver_id = ? AND sender_id = ? AND is_seen = FALSE";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $userId, $contactId);
$stmt->execute();

// Get the timestamp of when messages were marked as seen
$seenAt = date('Y-m-d H:i:s');

echo json_encode([
    'success' => true,
    'seen_at' => $seenAt
]);

$stmt->close();
$conn->close();
?>