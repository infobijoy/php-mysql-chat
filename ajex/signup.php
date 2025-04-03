<?php
session_start();
include '../include/config-db.php';

// Handle file upload
$profilePicturePath = null;
if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = '../profile-photo/'; // Directory to store uploaded images
    $uploadFile = $uploadDir . basename($_FILES['profile_picture']['name']);

    // Basic file validation (you should add more robust checks)
    $imageFileType = strtolower(pathinfo($uploadFile, PATHINFO_EXTENSION));
    $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
    if (in_array($imageFileType, $allowedTypes)) {
        if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $uploadFile)) {
            $profilePicturePath = basename($_FILES['profile_picture']['name']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to upload profile picture.']);
            exit;
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid file type for profile picture.']);
        exit;
    }
}

// Get form data
$username = $_POST['username'] ?? '';
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';
$displayName = $_POST['display_name'] ?? '';

// Validate input
if (empty($username) || empty($email) || empty($password) || empty($displayName)) {
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
    $stmt = $conn->prepare("INSERT INTO users (username, email, password_hash, display_name, profile_picture, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW(), NOW())");
    $status = 1; // Or any default status
    $stmt->bind_param('sssss', $username, $email, $passwordHash, $displayName, $profilePicturePath);
    $stmt->execute();

    // Get the newly created user ID
    $user_id = $stmt->insert_id;

    // Create session
    $_SESSION['user_id'] = $user_id;
    $_SESSION['username'] = $username;
    $_SESSION['email'] = $email;
    $_SESSION['display_name'] = $displayName;
    $_SESSION['profile_picture'] = $profilePicturePath;

    // Generate a secure remember token
    $selector = bin2hex(random_bytes(12));
    $validator = bin2hex(random_bytes(32));
    $validatorHash = hash('sha256', $validator);
    $expiry = date('Y-m-d H:i:s', time() + 365 * 24 * 60 * 60); // 1 year from now

    // Store token in database
    $tokenStmt = $conn->prepare("INSERT INTO user_tokens (user_id, selector, validator_hash, expires_at) VALUES (?, ?, ?, ?)");
    $tokenStmt->bind_param('isss', $user_id, $selector, $validatorHash, $expiry);
    $tokenStmt->execute();

    // Set secure HTTP-only cookie
    setcookie(
        'remember_token',
        $selector . ':' . $validator,
        [
            'expires' => time() + (365 * 24 * 60 * 60),
            'path' => '/',
            'samesite' => 'Strict', // Or 'Lax'
            'httponly' => true,    // Not accessible via JavaScript
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