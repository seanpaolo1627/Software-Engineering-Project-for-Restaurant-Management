<?php
// menu_webpage/create_paymongo_checkout.php

require_once __DIR__ . '/vendor/autoload.php';

// Debug: Check if file exists and log its location
if (!file_exists(__DIR__ . '/.env')) {
    error_log("ENV file not found at: " . __DIR__ . '/.env');
    throw new Exception("Environment file not found");
}

// Try loading from .env first
try {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
} catch (Exception $e) {
    error_log("Error loading .env file: " . $e->getMessage());
}

use GuzzleHttp\Client;

// Start the session
session_start();

// Set the appropriate headers
header('Content-Type: application/json');

// Function to send JSON response and exit
function sendResponse($success, $message, $data = []) {
    echo json_encode(['success' => $success, 'message' => $message, 'data' => $data]);
    exit();
}

try {
    // Try multiple methods to get the key
    $paymongoSecretKey = $_ENV['PAYMONGO_SECRET_KEY'] 
        ?? getenv('PAYMONGO_SECRET_KEY')
        ?? (defined('PAYMONGO_SECRET_KEY') ? PAYMONGO_SECRET_KEY : null);

    // Fallback to config.php if environment variables fail
    if (!$paymongoSecretKey && file_exists(__DIR__ . '/config.php')) {
        require_once __DIR__ . '/config.php';
        $paymongoSecretKey = defined('PAYMONGO_SECRET_KEY') ? PAYMONGO_SECRET_KEY : null;
    }

    if (!$paymongoSecretKey) {
        error_log("PayMongo secret key not found in any configuration");
        throw new Exception("Payment configuration error");
    }

    // Ensure that the user is logged in and has a customer_id
    if (!isset($_SESSION['customer_id'])) {
        throw new Exception("User not authenticated.");
    }

    // Collect and validate POST data
    $requiredFields = [
        'order_type',
        'customer_name',
        'contact_number',
        'email',
        'payment_method',
        'total_amount',
        'service_fee',
        'order_items'
    ];

    foreach ($requiredFields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            throw new Exception("Missing required field: {$field}");
        }
    }

    // Collect POST data with proper validation
    $orderType = filter_var($_POST['order_type'], FILTER_SANITIZE_STRING);
    $customerName = filter_var($_POST['customer_name'], FILTER_SANITIZE_STRING);
    $contactNumber = filter_var($_POST['contact_number'], FILTER_SANITIZE_STRING);
    $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
    $address = filter_var($_POST['address'] ?? '', FILTER_SANITIZE_STRING);
    $paymentMethod = filter_var($_POST['payment_method'], FILTER_SANITIZE_STRING);
    $totalAmount = filter_var($_POST['total_amount'], FILTER_VALIDATE_FLOAT);
    $serviceFee = filter_var($_POST['service_fee'], FILTER_VALIDATE_FLOAT);
    $orderItems = $_POST['order_items'];

    if (!$email) {
        throw new Exception("Invalid email format.");
    }

    $payableAmount = $totalAmount + $serviceFee;

    // Basic validations
    if ($paymentMethod !== 'paymongo') {
        throw new Exception("Invalid payment method.");
    }

    if ($payableAmount <= 0) {
        throw new Exception("Invalid payable amount.");
    }

    // Get the base URL for success/failure redirects
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    $baseUrl = $protocol . $host . dirname($_SERVER['PHP_SELF']);

    // Prepare data for PayMongo Link creation
    $data = [
        "data" => [
        "attributes" => [
            "amount" => intval($payableAmount * 100), // Convert to cents
            "currency" => "PHP",
            "description" => "Order Payment at Cara's Food Haven",
            "redirect" => [
                "success" => $baseUrl . "/menu.php?payment_status=success",
                "failed" => $baseUrl . "/menu.php?payment_status=failed"
            ],
            "metadata" => [
                    "customer_id" => $_SESSION['customer_id'],
                    "order_type" => $orderType,
                    "customer_name" => $customerName,
                    "contact_number" => $contactNumber,
                    "email" => $email,
                    "address" => $address,
                    "order_items" => $orderItems
                ]
            ]
        ]
    ];

    // Initialize Guzzle Client with error handling
    try {
        $client = new Client([
            'timeout' => 30,
            'connect_timeout' => 5
        ]);

        // Make the API request to PayMongo to create a Link
        $response = $client->request('POST', 'https://api.paymongo.com/v1/links', [
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Authorization' => 'Basic ' . base64_encode($paymongoSecretKey . ':')
            ],
            'json' => $data
        ]);

        // Parse the response
        $responseBody = json_decode($response->getBody(), true);

        if (isset($responseBody['data']['attributes']['checkout_url'])) {
            $checkoutUrl = $responseBody['data']['attributes']['checkout_url'];

            // Store payment details in session
            $_SESSION['paymongo_link_id'] = $responseBody['data']['id'];
            $_SESSION['payment_amount'] = $payableAmount;
            $_SESSION['order_details'] = [
                'order_type' => $orderType,
                'customer_name' => $customerName,
                'contact_number' => $contactNumber,
                'email' => $email,
                'address' => $address,
                'order_items' => $orderItems,
                'total_amount' => $totalAmount,
                'service_fee' => $serviceFee
            ];

            // Respond with the checkout URL
            sendResponse(true, "Checkout session created successfully.", [
                'checkout_url' => $checkoutUrl
            ]);
        } else {
            throw new Exception("Failed to retrieve checkout URL from PayMongo response: " . json_encode($responseBody));
        }

    } catch (GuzzleHttp\Exception\RequestException $e) {
        error_log("PayMongo API Error: " . $e->getMessage());
        if ($e->hasResponse()) {
            $errorBody = json_decode($e->getResponse()->getBody(), true);
            error_log("PayMongo Error Response: " . json_encode($errorBody));
        }
        throw new Exception("Failed to communicate with payment service.");
    }

} catch (Exception $e) {
    // Log the error message for debugging
    error_log("PayMongo Checkout Error: " . $e->getMessage());
    
    // Send a user-friendly error response
    $errorMessage = "An error occurred while initiating the payment. Please try again.";
    if (defined('DEBUG_MODE') && DEBUG_MODE === true) {
        $errorMessage .= " Debug: " . $e->getMessage();
    }
    
    sendResponse(false, $errorMessage);
}
?>