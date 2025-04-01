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

// Check if username or email already exists
try {
    $checkStmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $checkStmt->bind_param('ss', $username, $email);
    $checkStmt->execute();
    $checkStmt->store_result();
    
    if ($checkStmt->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Username or email already exists.']);
        exit;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error. Please try again.']);
    exit;
}

// Hash the password
$passwordHash = password_hash($password, PASSWORD_BCRYPT);

// Insert the user into the database
try {
    $stmt = $conn->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
    $stmt->bind_param('sss', $username, $email, $passwordHash);
    $stmt->execute();
    
    // Get the newly created user ID
    $user_id = $stmt->insert_id;
    
    // Create session
    $_SESSION['user_id'] = $user_id;
    $_SESSION['username'] = $username;
    $_SESSION['email'] = $email;
    
    // Generate a secure remember token
    $rememberToken = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $rememberToken);
    $expiry = time() + 365 * 24 * 60 * 60; // 1 year from now
    
    // Store token in database
    $tokenStmt = $conn->prepare("INSERT INTO user_tokens (user_id, token_hash, expires_at) VALUES (?, ?, ?)");
    $tokenStmt->bind_param('iss', $user_id, $tokenHash, date('Y-m-d H:i:s', $expiry));
    $tokenStmt->execute();
    
    // Set secure HTTP-only cookie
    setcookie(
        'remember_token',
        $rememberToken,
        [
            'expires' => $expiry,
            'path' => '/',
            'domain' => $_SERVER['HTTP_HOST'],
            'secure' => true,     // Send only over HTTPS
            'httponly' => true,   // Not accessible via JavaScript
            'samesite' => 'Lax'   // Prevent CSRF
        ]
    );
    
    echo json_encode([
        'success' => true, 
        'message' => 'Signup successful!',
        'redirect' => 'deshboard.php'
    ]);
    
} catch (Exception $e) {
    error_log("Signup error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Signup failed. Please try again.']);
}
?>