<?php
session_start();
date_default_timezone_set('Asia/Dhaka');

if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    die(json_encode(['error' => 'Not authenticated']));
}

include '../include/config-db.php';

function isUserOnline($statusTime) {
    return $statusTime && (time() - strtotime($statusTime)) <= 30;
}

function formatLastSeen($statusTime) {
    if (isUserOnline($statusTime)) return 'Online';
    if (!$statusTime) return 'Long time ago';

    $diff = time() - strtotime($statusTime);
    $statusTimestamp = strtotime($statusTime);
    $now = time();
    
    // If within last minute
    if ($diff < 60) return "{$diff}s ago";
    
    // If within last hour
    if ($diff < 3600) return floor($diff / 60) . 'm ago';
    
    // If within last 24 hours
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    
    // If within the current week (last 7 days)
    if ($diff < 604800) {
        $statusDate = date('Y-m-d', $statusTimestamp);
        $today = date('Y-m-d', $now);
        $yesterday = date('Y-m-d', strtotime('-1 day', $now));
        
        if ($statusDate === $today) {
            return 'Today at ' . date('g:i A', $statusTimestamp);
        } elseif ($statusDate === $yesterday) {
            return 'Yesterday at ' . date('g:i A', $statusTimestamp);
        } else {
            return date('l \a\t g:i A', $statusTimestamp); // Day name (Monday, Tuesday, etc.)
        }
    }
    
    // If within current year
    if (date('Y', $statusTimestamp) === date('Y', $now)) {
        return date('M j \a\t g:i A', $statusTimestamp); // Month and day
    }
    
    // For previous years
    return date('M j, Y \a\t g:i A', $statusTimestamp); // Month, day, and year
}

$userId = $_GET['user_id'];
$stmt = $conn->prepare("SELECT status FROM users WHERE id = ?");
$stmt->bind_param('i', $userId);
$stmt->execute();
$stmt->bind_result($status);
$stmt->fetch();
$stmt->close();

header('Content-Type: application/json');
echo json_encode([
    'statusClass' => isUserOnline($status) ? 'online' : 'offline',
    'lastSeen' => formatLastSeen($status)
]);
?>