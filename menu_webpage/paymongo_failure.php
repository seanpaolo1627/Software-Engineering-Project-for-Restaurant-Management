<?php
// menu_webpage/paymongo_failure.php
require_once('vendor/autoload.php');

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment Failed - Cara's Food Haven</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            font-family: 'Open Sans', sans-serif;
            background-color: #f8d7da;
            color: #721c24;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .failure-container {
            text-align: center;
            background: #f8d7da;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        .failure-container i {
            font-size: 64px;
            margin-bottom: 20px;
            color: #f5c6cb;
        }
        .back-button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #c82333;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
            transition: background-color 0.3s;
        }
        .back-button:hover {
            background-color: #a71d2a;
        }
    </style>
</head>
<body>
    <div class="failure-container">
        <i class="fas fa-times-circle"></i>
        <h1>Payment Failed</h1>
        <p>We're sorry, but your payment could not be processed.</p>
        <a href="menu.php" class="back-button"><i class="fas fa-arrow-left"></i> Back to Menu</a>
    </div>
</body>
</html>