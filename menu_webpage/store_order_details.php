<?php
session_start();
header('Content-Type: application/json');

try {
    // Get JSON data if content type is application/json
    $contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';
    
    if (stripos($contentType, 'application/json') !== false) {
        $_POST = json_decode(file_get_contents('php://input'), true);
    }
    
    // Get order details from POST request
    $orderDetails = [
        'customer_name' => $_POST['customer-name'],
        'contact_number' => $_POST['contact-number'],
        'email' => $_POST['email'],
        'address' => $_POST['address'] ?? '',
        'order_type' => $_POST['order-type'],
        'order_items' => is_string($_POST['order_items']) ? json_decode($_POST['order_items'], true) : $_POST['order_items'],
        'total' => floatval($_POST['total'] ?? $_POST['payable_amount']),
        'service_fee' => floatval($_POST['total'] ?? $_POST['payable_amount']) * 0.05,
        'payment_method' => $_POST['payment_method'] ?? 'cash',
        'payment_id' => $_POST['payment_id'] ?? null
    ];

    // Validate order items
    if (!is_array($orderDetails['order_items'])) {
        throw new Exception('Invalid order items format');
    }

    // Store in session
    $_SESSION['order_details'] = $orderDetails;

    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Order details stored successfully'
    ]);

} catch (Exception $e) {
    // Return error response
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}