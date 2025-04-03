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

function formatLastSeen($statusTime) {
    if (isUserOnline($statusTime)) return 'Online';
    if (!$statusTime) return 'Long time ago';
    
    $diff = time() - strtotime($statusTime);
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff/60) . 'm ago';
    if ($diff < 86400) return floor($diff/3600) . 'h ago';
    return floor($diff/86400) . 'd ago';
}
?>