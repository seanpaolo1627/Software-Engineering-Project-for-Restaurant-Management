<?php
// menu_webpage/paymongo_success.php

require_once('vendor/autoload.php'); // Ensure Guzzle is autoloaded

   // Load environment variables
   $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
   $dotenv->load();

use GuzzleHttp\Client;

// Start the session
session_start();

// Include database connection
include 'login/connect.php';

// Function to redirect with message
function redirectWithMessage($message, $success = true) {
    // You can customize this to redirect to a specific page with a message
    // For simplicity, we'll redirect to order_confirmation.php
    header("Location: order_confirmation.php?message=" . urlencode($message) . "&success=" . ($success ? "1" : "0"));
    exit();
}

try {
    // Retrieve the session_id from PayMongo URL parameters
    if (!isset($_GET['payment_session'])) {
        throw new Exception("Payment session ID not found.");
    }

    $sessionId = $_GET['payment_session'];

    if (!$sessionId) {
        throw new Exception("Invalid payment session ID.");
    }

    // Initialize Guzzle Client
    $client = new Client();

    // Replace 'YOUR_PAYMONGO_SECRET_KEY' with your actual PayMongo secret key
    $paymongoSecretKey = getenv('PAYMONGO_SECRET_KEY'); // Ensure this is set in your environment variables

    if (!$paymongoSecretKey) {
        throw new Exception("PayMongo secret key not set in environment variables.");
    }

    // Fetch the payment session details from PayMongo
    $response = $client->request('GET', 'https://api.paymongo.com/v1/checkout_sessions/' . $sessionId, [
        'headers' => [
            'Accept' => 'application/json',
            'Authorization' => 'Basic ' . base64_encode($paymongoSecretKey . ':')
        ]
    ]);

    $responseBody = json_decode($response->getBody(), true);

    if (
        isset($responseBody['data']['attributes']['payments']) &&
        count($responseBody['data']['attributes']['payments']) > 0
    ) {
        $payment = $responseBody['data']['attributes']['payments'][0];
        $status = $payment['attributes']['status'];

        if ($status === 'paid') {
            // Payment was successful, process the order
            // Retrieve order details from metadata
            $metadata = $responseBody['data']['attributes']['metadata'];

            $orderType = $metadata['order_type'] ?? '';
            $customerName = $metadata['customer_name'] ?? '';
            $contactNumber = $metadata['contact_number'] ?? '';
            $email = $metadata['email'] ?? '';
            $address = $metadata['address'] ?? '';
            $orderItems = $metadata['order_items'] ?? [];

            $customerId = $_SESSION['customer_id'] ?? null;

            if (!$customerId) {
                throw new Exception("Customer ID not found in session.");
            }

            // Start transaction
            $conn->begin_transaction();

            // Insert order header
            $orderQuery = "INSERT INTO `order` (order_type, order_status, total_price, payment_id, customer_ID) 
                          VALUES (?, 'PAID', ?, ?, ?)";
            $stmt = $conn->prepare($orderQuery);
            if (!$stmt) {
                throw new Exception("Prepare statement failed: " . $conn->error);
            }

            $totalAmount = array_sum(array_column($orderItems, 'subtotal'));

            // Bind parameters
            $stmt->bind_param("sdsi", 
                $orderType, 
                $totalAmount,
                $payment['id'], // payment_id from PayMongo
                $customerId
            );

            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }

            $orderId = $conn->insert_id;

            // Insert order items
            $itemQuery = "INSERT INTO order_items (order_ID, menu_item_ID, quantity, price_at_time) 
                          VALUES (?, ?, ?, ?)";
            $itemStmt = $conn->prepare($itemQuery);
            if (!$itemStmt) {
                throw new Exception("Prepare statement for order items failed: " . $conn->error);
            }

            foreach ($orderItems as $item) {
                // Get menu_item_ID based on item name
                $menuItemQuery = "SELECT ID FROM menu_item WHERE name = ?";
                $menuItemStmt = $conn->prepare($menuItemQuery);
                if (!$menuItemStmt) {
                    throw new Exception("Prepare statement for menu item failed: " . $conn->error);
                }

                $menuItemStmt->bind_param("s", $item['name']);
                if (!$menuItemStmt->execute()) {
                    throw new Exception("Execute failed for menu item: " . $menuItemStmt->error);
                }

                $result = $menuItemStmt->get_result();
                $menuItem = $result->fetch_assoc();

                if ($menuItem) {
                    $itemStmt->bind_param("iiid",
                        $orderId,
                        $menuItem['ID'],
                        $item['quantity'],
                        $item['price']
                    );
                    if (!$itemStmt->execute()) {
                        throw new Exception("Execute failed for order item: " . $itemStmt->error);
                    }
                } else {
                    throw new Exception("Menu item not found: " . $item['name']);
                }

                $menuItemStmt->close();
            }

            // Commit transaction
            $conn->commit();

            // Optionally, you can send a confirmation email to the user here

            // Redirect to order confirmation page
            redirectWithMessage("Your payment was successful! Your order has been placed.", true);
        } else {
            throw new Exception("Payment not completed. Status: " . $status);
        }
    } else {
        throw new Exception("No payment found for this session.");
    }

} catch (Exception $e) {
    // Log the error message for debugging (do not expose sensitive info to users)
    error_log("PayMongo Success Error: " . $e->getMessage());

    // Redirect with error message
    redirectWithMessage("There was an issue processing your payment. Please try again.", false);
}

?>