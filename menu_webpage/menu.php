<script src="https://www.paypalobjects.com/api/checkout.js"></script>
<?php
// menu.php
require_once __DIR__ . '/vendor/autoload.php';


$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$paypalClientId = $_ENV['PAYPAL_CLIENT_ID'];


include 'login/connect.php'; 

if (isset($_GET['payment_status'])) {
    $status = $_GET['payment_status'];
    if ($status === 'success') {
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                showNotification('Payment successful! Thank you for your order.', 'success');
            });
        </script>";
    } else if ($status === 'failed') {
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                showNotification('Payment failed. Please try again.', 'error');
            });
        </script>";
    }
}

require_once 'login/google-config.php'; 

if (isset($_GET['code'])) {
    try {
        $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    } catch (Exception $e) {
        $_SESSION['error'] = "Error fetching access token: " . $e->getMessage();
        header("Location: login/index.php");
        exit();
    }

    if (isset($token['error'])) {
        $_SESSION['error'] = "Error fetching access token: " . htmlspecialchars($token['error']);
        header("Location: login/index.php");
        exit();
    }

    $client->setAccessToken($token['access_token']);

    $google_oauth = new Google_Service_Oauth2($client);
    try {
        $google_account_info = $google_oauth->userinfo->get();
    } catch (Exception $e) {
        $_SESSION['error'] = "Error fetching user info: " . $e->getMessage();
        header("Location: login/index.php");
        exit();
    }

    $email = strtolower(trim($google_account_info->email));
    $firstName = trim($google_account_info->givenName);
    $lastName = trim($google_account_info->familyName);

    $checkUserQuery = "SELECT * FROM customer WHERE email = ?";
    $stmt = $conn->prepare($checkUserQuery);
    if(!$stmt){
        $_SESSION['error'] = "Database error: " . $conn->error;
        header("Location: login/index.php");
        exit();
    }
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows > 0){
        $_SESSION['email'] = $email;
    } else {
        $defaultContactNumber = 'N/A';
        $defaultAddress = 'N/A';
        $defaultPassword = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT); 

        $insertUserQuery = "INSERT INTO customer (firstName, lastName, email, password, contactNumber, address)
                            VALUES (?, ?, ?, ?, ?, ?)";
        $insertStmt = $conn->prepare($insertUserQuery);
        if(!$insertStmt){
            $_SESSION['error'] = "Database error: " . $conn->error;
            header("Location: login/index.php");
            exit();
        }
        $insertStmt->bind_param('ssssss', $firstName, $lastName, $email, $defaultPassword, $defaultContactNumber, $defaultAddress);
        if($insertStmt->execute()){
            $_SESSION['email'] = $email;
        } else {
            $_SESSION['error'] = "Error creating user: " . $insertStmt->error;
            header("Location: login/index.php");
            exit();
        }
        $insertStmt->close();
    }
    $stmt->close();

    header("Location: menu.php");
    exit();
}

if (!isset($_SESSION['email'])) {
    header("Location: login/index.php");
    exit();
}

$email = strtolower(trim($_SESSION['email']));
$userName = "Guest";
$customerAddress = "";

$query = $conn->prepare("SELECT ID, firstName, lastName, address FROM customer WHERE email = ?");
if ($query) {
    $query->bind_param('s', $email);
    $query->execute();
    $result = $query->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $userName = htmlspecialchars($row['firstName'] . ' ' . $row['lastName']);
        $_SESSION['customer_id'] = $row['ID']; 
        $customerAddress = htmlspecialchars($row['address']);
    }
    $query->close();
} else {
    $_SESSION['error'] = "Database error: " . $conn->error;
    header("Location: login/index.php");
    exit();
}

// Fetch menu categories
$menuCategories = [];
$categoryQuery = "SELECT ID, name FROM menu_category";
$categoryResult = $conn->query($categoryQuery);
if ($categoryResult) {
    while ($categoryRow = $categoryResult->fetch_assoc()) {
        $menuCategories[] = $categoryRow;
    }
}

