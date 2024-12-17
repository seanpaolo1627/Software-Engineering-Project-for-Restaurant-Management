<?php
// get_account_details.php

header('Content-Type: application/json');
session_start();

// Start output buffering to prevent unintended output
ob_start();

try {
    include 'login/connect.php';
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit();
}

// Clear any buffered output
ob_clean();

// Check if the user is logged in
if(!isset($_SESSION['email'])){
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$email = strtolower(trim($_SESSION['email']));

// Fetch user details from the database
$query = $conn->prepare("SELECT firstName, lastName, contactNumber, address FROM customer WHERE email = ?");
if (!$query) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    exit();
}

$query->bind_param('s', $email);
$query->execute();
$result = $query->get_result();

if($result->num_rows > 0){
    $user = $result->fetch_assoc();
    echo json_encode(['success' => true, 'data' => $user]);
} else {
    echo json_encode(['success' => false, 'message' => 'User not found']);
}

$query->close();
$conn->close();
// No closing PHP tag