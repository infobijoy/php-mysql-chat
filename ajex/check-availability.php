<?php
include '../include/config-db.php';

// Get the field and value from the query string

if (!isset($_GET['email'])) {
    $field = 'username';
    $value = $_GET['username'] ?? '';
} elseif (!isset($_GET['username'])) {
    $field = 'email';
    $value = $_GET['email'] ?? '';
}

// Validate the input
if (empty($field) || empty($value)) {
    echo json_encode(['available' => false, 'error' => 'Field and value are required.']);
    exit;
}

// Prepare the SQL query to check availability
$query = '';
if ($field === 'username') {
    $query = "SELECT id FROM users WHERE username = ?";
} elseif ($field === 'email') {
    $query = "SELECT id FROM users WHERE email = ?";
}

// Execute the query
try {
    $stmt = $conn->prepare($query);
    $stmt->bind_param('s', $value);
    $stmt->execute();
    $stmt->store_result();

    // Check if the username or email already exists
    $available = $stmt->num_rows === 0;

    echo json_encode(['available' => $available]);
} catch (Exception $e) {
    echo json_encode(['available' => false, 'error' => 'An error occurred.']);
}
?>