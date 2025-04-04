<?php
session_start();
date_default_timezone_set('Asia/Dhaka');
if (!isset($_SESSION['user_id'])) {
    die('Not authenticated');
}

include '../include/config-db.php';

function isUserOnline($statusTime) {
    return $statusTime && (time() - strtotime($statusTime)) <= 30;
}

function getStatusClass($statusTime) {
    if (isUserOnline($statusTime)) {
        return 'online';
    }
    return 'offline';
}

function formatLastSeen($statusTime) {
    if (isUserOnline($statusTime)) {
        return 'Just now';
    }
    if (!$statusTime) {
        return 'Long time ago';
    }
    
    $diff = time() - strtotime($statusTime);
    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        return floor($diff/60) . 'm ago';
    } elseif ($diff < 86400) {
        return floor($diff/3600) . 'h ago';
    } else {
        return floor($diff/86400) . 'd ago';
    }
}

try {
    $query = "SELECT 
                 u.id, 
                 u.username, 
                 u.display_name, 
                 u.profile_picture,
                 u.status,
                 (SELECT message FROM messages 
                  WHERE (sender_id = u.id AND receiver_id = ?) OR (sender_id = ? AND receiver_id = u.id)
                  ORDER BY created_at DESC LIMIT 1) AS last_message,
                 (SELECT created_at FROM messages 
                  WHERE (sender_id = u.id AND receiver_id = ?) OR (sender_id = ? AND receiver_id = u.id)
                  ORDER BY created_at DESC LIMIT 1) AS last_message_time,
                 (SELECT sender_id FROM messages 
                  WHERE (sender_id = u.id AND receiver_id = ?) OR (sender_id = ? AND receiver_id = u.id)
                  ORDER BY created_at DESC LIMIT 1) AS last_message_sender,
                 (SELECT COUNT(*) FROM messages 
                  WHERE ((sender_id = u.id AND receiver_id = ?) OR (sender_id = ? AND receiver_id = u.id))
                  AND is_seen = 0 AND receiver_id = ?) AS unread_count,
                 t.typing_status
              FROM users u
              LEFT JOIN typing_status t ON t.typer_id = u.id AND t.typing_for = ?
              WHERE u.id != ?
              ORDER BY 
                  CASE 
                      WHEN last_message_time IS NOT NULL THEN last_message_time
                      ELSE u.status
                  END DESC";

    $stmt = $conn->prepare($query);
    $stmt->bind_param('iiiiiiiiiii', 
        $_SESSION['user_id'], $_SESSION['user_id'], 
        $_SESSION['user_id'], $_SESSION['user_id'],
        $_SESSION['user_id'], $_SESSION['user_id'],
        $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id'],
        $_SESSION['user_id'], $_SESSION['user_id']
    );
    $stmt->execute();
    $result = $stmt->get_result();

    while ($user = $result->fetch_assoc()) {
        $lastMessageTime = $user['last_message_time'] ? formatLastSeen($user['last_message_time']) : 'No messages';
        $statusClass = getStatusClass($user['status']);
        $unreadCount = $user['unread_count'] ?? 0;
        $isTyping = (!empty($user['typing_status']) && abs(time() - strtotime($user['typing_status'])) <= 3);
        
        $lastMessage = htmlspecialchars($user['last_message'] ?? 'No messages yet');
        if ($user['last_message_sender'] == $_SESSION['user_id'] && !empty($user['last_message'])) {
            $lastMessage = 'You: ' . $lastMessage;
        }
        
        $isActive = isset($_GET['user_id']) && $_GET['user_id'] == $user['id'];
?>
        <a href="?user_id=<?= htmlspecialchars($user['id']) ?>" 
           class="flex items-center p-4 space-x-3 relative <?= $isActive ? 'active-conversation bg-gray-100' : 'conversation-card hover:bg-gray-50' ?>">
            <div class="avatar relative">
                <div class="w-12 rounded-full">
                    <img src="./profile-photo/<?= htmlspecialchars($user['profile_picture'] ?? 'default.jpg') ?>" 
                         alt="<?= htmlspecialchars($user['display_name'] ?? $user['username']) ?>" />
                </div>
                <span class="status-dot <?= $statusClass ?> rounded-full absolute bottom-0 right-0"></span>
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex justify-between items-center">
                    <h3 class="font-semibold truncate"><?= htmlspecialchars($user['display_name'] ?? $user['username']) ?></h3>
                    <span class="text-xs opacity-70"><?= $lastMessageTime ?></span>
                </div>
                <div class="flex justify-between">
                    <p class="text-sm truncate max-w-[180px]">
                        <?php if ($isTyping): ?>
                            <span class="typing-indicator flex space-x-1 items-center">
                                <span class="text-xs italic">typing</span>
                                <span class="w-1 h-1 rounded-full bg-current opacity-70"></span>
                                <span class="w-1 h-1 rounded-full bg-current opacity-70"></span>
                                <span class="w-1 h-1 rounded-full bg-current opacity-70"></span>
                            </span>
                        <?php else: ?>
                            <?= $lastMessage ?>
                        <?php endif; ?>
                    </p>
                    <?php if ($unreadCount > 0): ?>
                        <span class="w-3 h-3 rounded-full bg-red-500"><?php //echo $unreadCount;?></span>
                    <?php endif; ?>
                </div>
            </div>
        </a>
<?php
    }

    $stmt->close(); 
} catch (Exception $e) {
    echo '<div class="p-4 bg-red-100 text-red-700 rounded-lg">Error loading conversations: '.htmlspecialchars($e->getMessage()).'</div>';
}
?>