// Fetch menu items
$menuItems = [];
$itemQuery = "SELECT mi.ID, mi.name, mi.image, mi.price, mi.description, mi.menu_category
              FROM menu_item mi
              WHERE mi.menu_item_status = 'Available'";
$itemResult = $conn->query($itemQuery);

if ($itemResult) {
    while ($itemRow = $itemResult->fetch_assoc()) {
        if ($itemRow['image']) {
            $itemRow['image'] = 'data:image/jpeg;base64,' . base64_encode($itemRow['image']);
        } else {
            $itemRow['image'] = '../img_placeholder.png';
        }
        $menuItems[] = $itemRow;
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="menu.css">
    <script src="menu.js"></script>
    <title> Menu Page | Cara's Food Haven </title>
    <link rel="shortcut icon" type="x-icon" href="../RestaurantLogo-Cut.png">
</head>

<body>
<div id="notification-container" style="position: fixed; top: 20px; right: 20px; z-index: 9999;"></div>    

<nav>
  <span> <img src="../RestaurantLogo-Cut.png" class="RestaurantLogo_Nav"> </span>
  <div class="search-bar">
      <input type="text" placeholder="What are you looking for?">
      <button type="submit">Search</button>
  </div>
  <ul>
      <li><a href="#"><i class="fas fa-home"></i> <span>Home</span></a></li>
      <li><a href="#" id="current-page"><i class="fas fa-utensils"></i> <span>Menu</span></a></li>
      <li><a href="../my-orders_webpage/my-orders.php"><i class="fas fa-receipt"></i> <span>My Orders</span></a></li>
      <li><a href="#"><i class="fas fa-info-circle"></i> <span>About Us</span></a></li>
  </ul>
  <li class="account-dropdown">
       <div class="account-info">
         <img id="account-img" src="../account-img-placeholder.jpg" alt="Avatar">
         <div>
           <span class="account-name"><?php echo htmlspecialchars($userName); ?></span>
           <span class="account-role">User</span>
         </div>
         <i class="fas fa-caret-down" id="dropdown-arrow"></i>
   </div>
   <div class="dropdown-content">
      <a href="login/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
      <a href="#" id="account-settings-btn"><i class="fas fa-user-cog"></i> Account Settings</a>
   </div>
  </li>
</ul>
</nav>

<div id="account-settings-modal" class="modal">
  <div class="modal-content">
      <span class="close-button" id="close-account-settings">&times;</span>
      <h2>Account Settings</h2>
      <form id="account-settings-form" method="post" action="update_account.php">
          <div class="form-group">
              <label for="firstName">First Name:</label>
              <input type="text" id="firstName" name="firstName" required>
          </div>
          <div class="form-group">
              <label for="lastName">Last Name:</label>
              <input type="text" id="lastName" name="lastName" required>
          </div>
          <div class="form-group">
              <label for="contactNumber">Contact Number:</label>
              <input type="text" id="contactNumber" name="contactNumber" required>
          </div>
          <div class="form-group">
              <label for="address">Address:</label>
              <input type="text" id="address" name="address" required>
          </div>
          <button type="submit" class="btn">Save Changes</button>
      </form>
  </div>
</div>

<div class="verticalmenu_container">
  <div class="verticalmenu-section">
    <label class="verticalmenu-label">MENU CATEGORIES</label>
    <?php foreach ($menuCategories as $category): ?>
        <button class="verticalmenu-btn" id="category-<?php echo htmlspecialchars($category['ID']); ?>">
          <i class="fas fa-utensils"></i> <?php echo htmlspecialchars($category['name']); ?>
          <span class="notification-badge" style="display: none;">0</span>
        </button>
    <?php endforeach; ?>
  </div>
</div>

<div id="confirm-order-modal" class="modal">
  <div id="confirm-order-content" class="modal-content">

      <form id="customer-form">
        <h2 id="confirm-order-header">CUSTOMER FORM</h2>
        <br>
        <div class="form-group">
            <label for="order-type">Order Type:</label>
            <select id="order-type" name="order-type" required>
              <option value="" disabled selected>--- Select the Order Type ---</option>
              <option value="DINE_IN">DINE IN</option>
              <option value="TAKEOUT">TAKEOUT</option>
              <option value="DELIVERY">DELIVERY</option>
            </select>
        </div>

        <div class="form-group">
            <label for="customer-name">Customer Name:<span style="color:red;">*</span></label>
            <input type="text" id="customer-name" name="customer-name" placeholder="Enter your name" required>
        </div>

        <div class="form-group">
            <label for="contact-number">Contact Number:<span style="color:red;">*</span></label>
            <input type="tel" id="contact-number" name="contact-number" placeholder="Enter your contact number" required>
        </div>

        <!-- Address will only show if DELIVERY and will be pre-filled from customer table -->
        <div class="form-group" id="delivery-address-group" style="display: none;">
            <label for="delivery-address">Address:<span style="color:red;">*</span></label>
            <input type="text" id="delivery-address" name="delivery-address" readonly>
        </div>

        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" placeholder="Enter your email">
        </div>
        <br>

        <div id="payment-section" style="display: none;">
          <fieldset id="pay-field">
              <h1 class="text-center text-info" id="payable_amount">0.00</h1>
              <hr class="border-light">
              <div class="form-group">
                  <dl class="row">
                      <dt class='text-info col-4'>Amount to Pay</dt>
                      <dd class="col-8 text-right" id="pay_amount"></dd>
                      <dt class='text-info col-4'>Service Fee</dt>
                      <dd class="col-8 text-right" id="fee"></dd>
                      <input type="hidden" name="fee" value='0'>
                      <input type="hidden" name="payable_amount" value='0'>
                      <input type="hidden" name="payment_code" value=''>
                  </dl>
              </div>
              <div class="payment-method-container">
                  <button type="button" class="payment-button paymongo-button">
                      <i class="fas fa-mobile-alt"></i> Checkout
                  </button>
                  <div id="paypal-button" class="paypal-container"></div>
              </div>
          </fieldset>
        </div>
        <button type="button" id="confirm-order-btn">Confirm Details</button>
      </form>
  </div>
</div>

<div id="menu-item-modal" class="modal">
  <div id="menu-item-content" class="modal-content">
      <div class="image-placeholder" style="text-align: center;">
          <img src="../img_placeholder.png" alt="Menu Item Image" id="modal-menu-item-img">
      </div>
      <br>
      <h2 id="menu-item-header" style="text-align: center;">MENU ITEM NAME</h2>
      <br>
      <p id="menu-item-price" style="text-align: center; font-weight: bold;">PRICE</p>
      <br><br><br>
      <p id="menu-item-desc" style="text-align: center;">MENU ITEM DESCRIPTION TEMPLATE</p>
  </div>
</div>

<div id="notification-container"></div>

<div class="container">
  <div class="container-left-side">
    <div class="header_wrapper">
      <div class="header_container">
        <h2 id="menu-category-header">Menu</h2>
      </div>
    </div>
    <div class="card-container" id="card-container">
      <?php foreach ($menuItems as $item): ?>
        <div class="card category-<?php echo htmlspecialchars($item['menu_category']); ?>" data-category="category-<?php echo htmlspecialchars($item['menu_category']); ?>" id="item-<?php echo htmlspecialchars($item['ID']); ?>">
          <div class="card-img-container">
            <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="Menu Item Image" onclick="openMenuItemModal(<?php echo htmlspecialchars($item['ID']); ?>)">
          </div>
          <h1><?php echo htmlspecialchars($item['name']); ?></h1>
          <p>Php <?php echo htmlspecialchars(number_format($item['price'], 2)); ?></p>
          <div class="quantity-controls">
            <button class="quantity-button" onclick="decrementQuantity(this)">-</button>
            <input type="text" class="quantity-input" placeholder="Qty" value="0" disabled>
            <button class="quantity-button" onclick="incrementQuantity(this)">+</button>
          </div>
          <input type="hidden" class="item-description" value="<?php echo htmlspecialchars($item['description']); ?>">
          <input type="hidden" class="item-image" value="<?php echo htmlspecialchars($item['image']); ?>">
          <input type="hidden" class="item-price" value="<?php echo htmlspecialchars($item['price']); ?>">
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="container-right-side">
    <h2 class="right-side-header">ORDER SUMMARY</h2>
    <table class="order-summary-table">
      <thead>
          <tr>
              <th>Menu Item</th>
              <th>Price</th>
              <th>Qty</th>
              <th>Subtotal</th>
          </tr>
      </thead>
      <tbody>
        <tr class="empty-message-row">
            <td colspan="4" class="empty-message">THIS IS WHERE YOUR ORDERS ARE DISPLAYED.</td>
        </tr>
        <tr class="total-row">
            <td colspan="3" class="total-label">Total</td>
            <td class="total-amount">Php 0</td>
        </tr>
        <tr class="discount-row">
            <td colspan="3" class="discount-label">Discount Code</td>
            <td><input type="text" placeholder="Enter code here"></td>
        </tr>
      </tbody>
    </table>
    <br>
    <button class="confirm-order-button">Confirm Order</button>
    <br><br><br>
  </div>
</div>

<script>
// Store address from PHP variable into a JS variable
const customerAddressFromDB = "<?php echo $customerAddress; ?>";

document.addEventListener('DOMContentLoaded', function() {
    const accountSettingsBtn = document.getElementById('account-settings-btn');
    const accountSettingsModal = document.getElementById('account-settings-modal');
    const closeAccountSettings = document.getElementById('close-account-settings');

    accountSettingsBtn.addEventListener('click', function(e) {
        e.preventDefault();
        fetchAccountDetails();
        accountSettingsModal.style.display = 'block';
    });

    closeAccountSettings.addEventListener('click', function() {
        accountSettingsModal.style.display = 'none';
    });

    window.addEventListener('click', function(event) {
        if (event.target == accountSettingsModal) {
            accountSettingsModal.style.display = 'none';
        }
    });

    function fetchAccountDetails() {
        fetch('get_account_details.php')
            .then(response => response.json())
            .then(data => {
                if(data.success){
                    document.getElementById('firstName').value = data.data.firstName;
                    document.getElementById('lastName').value = data.data.lastName;
                    document.getElementById('contactNumber').value = data.data.contactNumber;
                    document.getElementById('address').value = data.data.address;
                } else {
                    alert('Failed to fetch account details.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while fetching account details.');
            });
    }

    document.getElementById('account-settings-form').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);

        fetch('update_account.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if(data.success){
                alert('Account details updated successfully.');
                accountSettingsModal.style.display = 'none';
                location.reload(); 
            } else {
                alert('Failed to update account details: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while updating account details.');
        });
    });
});

