<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include '../menu_webpage/login/connect.php'; // Includes session_start()

// Check if the user is logged in via session
if (!isset($_SESSION['email'])) {
    header("Location: ../menu_webpage/login/index.php"); // Redirect to login page
    exit();
}

$email = strtolower(trim($_SESSION['email'])); // Ensure email is lowercase
$userName = "Guest";

// Fetch user's first and last name from the database
$query = $conn->prepare("SELECT firstName, lastName FROM customer WHERE email = ?");
if ($query) {
    $query->bind_param('s', $email);
    $query->execute();
    $result = $query->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $userName = $row['firstName'] . ' ' . $row['lastName'];
    }
    $query->close();
}

// Function to send JSON response and exit
function sendJsonResponse($data, $statusCode = 200) {
  if (ob_get_length()) {
      ob_clean(); // Clear any existing output
  } else {
      ob_start(); // Start output buffering if not started
  }
  header('Content-Type: application/json');
  http_response_code($statusCode);
  echo json_encode($data);
  exit;
}

// Function to sanitize input data
function sanitizeInput($data, $conn) {
  return htmlspecialchars(trim($data ?? ''), ENT_QUOTES, 'UTF-8');
}   


if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_orders') {
  try {
      $pendingStatusId = 1; // Assuming 'PENDING' has ID 1
      $insertOrderItemsSql = "INSERT INTO order_items (order_ID, menu_item_ID, quantity, price_at_time, status_id) VALUES (?, ?, ?, ?, ?)";
      $stmt = $conn->prepare($insertOrderItemsSql);
      $stmt->bind_param('iiidi', $orderId, $menuItemId, $quantity, $priceAtTime, $pendingStatusId);
      $status = isset($_GET['status']) ? sanitizeInput($_GET['status'], $conn) : 'PENDING';
      $statusCondition = $status === 'ALL' ? '' : 'WHERE o.order_status = ?';
      
      $sql = "SELECT o.*, c.firstName, c.lastName, c.contactNumber, c.email,
      mi.ID as menu_item_id, mi.name as item_name, mi.price, oi.quantity, oi.status_id as menu_item_status
      FROM `order` o
      LEFT JOIN customer c ON o.customer_ID = c.ID
      LEFT JOIN order_items oi ON o.ID = oi.order_ID
      LEFT JOIN menu_item mi ON oi.menu_item_ID = mi.ID
      $statusCondition
      ORDER BY o.date_ordered DESC";
      
      $stmt = $conn->prepare($sql);
      if ($status !== 'ALL') {
          $stmt->bind_param('s', $status);
      }
      $stmt->execute();
      $result = $stmt->get_result();
      
      $orders = [];
      while ($row = $result->fetch_assoc()) {
          if (!isset($orders[$row['ID']])) {
              $orders[$row['ID']] = [
                  'id' => $row['ID'],
                  'status' => $row['order_status'], 
                  'order_status' => $row['order_status'],
                  'order_type' => $row['order_type'],
                  'date_ordered' => $row['date_ordered'],
                  'total_price' => $row['total_price'],
                  'discount_code' => $row['discount_code'],
                  'customerName' => $row['firstName'] && $row['lastName'] 
                      ? $row['firstName'] . ' ' . $row['lastName']
                      : 'Guest Customer',
                  'contactInfo' => [
                      'phone' => $row['contactNumber'] ?? 'N/A',
                      'email' => $row['email'] ?? 'N/A',
                      'address' => $row['address'] ?? 'N/A'
                  ],
                  'menuItems' => []
              ];
          }
          
          if ($row['menu_item_id']) {
              $orders[$row['ID']]['menuItems'][] = [
                  'id' => $row['menu_item_id'],
                  'name' => $row['item_name'],
                  'price' => $row['price'],
                  'quantity' => $row['quantity'],
                  'menu_item_status' => $row['menu_item_status'] ?? 'PENDING'
              ];
          }
      }
      
      sendJsonResponse(['success' => true, 'orders' => array_values($orders)]);
      
  } catch (Exception $e) {
      sendJsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
  }
}

