<?php
session_start();
require_once __DIR__ . '/vendor/autoload.php';
include 'login/connect.php';

use GuzzleHttp\Client;

function redirectWithMessage($message, $success = true) {
    header("Location: order_confirmation.php?message=" . urlencode($message) . "&success=" . ($success ? "1" : "0"));
    exit();
}

try {
    // Retrieve the session ID from session storage
    if (!isset($_SESSION['paymongo_session_id'])) {
        throw new Exception("No payment session ID found in session. Payment might not have been completed.");
    }

    $sessionId = $_SESSION['paymongo_session_id'];

    $client = new Client();
    $paymongoSecretKey = 'sk_test_AkA74hYDrtPYk5ZA8BbuNbxZ';

    // Fetch payment session details
    $response = $client->request('GET', "https://api.paymongo.com/v1/checkout_sessions/" . $sessionId, [
        'headers' => [
            'Accept' => 'application/json',
            'Authorization' => 'Basic ' . base64_encode($paymongoSecretKey . ':')
        ]
    ]);

    $sessionData = json_decode($response->getBody(), true);

    if (!isset($sessionData['data']['attributes']['payment_intent']['id'])) {
        throw new Exception("Payment intent not found in session data.");
    }

    $paymentIntentId = $sessionData['data']['attributes']['payment_intent']['id'];
    $metadata = $sessionData['data']['attributes']['metadata'] ?? [];
    $paymentStatus = $sessionData['data']['attributes']['status'] ?? 'unknown';

    // If payment status is not paid, double-check the payment intent itself
    if ($paymentStatus !== 'paid') {
        // Check the payment intent status directly
        $piResponse = $client->request('GET', "https://api.paymongo.com/v1/payment_intents/" . $paymentIntentId, [
            'headers' => [
                'Accept' => 'application/json',
                'Authorization' => 'Basic ' . base64_encode($paymongoSecretKey . ':')
            ]
        ]);

        $piData = json_decode($piResponse->getBody(), true);
        $paymentIntentStatus = $piData['data']['attributes']['status'] ?? 'unknown';

        if ($paymentIntentStatus !== 'succeeded') {
            // Payment really isn't completed
            throw new Exception("Payment is not completed. Payment Intent Status: " . $paymentIntentStatus);
        }
    }

    // At this point, either paymentStatus is 'paid' or paymentIntentStatus is 'succeeded'
    if (!isset($metadata['customer_id']) || !isset($metadata['total_amount']) || !isset($metadata['order_items']) || !isset($metadata['order_type'])) {
        throw new Exception("Insufficient metadata to process order.");
    }

    $customerId = $metadata['customer_id'];
    $totalAmount = floatval($metadata['total_amount']);
    $orderType = $metadata['order_type'];
    $orderItems = json_decode($metadata['order_items'], true);

    if (!is_array($orderItems) || empty($orderItems)) {
        throw new Exception("No order items found in metadata.");
    }

    // Insert order and order items into the database
    $conn->begin_transaction();

    try {
        $orderQuery = "INSERT INTO `order` (order_type, order_status, total_price, payment_id, customer_ID) VALUES (?, 'PENDING', ?, ?, ?)";
        $stmt = $conn->prepare($orderQuery);
        if (!$stmt) {
            throw new Exception("Failed to prepare order statement: " . $conn->error);
        }

        $stmt->bind_param("sdsi", $orderType, $totalAmount, $paymentIntentId, $customerId);
        if (!$stmt->execute()) {
            throw new Exception("Failed to insert order: " . $stmt->error);
        }

        $orderId = $conn->insert_id;

        // Insert order items
        foreach ($orderItems as $item) {
            $menuItemStmt = $conn->prepare("SELECT ID FROM menu_item WHERE name = ?");
            $menuItemStmt->bind_param("s", $item['name']);
            $menuItemStmt->execute();
            $menuItemResult = $menuItemStmt->get_result();
            $menuItem = $menuItemResult->fetch_assoc();

            if (!$menuItem) {
                throw new Exception("Menu item not found: " . $item['name']);
            }

            $itemQuery = "INSERT INTO order_items (order_ID, menu_item_ID, quantity, price_at_time) VALUES (?, ?, ?, ?)";
            $itemStmt = $conn->prepare($itemQuery);
            $itemStmt->bind_param("iiid", $orderId, $menuItem['ID'], $item['quantity'], $item['price']);
            if (!$itemStmt->execute()) {
                throw new Exception("Failed to insert order item: " . $itemStmt->error);
            }
        }

        $conn->commit();
        redirectWithMessage("Payment successful! Your order has been placed.", true);

    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }

} catch (Exception $e) {
    redirectWithMessage("There was an issue processing your payment: " . $e->getMessage(), false);
}
