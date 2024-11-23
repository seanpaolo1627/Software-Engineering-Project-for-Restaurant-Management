


/*============================================================*/

function showNotification(message) {
    const container = document.getElementById('notification-container');

    // Check if there is an existing notification
    const existingNotification = container.querySelector('.notification');
    if (existingNotification) {
        existingNotification.remove(); // Remove it if found
    }

    // Create the new notification element
    const notification = document.createElement('div');
    notification.className = 'notification';
    notification.textContent = message;

    // Append the notification to the container
    container.appendChild(notification);

    // Start the slide-in animation
    requestAnimationFrame(() => {
        notification.classList.add('show');
    });

    // Automatically remove the notification after 3.5 seconds
    setTimeout(() => {
        notification.classList.remove('show'); // Trigger slide-out
        setTimeout(() => notification.remove(), 600); // Wait for slide-out to complete
    }, 3500);
}

/*============================================================*/

// Function to show the modal
function openModal(modalID, element) {
    const modal = document.getElementById(modalID);
    modal.style.display = "flex"; // Show modal using flex

    if (modalID === 'menu-item-modal' && element) {
        const card = element.closest('.card');
        const menuItemName = card.querySelector('h1').textContent;
        const menuItemPrice = card.querySelector('p').textContent;

        // Update modal content
        document.getElementById('menu-item-header').textContent = menuItemName;
        document.getElementById('menu-item-price').textContent = menuItemPrice;
    }
}

// Function to close the modal
function closeModal(modalID) {
    const modal = document.getElementById(modalID);
    modal.style.display = "none"; // Hide the modal
}

/*============================================================*/

function incrementQuantity(button) {
    const input = button.closest('.quantity-controls').querySelector('.quantity-input');
    const card = button.closest('.card');
    const menuItemName = card.querySelector('h1').textContent;
    let currentQty = parseInt(input.value) || 0;

    if (currentQty < 10) {
        input.value = currentQty + 1;
        updateOrderSummary(card, input.value);  
        updateCardStyle(card, input.value);

        // Check if the item is newly added
        if (currentQty === 0) {
            showNotification(`'${menuItemName}' added to your order.`);
        }
    } else {
        showNotification("Quantity cannot exceed 10.");
    }
}

/*============================================================*/

function decrementQuantity(button) {
    const card = button.closest('.card');
    const input = card.querySelector('.quantity-input');
    const menuItemName = card.querySelector('h1').textContent;
    let currentQty = parseInt(input.value) || 0;

    if (currentQty > 1) {
        input.value = currentQty - 1;
        updateOrderSummary(card, input.value);
        updateCardStyle(card, input.value);
    } else if (currentQty === 1) {
        input.value = '';
        showNotification(`'${menuItemName}' removed from your order.`);
        updateOrderSummary(card, input.value);
        updateCardStyle(card, input.value);
    }
}

/*============================================================*/

function updateOrderSummary(card, quantity) {
    const orderSummaryTable = document.querySelector('.order-summary-table tbody');
    const menuItemName = card.querySelector('h1').textContent;
    const priceText = card.querySelector('p').textContent;
    const price = parseFloat(priceText.replace('Php ', ''));
    const subtotal = price * quantity;
    const existingRow = orderSummaryTable.querySelector(`tr[data-item-id="${card.id}"]`);

    // Remove the empty message row if it exists
    const emptyMessageRow = orderSummaryTable.querySelector('.empty-message-row');
    if (emptyMessageRow) {
        emptyMessageRow.style.display = 'none';
    }

    if (quantity > 0) {
        if (existingRow) {
            // Update existing row
            existingRow.querySelector('.order-qty').textContent = quantity;
            existingRow.querySelector('.order-subtotal').textContent = `Php ${subtotal}`;
        } else {
            // Add new row
            const newRow = document.createElement('tr');
            newRow.setAttribute('data-item-id', card.id);
            newRow.innerHTML = `
                <td>${menuItemName}</td>
                <td>${priceText}</td>
                <td class="order-qty">${quantity}</td>
                <td class="order-subtotal">Php ${subtotal}</td>
            `;
            orderSummaryTable.insertBefore(newRow, orderSummaryTable.querySelector('.total-row'));
        }
    } else if (existingRow) {
        // Remove row if quantity is 0
        existingRow.remove();
    }

    updateTotalAmount();
}

/*============================================================*/

function updateTotalAmount() {
    const orderSummaryTable = document.querySelector('.order-summary-table tbody');
    const subtotals = orderSummaryTable.querySelectorAll('.order-subtotal');
    let total = 0;

    subtotals.forEach(subtotal => {
        total += parseFloat(subtotal.textContent.replace('Php ', ''));
    });

    const totalAmountCell = orderSummaryTable.querySelector('.total-amount');
    totalAmountCell.textContent = `Php ${total}`;

    // Check if there are no items in the order
    const emptyMessageRow = orderSummaryTable.querySelector('.empty-message-row');
    const confirmOrderButton = document.querySelector('.confirm-order-button');

    if (subtotals.length === 0) {
        if (emptyMessageRow) {
            emptyMessageRow.style.display = 'table-row';
        }
        confirmOrderButton.style.display = 'none'; // Hide the confirm order button
    } else {
        if (emptyMessageRow) {
            emptyMessageRow.style.display = 'none';
        }
        confirmOrderButton.style.display = 'block'; // Show the confirm order button
    }
}

/*============================================================*/
  
// Function to update card style based on quantity
function updateCardStyle(card, quantity) {
    const imgContainer = card.querySelector('.card-img-container img'); // Get the card image container
    const input = card.querySelector('.quantity-input'); 
    const categoryId = card.classList[1]; // Extract the category ID

    // Store the previous quantity in a data attribute
    const previousQuantity = input.dataset.previousQuantity || 0;

    if (quantity) {
        card.style.backgroundColor = 'rgb(255, 189, 67)'; 
        card.style.borderColor = '#ff6600'; 
        imgContainer.style.borderColor = '#ff6600'; 
        input.style.backgroundColor = 'orange';
        input.style.borderColor = '#ff6600';

        // Increment badge only if transitioning from 0 to 1 or more
        if (previousQuantity == 0) {
            updateBadge(categoryId, 1);
        }
    } else {
        card.style.backgroundColor = ''; 
        card.style.borderColor = ''; 
        imgContainer.style.borderColor = ''; 
        input.style.backgroundColor = '';
        input.style.borderColor = '';

        // Decrement badge only if transitioning to 0
        if (previousQuantity > 0) {
            updateBadge(categoryId, -1);
        }
    }

    // Update the previous quantity
    input.dataset.previousQuantity = quantity;
}

/*============================================================*/

// Function to update the badge count based on category ID
function updateBadge(categoryId, change) {
    const menuBtn = document.querySelector(`.verticalmenu-btn[id="${categoryId}"]`);
    
    if (menuBtn) { // Ensure the element is a vertical menu button
        const badge = menuBtn.querySelector('.notification-badge');
        let currentCount = parseInt(badge.textContent) || 0;

        currentCount += change; // Update the count based on the change
        badge.textContent = currentCount; // Set the new count

        // Show the badge if count is 1 or more, otherwise hide it
        badge.style.display = currentCount > 0 ? 'inline-block' : 'none';
    } else {
        console.warn(`No vertical menu button found with ID: ${categoryId}`);
    }
}

/*============================================================*/