if (isset($_GET['action']) && $_GET['action'] === 'get_order_counts') {
  try {
      $sql = "SELECT order_status, COUNT(*) as count FROM `order` GROUP BY order_status";
      $stmt = $conn->prepare($sql);
      if (!$stmt) {
          throw new Exception('Prepare failed: ' . $conn->error);
      }
      $stmt->execute();
      $result = $stmt->get_result();

      $counts = ['PENDING' => 0, 'PREPARING' => 0, 'READY_FOR_PICKUP' => 0, 'COMPLETE' => 0, 'CANCELED' => 0];
      while ($row = $result->fetch_assoc()) {
          if (array_key_exists($row['order_status'], $counts)) {
              $counts[$row['order_status']] = intval($row['count']);
          }
      }
      $stmt->close();

      sendJsonResponse(['success' => true, 'counts' => $counts]);
  } catch (Exception $e) {
      sendJsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Retrieve and decode JSON input
  $input = json_decode(file_get_contents('php://input'), true);

  if (isset($input['action']) && $input['action'] === 'update_order_status') {
      try {
          $orderId = isset($input['orderId']) ? intval($input['orderId']) : null;
          $newStatus = isset($input['status']) ? strtoupper(trim($input['status'])) : null;

          if (!$orderId || !$newStatus) {
              throw new Exception('Order ID and new status are required.');
          }

          $allowedStatuses = ['PENDING', 'PREPARING', 'READY_FOR_PICKUP', 'COMPLETE', 'CANCELED'];
          if (!in_array($newStatus, $allowedStatuses)) {
              throw new Exception('Invalid order status.');
          }

          // Fetch current status
          $currentStatusQuery = "SELECT order_status FROM `order` WHERE ID = ?";
          $currentStmt = $conn->prepare($currentStatusQuery);
          if (!$currentStmt) {
              throw new Exception('Prepare failed: ' . $conn->error);
          }
          $currentStmt->bind_param('i', $orderId);
          $currentStmt->execute();
          $currentResult = $currentStmt->get_result();
          $currentOrder = $currentResult->fetch_assoc();
          $currentStmt->close();

          if (!$currentOrder) {
              throw new Exception('Order not found.');
          }

          $currentStatus = $currentOrder['order_status'];

          // Define valid transitions
          $validTransitions = [
              'PENDING' => ['PREPARING', 'CANCELED'],
              'PREPARING' => ['READY_FOR_PICKUP', 'CANCELED', 'PENDING'],
              'READY_FOR_PICKUP' => ['COMPLETE', 'CANCELED', 'PREPARING'],
              'COMPLETE' => [],
              'CANCELED' => []
          ];

          if (!isset($validTransitions[$currentStatus]) || !in_array($newStatus, $validTransitions[$currentStatus])) {
              throw new Exception("Cannot change status from $currentStatus to $newStatus.");
          }

          // Update order status
          $updateQuery = "UPDATE `order` SET order_status = ? WHERE ID = ?";
          $updateStmt = $conn->prepare($updateQuery);
          if (!$updateStmt) {
              throw new Exception('Prepare failed: ' . $conn->error);
          }
          $updateStmt->bind_param('si', $newStatus, $orderId);
          if (!$updateStmt->execute()) {
              throw new Exception('Failed to update order status: ' . $updateStmt->error);
          }
          $updateStmt->close();


          if ($newStatus == 'CANCELED') {
              $updateItemsQuery = "UPDATE `order_items` SET status_id = 'CANCELED' WHERE order_ID = ?";
              $updateItemsStmt = $conn->prepare($updateItemsQuery);
              if (!$updateItemsStmt) {
                  throw new Exception('Prepare failed: ' . $conn->error);
              }
              $updateItemsStmt->bind_param('i', $orderId);
              if (!$updateItemsStmt->execute()) {
                  throw new Exception('Failed to update order items status: ' . $updateItemsStmt->error);
              }
              $updateItemsStmt->close();
          }
          
          if ($newStatus == 'COMPLETE') {
              $updateItemsQuery = "UPDATE `order_items` SET status_id = 'COMPLETE' WHERE order_ID = ?";
              $updateItemsStmt = $conn->prepare($updateItemsQuery);
              if (!$updateItemsStmt) {
                  throw new Exception('Prepare failed: ' . $conn->error);
              }
              $updateItemsStmt->bind_param('i', $orderId);
              if (!$updateItemsStmt->execute()) {
                  throw new Exception('Failed to update order items status: ' . $updateItemsStmt->error);
              }
              $updateItemsStmt->close();
          }

          // Fetch updated counts for statuses
          $countSql = "SELECT order_status, COUNT(*) as count FROM `order` GROUP BY order_status";
          $countStmt = $conn->prepare($countSql);
          if (!$countStmt) {
              throw new Exception('Prepare failed for count query: ' . $conn->error);
          }
          $countStmt->execute();
          $countResult = $countStmt->get_result();

          $counts = ['PENDING' => 0, 'PREPARING' => 0, 'READY_FOR_PICKUP' => 0, 'COMPLETE' => 0, 'CANCELED' => 0];
          while ($countRow = $countResult->fetch_assoc()) {
              if (array_key_exists($countRow['order_status'], $counts)) {
                  $counts[$countRow['order_status']] = intval($countRow['count']);
              }
          }
          $countStmt->close();

          sendJsonResponse(['success' => true, 'newStatus' => $newStatus, 'counts' => $counts]);

      } catch (Exception $e) {
          sendJsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
      }
  }

  if (isset($input['action']) && $input['action'] === 'update_menu_item_status') {
      try {
          $orderId = isset($input['orderId']) ? intval($input['orderId']) : null;
          $menuItemId = isset($input['menuItemId']) ? intval($input['menuItemId']) : null;
          $newStatus = isset($input['status']) ? strtoupper(trim($input['status'])) : null;

          if (!$orderId || !$menuItemId || !$newStatus) {
              throw new Exception('Order ID, Menu Item ID, and new status are required.');
          }

          // Validate newStatus
          $allowedStatuses = ['PENDING', 'PREPARING', 'READY FOR PICKUP'];
          if (!in_array($newStatus, $allowedStatuses)) {
              throw new Exception('Invalid menu item status.');
          }

          // Fetch the status ID from menu_item_status table
          $statusQuery = "SELECT ID FROM menu_item_status WHERE status_name = ?";
          $statusStmt = $conn->prepare($statusQuery);
          if (!$statusStmt) {
              throw new Exception('Prepare failed: ' . $conn->error);
          }
          $statusStmt->bind_param('s', $newStatus);
          $statusStmt->execute();
          $statusResult = $statusStmt->get_result();
          $statusRow = $statusResult->fetch_assoc();
          $statusStmt->close();

          if (!$statusRow) {
              throw new Exception('Menu item status not found.');
          }
          $statusId = $statusRow['ID'];

          // Update the order_items status_id
          $updateQuery = "UPDATE order_items SET status_id = ? WHERE order_ID = ? AND menu_item_ID = ?";
          $updateStmt = $conn->prepare($updateQuery);
          if (!$updateStmt) {
              throw new Exception('Prepare failed: ' . $conn->error);
          }
          $updateStmt->bind_param('iii', $statusId, $orderId, $menuItemId);
          if (!$updateStmt->execute()) {
              throw new Exception('Failed to update menu item status: ' . $updateStmt->error);
          }
          $updateStmt->close();

          sendJsonResponse(['success' => true]);

      } catch (Exception $e) {
          sendJsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
      }
  }

}
?>

<!DOCTYPE html>
<html>

  <head>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
      <link rel="stylesheet" href="my-orders.css">
      <script src="my-orders.js"></script>
      <title> My Orders Page | Cara's Food Haven </title>
      <link rel="shortcut icon" type="x-icon" href="../RestaurantLogo-Cut.png">
  </head>

  <body>
      
    <nav>
      <span> <img src="../RestaurantLogo-Cut.png" class="RestaurantLogo_Nav"> </span>
      <div class="search-bar">
          <input type="text" placeholder="Enter your Order ID here">
          <button type="submit">Search</button>
      </div>
      <ul>
        <li><a href="#"><i class="fas fa-home"></i> <span>Home</span></a></li>
        <li><a href="../menu_webpage/menu.php"><i class="fas fa-utensils"></i> <span>Menu</span></a></li>
        <li><a href="#" id="current-page"><i class="fas fa-receipt"></i> <span>My Orders</span></a></li>
        <li><a href="#"><i class="fas fa-info-circle"></i> <span>About Us</span></a></li>
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
                <a href="../menu_webpage/login/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </li>
      </ul>
    </nav>
      
    <div class="verticalmenu_container">
      <div class="verticalmenu-section">
        <label class="verticalmenu-label"> TODAY'S ORDERS </label>
      </div>
    </div>

    <div id="notification-container"></div>

    <div class="container" id="orderManagement">
      <div class="container-full">
        
        <div class="filter-bar">
          <button class="filter-btn" data-status="ALL">
            <i class="fas fa-list"></i> All<span class="count" id="count-ALL"></span>
          </button>
          <button class="filter-btn" data-status="PENDING">
            <i class="fas fa-hourglass-start"></i> Pending<span class="count" id="count-PENDING"></span>
          </button>
          <button class="filter-btn" data-status="PREPARING">
            <i class="fas fa-fire"></i>  Preparing<span class="count" id="count-PREPARING"></span>
          </button>
          <button class="filter-btn" data-status="READY_FOR_PICKUP">
            <i class="fas fa-concierge-bell"></i> Ready for Pickup<span class="count" id="count-READY_FOR_PICKUP"></span>
          </button>
          <button class="filter-btn" data-status="COMPLETE">
            <i class="fas fa-check-circle"></i> Complete<span class="count" id="count-COMPLETE"></span>
          </button>
          <button class="filter-btn" data-status="CANCELED">
            <i class="fas fa-times-circle"></i> Canceled<span class="count" id="count-CANCELED"></span>
          </button>
          <div id="current-date-time" class="current-date-time"></div>
        </div>

        <div class="orders">
          
        </div>

      </div>
    </div>   

    <div id="cancel-order-modal" class="modal">
      <div class="modal-content">
          <span class="close-button">&times;</span>
          <h2>Cancel Order</h2>
          <p>Are you sure you want to cancel this order?</p>
          <button id="confirmCancel" class="action-btn cancel">Yes, Cancel</button>
          <button id="closeModal" class="action-btn">Close</button>
      </div>
    </div>




<script>


///////////////// LOGIN //////////////////////////////

document.addEventListener("DOMContentLoaded", function() {
        const accountDropdown = document.querySelector('.account-dropdown');
        const dropdownArrow = document.getElementById('dropdown-arrow');

        accountDropdown.addEventListener('click', function(event) {
          event.stopPropagation(); // Prevent click from bubbling up
          accountDropdown.classList.toggle('active'); // Toggle the active class

          // Toggle the arrow direction
          if (accountDropdown.classList.contains('active')) {
            dropdownArrow.classList.remove('fa-caret-down');
            dropdownArrow.classList.add('fa-caret-up');
          } else {
            dropdownArrow.classList.remove('fa-caret-up');
            dropdownArrow.classList.add('fa-caret-down');
          }
        });

        document.addEventListener('click', function(event) {
          if (!accountDropdown.contains(event.target)) {
            accountDropdown.classList.remove('active'); // Remove active class when clicking outside
            dropdownArrow.classList.remove('fa-caret-up');
            dropdownArrow.classList.add('fa-caret-down');
          }
        });
      });


document.addEventListener('DOMContentLoaded', function() {
    const filterButtons = document.querySelectorAll('.filter-btn');
    filterButtons.forEach(button => {
        button.addEventListener('click', () => {
            filterButtons.forEach(btn => btn.classList.remove('active'));
            button.classList.add('active');
            const status = button.getAttribute('data-status');
            loadOrders(status);
        });
    });

    const activeStatusButton = document.querySelector('.filter-btn.active');
    const initialStatus = activeStatusButton ? activeStatusButton.getAttribute('data-status') : 'PENDING';
    loadOrders(initialStatus);
});






// ////////////////////////////////////////////////////////////////////

/**
 * Loads orders based on the provided status.
 * @param {string} status - The status to filter orders by.
 */
function loadOrders(status = 'PENDING') {
    fetch(`my-orders.php?action=get_orders&status=${status}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const ordersContainer = document.querySelector('.orders');
                if (!ordersContainer) {
                    console.error('Orders container not found');
                    return;
                }
                ordersContainer.innerHTML = ''; // Clear existing orders
                
                data.orders.forEach(order => {
                    const orderData = {
                        id: order.id,
                        status: order.status,
                        statusText: order.order_status.replace(/_/g, ' '),
                        orderDateTime: order.date_ordered,
                        orderType: order.order_type,
                        total: parseFloat(order.total_price),
                        discountCode: order.discount_code,
                        menuItems: order.menuItems || [],
                        customerInfo: {
                         name: order.customerName,
                         phone: order.contactInfo?.phone || 'N/A',
                         email: order.contactInfo?.email || 'N/A',
                         address: order.contactInfo?.address || 'N/A'
                        }
                    };
                    
                    addNewOrderCard(orderData);
                    fetchOrderCounts();
                });
                
            } else {
                console.error('Failed to load orders:', data.message);
            }
        })
        .catch(error => console.error('Error loading orders:', error));
}

function addNewOrderCard(orderData) {
    const ordersContainer = document.querySelector('.orders');
    if (!ordersContainer) {
        console.error('Orders container not found');
        return;
    }

    const newCard = document.createElement('div');
    newCard.className = 'order-card';
    newCard.setAttribute('data-status', orderData.status);
    newCard.setAttribute('data-id', orderData.id);

    const menuItems = orderData.menuItems.map(item => {
      let hourglassDisplay = 'none';
      let fireDisplay = 'none';
      let bellDisplay = 'none';
      let wasteDisplay = 'none';
      let cursorStyle = 'default';

      // In PREPARING state, customer just sees icons (no changing)
      if (orderData.status == 'PREPARING') {
          switch (item.menu_item_status) {
              case 'PENDING':
                  hourglassDisplay = 'inline';
                  break;
              case 'PREPARING':
                  fireDisplay = 'inline';
                  break;
              case 'READY_FOR_PICKUP':
                  bellDisplay = 'inline';
                  break;
              case 'CANCELED':
              case 'COMPLETE':
                  wasteDisplay = 'inline';
                  break;
          }
      } else if (orderData.status == 'COMPLETE' || orderData.status == 'CANCELED') {
          // Show waste icon
          wasteDisplay = 'inline';
      }

      return `
      <tr class="menu-item-row"
          style="cursor: ${cursorStyle};"
          data-wasted-ingredients=""
          data-menu-item-id="${item.id}"
          data-menu-item-status="${item.menu_item_status}">
          <td>
              <div class="icon-container">
                  <span class="icon hourglass-icon" data-state="hourglass" style="display:${hourglassDisplay};">
                      <i class="fas fa-hourglass-start"></i>
                  </span>
                  <span class="icon fire-icon" data-state="fire" style="display:${fireDisplay};">
                      <i class="fas fa-fire"></i>
                  </span>
                  <span class="icon bell-icon" data-state="bell" style="display:${bellDisplay};">
                      <i class="fas fa-concierge-bell"></i>
                  </span>
                  <span class="icon waste-icon" data-state="waste" style="display:${wasteDisplay};">
                      <i class="fas fa-trash-alt"></i>
                  </span>
                  ${item.name}
              </div>
          </td>
          <td>Php ${parseFloat(item.price).toFixed(2)}</td>
          <td>${item.quantity}</td>
          <td>Php${(parseFloat(item.price) * item.quantity).toFixed(2)}</td>
      </tr>
      `;
    }).join('');

    // Action buttons: Only show "Cancel Order" if status is PENDING
    let actionButtonsHTML = '';
    if (orderData.status === 'PENDING') {
        actionButtonsHTML = `
            <div class="action-buttons">
                <button class="action-btn cancel">Cancel Order</button>
            </div>
        `;
    } else {
        // For other statuses, no action buttons
        actionButtonsHTML = `<div class="action-buttons"></div>`;
    }

    newCard.innerHTML = `
        <div class="order-header">
            <h2>Order ID #${orderData.id}</h2>
            <span class="status ${orderData.status.toLowerCase().replace(/_/g, '-')}">
            ${orderData.statusText}
            </span>
        </div>

        <table id="order-card-details">
            <tr>
            <td>Order Date & Time:</td>
            <td>${orderData.orderDateTime || 'N/A'}</td>
            </tr>
            <tr>
            <td>Order Type:</td>
            <td>${orderData.orderType || 'N/A'}</td>
            </tr>
            <tr>
            <td>Table Number:</td>
            <td>${orderData.orderTableNum || 'N/A'}</td>
            </tr>
            <tr>
            <td>Address:</td>
            <td>${orderData.customerInfo.address || 'N/A'}</td>
            </tr>
            <tr>
            <td>Contact Number:</td>
            <td>${orderData.customerInfo.phone || 'N/A'}</td>
            </tr>
        </table>

        <table class="order-table">
            <tr>
            <th>Menu Item</th>
            <th>Price</th>
            <th>Quantity</th>
            <th>Subtotal</th>
            </tr>
            ${menuItems}
            <tr>
            <td colspan="3" class="total-label">TOTAL</td>
            <td>Php ${orderData.total}</td>
            </tr>
            <tr>
            <td colspan="3" class="discount-label">Discount Code</td>
            <td>${orderData.discountCode || 'N/A'}</td>
            </tr>
        </table>
        ${actionButtonsHTML}
    `;
    
    ordersContainer.appendChild(newCard);
    attachEventListeners(newCard);
    updateCounts();
    reapplyCurrentFilter();
    rearrangeOrderCards();
}

function attachEventListeners(card) {
    const cancelButton = card.querySelector('.action-btn.cancel');

    if (cancelButton) {
        cancelButton.addEventListener('click', () => handleCancel(card));
    }
}

// Function to rearrange order cards in descending order by ID
function rearrangeOrderCards() {
    const ordersContainer = document.querySelector('.orders');
    const orderCards = Array.from(ordersContainer.children);

    orderCards.sort((a, b) => {
        const idA = parseInt(a.querySelector('.order-header h2').textContent.match(/\d+/)[0]);
        const idB = parseInt(b.querySelector('.order-header h2').textContent.match(/\d+/)[0]);
        return idB - idA; 
    });

    ordersContainer.innerHTML = '';
    orderCards.forEach(card => ordersContainer.appendChild(card));
}

// Since no status change buttons are allowed, we simplify and only allow cancel if PENDING
function handleCancel(card) {
    const modal = document.getElementById('cancel-order-modal');
    if (!modal) {
      console.error('Cancel Order Modal not found');
      return;
    }

    modal.style.display = 'block';

    const confirmCancelButton = document.getElementById('confirmCancel');
    const closeModalButton = document.getElementById('closeModal');

    if (!confirmCancelButton || !closeModalButton) {
      console.error('Confirm or Close buttons not found in the modal');
      return;
    }

    confirmCancelButton.onclick = function() {
      const orderId = card.getAttribute('data-id');

      updateOrderStatusInDB(orderId, 'CANCELED')
        .then(response => {
          if (response.success) {
            updateOrderStatus(card, 'CANCELED', 'Canceled');
            showNotification(`Order ID #${orderId} has been canceled.`);
          } else {
            showNotification(`Failed to cancel Order ID #${orderId}: ${response.message}`);
          }
        })
        .catch(error => {
          console.error('Error canceling order:', error);
          showNotification('An error occurred while canceling the order.');
        });

      modal.style.display = 'none';
    };

    closeModalButton.onclick = function() {
      modal.style.display = 'none';
    };
    updateCounts();
}