// Show confirm order modal
document.querySelector('.confirm-order-button').addEventListener('click', function() {
  const orderSummaryTable = document.querySelector('.order-summary-table tbody');
  const existingRows = orderSummaryTable.querySelectorAll('tr:not(.total-row):not(.discount-row):not(.empty-message-row)');
  if (existingRows.length === 0) {
    alert('Please add items to your order before confirming.');
    return;
  }
  document.getElementById('confirm-order-modal').classList.add('show');
});

// Close modals
window.addEventListener('click', function(event) {
  const confirmOrderModal = document.getElementById('confirm-order-modal');
  if (event.target === confirmOrderModal) {
    confirmOrderModal.classList.remove('show');
  }

  const menuItemModal = document.getElementById('menu-item-modal');
  if (event.target === menuItemModal) {
    menuItemModal.classList.remove('show');
  }
});

// Show/hide address field based on order type
document.getElementById('order-type').addEventListener('change', function () {
    const orderType = this.value;
    const addressGroup = document.getElementById('delivery-address-group');

    if (orderType === 'DELIVERY') {
        addressGroup.style.display = 'block';
        document.getElementById('delivery-address').value = customerAddressFromDB;
    } else {
        addressGroup.style.display = 'none';
    }
});

// Confirm Details button logic
document.getElementById('confirm-order-btn').addEventListener('click', function() {
    const orderType = document.getElementById('order-type').value;
    const customerName = document.getElementById('customer-name').value.trim();
    const contactNumber = document.getElementById('contact-number').value.trim();
    const email = document.getElementById('email').value.trim();
    const deliveryAddressGroup = document.getElementById('delivery-address-group');
    let addressValid = true;

    if (orderType === 'DELIVERY') {
        // Ensure address is available
        const deliveryAddress = document.getElementById('delivery-address').value.trim();
        if (!deliveryAddress) addressValid = false;
    }

    if (!orderType || !customerName || !contactNumber || (orderType === 'DELIVERY' && !addressValid)) {
        alert('Please fill in all required fields.');
        return;
    }

    // If all required fields are present, show payment section
    showPaymentSection();
});

