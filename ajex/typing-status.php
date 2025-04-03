<?php
// Assuming you have established a database connection in $conn
session_start();
date_default_timezone_set('Asia/Dhaka');
include '../include/config-db.php';
$user_id = $_SESSION['user_id']; // The logging user's ID (typer_id)
$typing_for = $_POST['typing_for']; // Assuming you're passing typing_for via POST
$typing_status = date('Y-m-d H:i:s'); // Current timestamp for typing_status

// Sanitize inputs (important for security!)
$typing_for = mysqli_real_escape_string($conn, $typing_for);

// Check if a record exists for the given typer_id and typing_for
$check_sql = "SELECT id FROM typing_status WHERE typer_id = $user_id AND typing_for = '$typing_for'";
$check_result = mysqli_query($conn, $check_sql);

if (mysqli_num_rows($check_result) > 0) {
    // Record exists, update it
    $update_sql = "UPDATE typing_status SET typing_status = '$typing_status' WHERE typer_id = $user_id AND typing_for = '$typing_for'";
    if (mysqli_query($conn, $update_sql)) {
        echo "Typing status updated successfully.";
    } else {
        echo "Error updating typing status: " . mysqli_error($conn);
    }
} else {
    // Record doesn't exist, insert a new one
    $insert_sql = "INSERT INTO typing_status (typer_id, typing_for, typing_status) VALUES ($user_id, '$typing_for', '$typing_status')";
    if (mysqli_query($conn, $insert_sql)) {
        echo "Typing status inserted successfully.";
    } else {
        echo "Error inserting typing status: " . mysqli_error($conn);
    }
}

// Close the database connection
mysqli_close($conn);
?>