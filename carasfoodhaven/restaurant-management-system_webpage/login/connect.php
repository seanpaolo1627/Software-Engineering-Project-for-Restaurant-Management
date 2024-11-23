<?php
// connect.php

if (session_status() == PHP_SESSION_NONE) {
    // Set secure session cookie parameters
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => 'localhost', // Adjust if necessary
        'secure' => false, // Set to true if using HTTPS
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    session_start(); // Start the session
}

$host = "localhost";
$user = "root";
$pass = "";
$db = "carasfoodhaven";  // Ensure this matches your actual database name

$conn = new mysqli($host, $user, $pass, $db);

// Check the connection
if($conn->connect_error){
    die("Failed to connect to DB: " . $conn->connect_error);
}

// Set character set
$conn->set_charset("utf8mb4");
?>