function showPaymentSection() {
    const paymentSection = document.getElementById('payment-section');
    const confirmButton = document.getElementById('confirm-order-btn');

    const totalAmount = parseFloat(document.querySelector('.total-amount').textContent.replace('Php ', ''));
    const serviceFee = totalAmount * 0.05;
    const payableAmount = totalAmount + serviceFee;

    document.getElementById('pay_amount').textContent = `Php ${totalAmount.toFixed(2)}`;
    document.getElementById('fee').textContent = `Php ${serviceFee.toFixed(2)}`;
    document.getElementById('payable_amount').textContent = `Php ${payableAmount.toFixed(2)}`;
    document.querySelector('[name="fee"]').value = serviceFee;
    document.querySelector('[name="payable_amount"]').value = payableAmount;

    paymentSection.style.display = 'block';
    confirmButton.style.display = 'none';

    initializePayPalButton(payableAmount);
}

function initializePayPalButton(amount) {
    const paypalContainer = document.getElementById('paypal-button');
    paypalContainer.innerHTML = '';
    
    paypal.Button.render({
    env: 'sandbox',
    client: {
        sandbox: '<?php echo $paypalClientId; ?>'
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
                        total: amount.toFixed(2),
                        currency: 'PHP'
                    }
                }]
            });
        },
        onAuthorize: function(data, actions) {
            return actions.payment.execute().then(function() {
                const formData = new FormData(document.getElementById('customer-form'));
                formData.append('payment_method', 'paypal');
                formData.append('payment_id', data.paymentID);
                formData.append('order_items', JSON.stringify(collectOrderItems()));
                formData.append('total', (amount - parseFloat(document.querySelector('[name="fee"]').value)).toFixed(2));
                formData.append('service_fee', document.querySelector('[name="fee"]').value);

                return fetch('process_order.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        throw new Error(data.message || 'Order processing failed');
                    }
                    showNotification('Payment successful! Redirecting...', 'success');
                    setTimeout(() => {
                        window.location.href = 'order_confirmation.php';
                    }, 2000);
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification(error.message || 'An error occurred while processing your payment.', 'error');
                });
            });
        },
        onError: function(err) {
            console.error('PayPal Error:', err);
            showNotification('Payment Error occurred. Please try again.', 'error');
        }
    }, '#paypal-button');
}

