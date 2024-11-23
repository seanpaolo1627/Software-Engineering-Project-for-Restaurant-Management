<?php
session_start();

// Get order details from POST request
$orderDetails = [
    'customer_name' => $_POST['customer-name'],
    'contact_number' => $_POST['contact-number'],
    'email' => $_POST['email'],
    'address' => $_POST['address'],
    'order_type' => $_POST['order-type'],
    'order_items' => json_decode($_POST['order_items'], true),
    'total' => floatval($_POST['total']),
    'service_fee' => floatval($_POST['total']) * 0.05
];

// Store in session
$_SESSION['order_details'] = $orderDetails;

// Return success response
echo json_encode(['success' => true]);