<?php
session_start();
include '../include/config-db.php';

// Get form data
$data = json_decode(file_get_contents('php://input'), true);
$username = $data['username'] ?? '';
$email = $data['email'] ?? '';
$password = $data['password'] ?? '';

// Validate input
if (empty($username) || empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required.']);
    exit;
}

// Hash the password
$passwordHash = password_hash($password, PASSWORD_BCRYPT);

// Insert the user into the database
try {
    $stmt = $conn->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
    $stmt->bind_param('sss', $username, $email, $passwordHash);
    $stmt->execute();

    echo json_encode(['success' => true, 'message' => 'Signup successful!']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Signup failed. Please try again.']);
}
?>