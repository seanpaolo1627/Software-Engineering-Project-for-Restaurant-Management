<?php
// process_order.php
require_once __DIR__ . '/vendor/autoload.php';
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

include 'login/connect.php';
session_start();

header('Content-Type: application/json');

try {
    if (!isset($_SESSION['customer_id'])) {
        throw new Exception("Customer ID not found in session. User may not be logged in.");
    }

    $orderType = $_POST['order-type'];
    $customerName = $_POST['customer-name'];
    $contactNumber = $_POST['contact-number'];
    $email = $_POST['email'];
    $paymentId = $_POST['payment_id'] ?? null;
    $orderItems = json_decode($_POST['order_items'], true);

    $customerId = $_SESSION['customer_id'];

    $conn->begin_transaction();

    $totalAmount = array_sum(array_column($orderItems, 'subtotal'));

    // No address insertion here
    $orderQuery = "INSERT INTO `order` (order_type, order_status, total_price, payment_id, customer_ID) 
                   VALUES (?, 'PENDING', ?, ?, ?)";
    $stmt = $conn->prepare($orderQuery);
    if (!$stmt) {
        throw new Exception("Prepare statement failed: " . $conn->error);
    }

    $stmt->bind_param("sdsi", $orderType, $totalAmount, $paymentId, $customerId);

    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    $orderId = $conn->insert_id;

    $itemQuery = "INSERT INTO order_items (order_ID, menu_item_ID, quantity, price_at_time) 
                  VALUES (?, ?, ?, ?)";
    $itemStmt = $conn->prepare($itemQuery);
    if (!$itemStmt) {
        throw new Exception("Prepare statement for order items failed: " . $conn->error);
    }

    foreach ($orderItems as $item) {
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
            $itemStmt->bind_param("iiid", $orderId, $menuItem['ID'], $item['quantity'], $item['price']);
            if (!$itemStmt->execute()) {
                throw new Exception("Execute failed for order item: " . $itemStmt->error);
            }
        } else {
            throw new Exception("Menu item not found: " . $item['name']);
        }

        $menuItemStmt->close();
    }

    $conn->commit();

    echo json_encode(['success' => true, 'message' => 'Order processed successfully']);

} catch (Exception $e) {
    $conn->rollback();
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while processing your order.']);
}

$conn->close();

?>