document.addEventListener('DOMContentLoaded', function() {
    const paymongoButton = document.querySelector('.paymongo-button');
    if (paymongoButton) {
        paymongoButton.addEventListener('click', function(e) {
            e.preventDefault();
            const totalAmount = parseFloat(document.querySelector('.total-amount').textContent.replace('Php ', ''));
            const serviceFee = totalAmount * 0.05;
            const payableAmount = totalAmount + serviceFee;

            const orderType = document.getElementById('order-type').value;
            const customerName = document.getElementById('customer-name').value.trim();
            const contactNumber = document.getElementById('contact-number').value.trim();
            const email = document.getElementById('email').value.trim();

            if (!orderType || !customerName || !contactNumber || 
                (orderType === 'DELIVERY' && !document.getElementById('delivery-address').value.trim())) {
                alert('Please fill in all required fields.');
                return;
            }

            const orderItems = collectOrderItems();
            const data = {
                order_type: orderType,
                customer_name: customerName,
                contact_number: contactNumber,
                email: email,
                // No longer sending address as we are not modifying the order table
                payment_method: 'paymongo',
                total_amount: totalAmount,
                service_fee: serviceFee,
                order_items: orderItems
            };

            fetch('create_paymongo_checkout.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            })
            .then(response => {
                if (!response.ok) throw new Error('Network response was not ok');
                return response.json();
            })
            .then(result => {
                if (result.success && result.checkout_url) {
                    if (result.session_id) {
                        localStorage.setItem('paymongo_session_id', result.session_id);
                    }
                    const checkoutUrl = new URL(result.checkout_url);
                    const sessionId = checkoutUrl.searchParams.get('id');
                    if (sessionId) {
                        localStorage.setItem('paymongo_session_id', sessionId);
                    }
                    window.location.href = result.checkout_url;
                } else {
                    throw new Error(result.message || 'Failed to create checkout session');
                }
            })
            .catch(error => {
                console.error('PayMongo Error:', error);
                alert('Payment Error: ' + error.message);
            });
        });
    }
});

function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.innerHTML = `
        <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
        ${message}
    `;
    document.getElementById('notification-container').appendChild(notification);
    setTimeout(() => {
        notification.remove();
    }, 5000);
}

