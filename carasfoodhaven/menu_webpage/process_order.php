<?php
include 'login/connect.php';

header('Content-Type: application/json');

try {
    // Collect POST data
    $orderType = $_POST['order-type'];
    $customerName = $_POST['customer-name'];
    $contactNumber = $_POST['contact-number'];
    $email = $_POST['email'];
    $address = $_POST['address'] ?? '';
    $paymentCode = $_POST['payment_code'] ?? '';
    $orderItems = json_decode($_POST['order_items'], true);
    
    // Start transaction
    $conn->begin_transaction();
    
    // Insert order header
    $orderQuery = "INSERT INTO orders (customer_name, contact_number, email, address, order_type, payment_code, total_amount) 
                  VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($orderQuery);
    
    $totalAmount = array_sum(array_column($orderItems, 'subtotal'));
    
    $stmt->bind_param("ssssssd", 
        $customerName, 
        $contactNumber, 
        $email, 
        $address, 
        $orderType, 
        $paymentCode,
        $totalAmount
    );
    
    $stmt->execute();
    $orderId = $conn->insert_id;
    
    // Insert order items
    $itemQuery = "INSERT INTO order_items (order_id, item_name, quantity, price) VALUES (?, ?, ?, ?)";
    $itemStmt = $conn->prepare($itemQuery);
    
    foreach ($orderItems as $item) {
        $itemStmt->bind_param("isid",
            $orderId,
            $item['name'],
            $item['quantity'],
            $item['price']
        );
        $itemStmt->execute();
    }
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode(['success' => true, 'message' => 'Order processed successfully']);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();