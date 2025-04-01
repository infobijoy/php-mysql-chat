<?php
// Check if the remember_token cookie is set
if (isset($_COOKIE['remember_token'])) {
    // Extract selector and validator from the cookie
    list($selector, $validator) = explode(':', $_COOKIE['remember_token'], 2);

    // Sanitize input
    $selector = trim($selector);
    $validator = trim($validator);

    // Hash the validator for database comparison
    $validatorHash = hash('sha256', $validator);

    // Prepare and execute the query to find the token
    $query = "SELECT user_id FROM user_tokens WHERE selector = BINARY ? AND validator_hash = BINARY ?";
    $stmt = $conn->prepare($query);

    if ($stmt) {
        $stmt->bind_param('ss', $selector, $validatorHash);
        $stmt->execute();
        $stmt->bind_result($user_id);

        if ($stmt->fetch()) {
            $stmt->close(); // Close the first statement

            // Prepare and execute the query to fetch user data
            $userQuery = "SELECT id, username, email, display_name, profile_picture FROM users WHERE id = ?";
            $userStmt = $conn->prepare($userQuery);

            if ($userStmt) {
                $userStmt->bind_param("i", $user_id);
                $userStmt->execute();
                $userResult = $userStmt->get_result();

                if ($userResult && $userResult->num_rows > 0) {
                    $userData = $userResult->fetch_assoc();

                    // Set session variables
                    $_SESSION['user_id'] = $userData['id'];
                    $_SESSION['username'] = $userData['username'];
                    $_SESSION['email'] = $userData['email'];
                    $_SESSION['display_name'] = $userData['display_name'];
                    $_SESSION['profile_picture'] = $userData['profile_picture'];
                    $_SESSION['logged_in'] = true;

                    // Regenerate the token for security (recommended)
                    $newValidator = bin2hex(random_bytes(32));
                    $newValidatorHash = hash('sha256', $newValidator);
                    $expiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));

                    $updateTokenQuery = "UPDATE user_tokens SET validator_hash = ?, expires_at = ? WHERE selector = ?";
                    $updateStmt = $conn->prepare($updateTokenQuery);
                    if($updateStmt){
                        $updateStmt->bind_param("sss", $newValidatorHash, $expiresAt, $selector);
                        $updateStmt->execute();
                        $updateStmt->close();
                        setcookie('remember_token', $selector . ':' . $newValidator, [
                            'expires' => time() + (30 * 24 * 60 * 60),
                            'path' => '/',
                            'samesite' => 'Strict', // or 'Lax'
                            'httponly' => true, // Important for security
                        ]);

                    }

                }
                $userStmt->close();
            }
        }
    }
}
?>