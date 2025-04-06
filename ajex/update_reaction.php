<?php
session_start();
date_default_timezone_set('Asia/Dhaka');
if (!isset($_SESSION['user_id'])) {
    die(json_encode(['success' => false, 'error' => 'Not authenticated']));
}

include '../include/config-db.php';

header('Content-Type: application/json');

$messageId = $_POST['message_id'] ?? null;
$emoji = $_POST['emoji'] ?? null;
$userId = $_SESSION['user_id'];

if (!$messageId || !$emoji) {
    echo json_encode(['success' => false, 'error' => 'Missing parameters']);
    exit;
}

try {
    // Get current reactions
    $stmt = $conn->prepare("SELECT reactions FROM messages WHERE id = ?");
    $stmt->bind_param("i", $messageId);
    $stmt->execute();
    $result = $stmt->get_result();
    $message = $result->fetch_assoc();
    
    // Decode reactions or initialize as empty array
    $reactions = $message['reactions'] ? json_decode($message['reactions'], true) : [];
    
    // Check if user already reacted with this emoji
    $userReactionIndex = null;
    foreach ($reactions as $index => $reaction) {
        if ($reaction['user_id'] == $userId && $reaction['emoji'] == $emoji) {
            $userReactionIndex = $index;
            break;
        }
    }
    
    if ($userReactionIndex !== null) {
        // Remove this specific reaction
        unset($reactions[$userReactionIndex]);
        $reactions = array_values($reactions); // Reindex array
        $action = 'removed';
    } else {
        // Add new reaction
        $reactions[] = [
            'user_id' => $userId,
            'emoji' => $emoji,
            'timestamp' => time()
        ];
        $action = 'added';
    }
    
    // Update database
    $stmt = $conn->prepare("UPDATE messages SET reactions = ? WHERE id = ?");
    $reactionsJson = json_encode($reactions);
    $stmt->bind_param("si", $reactionsJson, $messageId);
    $stmt->execute();
    
    // Prepare response
    $response = [
        'success' => true,
        'action' => $action,
        'emoji' => $emoji,
        'reactions' => $reactions
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}