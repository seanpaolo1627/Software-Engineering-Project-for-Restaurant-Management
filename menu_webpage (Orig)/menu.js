
    
    /*============================================================*/

    // Function to handle toggling "Add to Order" and "Remove from Order"
    function toggleOrder(button) {
        var card = button.closest('.card');
        var mealName = card.querySelector('h2').textContent.trim();
        var priceStr = card.querySelector('p').textContent;
        var price = parseFloat(priceStr.replace('Php', '').trim());
        var table = document.querySelector('.order_table tbody');
        var rows = table.querySelectorAll('tr');
        var isMealInOrder = false;

        // Check if the meal is already in the order
        rows.forEach(function (row) {
            var rowMealName = row.querySelector('td:first-child').textContent.trim().split('\n')[0];
            if (rowMealName === mealName) {
                isMealInOrder = true;
                row.remove(); // Remove the row if the meal is already in the order
            }
        });

        // Toggle button text and color
        if (!isMealInOrder) {
            button.textContent = 'Remove from Order';
            button.style.backgroundColor = '#cb1c1c';
            button.style.border = '2px solid #931717'; 
        } else {
            button.textContent = 'Add to Order';
            button.style.backgroundColor = '#e66e12';
            button.style.border = '2px solid #9d4d10'; 
        }

        // If the meal is not in the order, add it
        if (!isMealInOrder) {
            var newRow = document.createElement('tr');
            newRow.innerHTML = 
            `
              <td>
                  ${mealName}
                  <br>
                  <button class="remove-from-order-row-btn"> Remove </button>
              </td>
              <td>Php ${price.toFixed(2)}</td>
              <td>
                  <button class="quantity-btn-decrease" onclick="decrementQuantity(this)"> - </button>
                  <span class="quantity">1</span>
                  <button class="quantity-btn-increase" onclick="incrementQuantity(this)"> + </button>
              </td>
              <td>Php <span class="subtotal">${price.toFixed(2)}</span></td>
            `;
            table.appendChild(newRow); // Append the new row to the table
            
            // Attach event listener to the "Remove" button of the newly added row
            var removeButton = newRow.querySelector('.remove-from-order-row-btn');
            removeButton.addEventListener('click', function() {
                removeFromOrder(this); // Pass reference to button
            });
        }

        // Update total
        updateTotal();

        // Show or hide the "Confirm Order" button based on the number of rows in the order table
        var confirmButton = document.querySelector('.confirm_button');
        confirmButton.style.display = table.rows.length > 0 ? 'block' : 'none';
    }
    
    /*============================================================*/

    // Function to handle removing an item from the order
    function removeFromOrder(button) {
        var row = button.closest('tr');
        var mealName = row.querySelector('td:first-child').textContent.trim().split('\n')[0];
        var cards = document.querySelectorAll('.card');
        
        // Remove the row from the table
        row.remove();
        updateTotal(); // Update total after removing the item

        // Show or hide the "Confirm Order" button based on the number of rows in the order table
        var table = document.querySelector('.order_table tbody');
        var confirmButton = document.querySelector('.confirm_button');
        confirmButton.style.display = table.rows.length > 0 ? 'block' : 'none';

        // Loop through cards to find the one with matching meal name
        cards.forEach(function(card) {
            if (card.querySelector('h2').textContent.trim().split('\n')[0] === mealName) {
                // Update the "Add to Order" button on the card
                var addToOrderButton = card.querySelector('.add-to-order');
                addToOrderButton.textContent = 'Add to Order';
                addToOrderButton.style.backgroundColor = '#e66e12';
                addToOrderButton.style.border = '2px solid #9d4d10'; 
            }
        });
    }
    
    /*============================================================*/

    // Function to increment quantity
    function incrementQuantity(button) {
        var quantityElement = button.parentElement.querySelector('.quantity');
        var quantity = parseInt(quantityElement.textContent);
        
        if (quantity < 10) {
            quantity++;
            quantityElement.textContent = quantity;
    
            updateSubtotal(button);
            updateTotal();
        } else {
            alert("Quantity cannot exceed 10.");
        }
    }
    
    // Function to decrement quantity
    function decrementQuantity(button) {
        var quantityElement = button.parentElement.querySelector('.quantity');
        var quantity = parseInt(quantityElement.textContent);
        
        if (quantity > 1) {
            quantity--;
            quantityElement.textContent = quantity;
    
            updateSubtotal(button);
            updateTotal();
        } else {
            alert("Quantity cannot be less than 1.");
        }
    }
    
    /*============================================================*/

    // Function to update subtotal
    function updateSubtotal(button) {
        var row = button.closest('tr');
        var price = parseFloat(row.querySelector('td:nth-child(2)').textContent.replace('Php ', ''));
        var quantity = parseInt(row.querySelector('.quantity').textContent);
        var subtotal = price * quantity;
        row.querySelector('.subtotal').textContent = subtotal.toFixed(2);
    }

    // Function to update total
    function updateTotal() {
        var subtotalElements = document.querySelectorAll('.subtotal');
        var total = 0;
        subtotalElements.forEach(function (element) {
            total += parseFloat(element.textContent);
        });
        document.getElementById('total').textContent = 'Php ' + total.toFixed(2);
    }
    
    /*============================================================*/

    // Open the modal
    function openOrderModal() {
        var modal = document.getElementById('orderModal');
        modal.style.display = "block";
    }

    // Close the modal
    function closeOrderModal() {
        var modal = document.getElementById('orderModal');
        modal.style.display = "none";
    }
    
    /*============================================================*/

    // Open the modal
    function openMenuItemModal() {
        var modal = document.getElementById('menuItemModal');
        modal.style.display = "block";
    }

    // Close the modal
    function closeMenuItemModal() {
        var modal = document.getElementById('menuItemModal');
        modal.style.display = "none";
    }
    
    /*============================================================*/

    // Function to submit the order
    function submitOrder() {
        
        closeOrderModal();

        const container = document.querySelector('.container_displayBox');
        container.scrollTop = 0;
        
        // Get all "Remove" buttons within table rows
        const removeButtons = document.querySelectorAll(".order_table tbody .remove-from-order-row-btn");
      
        // Loop through each button and trigger a click event
        removeButtons.forEach(button => button.click());

        var ordertype = document.getElementById('order-type').value;
        var customername = document.getElementById('customer-name').value;
        var address = document.getElementById('address').value;
        var phone = document.getElementById('phone').value;
        var paymentmethod = document.getElementById('payment-method').value;

        // Delay for 2 seconds before showing the alert and processing the order
        setTimeout(() => {
            alert("Your order for '" + ordertype + "' has been submitted. We will deliver to: \n\nCustomer Name: " + customername + "\nAddress Line: " + address + "\nPhone: " + phone + "\n\nOrder ID: KAPS2003\n" + "Payment Method: " + paymentmethod);
        }, 1000);
        
        document.getElementById('orderForm').reset()

        // Set the default category to display on page load
        var defaultCategory = document.querySelector('#caras-specials-btn');
        if (defaultCategory) {
            toggle(defaultCategory); // Trigger click event for the default category button
        }

    }
    
    /*============================================================*/

    // Function to handle toggling menu categories
    function toggleMenu(category) {

      // Hide all cards 
      document.querySelectorAll('.card').forEach(function(card) {
          card.style.display = 'none';
      });

      // Hide all linebreakers
      document.querySelectorAll('.linebreaker').forEach(function(linebreaker) {
          linebreaker.style.display = 'none';
      });
      
      // Update menu label
      let menuLabel = document.querySelector('.menu_label');
        if (category === 'caras-specials') {
            menuLabel.textContent = "CARA'S SPECIALS";
            document.querySelectorAll('.linebreaker').forEach(function(linebreaker) {
            linebreaker.style.display = 'block';
      });
        } else if (category === 'delighted-bites') {
            menuLabel.textContent = "DELIGHTED BITES";
        } else {
            menuLabel.textContent = category.toUpperCase();
        }

      // Show cards of the selected category
      document.querySelectorAll('.card.' + category).forEach(function(card) {
          card.style.display = 'flex';
      });

    }
    
    /*============================================================*/

    // Function to handle toggling menu categories
    function toggle(button) {
        // Remove 'active' class from all buttons
        var buttons = document.querySelectorAll('.verticalmenu-btn');
        buttons.forEach(function(btn) {
            btn.classList.remove('active');
        });

        // Add 'active' class to the clicked button
        button.classList.add('active');

        // Extract category name from button id
        var category = button.id.replace('-btn', ''); // Remove the '-btn' suffix
        toggleMenu(category); // Call toggleMenu with the category name

        const container = document.querySelector('.container_displayBox');
        container.scrollTop = 0;
    }
    
    /*============================================================*/

    // Add event listener to the caras-specials button
    document.querySelector('#caras-specials-btn').addEventListener('click', function() {
        toggleMenu('caras-specials');
    });

    // Add event listener to the appetizer button
    document.querySelector('#appetizers-btn').addEventListener('click', function() {
        toggleMenu('appetizers');
    });

    // Add event listener to the platters button
    document.querySelector('#platters-btn').addEventListener('click', function() {
        toggleMenu('platters');
    });

    // Add event listener to the sizzles button
    document.querySelector('#sizzles-btn').addEventListener('click', function() {
        toggleMenu('sizzles');
    });

    // Add event listener to the soup button
    document.querySelector('#soup-btn').addEventListener('click', function() {
        toggleMenu('soup');
    });

    // Add event listener to the pasta button
    document.querySelector('#pasta-btn').addEventListener('click', function() {
        toggleMenu('pasta');
    });

    // Add event listener to the sandwiches button
    document.querySelector('#sandwiches-btn').addEventListener('click', function() {
        toggleMenu('sandwiches');
    });

    // Add event listener to the frosties button
    document.querySelector('#frosties-btn').addEventListener('click', function() {
        toggleMenu('frosties');
    });

    // Add event listener to the beverages button
    document.querySelector('#beverages-btn').addEventListener('click', function() {
        toggleMenu('beverages');
    });

    // Add event listener to the delighted-bites button
    document.querySelector('#delighted-bites-btn').addEventListener('click', function() {
        toggleMenu('delighted-bites');
    });

    // Add event listener to the bundles button
    document.querySelector('#bundles-btn').addEventListener('click', function() {
        toggleMenu('bundles');
    });
    
    /*============================================================*/