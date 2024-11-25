<?php
include 'login/connect.php';
session_start();

// Retrieve order details from session
$orderDetails = isset($_SESSION['order_details']) ? $_SESSION['order_details'] : null;

if (!$orderDetails) {
    header("Location: menu.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - Cara's Food Haven</title>
    <script src="https://www.paypalobjects.com/api/checkout.js"></script>
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

        .payment-container {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 600px;
        }

        .payment-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .payment-header h1 {
            color: #333;
            font-size: 28px;
            margin-bottom: 10px;
        }

        .order-summary {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }

        .order-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 20px;
        }

        .order-details dt {
            color: #666;
            font-weight: 600;
        }

        .order-details dd {
            color: #333;
            font-weight: 700;
            margin: 0;
            text-align: right;
        }

        .payment-methods {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .gcash-button {
            background-color: #007EE5;
            color: white;
            border: none;
            padding: 15px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .gcash-button:hover {
            background-color: #0069c2;
            transform: translateY(-2px);
        }

        .back-button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
            transition: background-color 0.3s;
        }

        .back-button:hover {
            background-color: #5a6268;
        }
    </style>
</head>
<body>
    <div class="payment-container">
        <div class="payment-header">
            <h1>Payment Details</h1>
            <p>Complete your order by selecting a payment method</p>
        </div>

        <div class="order-summary">
            <div class="order-details">
                <dt>Order Total:</dt>
                <dd>₱ <?php echo number_format($orderDetails['total'], 2); ?></dd>
                <dt>Service Fee:</dt>
                <dd>₱ <?php echo number_format($orderDetails['service_fee'], 2); ?></dd>
                <dt>Total Amount:</dt>
                <dd>₱ <?php echo number_format($orderDetails['total'] + $orderDetails['service_fee'], 2); ?></dd>
            </div>
        </div>

        <div class="payment-methods">
            <button type="button" class="gcash-button">
                <i class="fas fa-mobile-alt"></i> Pay with GCash
            </button>
            <div id="paypal-button"></div>
        </div>

        <a href="menu.php" class="back-button">
            <i class="fas fa-arrow-left"></i> Back to Order
        </a>
    </div>

    <script>
        paypal.Button.render({
            env: 'sandbox',
            client: {
                sandbox: 'AZCUVkTFU1Cj9NZ7Bn2nhePKuZWI0kmaZAuI_wV_8OYLRLOo-3C3Le4bRoEpBTwS1sYBhIHjVQrjLQEf'
            },
            commit: true,
            style: {
                layout: 'vertical',
                color: 'blue',
                shape: 'rect',
                label: 'paypal'
            },
            payment: function(data, actions) {
                return actions.payment.create({
                    transactions: [{
                        amount: {
                            total: <?php echo $orderDetails['total'] + $orderDetails['service_fee']; ?>,
                            currency: 'PHP'
                        }
                    }]
                });
            },
            onAuthorize: function(data, actions) {
                return actions.payment.execute().then(function(payment) {
                    // Handle successful payment
                    processPayment('paypal', data.paymentID);
                });
            },
            onError: function(err) {
                console.error('PayPal Error:', err);
                alert('Payment Error occurred. Please try again.');
            }
        }, '#paypal-button');

        // Handle GCash payment
        document.querySelector('.gcash-button').addEventListener('click', function() {
            alert('GCash payment integration coming soon!');
        });

        function processPayment(method, paymentId) {
            // Send payment details to server
            fetch('process_payment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    payment_method: method,
                    payment_id: paymentId,
                    order_details: <?php echo json_encode($orderDetails); ?>
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = 'order_confirmation.php';
                } else {
                    alert('Payment processing failed. Please try again.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while processing your payment.');
            });
        }
    </script>
</body>
</html>