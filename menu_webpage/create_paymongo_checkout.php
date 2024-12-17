<?php
//create_paymongo_checkout.php
session_start();
require_once __DIR__ . '/vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

header('Content-Type: application/json');

try {
    if (!isset($_SESSION['customer_id'])) {
        throw new Exception('User not authenticated.');
    }

    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON data: ' . json_last_error_msg());
    }

    if (!$data) {
        throw new Exception('Invalid input data');
    }

    $requiredFields = ['order_type', 'customer_name', 'contact_number', 'email', 'order_items', 'total_amount'];
    foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            throw new Exception("Missing required field: {$field}");
        }
    }

    $orderType = $data['order_type'];
    $customerName = $data['customer_name'];
    $contactNumber = $data['contact_number'];
    $email = $data['email'];
    $address = $data['address'] ?? '';
    $orderItems = $data['order_items'];
    $totalAmount = floatval($data['total_amount']);

    if (!is_array($orderItems) || empty($orderItems)) {
        throw new Exception('Order items must be a non-empty array');
    }

    foreach ($orderItems as $index => $item) {
        $requiredItemFields = ['name', 'price', 'quantity'];
        foreach ($requiredItemFields as $field) {
            if (!isset($item[$field])) {
                throw new Exception("Order item at index {$index} is missing required field: {$field}");
            }
        }

        if (!is_numeric($item['price']) || $item['price'] <= 0) {
            throw new Exception("Invalid price for item: {$item['name']}");
        }
        if (!is_numeric($item['quantity']) || $item['quantity'] <= 0) {
            throw new Exception("Invalid quantity for item: {$item['name']}");
        }
    }

    // Convert amount to centavos
    $amountInCentavos = intval(round($totalAmount * 100));
    if ($amountInCentavos < 2000) { // Minimum 20 PHP
        throw new Exception('The total amount cannot be less than 20 PHP.');
    }

    $metadata = [
        'order_type' => $orderType,
        'customer_name' => $customerName,
        'contact_number' => $contactNumber,
        'email' => $email,
        'address' => $address,
        'order_items' => json_encode($orderItems),
        'customer_id' => $_SESSION['customer_id'],
        'total_amount' => $totalAmount
    ];

    $client = new Client();
    $paymongoSecretKey = 'sk_test_AkA74hYDrtPYk5ZA8BbuNbxZ';

    // Use a fixed URL for success and cancel without placeholders
    $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://") . $_SERVER['HTTP_HOST'];
    $successUrl = $baseUrl . '/carasfoodhaven/menu_webpage/paymongo_success.php';
    $cancelUrl = $baseUrl . '/carasfoodhaven/menu_webpage/menu.php';

    $response = $client->request('POST', 'https://api.paymongo.com/v1/checkout_sessions', [
        'headers' => [
            'Content-Type' => 'application/json',
            'Authorization' => 'Basic ' . base64_encode($paymongoSecretKey . ':')
        ],
        'json' => [
            'data' => [
                'attributes' => [
                    'cancel_url' => $cancelUrl,
                    'success_url' => $successUrl,
                    'billing' => [
                        'name' => $customerName,
                        'email' => $email,
                        'phone' => $contactNumber
                    ],
                    'line_items' => array_map(function($item) {
                        return [
                            'amount' => intval(round($item['price'] * 100)),
                            'currency' => 'PHP',
                            'name' => $item['name'],
                            'quantity' => intval($item['quantity'])
                        ];
                    }, $orderItems),
                    'payment_method_types' => ['card', 'paymaya'],
                    'send_email_receipt' => false,
                    'description' => 'Order at Cara\'s Food Haven',
                    'metadata' => $metadata
                ]
            ]
        ]
    ]);

    $responseBody = json_decode($response->getBody(), true);

    if (!isset($responseBody['data']['id'])) {
        throw new Exception('Session ID not returned by PayMongo.');
    }

    if (!isset($responseBody['data']['attributes']['checkout_url'])) {
        throw new Exception('Checkout URL not returned by PayMongo.');
    }

    // Store the session_id in the session before redirecting the user
    $_SESSION['paymongo_session_id'] = $responseBody['data']['id'];

    echo json_encode([
        'success' => true,
        'checkout_url' => $responseBody['data']['attributes']['checkout_url'],
        'session_id' => $responseBody['data']['id']
    ]);

} catch (RequestException $e) {
    $errorResponse = $e->hasResponse()
        ? json_decode($e->getResponse()->getBody(), true)
        : null;

    $errorMessage = isset($errorResponse['errors'][0]['detail'])
        ? $errorResponse['errors'][0]['detail']
        : $e->getMessage();

    echo json_encode([
        'success' => false,
        'message' => 'PayMongo API Error: ' . $errorMessage
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
