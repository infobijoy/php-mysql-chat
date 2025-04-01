<?php

function verifyAuth($conn) {
    // First check session
    if (isset($_SESSION['user_id'])) {
        return $_SESSION['user_id'];
    }

    // If no session, check remember token
    if (isset($_COOKIE['remember_token'])) {
        $tokenParts = explode(':', $_COOKIE['remember_token']);
        if (count($tokenParts) === 2) {
            list($selector, $validator) = $tokenParts;
            
            $stmt = $conn->prepare("SELECT t.user_id, t.validator_hash, t.expires_at, u.username, u.email 
                                   FROM user_tokens t
                                   JOIN users u ON t.user_id = u.id
                                   WHERE t.selector = ? LIMIT 1");
            $stmt->bind_param('s', $selector);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($row = $result->fetch_assoc()) {
                if (strtotime($row['expires_at']) >= time() && 
                    hash_equals($row['validator_hash'], hash('sha256', $validator))) {
                    
                    // Regenerate session
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $row['user_id'];
                    $_SESSION['username'] = $row['username'];
                    $_SESSION['email'] = $row['email'];
                    $_SESSION['logged_in'] = true;
                    
                    return $row['user_id'];
                }
            }
        }
    }
    
    return false;
}

// Usage in protected pages:
if (!verifyAuth($conn)) {
    header('Location: /log-in.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}
?>