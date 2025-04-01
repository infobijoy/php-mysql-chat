<?php
session_start();
require '../include/config-db.php';

header('Content-Type: application/json');

// Get form data
$data = json_decode(file_get_contents('php://input'), true);
$email = $data['email'] ?? '';
$password = $data['password'] ?? '';
$rememberMe = $data['rememberMe'] ?? false;

// Validate input
if (empty($email) || empty($password)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email and password are required']);
    exit;
}

try {
    // Fetch user from database
    $stmt = $conn->prepare("SELECT id, username, email, password_hash FROM users WHERE email = ? LIMIT 1");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // Simulate password verification to prevent timing attacks
        password_verify('dummy_password', '$2y$10$fakehashfakehashfakehashfake');
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
        exit;
    }

    $user = $result->fetch_assoc();
    
    if (password_verify($password, $user['password_hash'])) {
        // Regenerate session ID to prevent fixation
        session_regenerate_id(true);
        
        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['logged_in'] = true;
        
        $response = [
            'success' => true,
            'message' => 'Login successful',
            'redirect' => 'deshboard.php'
        ];
        
        // Generate token for "Remember Me" if requested
        if ($rememberMe) {
            $selector = bin2hex(random_bytes(12));
            $validator = bin2hex(random_bytes(32));
            $validatorHash = hash('sha256', $validator);
            $expires = date('Y-m-d H:i:s', time() + 365 * 24 * 60 * 60);
            
            // Store token in database
            $tokenStmt = $conn->prepare("INSERT INTO user_tokens (user_id, selector, validator_hash, expires_at) VALUES (?, ?, ?, ?)");
            $tokenStmt->bind_param('isss', $user['id'], $selector, $validatorHash, $expires);

            if (!$tokenStmt->execute()) {
                error_log("Token storage error: " . $tokenStmt->error);
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Failed to store remember me token.']);
                exit;
            }
            
            // Return token parts to be set as cookie via JavaScript
            $response['rememberToken'] = [
                'selector' => $selector,
                'validator' => $validator,
                'expires' => time() + 365 * 24 * 60 * 60
            ];
        }
        
        echo json_encode($response);
        
    } else {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
    }
    
} catch (Exception $e) {
    error_log("Login error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again.']);
}
?>