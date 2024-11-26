document.addEventListener('DOMContentLoaded', () => {

    /*============================================================*/

    // Add event listener to close the cancel order modal when clicking outside of the modal content
    document.addEventListener('click', function(event) {
      const modal = document.getElementById('cancel-order-modal'); // Get the specific modal
      if (modal && event.target === modal) { // Check if the clicked target is the modal
          modal.style.display = 'none'; // Close the modal
      }
  });

  /*==============================*/

    function addNewOrderCard(orderData) {
      const newCard = document.createElement('div');
      newCard.className = 'order-card';
      newCard.setAttribute('data-status', orderData.status);

      const nextStatusClass = getNextStatusClass(orderData.status); // Get the next status class

      // Sample menu items for the order with clickable icon
      const menuItems = orderData.menuItems.map(item => `
        <tr class="menu-item-row" onclick="toggleIcon(event)" style="cursor: ${['PREPARING', 'COMPLETE', 'CANCELED'].includes(orderData.status) ? 'pointer' : 'default'};" data-wasted-ingredients="">
          <td>
            <div class="icon-container">
              <span class="icon hourglass-icon" data-state="hourglass" style="display:${orderData.status === 'PREPARING' ? 'inline-block' : 'none'};">
                  <i class="fas fa-hourglass-start"></i>
                  <span class="tooltip">Click to change status of menu item</span>
              </span>
              <span class="icon fire-icon" data-state="fire" style="display:none;">
                <i class="fas fa-fire"></i>
                <span class="tooltip">Click to change status of menu item</span>
              </span>
              <span class="icon bell-icon" data-state="bell" style="display:none;">
                <i class="fas fa-concierge-bell"></i>
                <span class="tooltip">Click to change status of menu item</span>
              </span>
              <span class="icon waste-icon" data-state="waste" style="display:${orderData.status === 'COMPLETE' || orderData.status === 'CANCELED' ? 'inline' : 'none'};">
                <i class="fas fa-trash-alt"></i>
                <span class="tooltip">Click to assign wasted ingredients (if any)</span>
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
            <td>${orderData.addressLine || 'N/A'}</td>
          </tr>
          <tr>
            <td>Contact Number:</td>
            <td>${orderData.contactNum || 'N/A'}</td>
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
            <td>Php ${total}</td>
          </tr>
          <tr>
            <td colspan="3" class="discount-label">Discount Code</td>
            <td>${orderData.discountCode || 'N/A'}</td>
          </tr>
        </table>
        <div class="action-buttons">
          ${getRevertButton(orderData.status)}
          <div class="right-buttons">
            <button class="action-btn update ${nextStatusClass}" 
              ${orderData.status === 'COMPLETE' || orderData.status === 'CANCELED' ? 'style="display:none;"' : ''}>
              ${getButtonText(orderData.status)}
            </button>
            <button class="action-btn cancel" 
              ${orderData.status === 'PREPARING' || orderData.status === 'READY FOR PICKUP' || orderData.status === 'COMPLETE' || orderData.status === 'CANCELED' ? 'style="display:none;"' : ''}>
              Cancel Order
            </button>
          </div>
        </div>
      `;

      ordersContainer.appendChild(newCard);
      attachEventListeners(newCard);
      updateCounts();
      reapplyCurrentFilter();

      // Update the update button state
      updateUpdateButtonState(newCard); // Check button state when a new card is added
      rearrangeOrderCards();
    }
  
    /*==============================*/

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
  
    /*==============================*/

    function toggleIcon(event) {
      const card = event.currentTarget.closest('.order-card'); // Get the card
      const status = card.getAttribute('data-status'); // Get the status of the card
    
      // Check if the status is COMPLETE or CANCELED
      if (status === 'COMPLETE' || status === 'CANCELED') {
        // Remove 'clicked-menu-item-row' class from all menu item rows
        const allMenuItemRows = document.querySelectorAll('.menu-item-row');
        allMenuItemRows.forEach(row => row.classList.remove('clicked-menu-item-row'));

        // Add 'clicked-menu-item-row' class to the current row
        const currentRow = event.currentTarget.closest('tr'); // Assuming the row is a <tr>
        currentRow.classList.add('clicked-menu-item-row');

        showWastedIngredientsModal(); // Show the modal for wasted ingredients
        return; // Exit the function to prevent further icon toggling
      }

      const icons = event.currentTarget.querySelectorAll('.icon');
      const questionIcon = icons[0]; // Question icon
      const fireIcon = icons[1];      // Fire icon
      const bellIcon = icons[2];      // Bell icon

      // Check which icon is currently displayed and toggle accordingly
      if (questionIcon.style.display !== 'none') {
          // If the question icon is displayed, switch to fire icon
          questionIcon.style.display = 'none'; // Hide question icon
          fireIcon.style.display = 'inline';    // Show fire icon
      } else if (fireIcon.style.display !== 'none') {
          // If the fire icon is displayed, switch to bell icon
          fireIcon.style.display = 'none'; // Hide fire icon
          bellIcon.style.display = 'inline'; // Show bell icon
      } else {
          // If the bell icon is displayed, reset to question mark
          bellIcon.style.display = 'none'; // Hide bell icon
          questionIcon.style.display = 'inline'; // Show question icon
      }

      updateUpdateButtonState(card); // Call update function for this card
    }
  
    /*==============================*/

    // Function to show the modal for wasted ingredients
    function showWastedIngredientsModal() {
      const modal = document.getElementById('wasted-ingredients-modal'); // Get the modal
      const tableBody = document.getElementById('wasted-ingredients-table-body'); // Get the table body

      // Clear existing rows in the modal table
      tableBody.innerHTML = '';

      // Get all rows from the ingredient table
      const ingredientTable = document.getElementById('ingredient-table');
      const ingredientRows = ingredientTable.querySelectorAll('tbody tr');

      // Get the clicked menu item row to check for existing data
      const clickedRow = document.querySelector('.clicked-menu-item-row'); // Use the new class
      let wastedIngredientsData = {};

      // Get the menu item name from the clicked row
      const menuItemName = clickedRow ? clickedRow.querySelector('.icon-container').lastChild.textContent.trim().toUpperCase() : 'MENU ITEM'; // Adjusted to use lastChild
    
      // Update the modal header
      const menuItemLabelRow = document.getElementById('menu-item-label-row');
      menuItemLabelRow.innerHTML = menuItemName; // Set the new header

      if (clickedRow) {
          const existingData = clickedRow.getAttribute('data-wasted-ingredients');
          if (existingData) {
              wastedIngredientsData = JSON.parse(existingData); // Parse existing data
          } else {
              // Initialize to empty if no data exists
              wastedIngredientsData = {};
          }
      }

      // Loop through each ingredient row to populate the modal
      ingredientRows.forEach(row => {
          const ingredientData = JSON.parse(row.getAttribute('data-ingredient')); // Get ingredient data
          const ingredientID = ingredientData.id; // Get ingredient ID
          const ingredientName = ingredientData.name; // Get ingredient name
          const ingredientUnit = ingredientData.unit; // Get ingredient unit

          // Get the quantity consumed from the ingredient list table
          const ingredientListTableBody = document.getElementById('ingredient-list-table-body');
          const ingredientListRows = ingredientListTableBody.querySelectorAll('tr');

          let quantityConsumed = 0; // Initialize quantity consumed

          ingredientListRows.forEach(listRow => {
              const listData = JSON.parse(listRow.getAttribute('data-ingredient')); // Get ingredient list data
              if (listData.ingredientID === ingredientID) {
                  quantityConsumed = listData.quantityConsumed; // Get the quantity consumed
              }
          });

          // Create a new row for the modal table
          const newRow = document.createElement('tr');
          newRow.innerHTML = `
              <td>[${ingredientID}] ${ingredientName}<br><small>(${ingredientUnit})</small></td>
              <td>
                  <input type="number" min="0" placeholder="Qty" value="${wastedIngredientsData[ingredientID] || quantityConsumed}" />
                  <br>
                  <span>${ingredientUnit}</span>
              </td>
          `;
          tableBody.appendChild(newRow); // Append the new row to the table body
      });

      modal.style.display = 'flex'; // Show the modal

      // Add event listener to close the modal when clicking outside of it
      window.onclick = function(event) {
          if (event.target === modal) {
              modal.style.display = 'none'; // Close the modal
          }
      };

      document.getElementById('confirm-wasted-ingredients').addEventListener('click', confirmWastedIngredientsData);
    }
  
    /*==============================*/

    function confirmWastedIngredientsData() {
      const tableBody = document.getElementById('wasted-ingredients-table-body');
      const rows = tableBody.querySelectorAll('tr');
      
      // Create an object to hold wasted ingredients data
      const wastedIngredientsData = {};

      rows.forEach(row => {
          const ingredientCell = row.cells[0];
          const quantityInput = row.cells[1].querySelector('input[type="number"]');
  
          if (ingredientCell && quantityInput) {
              const ingredientIDMatch = ingredientCell.textContent.match(/\[(\d+)\]/);
              if (ingredientIDMatch) {
                  const ingredientID = ingredientIDMatch[1];
                  const quantityWasted = parseFloat(quantityInput.value);
                  wastedIngredientsData[ingredientID] = quantityWasted;
              }
          }
      });
  
      const clickedRow = document.querySelector('.clicked-menu-item-row');
      if (clickedRow) {
          const menuItemName = clickedRow.querySelector('.icon-container').lastChild.textContent.trim();
          const hasWastedIngredients = Object.values(wastedIngredientsData).some(value => parseFloat(value) > 0);
  
          // Show appropriate notification based on whether there are wasted ingredients
          if (hasWastedIngredients) {
              showNotification(`Successfully assigned some wasted ingredients on "${menuItemName}".`);
          } else {
              showNotification(`Successfully reset wasted ingredients for "${menuItemName}" to none.`);
          }
  
          // Store as JSON string
          clickedRow.setAttribute('data-wasted-ingredients', JSON.stringify(wastedIngredientsData));
      }
  
      updateWasteIconStyles();
  
      // Close the modal after confirming
      const modal = document.getElementById('wasted-ingredients-modal');
      modal.style.display = 'none';
  }
  
    /*==============================*/

    function updateWasteIconStyles() {
      const menuItemRows = document.querySelectorAll('.menu-item-row');
      menuItemRows.forEach(row => {
        const wasteIcon = row.querySelector('.waste-icon i');
        const wastedIngredientsData = row.getAttribute('data-wasted-ingredients');
        
        if (wastedIngredientsData) {
          const data = JSON.parse(wastedIngredientsData);
          const hasWastedIngredients = Object.values(data).some(value => parseFloat(value) > 0);
    
          if (hasWastedIngredients) {
            wasteIcon.style.border = '2px solid red';
            wasteIcon.style.color = 'red';
            wasteIcon.style.backgroundColor = 'rgb(255, 157, 157)'
          } else {
            wasteIcon.style.border = '';
            wasteIcon.style.color = '';
            wasteIcon.style.backgroundColor = '';
          }
        } else {
          wasteIcon.style.border = '';
          wasteIcon.style.color = '';
          wasteIcon.style.backgroundColor = '';
        }
      });
    }
  
    /*==============================*/

    // Function to determine button text and class based on order status
    function getButtonText(status) {
      switch (status) {
        case 'PENDING':
          return 'Prepare Order';
        case 'PREPARING':
          return 'Mark Ready';
        case 'READY FOR PICKUP':
          return 'Mark Complete';
        default:
          return '';
      }
    }
  
    /*==============================*/

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
  
    /*==============================*/

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
  
    /*==============================*/

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
  
    /*==============================*/

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
  
    /*==============================*/

    const ordersContainer = document.querySelector('.orders');
    const filterButtons = document.querySelectorAll('.filter-btn');

    // Attach event listeners to existing order cards
    const orderCards = document.querySelectorAll('.order-card');
    orderCards.forEach(card => attachEventListeners(card));

    // Attach event listeners to filter buttons
    filterButtons.forEach(button => {
      button.addEventListener('click', () => filterOrders(button));
    });
  
    /*==============================*/

    // Function to handle the revert button click
    function handleRevert(event) {
      const button = event.target;
      if (button.classList.contains('revert')) {
        const newStatus = button.getAttribute('data-revert-to'); // Get the status to revert to
        const card = button.closest('.order-card'); // Get the associated order card
        const orderId = card.querySelector('.order-header h2').textContent.match(/\d+/)[0]; // Extract order ID
        
        // Convert status to title case
        const formattedStatus = newStatus.toLowerCase().replace(/(^|\s)\S/g, letter => letter.toUpperCase());
      
        updateOrderStatus(card, newStatus, formattedStatus, getButtonText(newStatus)); // Update status
        showNotification(`Order ID #${orderId} has been reverted to "${formattedStatus}".`); // Notify user
      }
    }
  
    /*==============================*/

    // Function to handle the update button click
    function handleUpdate(card) {
      const updateButton = card.querySelector('.action-btn.update.ready-for-pickup');

      const status = card.getAttribute('data-status');
      
      // Use areAllIconsBell to check if all icons are bell icons
      if (!areAllIconsBell(card) && status === 'PREPARING') {
          showNotification('Please ensure all menu items are marked as "Ready For Pickup" before proceeding.');
          return; // Exit function if not all icons are bell icons
      }
      
      const orderId = card.querySelector('.order-header h2').textContent.match(/\d+/)[0]; // Extract order ID

      switch (status) {
          case 'PENDING':
              updateOrderStatus(card, 'PREPARING', 'Preparing', 'Mark Ready');
              showNotification(`Order ID #${orderId} has been updated to "Preparing".`);
              break;
          case 'PREPARING':
              // Check if all menu items have the bell icon displayed
              if (areAllIconsBell(card)) {
                  updateOrderStatus(card, 'READY FOR PICKUP', 'Ready for Pickup', 'Mark Complete');
                  showNotification(`Order ID #${orderId} has been updated to "Ready for Pickup".`);
              }
              break;
          case 'READY FOR PICKUP':
              updateOrderStatus(card, 'COMPLETE', 'Complete', null);
              showNotification(`Order ID #${orderId} has been updated to "Complete".`);
              break;
          default:
              console.warn('Unexpected status:', status);
              break;
      }
    }
  
    /*==============================*/

    // Function to check if all icons are the bell icon
    function areAllIconsBell(card) {
      const menuItemRows = card.querySelectorAll('.menu-item-row');
      return Array.from(menuItemRows).every(row => {
        const icons = row.querySelectorAll('.icon');
        return icons[2].style.display === 'inline'; // Check if the bell icon is displayed
      });
    }
  
    /*==============================*/

    // Update the update button state based on icons
    function updateUpdateButtonState(card) {
      const updateButton = card.querySelector('.action-btn.update.ready-for-pickup');
      if (updateButton) {
          if (areAllIconsBell(card)) {
              updateButton.style.filter = 'none'; // Reset filter to normal
              const orderId = card.querySelector('.order-header h2').textContent.match(/\d+/)[0]; // Extract order ID
              showNotification(`Order ID #${orderId} is ready to be marked as "Ready for Pickup".`); // Notify user
          } else {
              updateButton.style.filter = 'brightness(0.5)'; // Lower brightness
          }
      }
    }
  
    /*==============================*/

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

          const orderId = card.querySelector('.order-header h2').textContent.match(/\d+/)[0]; // Extract order ID
          showNotification(`Order ID #${orderId} has been canceled.`); // Notify user
      };

      // Close modal without action
      closeModalButton.onclick = function() {
          modal.style.display = 'none'; // Close the modal
      };
    }
  
    /*==============================*/

    function attachEventListeners(card) {
      const revertButton = card.querySelector('.action-btn.revert'); 
      const updateButton = card.querySelector('.action-btn.update');
      const cancelButton = card.querySelector('.action-btn.cancel');
      
      // Add click event listener to menu item rows if the status is PREPARING, COMPLETE, or CANCELED
      const status = card.getAttribute('data-status');
      if (status === 'PREPARING' || status === 'COMPLETE' || status === 'CANCELED') {
          const menuItemRows = card.querySelectorAll('.menu-item-row');
          menuItemRows.forEach(row => {
              row.addEventListener('click', toggleIcon);
          });
      }

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
  
    /*==============================*/

    function updateOrderStatus(card, newStatus, newStatusText, newButtonText) {
      card.setAttribute('data-status', newStatus);

      // Update the status span with the new status and class
      const statusSpan = card.querySelector('.status');
      statusSpan.textContent = newStatusText;
      statusSpan.className = `status ${newStatus.toLowerCase().replace(/ /g, '-')}`;

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

      // Update the action buttons dynamically
      const actionButtons = card.querySelector('.action-buttons');
      actionButtons.innerHTML = `
        ${getRevertButton(newStatus)}
        <div class="right-buttons">
          <button class="action-btn update ${nextStatusClass}" 
            ${newStatus === 'COMPLETE' || newStatus === 'CANCELED' ? 'style="display:none;"' : ''}>
            ${newButtonText || getButtonText(newStatus)}
          </button>
          <button class="action-btn cancel status-canceled"
            ${newStatus === 'COMPLETE' || newStatus === 'CANCELED' ? 'style="display:none;"' : ''}>
            Cancel Order
          </button>
        </div>
      `;

      // Re-attach event listeners to the new buttons
      attachEventListeners(card);

      const menuItemRows = card.querySelectorAll('.menu-item-row');

      if (newStatus !== 'PREPARING' && newStatus !== 'COMPLETE' && newStatus !== 'CANCELED') {
        // Hide icons and remove clickability from menu item rows
        menuItemRows.forEach(row => {
          const icons = row.querySelectorAll('.icon');
          icons.forEach(icon => {
            icon.style.display = 'none'; // Hide all icons
          });
          row.style.cursor = 'default'; // Change cursor to default
          row.removeEventListener('click', toggleIcon); // Remove click event listener
        });
      } else if (newStatus === 'PREPARING') {
        // If status is 'PREPARING', show the first icon and attach click event listeners
        menuItemRows.forEach(row => {
          const icons = row.querySelectorAll('.icon');
          // Show the question icon and hide others
          icons[0].style.display = 'inline'; // Show the question icon
          icons[1].style.display = 'none';   // Hide the fire icon
          icons[2].style.display = 'none';   // Hide the bell icon
          row.style.cursor = 'pointer'; // Change cursor to pointer
          row.addEventListener('click', toggleIcon); // Reattach click event listener
        });
      } else {
        // If status is 'COMPLETE' or 'CANCELED', show all waste icons
        menuItemRows.forEach(row => {
          const icons = row.querySelectorAll('.icon');
          // Show the waste icon and hide others
          icons[3].style.display = 'inline'; // Show the waste icon
          row.style.cursor = 'pointer'; // Change cursor to pointer
          row.addEventListener('click', toggleIcon); // Reattach click event listener
        });
      }

      updateCounts();  // Update the filter button counts
      reapplyCurrentFilter();  // Apply the active filter to update the UI

      // Update the update button state based on icons
      updateUpdateButtonState(card);
    }
  
    /*==============================*/

    function updateCountsDisplay() {
      const statuses = ['ALL', 'PENDING', 'PREPARING', 'READY FOR PICKUP', 'COMPLETE', 'CANCELED'];
      statuses.forEach(status => {
        const count = status === 'ALL' 
          ? document.querySelectorAll('.order-card').length 
          : document.querySelectorAll(`.order-card[data-status="${status}"]`).length;
        document.getElementById(`count-${status}`).textContent = count;
      });
    }
  
    /*==============================*/

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
  
    /*==============================*/

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

    // Fetch orders from the server
    fetch('management.php?action=get_orders')
      .then(response => response.json())
      .then(data => {
          if (data.success) {
              // Add each order card using the formatted data from the server
              data.orders.forEach(orderData => {
                  addNewOrderCard(orderData);
              });
          } else {
              console.error('Failed to load orders:', data.message);
          }
      })
      .catch(error => console.error('Error loading orders:', error));

    /*============================================================*/

  });