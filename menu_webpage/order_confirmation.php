<?php
// menu_webpage/order_confirmation.php
include 'login/connect.php';
session_start();




// Check if the user is logged in
if (!isset($_SESSION['email'])) {
    header("Location: login/index.php");
    exit();
}

// Retrieve any messages from URL parameters
$message = isset($_GET['message']) ? htmlspecialchars($_GET['message']) : '';
$success = isset($_GET['success']) ? $_GET['success'] : '1';

// Get the latest order for this customer
$stmt = $conn->prepare("
    SELECT o.*, c.firstName, c.lastName 
    FROM `order` o 
    JOIN customer c ON o.customer_ID = c.ID 
    WHERE c.email = ? 
    ORDER BY o.date_ordered DESC 
    LIMIT 1
");

$stmt->bind_param('s', $_SESSION['email']);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();

if (!$order) {
    header("Location: menu.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Existing head content -->
    <meta charset="UTF-8">
    <title>Order Confirmation - Cara's Food Haven</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            font-family: 'Open Sans', sans-serif;
            background-image: radial-gradient(circle, rgb(226, 226, 226), rgb(242, 242, 116), rgb(234, 234, 96));
            margin: 0;
            padding: 20px;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .confirmation-container {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 600px;
            text-align: center;
        }

        .success-icon {
            color: #28a745;
            font-size: 64px;
            margin-bottom: 20px;
        }

        .failed-icon {
            color: #c82333;
            font-size: 64px;
            margin-bottom: 20px;
        }

        h1 {
            color: #333;
            margin-bottom: 20px;
        }

        .order-details {
            text-align: left;
            margin: 20px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
        }

        .order-details p {
            margin: 10px 0;
            color: #666;
        }

        .back-button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
            transition: background-color 0.3s;
        }

        .back-button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="confirmation-container">
        <?php if ($success === '1'): ?>
            <i class="fas fa-check-circle success-icon"></i>
            <h1>Order Confirmed!</h1>
            <p><?php echo $message ? $message : "Thank you for your order, " . htmlspecialchars($order['firstName'] . ' ' . $order['lastName']) . "!"; ?></p>
        <?php else: ?>
            <i class="fas fa-times-circle failed-icon"></i>
            <h1>Order Failed</h1>
            <p><?php echo $message ? $message : "There was an issue processing your order. Please try again."; ?></p>
        <?php endif; ?>
        
        <div class="order-details">
            <p><strong>Order ID:</strong> #<?php echo $order['ID']; ?></p>
            <p><strong>Order Type:</strong> <?php echo htmlspecialchars($order['order_type']); ?></p>
            <p><strong>Total Amount:</strong> ₱<?php echo number_format($order['total_price'], 2); ?></p>
            <p><strong>Payment ID:</strong> <?php echo htmlspecialchars($order['payment_id']); ?></p>
            <p><strong>Order Status:</strong> <?php echo htmlspecialchars($order['order_status']); ?></p>
            <p><strong>Date Ordered:</strong> <?php echo date('F j, Y g:i A', strtotime($order['date_ordered'])); ?></p>
        </div>

        <a href="menu.php" class="back-button">
            <i class="fas fa-arrow-left"></i> Back to Menu
        </a>
    </div>
</body>
</html>