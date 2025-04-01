<?php

session_start();

// Remove the remember_token cookie from the browser
if (isset($_COOKIE['remember_token'])) {
    // Set the cookie's expiration to a past time to remove it
    setcookie('remember_token', '', time() - 3600, '/'); // Adjust path if needed
    unset($_COOKIE['remember_token']); //unset the cookie from the $_COOKIE array.
}

// Remove the token from the database
include './include/config-db.php'; // Include your database connection

if (isset($_SESSION['user_id'])) { // only if user is logged in.
    $user_id = $_SESSION['user_id'];
    $query = "DELETE FROM user_tokens WHERE user_id = ?";
    $stmt = $conn->prepare($query);     

    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();
    }
}

// Destroy the session on the server
session_destroy();

// Redirect to the login page
header('Location: ./log-in.php');
exit;

?>