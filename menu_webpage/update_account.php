<?php
// update_account.php

// Set the Content-Type header to application/json
header('Content-Type: application/json');

// Start the session
session_start();

// Disable error display for production to prevent outputting errors in JSON
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../error.log'); // Ensure this path is correct and writable

// Start output buffering to prevent unintended output
ob_start();

try {
    // Include the database connection
    include 'login/connect.php';
} catch (Exception $e) {
    // If connection fails, return a JSON error response
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit();
}

// Clear any buffered output to ensure only JSON is sent
ob_clean();

// Check if the user is logged in
if(!isset($_SESSION['email'])){
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$email = strtolower(trim($_SESSION['email']));

// Retrieve and sanitize POST data
$firstName = trim($_POST['firstName'] ?? '');
$lastName = trim($_POST['lastName'] ?? '');
$contactNumber = trim($_POST['contactNumber'] ?? '');
$address = trim($_POST['address'] ?? '');

// Validate inputs
if(empty($firstName) || empty($lastName) || empty($contactNumber) || empty($address)){
    echo json_encode(['success' => false, 'message' => 'All fields are required.']);
    exit();
}

// Optional: Add further validation (e.g., regex for contact number)
if(!preg_match('/^\d{10,11}$/', $contactNumber)){
    echo json_encode(['success' => false, 'message' => 'Contact number must be 10 or 11 digits.']);
    exit();
}

// Prepare the UPDATE query
$updateQuery = "UPDATE customer SET firstName = ?, lastName = ?, contactNumber = ?, address = ? WHERE email = ?";
$stmt = $conn->prepare($updateQuery);

// Check if the statement was prepared successfully
if(!$stmt){
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    exit();
}

// Bind parameters
$stmt->bind_param('sssss', $firstName, $lastName, $contactNumber, $address, $email);

// Execute the statement
if($stmt->execute()){
    echo json_encode(['success' => true, 'message' => 'Account updated successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update account: ' . $stmt->error]);
}

// Close the statement and connection
$stmt->close();
$conn->close();

