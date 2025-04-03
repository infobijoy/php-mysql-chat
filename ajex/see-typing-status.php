<?php
// Assuming you have a database connection established as $conn
session_start();
date_default_timezone_set('Asia/Dhaka');
include '../include/config-db.php';
function checkTypingStatus($conn, $loggedInUserId, $typingForUserId) {
    $sql = "SELECT typing_status FROM typing_status WHERE typer_id = ? AND typing_for = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $typingForUserId, $loggedInUserId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $typingStatusTimestamp = strtotime($row['typing_status']);
        $currentTime = time();
        $diff = abs($currentTime - $typingStatusTimestamp);

        if ($diff <= 3) {
            
            return  '<span class="typing-indicator flex space-x-1 items-center">
                                <span class="text-xs italic">typing</span>
                                <span class="w-1 h-1 rounded-full bg-current opacity-70"></span>
                                <span class="w-1 h-1 rounded-full bg-current opacity-70"></span>
                                <span class="w-1 h-1 rounded-full bg-current opacity-70"></span>
                            </span>';
        } else {
            return ""; // Or some other message like "" or "User is not typing"
        }
    } else {
        return ""; // Or some other message like "" or "User is not typing"
    }
}

if (isset($_GET['typingForUserId']) && isset($_GET['loggedInUserId'])) {
    $typingForUserId = $_GET['typingForUserId'];
    $loggedInUserId = $_GET['loggedInUserId'];
    echo checkTypingStatus($conn, $loggedInUserId, $typingForUserId);
    exit; // Important to stop further execution after sending the response
}
?>