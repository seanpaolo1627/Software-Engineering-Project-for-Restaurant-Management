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

// Close the database connection
$conn->close();
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
      
    <!-- ============================================================ -->
    <!-- ============================================================ -->
    <!-- ============================================================ -->
    
    <nav>
      <span> <img src="../RestaurantLogo-Cut.png" class="RestaurantLogo_Nav"> </span>
      <div class="search-bar">
          <input type="text" placeholder="Enter your Order ID here">
          <button type="submit">Search</button>
      </div>
      <!-- Sean added redirection to menu and my orders vice versa -->
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
    <!-- ============================================================ -->
    <!-- ============================================================ -->
    <!-- ============================================================ -->

    <div class="verticalmenu_container">
      <div class="verticalmenu-section">
        <label class="verticalmenu-label"> TODAY'S ORDERS </label>
      </div>
    </div>
        
    <!-- ============================================================ -->
    <!-- ============================================================ -->
    <!-- ============================================================ -->

    <div id="confirm-order-modal" class="modal">
      <div id="confirm-order-content" class="modal-content">
  
          <form id="customer-form">
            <h2 id="confirm-order-header">CUSTOMER FORM</h2>
            <br>
            <!-- Order Type Selection -->
            <div class="form-group">
                <label for="order-type">Order Type:</label>
                <select id="order-type" name="order-type" required>
                  <option value="" disabled selected>--- Select the Order Type ---</option>
                  <option value="DINE IN">DINE IN</option>
                  <option value="TAKEOUT">TAKEOUT</option>
                  <option value="DELIVERY">DELIVERY</option>
                </select>
            </div>

            <!-- Payment Method Selection -->
            <div class="form-group" style="display: none">
                <label for="cash-amount">Cash Amount:</label>
                <input type="number" id="cash-amount" name="cash-amount" placeholder="Enter cash amount" min="0">
            </div>

            <div class="form-group">
                <label>Wire Transfer Options:</label>
                <div class="wire-transfer-options">
                    <button type="button" class="wire-transfer-btn" data-method="gcash">GCash</button>
                    <button type="button" class="wire-transfer-btn" data-method="paymaya">PayMaya</button>
                    <button type="button" class="wire-transfer-btn" data-method="bank-transfer">Bank Transfer</button>
                </div>
            </div>

            <!-- Customer Name -->
            <div class="form-group">
                <label for="customer-name">Customer Name:</label>
                <input type="text" id="customer-name" name="customer-name" placeholder="Enter your name" required>
            </div>

            <!-- Contact Number -->
            <div class="form-group">
                <label for="contact-number">Contact Number:</label>
                <input type="tel" id="contact-number" name="contact-number" placeholder="Enter your contact number" required>
            </div>

            <!-- Address -->
            <div class="form-group" style="display: none">
                <label for="address">Address:</label>
                <input type="text" id="address" name="address" placeholder="Enter your address">
            </div>

            <!-- Email -->
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" placeholder="Enter your email">
            </div>
            <br>

            <button type="submit" id="confirm-order-btn">Confirm Details</button>
          </form>
      </div>
    </div>

    <!-- ============================================================ -->
    <!-- ============================================================ -->
    <!-- ============================================================ -->

    <div id="menu-item-modal" class="modal">
      <div id="menu-item-content" class="modal-content">
          <!-- Image Placeholder -->
          <div class="image-placeholder" style="text-align: center;">
              <img src="../img_placeholder.png" alt="Menu Item Image" id="modal-menu-item-img">
          </div>
          
          <br>
          
          <!-- Header -->
          <h2 id="menu-item-header" style="text-align: center;">MENU ITEM NAME</h2>
          
          <br>

          <!-- Price -->
          <p id="menu-item-price" style="text-align: center; font-weight: bold;">PRICE</p>

          <br>
          <br>
          <br>
          
          <!-- Description -->
          <p id="menu-item-desc" style="text-align: center;">MENU ITEM DESCRIPTION TEMPLATE</p>
      </div>
  </div>

    <!-- ============================================================ -->
    <!-- ============================================================ -->
    <!-- ============================================================ -->

    <!-- Notification Container -->
    <div id="notification-container"></div>

    <!-- ============================================================ -->
    <!-- ============================================================ -->
    <!-- ============================================================ -->









    
    <!-- ============================================================ -->
    <!-- ============================================================ -->
    <!-- ============================================================ -->

    <div class="container" id="orderManagement">
      <div class="container-full">
        
        <!-- ============================== -->

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
          <button class="filter-btn" data-status="READY FOR PICKUP">
            <i class="fas fa-concierge-bell"></i> Ready for Pickup<span class="count" id="count-READY FOR PICKUP"></span>
          </button>
          <button class="filter-btn" data-status="COMPLETE">
            <i class="fas fa-check-circle"></i> Complete<span class="count" id="count-COMPLETE"></span>
          </button>
          <button class="filter-btn" data-status="CANCELED">
            <i class="fas fa-times-circle"></i> Canceled<span class="count" id="count-CANCELED"></span>
          </button>
          <!-- Date Display -->
          <div id="current-date-time" class="current-date-time"></div>
        </div>

        <!-- ============================== -->
    
        <div class="orders">
          
        </div>

        <!-- ============================== -->

      </div>
    </div>   
    
    <!-- ============================================================ -->
    <!-- ============================================================ -->
    <!-- ============================================================ -->







    <!-- ============================================================ -->
    <!-- ============================================================ -->
    <!-- ============================================================ -->

    <script>

      /*============================================================*/

      document.addEventListener("DOMContentLoaded", function() {
        
        /*==============================*/

        // Add event listener to close any modal when clicking outside of the modal content
        document.addEventListener('click', function(event) {
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            });
        });

        /*==============================*/

        const accountDropdown = document.querySelector('.account-dropdown');
        const dropdownArrow = document.getElementById('dropdown-arrow');

        accountDropdown.addEventListener('click', function(event) {
          event.stopPropagation(); // Prevent click from bubbling up
          accountDropdown.classList.toggle('active'); 

          // Toggle the arrow direction
          if (accountDropdown.classList.contains('active')) {
            dropdownArrow.classList.remove('fa-caret-down');
            dropdownArrow.classList.add('fa-caret-up');
          } else {
            dropdownArrow.classList.remove('fa-caret-up');
            dropdownArrow.classList.add('fa-caret-down');
          }
        });
        
        /*==============================*/

        document.addEventListener('click', function(event) {
          if (!accountDropdown.contains(event.target)) {
            accountDropdown.classList.remove('active'); 
            dropdownArrow.classList.remove('fa-caret-up');
            dropdownArrow.classList.add('fa-caret-down');
          }
        });
        
        /*==============================*/

      });

      /*============================================================*/

      document.addEventListener('DOMContentLoaded', () => {

        /*============================================================*/

        function addNewOrderCard(orderData) {
          const newCard = document.createElement('div');
          const ordersContainer = document.querySelector('.orders');
          newCard.className = 'order-card';
          newCard.setAttribute('data-status', orderData.status);
          newCard.id = `order-card-id-${orderData.id}`; // Set the ID of the new card

          // Add button to vertical menu
          const verticalMenuContainer = document.querySelector('.verticalmenu-section');
          const orderButton = document.createElement('button');
          orderButton.className = 'verticalmenu-btn';
          orderButton.id = orderData.id; // Set the button ID to the order ID
          
          // Set inner HTML for the button with icon and text
          orderButton.innerHTML = `
            ${getStatusIcon(orderData.status)} Order ID #${orderData.id}
          `;
          orderButton.style.backgroundColor = getStatusColor(orderData.status); // Set background color based on status
          verticalMenuContainer.appendChild(orderButton); // Append the button to the vertical menu

          // Rearrange buttons in descending order
          rearrangeOrderButtons();

          // Sample menu items for the order with clickable icon
          const menuItems = orderData.menuItems.map(item => `
            <tr class="menu-item-row">
              <td>
                <div class="icon-container">
                  <span class="icon question-icon" data-state="question" style="display:${orderData.status === 'PREPARING' ? 'inline-block' : 'none'};">
                      <i class="fas fa-question"></i>
                  </span>
                  <span class="icon fire-icon" data-state="fire" style="display:none;">
                    <i class="fas fa-fire"></i>
                  </span>
                  <span class="icon bell-icon" data-state="bell" style="display:none;">
                    <i class="fas fa-concierge-bell"></i>
                  </span>
                  ${item.name}
                </div>
              </td>
              <td>Php ${item.price}</td>
              <td>${item.quantity}</td>
              <td>Php ${(item.price * item.quantity)}</td>
            </tr>
          `).join('');

          // Calculate total
          const total = orderData.menuItems.reduce((sum, item) => sum + (item.price * item.quantity), 0);

          newCard.innerHTML = `
            <div class="order-header">
              <h2>Order ID #${orderData.id}</h2>
              <span class="status ${orderData.status.toLowerCase().replace(/ /g, '-')}">
                ${orderData.statusText}
              </span>
            </div>

            <table id="order-card-details">
              ${orderData.orderDateTime ? `
              <tr>
                <td>Order Date & Time:</td>
                <td>${orderData.orderDateTime}</td>
              </tr>` : ''}
              ${orderData.orderType ? `
              <tr>
                <td>Order Type:</td>
                <td>${orderData.orderType}</td>
              </tr>` : ''}
              ${orderData.orderTableNum ? `
              <tr>
                <td>Table Number:</td>
                <td>${orderData.orderTableNum}</td>
              </tr>` : ''}
              ${orderData.addressLine ? `
              <tr>
                <td>Address:</td>
                <td>${orderData.addressLine}</td>
              </tr>` : ''}
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
                <td>Php ${total}</td>
              </tr>
              <tr>
                <td colspan="3" class="discount-label">Discount Code</td>
                <td>${orderData.discountCode || 'N/A'}</td>
              </tr>
            </table>
          `;

          // If the order status is 'PREPARING', alternate the icons
          if (orderData.status === 'PREPARING') {
            const iconContainers = newCard.querySelectorAll('.icon-container');
            iconContainers.forEach(container => {
              let currentIconIndex = 0; // Start with the question icon
              const icons = ['question-icon', 'fire-icon', 'bell-icon'];

              // Function to alternate icons
              const alternateIcons = () => {
                // Hide all icons
                container.querySelectorAll('.icon').forEach(icon => {
                  icon.style.display = 'none';
                });

                // Show the current icon
                container.querySelector(`.${icons[currentIconIndex]}`).style.display = 'inline-block';

                // Update the index for the next icon
                currentIconIndex = (currentIconIndex + 1) % icons.length; // Loop back to the first icon
              };

              // Start the icon alternation
              setInterval(alternateIcons, 1000); // Change icon every second
            });
          }

          ordersContainer.appendChild(newCard);
          rearrangeOrderCards();
          
          // Add event listeners to vertical menu buttons
          const verticalMenuButtons = document.querySelectorAll(".verticalmenu-btn");

          verticalMenuButtons.forEach(button => {
            button.addEventListener("click", function () {
              const targetId = button.id; // Get the clicked button's id

              // Scroll to the order card with the corresponding ID
              const orderCard = document.getElementById(`order-card-id-${targetId}`);
              if (orderCard) {
                // Get the data-status of the found card
                const cardStatus = orderCard.getAttribute('data-status');

                // Simulate a click on the corresponding filter button
                const filterButton = document.querySelector(`.filter-btn[data-status="${cardStatus}"]`);
                if (filterButton) {
                    filterButton.click(); // Simulate click on the filter button
                }

                // Select the container
                const container = document.querySelector('.container-full');

                // Calculate the position of the order card relative to the container
                const cardPosition = orderCard.getBoundingClientRect().top + container.scrollTop;

                // Calculate the target position to scroll to, ensuring the top of the card is 240px from the top of the viewport
                const targetPosition = cardPosition - 240;

                // Scroll to the adjusted position within the container
                container.scrollTo({ top: targetPosition, behavior: 'smooth' });
              }

              // Toggle 'active' class for the buttons
              verticalMenuButtons.forEach(btn => btn.classList.remove("active"));
              button.classList.add("active");
            });
          });
        }
      
        /*============================================================*/

        // Function to rearrange buttons in descending order by ID
        function rearrangeOrderButtons() {
            const verticalMenuContainer = document.querySelector('.verticalmenu-section');
            const buttons = Array.from(verticalMenuContainer.querySelectorAll('.verticalmenu-btn')); // Get all buttons as an array

            // Sort the buttons by ID in descending order
            buttons.sort((a, b) => {
                const idA = parseInt(a.id); // Get ID from button
                const idB = parseInt(b.id);
                return idB - idA; // Sort in descending order
            });

            // Remove all buttons from the container
            buttons.forEach(button => verticalMenuContainer.removeChild(button)); // Remove each button

            // Append sorted buttons back to the vertical menu
            buttons.forEach(button => verticalMenuContainer.appendChild(button)); // Append sorted buttons
        }
      
        /*============================================================*/

        // Function to rearrange order cards in descending order by ID
        function rearrangeOrderCards() {
            const ordersContainer = document.querySelector('.orders');
            const orderCards = Array.from(ordersContainer.children); // Get all order cards as an array

            // Sort the order cards by ID in descending order
            orderCards.sort((a, b) => {
                const idA = parseInt(a.querySelector('.order-header h2').textContent.match(/\d+/)[0]); // Extract ID from the header
                const idB = parseInt(b.querySelector('.order-header h2').textContent.match(/\d+/)[0]);
                return idB - idA; // Sort in descending order
            });

            // Clear the container and append sorted cards
            ordersContainer.innerHTML = ''; // Clear existing cards
            orderCards.forEach(card => ordersContainer.appendChild(card)); // Append sorted cards
        }
      
        /*============================================================*/

        // Function to get the icon HTML based on order status
        function getStatusIcon(status) {
          switch (status) {
            case 'PENDING':
              return '<i class="fas fa-hourglass-start"></i>'; // Icon for pending
            case 'PREPARING':
              return '<i class="fas fa-fire"></i>'; // Icon for preparing
            case 'READY FOR PICKUP':
              return '<i class="fas fa-concierge-bell"></i>'; // Icon for ready for pickup
            case 'COMPLETE':
              return '<i class="fas fa-check-circle"></i>'; // Icon for complete
            case 'CANCELED':
              return '<i class="fas fa-times-circle"></i>'; // Icon for canceled
            default:
              return '<i class="fas fa-question"></i>'; // Default icon
          }
        }
      
        /*============================================================*/

        // Function to get the background color based on order status
        function getStatusColor(status) {
          switch (status) {
            case 'PENDING':
              return 'orange'; 
            case 'PREPARING':
              return 'orangered'; 
            case 'READY FOR PICKUP':
              return 'blue'; 
            case 'COMPLETE':
              return 'green'; 
            case 'CANCELED':
              return 'red'; 
            default:
              return 'gray'; // Default color
          }
        }
      
        /*============================================================*/

        // Function to get the class corresponding to the next status
        function getButtonClassForStatus(status) {
          switch (status) {
            case 'PENDING':
              return 'status-preparing';
            case 'PREPARING':
              return 'status-ready-for-pickup';
            case 'READY FOR PICKUP':
              return 'status-complete';
            default:
              return '';
          }
        }

        // Attach event listeners to existing order cards
        const orderCards = document.querySelectorAll('.order-card');
        orderCards.forEach(card => attachEventListeners(card));

        function updateCounts() {
          const statuses = ['ALL', 'PENDING', 'PREPARING', 'READY FOR PICKUP', 'COMPLETE', 'CANCELED'];
          statuses.forEach(status => {
            const count = status === 'ALL' 
              ? document.querySelectorAll('.order-card').length 
              : document.querySelectorAll(`.order-card[data-status="${status}"]`).length;
            document.getElementById(`count-${status}`).textContent = count;
          });
        }

        function filterOrders(button) {
          const status = button.getAttribute('data-status');
          orderCards.forEach(card => {
            if (status === 'ALL' || card.getAttribute('data-status') === status) {
              card.style.display = 'block';
            } else {
              card.style.display = 'none';
            }
          });
        }

        // Initial count update
        updateCounts();

        // Simulate a click on the "ALL" filter button on page load
        const allButton = document.querySelector('.filter-btn[data-status="ALL"]');
        if (allButton) {
          allButton.click();
        }
      
        /*============================================================*/

        // Get the current date and time
        const currentDateTime = formatDateTime(new Date()); // Formatted current date and time

        // Sample order data for each status with menu items
        const pendingOrderData6 = {
          id: 10,
          status: 'PENDING',
          statusText: 'Pending',
          orderDateTime: currentDateTime, 
          orderType: 'DINE IN', 
          menuItems: [
            { name: 'Chicken Alfredo', price: 200, quantity: 1 },
            { name: 'Garlic Bread', price: 50, quantity: 3 }
          ],
           discountCode: 'ABC123'
        };

        const pendingOrderData5 = {
          id: 9,
          status: 'PENDING',
          statusText: 'Pending',
          orderDateTime: currentDateTime, 
          orderType: 'DINE IN', 
          menuItems: [
            { name: 'Chicken Alfredo', price: 200, quantity: 1 },
            { name: 'Garlic Bread', price: 50, quantity: 3 }
          ],
           discountCode: 'ABC123'
        };

        const pendingOrderData4 = {
          id: 8,
          status: 'PENDING',
          statusText: 'Pending',
          orderDateTime: currentDateTime, 
          orderType: 'DINE IN', 
          menuItems: [
            { name: 'Chicken Alfredo', price: 200, quantity: 1 },
            { name: 'Garlic Bread', price: 50, quantity: 3 },
            { name: 'Chicken Alfredo', price: 200, quantity: 1 },
            { name: 'Garlic Bread', price: 50, quantity: 3 },
            { name: 'Chicken Alfredo', price: 200, quantity: 1 },
            { name: 'Garlic Bread', price: 50, quantity: 3 },
            { name: 'Chicken Alfredo', price: 200, quantity: 1 },
            { name: 'Garlic Bread', price: 50, quantity: 3 },
            { name: 'Chicken Alfredo', price: 200, quantity: 1 },
            { name: 'Garlic Bread', price: 50, quantity: 3 },
            { name: 'Chicken Alfredo', price: 200, quantity: 1 },
            { name: 'Garlic Bread', price: 50, quantity: 3 },
            { name: 'Chicken Alfredo', price: 200, quantity: 1 },
            { name: 'Garlic Bread', price: 50, quantity: 3 }
          ],
           discountCode: 'ABC123'
        };

        const pendingOrderData3 = {
          id: 7,
          status: 'PENDING',
          statusText: 'Pending',
          orderDateTime: currentDateTime, 
          orderType: 'DINE IN', 
          menuItems: [
            { name: 'Chicken Alfredo', price: 200, quantity: 1 },
            { name: 'Garlic Bread', price: 50, quantity: 3 }
          ],
           discountCode: 'ABC123'
        };

        const pendingOrderData2 = {
          id: 6,
          status: 'PENDING',
          statusText: 'Pending',
          orderDateTime: currentDateTime, 
          orderType: 'DINE IN', 
          menuItems: [
            { name: 'Chicken Alfredo', price: 200, quantity: 1 },
            { name: 'Garlic Bread', price: 50, quantity: 3 }
          ],
           discountCode: 'ABC123'
        };

        const pendingOrderData = {
          id: 5,
          status: 'PENDING',
          statusText: 'Pending',
          orderDateTime: currentDateTime, 
          orderType: 'DINE IN', 
          orderTableNum: 'F2-T27',
          menuItems: [
            { name: 'Chicken Alfredo', price: 200, quantity: 1 },
            { name: 'Garlic Bread', price: 50, quantity: 3 }
          ],
           discountCode: 'ABC123'
        };

        const preparingOrderData = {
          id: 4,
          status: 'PREPARING',
          statusText: 'Preparing',
          orderDateTime: currentDateTime, 
          orderType: 'DELIVERY', 
          addressLine: '123 Main St, Springfield, IL',
          menuItems: [
            { name: 'Cheeseburger', price: 150, quantity: 2 },
            { name: 'Sphagetti', price: 220, quantity: 1 },
            { name: 'French Fries', price: 60, quantity: 3 }
          ],
           discountCode: 'ABC123'
        };

        const readyForPickupOrderData = {
          id: 3,
          status: 'READY FOR PICKUP',
          statusText: 'Ready for Pickup',
          orderDateTime: currentDateTime, 
          orderType: 'DINE IN', 
          orderTableNum: 'F1-T13',
          menuItems: [
            { name: 'Veggie Pizza', price: 300, quantity: 1 },
            { name: 'Coke', price: 50, quantity: 2 }
          ]
        };

        const completeOrderData = {
          id: 2,
          status: 'COMPLETE',
          statusText: 'Complete',
          orderDateTime: currentDateTime, 
          orderType: 'TAKEOUT', 
          menuItems: [
            { name: 'Pasta Primavera', price: 250, quantity: 1 }
          ]
        };

        const canceledOrderData = {
          id: 1,
          status: 'CANCELED',
          statusText: 'Canceled',
          orderDateTime: currentDateTime, 
          orderType: 'DELIVERY', 
          addressLine: '654 Maple St, Springfield, IL',
          menuItems: [
            { name: 'Caesar Salad', price: 120, quantity: 1 }
          ],
           discountCode: 'ABC123'
        };

        /*============================================================*/

        // Example of adding each order card
        addNewOrderCard(pendingOrderData);
        // addNewOrderCard(pendingOrderData2);
        // addNewOrderCard(pendingOrderData3);
        // addNewOrderCard(pendingOrderData4);
        // addNewOrderCard(pendingOrderData5);
        // addNewOrderCard(pendingOrderData6);
        addNewOrderCard(preparingOrderData);
        addNewOrderCard(readyForPickupOrderData);
        addNewOrderCard(completeOrderData);
        addNewOrderCard(canceledOrderData);

        /*============================================================*/

      });
      
      /*============================================================*/

      document.addEventListener('DOMContentLoaded', () => {
        const filterButtons = document.querySelectorAll('.filter-btn');
        const orderCards = document.querySelectorAll('.order-card');

        // Function to update the counts on filter buttons
        function updateCounts() {
          const statuses = ['ALL', 'PENDING', 'PREPARING', 'READY FOR PICKUP', 'COMPLETE', 'CANCELED'];
          statuses.forEach(status => {
            const count = status === 'ALL' 
              ? orderCards.length 
              : document.querySelectorAll(`.order-card[data-status="${status}"]`).length;
            document.getElementById(`count-${status}`).textContent = count;
          });
        }

        // Function to handle filter button click and apply active class
        function handleFilterClick(button) {
          // Remove 'active' class from all buttons
          filterButtons.forEach(btn => btn.classList.remove('active'));

          // Add 'active' class to the clicked button
          button.classList.add('active');

          // Filter orders based on the button's status
          const status = button.getAttribute('data-status');
          orderCards.forEach(card => {
            const cardStatus = card.getAttribute('data-status');
            card.style.display = (status === 'ALL' || status === cardStatus) ? 'block' : 'none';
          });

          // Update counts after filtering
          updateCounts();
        }

        // Add click event listeners to filter buttons
        filterButtons.forEach(button => {
          button.addEventListener('click', () => handleFilterClick(button));
        });

        // Initial count update
        updateCounts();

        // Simulate a click on the "ALL" filter button on page load
        document.querySelector('.filter-btn[data-status="ALL"]').click();
      });

      /*============================================================*/  

      // Function to format the date and time
      function formatDateTime(date) {
          const options = { year: 'numeric', month: 'long', day: 'numeric', hour: 'numeric', minute: 'numeric', hour12: true };
          return date.toLocaleString('en-US', options); // Format the date and time
      } 

      const currentDateTimeElement = document.getElementById('current-date-time');

      // Function to update the date and time
      function updateDateTime() {
          const currentDateTime = formatDateTime(new Date());
          currentDateTimeElement.textContent = `Date & Time: ${currentDateTime}`;
      }

      // Initial call to set the date and time immediately
      updateDateTime();

      // Update the date and time every second
      setInterval(updateDateTime, 1000);

      /*============================================================*/ 

      const accountDropdown = document.querySelector('.account-dropdown');
const dropdownArrow = document.getElementById('dropdown-arrow');

accountDropdown.addEventListener('click', function(event) {
    event.stopPropagation(); // Prevent click from bubbling up
    accountDropdown.classList.toggle('active'); 

    // Toggle the arrow direction
    if (accountDropdown.classList.contains('active')) {
        dropdownArrow.classList.remove('fa-caret-down');
        dropdownArrow.classList.add('fa-caret-up');
    } else {
        dropdownArrow.classList.remove('fa-caret-up');
        dropdownArrow.classList.add('fa-caret-down');
    }
});

// Close dropdown when clicking outside
document.addEventListener('click', function(event) {
    if (!accountDropdown.contains(event.target)) {
        accountDropdown.classList.remove('active'); 
        dropdownArrow.classList.remove('fa-caret-up');
        dropdownArrow.classList.add('fa-caret-down');
    }
});

    </script>
      
    <!-- ============================================================ -->
    <!-- ============================================================ -->
    <!-- ============================================================ -->
    
  </body>

</html>