function collectOrderItems() {
    const items = [];
    const rows = document.querySelectorAll('.order-summary-table tbody tr:not(.total-row):not(.discount-row):not(.empty-message-row)');
    rows.forEach(row => {
        if (row.cells.length >= 4) {
            const quantity = parseInt(row.cells[2].textContent);
            if (quantity > 0) {
                items.push({
                    name: row.cells[0].textContent.trim(),
                    price: parseFloat(row.cells[1].textContent.replace('Php ', '').trim()),
                    quantity: quantity,
                    subtotal: parseFloat(row.cells[3].textContent.replace('Php ', '').trim())
                });
            }
        }
    });
    return items;
}

const accountDropdown = document.querySelector('.account-dropdown');
const dropdownArrow = document.getElementById('dropdown-arrow');
accountDropdown.addEventListener('click', function(event) {
  event.stopPropagation();
  accountDropdown.classList.toggle('active'); 
  if (accountDropdown.classList.contains('active')) {
    dropdownArrow.classList.remove('fa-caret-down');
    dropdownArrow.classList.add('fa-caret-up');
  } else {
    dropdownArrow.classList.remove('fa-caret-up');
    dropdownArrow.classList.add('fa-caret-down');
  }
});

document.addEventListener("DOMContentLoaded", function() {
    const verticalMenuButtons = document.querySelectorAll(".verticalmenu-btn");
    const cards = document.querySelectorAll(".card");
    const categoryHeader = document.getElementById("menu-category-header");
    updateCategoryCounts();

    verticalMenuButtons.forEach(button => {
      button.addEventListener("click", function () {
        const targetCategory = button.id;
        categoryHeader.textContent = button.textContent.trim();
        verticalMenuButtons.forEach(btn => btn.classList.remove("active"));
        button.classList.add("active");

        cards.forEach(card => {
          if (card.getAttribute('data-category') === targetCategory) {
            card.style.display = "block";
          } else {
            card.style.display = "none";
          }
        });
      });
    });

    if (verticalMenuButtons.length > 0) {
      verticalMenuButtons[0].click();
    }

    window.openMenuItemModal = function(itemId) {
      const modal = document.getElementById('menu-item-modal');
      const card = document.getElementById('item-' + itemId);
      if (card) {
        const itemName = card.querySelector('h1').textContent;
        const itemPrice = card.querySelector('.item-price').value;
        const itemDescription = card.querySelector('.item-description').value;
        const itemImageSrc = card.querySelector('.item-image').value;
        document.getElementById('menu-item-header').textContent = itemName;
        document.getElementById('menu-item-price').textContent = 'Php ' + parseFloat(itemPrice).toFixed(2);
        document.getElementById('menu-item-desc').textContent = itemDescription;
        document.getElementById('modal-menu-item-img').src = itemImageSrc;
        modal.classList.add('show');
      }
    };

    function updateCategoryCounts() {
      const categoryCounts = {};
      const cards = document.querySelectorAll('.card');
      cards.forEach(card => {
        const quantityInput = card.querySelector('.quantity-input');
        const quantity = parseInt(quantityInput.value) || 0;
        const category = card.getAttribute('data-category');
        if (!categoryCounts[category]) {
          categoryCounts[category] = 0;
        }
        categoryCounts[category] += quantity;
      });

      const verticalMenuButtons = document.querySelectorAll('.verticalmenu-btn');
      verticalMenuButtons.forEach(button => {
        const category = button.id;
        const count = categoryCounts[category] || 0;
        const badge = button.querySelector('.notification-badge');
        if (count > 0) {
          badge.style.display = 'block';
          badge.textContent = count;
          button.classList.add('has-items');
        } else {
          badge.style.display = 'none';
          badge.textContent = '';
          button.classList.remove('has-items');
        }
      });
    }

    window.incrementQuantity = function(button) {
      const quantityInput = button.parentElement.querySelector('.quantity-input');
      let quantity = parseInt(quantityInput.value) || 0;
      quantity++;
      quantityInput.value = quantity;
      const card = button.closest('.card');
      if (quantity > 0) {
        card.classList.add('selected');
      }
      updateOrderSummary();
      updateCategoryCounts();
    }

    window.decrementQuantity = function(button) {
      const quantityInput = button.parentElement.querySelector('.quantity-input');
      let quantity = parseInt(quantityInput.value) || 0;
      if (quantity > 0) {
        quantity--;
        quantityInput.value = quantity;
        const card = button.closest('.card');
        if (quantity === 0) {
          card.classList.remove('selected');
        }
        updateOrderSummary();
        updateCategoryCounts();
      }
    }

    function updateOrderSummary() {
      const orderSummaryTable = document.querySelector('.order-summary-table tbody');
      const totalRow = orderSummaryTable.querySelector('.total-row');
      const discountRow = orderSummaryTable.querySelector('.discount-row');
      const emptyMessageRow = orderSummaryTable.querySelector('.empty-message-row');
      const existingRows = orderSummaryTable.querySelectorAll('tr:not(.total-row):not(.discount-row):not(.empty-message-row)');
      existingRows.forEach(row => row.remove());

      let totalAmount = 0;
      let hasItems = false;

      const cards = document.querySelectorAll('.card');
      cards.forEach(card => {
        const quantityInput = card.querySelector('.quantity-input');
        let quantity = parseInt(quantityInput.value) || 0;
        if (quantity > 0) {
          hasItems = true;
          const itemName = card.querySelector('h1').textContent;
          const itemPrice = parseFloat(card.querySelector('.item-price').value);
          const subtotal = itemPrice * quantity;
          card.classList.add('selected');
          totalAmount += subtotal;
          const row = document.createElement('tr');
          row.innerHTML = `
            <td>${itemName}</td>
            <td>Php ${itemPrice.toFixed(2)}</td>
            <td>${quantity}</td>
            <td>Php ${subtotal.toFixed(2)}</td>
          `;
          orderSummaryTable.insertBefore(row, totalRow);
        }
      });

      totalRow.querySelector('.total-amount').textContent = 'Php ' + totalAmount.toFixed(2);

      if (hasItems) {
        emptyMessageRow.style.display = 'none';
      } else {
        emptyMessageRow.style.display = '';
      }

      const confirmOrderButton = document.querySelector('.confirm-order-button');
      if (hasItems) {
        confirmOrderButton.style.display = 'block';
      } else {
        confirmOrderButton.style.display = 'none';
      }
    }

    function resetOrder() {
      const cards = document.querySelectorAll('.card');
      cards.forEach(card => {
        const quantityInput = card.querySelector('.quantity-input');
        quantityInput.value = 0;
        card.classList.remove('selected');
      });
      updateOrderSummary();
      updateCategoryCounts();
    }

});


</script>

</body>
</html>
