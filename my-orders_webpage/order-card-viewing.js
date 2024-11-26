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

      const nextStatusClass = getNextStatusClass(orderData.status); // Get the next status class

      // Sample menu items for the order with clickable icon
      const menuItems = orderData.menuItems.map(item => `
        <tr class="menu-item-row">
          <td>
            <div class="icon-container">
              <span class="icon hourglass-icon" data-state="hourglass" style="display:${orderData.status === 'PREPARING' ? 'inline-block' : 'none'};">
                  <i class="fas fa-hourglass-start"></i>
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
        <button class="action-btn cancel" 
          ${orderData.status === 'COMPLETE' || orderData.status === 'CANCELED' ? 'style="display:none;"' : ''}>
          CANCEL ORDER
        </button>
      `;

      // If the order status is 'PREPARING', alternate the icons
      if (orderData.status === 'PREPARING') {
        const iconContainers = newCard.querySelectorAll('.icon-container');
        iconContainers.forEach(container => {
          let currentIconIndex = 0; // Start with the hourglass icon
          const icons = ['hourglass-icon', 'fire-icon', 'bell-icon'];

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
      attachEventListeners(newCard);
      updateCounts();
      reapplyCurrentFilter();

      // Update the update button state
      updateUpdateButtonState(newCard); // Check button state when a new card is added
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

    // Function to determine button text and class based on order status
    function getButtonText(status) {
      switch (status) {
        case 'PENDING':
          return 'Prepare';
        case 'PREPARING':
          return 'Mark Ready';
        case 'READY FOR PICKUP':
          return 'Mark Complete';
        default:
          return '';
      }
    }

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

    function getNextStatusClass(currentStatus) {
      switch (currentStatus) {
        case 'PENDING':
          return 'preparing'; // Transitioning from PENDING to PREPARING
        case 'PREPARING':
          return 'ready-for-pickup'; // Transitioning from PREPARING to READY FOR PICKUP
        case 'READY FOR PICKUP':
          return 'complete'; // Transitioning from READY FOR PICKUP to COMPLETE
        default:
          return ''; // No further transitions for COMPLETE or CANCELED
      }
    }

    // Function to add the "Revert" button with status-based class
    function getRevertButton(status) {
      let revertTo;
      let revertClass;

      switch (status) {
        case 'PREPARING':
          revertTo = 'PENDING';
          revertClass = 'status-pending';
          break;
        case 'READY FOR PICKUP':
          revertTo = 'PREPARING';
          revertClass = 'status-preparing';
          break;
        default:
          return ''; // No revert button for COMPLETE or CANCELED
      }

      return `
        <button class="action-btn revert ${revertClass}" data-revert-to="${revertTo}">
          Revert to ${revertTo.replace('_', ' ')}
        </button>
      `;
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

    const ordersContainer = document.querySelector('.orders');
    const filterButtons = document.querySelectorAll('.filter-btn');

    // Attach event listeners to existing order cards
    const orderCards = document.querySelectorAll('.order-card');
    orderCards.forEach(card => attachEventListeners(card));

    // Attach event listeners to filter buttons
    filterButtons.forEach(button => {
      button.addEventListener('click', () => filterOrders(button));
    });

    // Function to handle the revert button click
    function handleRevert(event) {
      const button = event.target;
      if (button.classList.contains('revert')) {
        const newStatus = button.getAttribute('data-revert-to'); // Get the status to revert to
        const card = button.closest('.order-card'); // Get the associated order card
        updateOrderStatus(card, newStatus, newStatus.replace('_', ' '), getButtonText(newStatus)); // Update status
      }
    }

    // Function to handle the update button click
    function handleUpdate(card) {
      // const updateButton = card.querySelector('.action-btn.update.ready-for-pickup');
      
      // // Check if the update button is disabled
      // if (updateButton.disabled) {
      //   showNotification('Please ensure all menu items are marked as ready before proceeding.'); 
      //   return; // Exit function if button is disabled
      // }
      // // Check if all menu items have the bell icon displayed
      // if (!areAllIconsBell(card)) {
      //     showNotification('Please ensure all menu items are marked as ready before proceeding.'); 
      //     return; // Exit function if not all icons are bell
      // }

      const status = card.getAttribute('data-status');
      
      switch (status) {
        case 'PENDING':
          updateOrderStatus(card, 'PREPARING', 'Preparing', 'Mark Ready');
          break;
        case 'PREPARING':
          // Check if all menu items have the bell icon displayed
          if (areAllIconsBell(card)) {
            updateOrderStatus(card, 'READY FOR PICKUP', 'Ready for Pickup', 'Mark Complete');
          }
          break;
        case 'READY FOR PICKUP':
          updateOrderStatus(card, 'COMPLETE', 'Complete', null);
          break;
        default:
          console.warn('Unexpected status:', status);
          break;
      }
    }

    // Function to check if all icons are the bell icon
    function areAllIconsBell(card) {
      const menuItemRows = card.querySelectorAll('.menu-item-row');
      return Array.from(menuItemRows).every(row => {
        const icons = row.querySelectorAll('.icon');
        return icons[2].style.display === 'inline'; // Check if the bell icon is displayed
      });
    }

    // Update the update button state based on icons
    function updateUpdateButtonState(card) {
      const updateButton = card.querySelector('.action-btn.update.ready-for-pickup');
      if (updateButton) {
        if (areAllIconsBell(card)) {
          updateButton.disabled = false;
          updateButton.style.filter = 'none'; // Reset filter to normal
        } else {
          updateButton.disabled = true;
          updateButton.style.filter = 'brightness(0.5)'; // Lower brightness
        }
      }
    }

    // Function to handle the cancel button click
    function handleCancel(card) {
      // Show the modal
      const modal = document.getElementById('cancel-order-modal');
      modal.style.display = 'flex';

      // Get the confirm and close buttons
      const confirmCancelButton = document.getElementById('confirmCancel');
      const closeModalButton = document.getElementById('closeModal');

      // Confirm cancel action
      confirmCancelButton.onclick = function() {
        updateOrderStatus(card, 'CANCELED', 'Canceled', null);
        modal.style.display = 'none'; // Close the modal
      };

      // Close modal without action
      closeModalButton.onclick = function() {
        modal.style.display = 'none'; // Close the modal
      };
    }

    function attachEventListeners(card) {
      const revertButton = card.querySelector('.action-btn.revert'); 
      const updateButton = card.querySelector('.action-btn.update');
      const cancelButton = card.querySelector('.action-btn.cancel');

      if (revertButton) {
        revertButton.addEventListener('click', handleRevert);
      }

      if (updateButton) {
        updateButton.addEventListener('click', () => handleUpdate(card));
      }

      if (cancelButton) {
        cancelButton.addEventListener('click', () => handleCancel(card));
      }
    }

    function updateOrderStatus(card, newStatus, newStatusText, newButtonText) {
      card.setAttribute('data-status', newStatus);

      // Update the status span with the new status and class
      const statusSpan = card.querySelector('.status');
      statusSpan.textContent = newStatusText;
      statusSpan.className = `status ${newStatus.toLowerCase().replace(/ /g, '-')}`;

      // Update the vertical menu button color if the order is canceled
      if (newStatus === 'CANCELED') {
        const verticalMenuButton = document.getElementById(card.id.replace('order-card-id-', '')); // Get the corresponding button
        if (verticalMenuButton) {
          verticalMenuButton.style.backgroundColor = getStatusColor(newStatus); // Update the button color
        }
      }

      // Create or update the status update time element
      let statusUpdateTime = card.querySelector('.status-update-time');
      const currentTime = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }); // Get the current time in HH:MM format
      if (!statusUpdateTime) {
        // If it doesn't exist, create it
        statusUpdateTime = document.createElement('table'); // Use a table for layout
        statusUpdateTime.className = 'status-update-time';
        card.appendChild(statusUpdateTime);
      }
        
      // Append the new status update to the existing updates
      const newUpdateRow = document.createElement('tr'); // Create a new row for the update
      const statusCell = document.createElement('td'); // Create a cell for the status
      const timeCell = document.createElement('td'); // Create a cell for the time

      statusCell.textContent = `${newStatusText.toUpperCase()}`; // Set the status text
      timeCell.textContent = currentTime; // Set the current time

      // Style the cells
      statusCell.style.textAlign = 'left'; // Align status text to the left
      timeCell.style.textAlign = 'right'; // Align time text to the right
      statusCell.style.border = 'none'; // Hide border for the status cell
      timeCell.style.border = 'none'; // Hide border for the time cell

      newUpdateRow.appendChild(statusCell); // Add status cell to the row
      newUpdateRow.appendChild(timeCell); // Add time cell to the row
      statusUpdateTime.appendChild(newUpdateRow)

      // Determine the new class for the update button based on the new status
      const nextStatusClass = getNextStatusClass(newStatus);

      // Re-attach event listeners to the new buttons
      attachEventListeners(card);

      // Hide the "Cancel Order" button if the order is canceled
      const cancelButton = card.querySelector('.action-btn.cancel');
      if (cancelButton) {
        cancelButton.style.display = (newStatus === 'CANCELED') ? 'none' : 'inherit';
      }

      // Update the vertical menu button color if the order is canceled
      if (newStatus === 'CANCELED') {
          const verticalMenuButton = document.getElementById(card.id.replace('order-card-id-', '')); // Get the corresponding button
          if (verticalMenuButton) {
              verticalMenuButton.style.backgroundColor = getStatusColor(newStatus); // Update the button color
              verticalMenuButton.innerHTML = `${getStatusIcon(newStatus)} Order ID #${card.id.replace('order-card-id-', '')}`; // Update the button icon
          }
      }

      updateCounts();  // Update the filter button counts
      reapplyCurrentFilter();  // Apply the active filter to update the UI

      // Update the update button state based on icons
      updateUpdateButtonState(card);
    }

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
    addNewOrderCard(preparingOrderData);
    addNewOrderCard(readyForPickupOrderData);
    addNewOrderCard(completeOrderData);
    addNewOrderCard(canceledOrderData);

    /*============================================================*/

  });