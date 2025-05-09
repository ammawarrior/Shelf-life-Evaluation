<?php
session_start();
include 'db.php'; // Database connection


// Check if user is logged in
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];




    // Destroy session
    session_unset();
    session_destroy();
}

// Redirect to login page
header("Location: login.php");
exit();
?>
