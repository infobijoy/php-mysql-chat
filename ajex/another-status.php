<?php
session_start();
include '../include/config-db.php'; // Database connection

if (!isset($_SESSION['user_id'])) {
    header('Location: ./log-in.php');
    exit;
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'])) {
    $userId = intval($_POST['user_id']);

    $stmt = $conn->prepare("SELECT status FROM users WHERE id = ?");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $stmt->bind_result($lastSeen);
    $stmt->fetch();
    $stmt->close();

    if ($lastSeen) {
        $formattedTime = formatTime($lastSeen);
    } else {
        $formattedTime = 'No Data';
    }

    echo json_encode(['last_seen' => $formattedTime]);
}
$conn->close();

/**
 * Formats the timestamp according to the given conditions.
 * @param string $timestamp MySQL datetime format (YYYY-MM-DD HH:MM:SS)
 * @return string Formatted time
 */
function formatTime($timestamp) {
    $time = strtotime($timestamp);
    $currentDate = date('Y-m-d');
    $givenDate = date('Y-m-d', $time);

    if ($currentDate === $givenDate) {
        return 'Today ' . date('g:i A', $time);
    } elseif ($givenDate === date('Y-m-d', strtotime('-1 day'))) {
        return 'Yesterday ' . date('g:i A', $time);
    } elseif ($givenDate >= date('Y-m-d', strtotime('-6 days'))) {
        return date('l g:i A', $time); // Day name (e.g., Monday)
    } else {
        $format = (date('Y', $time) === date('Y')) ? 'j M g:i A' : 'j M Y g:i A';
        return date($format, $time);
    }
}
?>
