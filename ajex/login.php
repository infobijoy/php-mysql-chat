<?php
session_start();
include '../include/config-db.php';

// Get form data
$data = json_decode(file_get_contents('php://input'), true);
$email = $data['email'] ?? '';
$password = $data['password'] ?? '';

// Validate input
if (empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Email and password are required.']);
    exit;
}

// Fetch the user from the database
try {
    $stmt = $conn->prepare("SELECT id, password_hash FROM users WHERE email = ?");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($userId, $passwordHash);

    if ($stmt->fetch() && password_verify($password, $passwordHash)) {
        $_SESSION['user_id'] = $userId;
        echo json_encode(['success' => true, 'message' => 'Login successful!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid email or password.']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'An error occurred.']);
}
?>