<?php
session_start();

// Redirect to login if the user is not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../log-in.php');
    exit;
}

// Include the database configuration file
include '../include/config-db.php';

// Get the selected user's ID from the query string
$selectedUserId = $_GET['user_id'] ?? null;

// Validate the selected user ID
if (!$selectedUserId) {
    echo json_encode(['success' => false, 'message' => 'Invalid user ID.']);
    exit;
}

// Fetch messages between the logged-in user and the selected user
try {
    $stmt = $conn->prepare("
        SELECT m.*, 
          CASE WHEN m.is_seen THEN 'Seen' ELSE 'Delivered' END AS status_text,
          CASE WHEN m.seen_at IS NOT NULL THEN m.seen_at ELSE NULL END AS seen_time
          FROM messages m 
          WHERE (m.sender_id = ? AND m.receiver_id = ?) 
          OR (m.sender_id = ? AND m.receiver_id = ?) 
          ORDER BY m.created_at ASC
    ");
    $stmt->bind_param('iiii', $_SESSION['user_id'], $selectedUserId, $selectedUserId, $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $messages = $result->fetch_all(MYSQLI_ASSOC);

    // Process reactions for each message (only if reactions column exists)
    foreach ($messages as &$message) {
        if (isset($message['reactions'])) {
            $message['reactions'] = $message['reactions'] ? json_decode($message['reactions'], true) : [];
            
            // Group reactions by emoji with counts
            $groupedReactions = [];
            foreach ($message['reactions'] as $reaction) {
                $emoji = $reaction['emoji'];
                if (!isset($groupedReactions[$emoji])) {
                    $groupedReactions[$emoji] = [
                        'count' => 0,
                        'user_reacted' => false
                    ];
                }
                $groupedReactions[$emoji]['count']++;
                if ($reaction['user_id'] == $_SESSION['user_id']) {
                    $groupedReactions[$emoji]['user_reacted'] = true;
                }
            }
            $message['reactions_grouped'] = $groupedReactions;
        }
    }

    // Return JSON response
    echo json_encode(['success' => true, 'messages' => $messages]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error fetching messages: ' . $e->getMessage()]);
}
?>