function updateOrderStatusInDB(orderId, status) {
    return fetch('my-orders.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'update_order_status',
            orderId: orderId,
            status: status 
        })
    })
    .then(response => response.json());
}

function updateOrderStatus(card, newStatus, newStatusText) {
    card.setAttribute('data-status', newStatus);
    const statusSpan = card.querySelector('.status');
    statusSpan.textContent = newStatusText;
    statusSpan.className = `status ${newStatus.toLowerCase().replace(/ /g, '-')}`;

    // Remove action buttons except if PENDING
    const actionButtons = card.querySelector('.action-buttons');
    if (actionButtons) {
        actionButtons.innerHTML = '';
    }

    updateCounts(); 
    reapplyCurrentFilter(); 
}

function reapplyCurrentFilter() {
    const activeButton = document.querySelector('.filter-btn.active');
    if (activeButton) {
        const status = activeButton.getAttribute('data-status');
        const orderCards = document.querySelectorAll('.order-card');
        orderCards.forEach(card => {
            const cardStatus = card.getAttribute('data-status');
            card.style.display = (status === 'ALL' || status === cardStatus) ? 'block' : 'none';
        });
    }
}

function fetchOrderCounts() {
    fetch('my-orders.php?action=get_order_counts')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const counts = data.counts;
                updateCountsInUI(counts);
            } else {
                console.error('Failed to fetch order counts:', data.message);
            }
        })
        .catch(error => console.error('Error fetching order counts:', error));
}

