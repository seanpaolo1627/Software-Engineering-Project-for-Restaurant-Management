document.addEventListener('DOMContentLoaded', function() {
    // Initialize order management
    const filterButtons = document.querySelectorAll('.filter-btn');
    filterButtons.forEach(button => {
        button.addEventListener('click', () => {
            filterButtons.forEach(btn => btn.classList.remove('active'));
            button.classList.add('active');
            const status = button.getAttribute('data-status');
            loadOrders(status);
        });
    });

    // Load initial orders and counts
    const activeStatusButton = document.querySelector('.filter-btn.active');
    const initialStatus = activeStatusButton ? activeStatusButton.getAttribute('data-status') : 'ALL';
    loadOrders(initialStatus);

    // Initialize account dropdown functionality
    const accountDropdown = document.querySelector('.account-dropdown');
    if (accountDropdown) {
        accountDropdown.addEventListener('click', function() {
            this.querySelector('.dropdown-content').classList.toggle('show');
        });
    }

    // Initialize modal close functionality
    const modal = document.getElementById('cancel-order-modal');
    const closeBtn = document.getElementById('closeModal');

    closeBtn.onclick = () => {
        modal.style.display = 'none';
    };

    window.onclick = (event) => {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    };
});

/**
 * Loads orders based on the provided status.
 * @param {string} status - The status to filter orders by.
 */
function loadOrders(status = 'ALL') {
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

                // Process all orders
                data.orders.forEach(order => {
                    const orderData = {
                        id: order.id,
                        status: order.status,
                        statusText: order.statusText,
                        orderDateTime: order.orderDateTime,
                        orderType: order.orderType,
                        total: order.total,
                        discountCode: order.discountCode,
                        menuItems: order.menuItems,
                        customerInfo: {
                            name: order.customerName,
                            phone: order.contactInfo?.phone || 'N/A',
                            email: order.contactInfo?.email || 'N/A',
                            address: order.contactInfo?.address || 'N/A'
                        }
                    };

                    addNewOrderCard(orderData);
                });

                updateCounts();
                reapplyCurrentFilter();
                rearrangeOrderCards();
            } else {
                console.error('Failed to load orders:', data.message);
                showNotification(`Error: ${data.message}`, 'error');
            }
        })
        .catch(error => {
            console.error('Error loading orders:', error);
            showNotification('An error occurred while loading orders.', 'error');
        });
}

/**
 * Creates and appends a new order card to the orders container.
 * @param {Object} orderData - The data of the order.
 */
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

    // Generate menu items HTML
    const menuItems = orderData.menuItems.map(item => `
        <tr class="menu-item-row">
            <td>${item.name}</td>
            <td>Php ${item.price}</td>
            <td>${item.quantity}</td>
            <td>Php ${item.subtotal}</td>
        </tr>
    `).join('');

    newCard.innerHTML = `
        <div class="order-header">
            <h2>Order ID #${orderData.id}</h2>
            <span class="status ${orderData.status.toLowerCase().replace(/_/g, '-')}">
                ${orderData.statusText}
            </span>
        </div>

        <table class="order-card-details">
            <tr>
                <td>Order Date & Time:</td>
                <td>${orderData.orderDateTime || 'N/A'}</td>
            </tr>
            <tr>
                <td>Order Type:</td>
                <td>${orderData.orderType || 'N/A'}</td>
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
        <div class="action-buttons">
            <div class="right-buttons">
                ${orderData.status === 'PENDING' || orderData.status === 'PREPARING' ? `
                    <button class="action-btn cancel">Cancel Order</button>
                ` : ''}
            </div>
        </div>
    `;

    ordersContainer.appendChild(newCard);
    attachEventListeners(newCard);
}

/**
 * Attaches event listeners to buttons within an order card.
 * @param {HTMLElement} card - The order card element.
 */
function attachEventListeners(card) {
    const cancelBtn = card.querySelector('.action-btn.cancel');
    if (cancelBtn) {
        cancelBtn.addEventListener('click', () => {
            showCancelConfirmation(card.getAttribute('data-id'));
        });
    }
}

/**
 * Shows the cancel confirmation modal.
 * @param {number} orderId - The ID of the order to cancel.
 */
function showCancelConfirmation(orderId) {
    const modal = document.getElementById('cancel-order-modal');
    const confirmBtn = document.getElementById('confirmCancel');

    modal.style.display = 'block';

    confirmBtn.onclick = () => {
        cancelOrder(orderId);
        modal.style.display = 'none';
    };
}

/**
 * Sends a request to cancel an order.
 * @param {number} orderId - The ID of the order to cancel.
 */
function cancelOrder(orderId) {
    fetch('my-orders.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'update_order_status',
            orderId: orderId,
            status: 'CANCELED'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Order canceled successfully.', 'success');
            loadOrders(document.querySelector('.filter-btn.active').getAttribute('data-status'));
        } else {
            showNotification(`Failed to cancel order: ${data.message}`, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred while canceling the order.', 'error');
    });
}

/**
 * Displays a notification message.
 * @param {string} message - The message to display.
 * @param {string} type - The type of notification ('success' or 'error').
 */
function showNotification(message, type = 'success') {
    const container = document.getElementById('notification-container');
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;
    container.appendChild(notification);

    setTimeout(() => {
        notification.remove();
    }, 3000);
}

/**
 * Updates the order counts displayed on the filter buttons.
 */
function updateCounts() {
    fetch('my-orders.php?action=get_orders_counts')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const counts = data.counts;
                Object.keys(counts).forEach(status => {
                    const countElement = document.getElementById(`count-${status}`);
                    if (countElement) {
                        countElement.textContent = counts[status];
                    }
                });

                // Update 'ALL' count
                const allCount = counts['PENDING'] + counts['COMPLETE'] + counts['CANCELED'];
                const allCountElement = document.getElementById('count-ALL');
                if (allCountElement) {
                    allCountElement.textContent = allCount;
                }
            } else {
                console.error('Failed to fetch order counts:', data.message);
            }
        })
        .catch(error => console.error('Error fetching counts:', error));
}

/**
 * Reapplies the current filter to the displayed orders.
 */
function reapplyCurrentFilter() {
    const activeFilter = document.querySelector('.filter-btn.active');
    if (activeFilter) {
        const status = activeFilter.getAttribute('data-status');
        const cards = document.querySelectorAll('.order-card');
        cards.forEach(card => {
            if (status === 'ALL' || card.getAttribute('data-status') === status) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
    }
}

/**
 * Rearranges the order cards in descending order by Order ID.
 */
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

/**
 * Fetches and updates order counts for filter buttons.
 */
function updateCounts() {
    fetch('my-orders.php?action=get_orders_counts')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const counts = data.counts;
                // Update specific status counts
                ['PENDING', 'COMPLETE', 'CANCELED'].forEach(status => {
                    const countElement = document.getElementById(`count-${status}`);
                    if (countElement) {
                        countElement.textContent = counts[status] || 0;
                    }
                });

                // Update 'ALL' count
                const allCount = (counts.PENDING || 0) + (counts.COMPLETE || 0) + (counts.CANCELED || 0);
                const allCountElement = document.getElementById('count-ALL');
                if (allCountElement) {
                    allCountElement.textContent = allCount;
                }
            } else {
                console.error('Failed to fetch order counts:', data.message);
            }
        })
        .catch(error => console.error('Error fetching counts:', error));
}