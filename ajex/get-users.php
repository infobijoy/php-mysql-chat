<?php
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

include '../include/config-db.php';

try {
    $query = "SELECT 
             u.id, 
             u.username, 
             u.display_name, 
             u.profile_picture,
             u.status,
             m.message AS last_message,
             m.created_at AS last_message_time
          FROM users u
          LEFT JOIN (
              SELECT 
                  sender_id, 
                  receiver_id, 
                  message, 
                  created_at,
                  ROW_NUMBER() OVER (PARTITION BY LEAST(sender_id, receiver_id), GREATEST(sender_id, receiver_id) ORDER BY created_at DESC) as rn
              FROM messages
          ) m ON (m.sender_id = u.id OR m.receiver_id = u.id) AND m.rn = 1
          WHERE u.id != ?
          GROUP BY u.id
          ORDER BY MAX(COALESCE(m.created_at, u.status)) DESC";

    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $users = $result->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode([
        'success' => true,
        'users' => $users
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>