function updateCountsInUI(counts) {
    const statuses = ['ALL', 'PENDING', 'PREPARING', 'READY_FOR_PICKUP', 'COMPLETE', 'CANCELED'];
    statuses.forEach(status => {
        const count = status === 'ALL'
            ? Object.values(counts).reduce((sum, val) => sum + val, 0)
            : counts[status] || 0;
        const countElement = document.getElementById(`count-${status}`);
        if (countElement) {
            countElement.textContent = count;
        }
    });
}

function updateCounts() {
    const statuses = ['ALL', 'PENDING', 'PREPARING', 'READY_FOR_PICKUP', 'COMPLETE', 'CANCELED'];
    statuses.forEach(status => {
        const count = status === 'ALL' 
            ? document.querySelectorAll('.order-card').length 
            : document.querySelectorAll(`.order-card[data-status="${status}"]`).length;
        document.getElementById(`count-${status}`).textContent = count;
    });
}

const allButton = document.querySelector('.filter-btn[data-status="ALL"]');
if (allButton) {
    allButton.click();
}

function formatDateTime(date) {
    const options = { year: 'numeric', month: 'long', day: 'numeric', hour: 'numeric', minute: 'numeric', hour12: true };
    return date.toLocaleString('en-US', options);
} 

const currentDateTimeElement = document.getElementById('current-date-time');

function updateDateTime() {
    const currentDateTime = formatDateTime(new Date());
    currentDateTimeElement.textContent = `Date & Time: ${currentDateTime}`;
}

updateDateTime();
setInterval(updateDateTime, 1000);

</script>  

      
  </body>
</html>