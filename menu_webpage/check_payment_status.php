<?php
require_once __DIR__ . '/vendor/autoload.php';
session_start();

header('Content-Type: application/json');

try {
    // Load environment variables
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();

    if (!isset($_SESSION['paymongo_link_id'])) {
        throw new Exception("No payment session found");
    }

    $client = new GuzzleHttp\Client();
    $paymongoSecretKey = $_ENV['PAYMONGO_SECRET_KEY'] 
        ?? getenv('PAYMONGO_SECRET_KEY')
        ?? (defined('PAYMONGO_SECRET_KEY') ? PAYMONGO_SECRET_KEY : null);

    if (!$paymongoSecretKey) {
        throw new Exception("PayMongo secret key not found");
    }

    // Check payment status
    $response = $client->request('GET', 'https://api.paymongo.com/v1/links/' . $_SESSION['paymongo_link_id'], [
        'headers' => [
            'Accept' => 'application/json',
            'Authorization' => 'Basic ' . base64_encode($paymongoSecretKey . ':')
        ]
    ]);

    $responseBody = json_decode($response->getBody(), true);
    $status = $responseBody['data']['attributes']['status'] ?? null;

    if ($status === 'paid') {
        // Payment successful
        // Process the order here
        include 'login/connect.php';
        
        // Update order status in database
        // Add your order processing logic here
        
        echo json_encode([
            'success' => true,
            'message' => 'Payment successful'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Payment not completed'
        ]);
    }

} catch (Exception $e) {
    error_log("Payment Status Check Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error checking payment status'
    ]);
}
?>