<?php
// register.php

// Enable error reporting for debugging (remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start(); // Start the session at the very beginning
include 'connect.php'; // Include the database connection

// Handle Sign Up
if(isset($_POST['signUp'])){
    // Retrieve and sanitize user inputs
    $firstName = trim($_POST['fName']);
    $lastName = trim($_POST['lName']);
    $email = strtolower(trim($_POST['email'])); // Convert email to lowercase
    $password = trim($_POST['password']);
    $contactNumber = trim($_POST['contactNumber']);
    $address = trim($_POST['address']);

    // Validate inputs
    if(empty($firstName) || empty($lastName) || empty($email) || empty($password) || empty($contactNumber) || empty($address)){
        $_SESSION['error'] = "All fields are required.";
        header("Location: index.php");
        exit();
    }

    // Check if email already exists
    $checkEmail = "SELECT * FROM users WHERE email = ?";
    $stmt = $conn->prepare($checkEmail);
    if(!$stmt){
        $_SESSION['error'] = "Database error: " . $conn->error;
        header("Location: index.php");
        exit();
    }
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows > 0){
        // Email already exists
        $_SESSION['error'] = "Email Address Already Exists!";
        header("Location: index.php");
        exit();
    } else {
        // Hash the password using password_hash
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Insert new user into database
        $insertQuery = "INSERT INTO users (firstName, lastName, email, password, contactNumber, address)
                        VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insertQuery);
        if(!$stmt){
            $_SESSION['error'] = "Database error: " . $conn->error;
            header("Location: index.php");
            exit();
        }
        $stmt->bind_param('ssssss', $firstName, $lastName, $email, $hashedPassword, $contactNumber, $address);

        if($stmt->execute()){
            $_SESSION['email'] = $email; // Set session variable after registration
            header("Location: ../menu.php"); // Redirect to menu.php
            exit();
        } else {
            // Handle insertion error
            $_SESSION['error'] = "Registration failed: " . $stmt->error;
            header("Location: index.php");
            exit();
        }
    }
    $stmt->close();
}

// Handle Sign In
if(isset($_POST['signIn'])){
    // Retrieve and sanitize user inputs
    $email = strtolower(trim($_POST['email'])); // Convert email to lowercase
    $password = trim($_POST['password']);

    // Validate inputs
    if(empty($email) || empty($password)){
        $_SESSION['error'] = "Both email and password are required.";
        header("Location: index.php");
        exit();
    }

    // Check if the user exists with the given email
    $sql = "SELECT * FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    if(!$stmt){
        $_SESSION['error'] = "Database error: " . $conn->error;
        header("Location: index.php");
        exit();
    }
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows > 0){
        $row = $result->fetch_assoc();

        // Debugging: Uncomment the lines below to inspect password hashes
        // echo "<!-- Debug: Stored Hash - " . htmlspecialchars($row['password']) . " -->";
        // echo "<!-- Debug: Entered Password - " . htmlspecialchars($password) . " -->";

        // Verify the entered password with the hashed password
        if(password_verify($password, $row['password'])){
            // Password is correct
            session_regenerate_id(true); // Prevent session fixation
            $_SESSION['email'] = $row['email'];
            header("Location: ../menu.php"); // Redirect to menu.php
            exit();
        } else {
            // Incorrect password
            $_SESSION['error'] = "Incorrect Email or Password.";
            header("Location: index.php");
            exit();
        }
    } else {
        // Email not found
        $_SESSION['error'] = "Incorrect Email or Password.";
        header("Location: index.php");
        exit(); 
    }
    $stmt->close();
}
?>
