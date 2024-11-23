/*============================================================*/

function clearFormFields(tableId,formId,ifAlert) {
    // Get the form element by ID
    const form = document.getElementById(formId);

    if (form) {
        // Select all input elements within the form and set their value to an empty string
        form.querySelectorAll('input').forEach(input => {
            input.value = '';
        });

        // Select all select elements within the form and set their value to the first option
        form.querySelectorAll('select').forEach(select => {
            select.selectedIndex = 0;
        });

        // Select all textarea elements within the form and set their value to an empty string
        form.querySelectorAll('textarea').forEach(textarea => {
            textarea.value = '';
        });

        // Check if the form is "menu-item-form"
        if (formId === "menu-item-form") {
            // Remove the image source from the imgBox_Display
            const previewImage = document.getElementById("preview-image");
            if (previewImage) {
                previewImage.src = "../img_placeholder.png";  // Set back to placeholder or empty image
            }

            // Remove all rows from the ingredient-list-table
            const ingredientListTableBody = document.getElementById("ingredient-list-table-body");
            if (ingredientListTableBody) {
                ingredientListTableBody.innerHTML = "";  // Clear all rows
            }
        }

        // Scroll the grandparent container of the form back to the top with smooth animation
        const grandParentContainer = form.parentElement?.parentElement;
        if (grandParentContainer) {
            grandParentContainer.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }

        // Remove "clickedTableRow" class from the previously clicked row in the specified table
        const table = document.getElementById(tableId);
        if (table) {
            const previouslyClickedRow = table.querySelector(".clickedTableRow");
            if (previouslyClickedRow) {
                previouslyClickedRow.classList.remove("clickedTableRow");
            }

            // Uncheck all checkboxes in each row of the specified table
            const checkboxes = table.querySelectorAll('input[type="checkbox"]');
            checkboxes.forEach(checkbox => {
                checkbox.checked = false; // Uncheck the checkbox
            });
        }
    }

    if(ifAlert == "YES") {
        showNotification(`Form cleared, row deselected, and checkboxes unchecked!`);
    }
}

/*============================================================*/

function formValidity(formId, excludeIds = []) {
    // Get form element
    const selectedForm = document.getElementById(formId);
    const formElements = selectedForm.elements; // Get all form elements

    // Check validity for all form elements except those in the excludeIds
    for (const element of formElements) {
        if (!excludeIds.includes(element.id) && !element.checkValidity()) {
            element.reportValidity(); // Show browser validation message
            return false; // Prevent further execution if any field is invalid
        }
    }

    return true; // All fields are valid
}

/*============================================================*/

function highlightClickedTableRow(tableId, row) {
    // Get the table by the provided tableId
    const table = document.getElementById(tableId);

    // Check if the clicked row already has the class
    if (row.classList.contains("clickedTableRow")) {
        // If it does, remove the class
        row.classList.remove("clickedTableRow");

        // Construct the form ID by replacing "-table" with "-form"
        const formId = tableId.replace("-table", "-form");
        clearFormFields(tableId,formId);
    } else {
        // Find the previously clicked row within the specified table
        const previouslyClickedRow = table.querySelector(".clickedTableRow");
        if (previouslyClickedRow) {
            previouslyClickedRow.classList.remove("clickedTableRow");
        }

        // Add "clickedTableRow" class to the currently clicked row
        row.classList.add("clickedTableRow");
    }
}

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

function repopulateComboBoxFromTable(tableID, dataName, comboBoxID) {
    // Get the table and combo box elements
    const table = document.getElementById(tableID);
    const comboBox = document.getElementById(comboBoxID);

    // Remove all options from the combo box except the first one (index 0)
    while (comboBox.options.length > 1) {
        comboBox.remove(1);  // Remove from index 1 onwards, keeping index 0
    }

    // Get all rows in the table body
    const rows = table.querySelectorAll("tbody tr");
    rows.forEach(row => {
        // Access the data stored in the row's custom attribute
        const rowData = JSON.parse(row.getAttribute(dataName));

        // Assuming rowData is an array with the ID at index 0 and name at index 1
        const IDForValue = rowData.id; 
        const valueToAdd = rowData.name;

        // Add new option to the combo box
        comboBox.add(new Option(valueToAdd, IDForValue));
    });
}

/*============================================================*/

function sortTableByColumn(tableId, selectId, button, buttonClicked) {
    const table = document.getElementById(tableId);
    const select = document.getElementById(selectId);
    const selectedColumnIndex = select.value;  // Get the selected column index from the dropdown
    let symbol = button.textContent.trim();  // Get the symbol from the button's text content

    let ascending;

    if (buttonClicked === "YES") {
        // Toggle the button symbol and determine the sort order
        if (symbol === '🡹') {
            button.textContent = '🡻';
            ascending = false;
        } else {
            button.textContent = '🡹';
            ascending = true;
        }
    } else {
        // Do not change the symbol, just determine the sort order from the current symbol
        ascending = (symbol === '🡹');
    }

    // const tbody = table.querySelector("tbody");
    // const rows = Array.from(tbody.querySelectorAll("tr")); // Get all rows in the tbody

    // // Sort the rows based on the text content of the selected column
    // rows.sort((rowA, rowB) => {
    //     const cellA = rowA.cells[selectedColumnIndex].textContent.trim().toLowerCase();
    //     const cellB = rowB.cells[selectedColumnIndex].textContent.trim().toLowerCase();

    //     if (!isNaN(cellA) && !isNaN(cellB)) {
    //         // Sort numerically if both values are numbers
    //         return ascending ? cellA - cellB : cellB - cellA;
    //     }

    //     if (cellA < cellB) {
    //         return ascending ? -1 : 1;
    //     }
    //     if (cellA > cellB) {
    //         return ascending ? 1 : -1;
    //     }
    //     return 0;
    // });

    // // Reorder the rows in the tbody
    // rows.forEach(row => tbody.appendChild(row));
}

/*============================================================*/

 // Generalized toggle function
 function toggleInputField(button, inputID) {
    const inputField = document.getElementById(inputID);
    
    inputField.disabled = !inputField.disabled; // Toggle the disabled state
    inputField.value = '';
    button.textContent = inputField.disabled ? 'Fill Form with ID' : 'Disable ID Input'; // Update button text
}

/*============================================================*/

function fillForm(inputID, tableID, dataName, formID) {
    // Find the input element
    const inputElement = document.getElementById(inputID);
    
    // Check if the input element exists
    if (!inputElement) {
        console.error(`Input element with ID '${inputID}' not found.`);
        return; // Exit the function if the input element is not found
    }

    const inputValue = inputElement.value.trim(); // Trim any extra spaces
    const originalValue = inputValue; // Save the original value of the ID input

    // Click the closest "Clear Form" button if input is invalid
    if (!inputValue || isNaN(inputValue)) {
        console.warn(`Invalid input: '${inputValue}'. Clicking Clear Form.`);
        const clearButton = Array.from(inputElement.closest('.container').querySelectorAll('button'))
            .find(button => button.textContent.trim() === "Clear Form");
        
        if (clearButton) {
            console.log("Clicking the Clear Form button."); // Debugging log
            clearButton.click(); // Click the clear button
        } else {
            console.warn("Clear Form button not found."); // Debugging log
        }
        showNotification(`Please enter a valid numeric ID to auto-fill the form.`);
        inputElement.value = originalValue; // Restore the original value
        return;
    }

    const table = document.getElementById(tableID);
    const rows = table.querySelectorAll("tbody tr");

    // Click the closest "Clear Form" button if no rows are found
    if (rows.length === 0) {
        console.warn(`No rows found in the table. Clicking Clear Form.`);
        const clearButton = Array.from(inputElement.closest('.container').querySelectorAll('button'))
            .find(button => button.textContent.trim() === "Clear Form");
        
        if (clearButton) {
            console.log("Clicking the Clear Form button."); // Debugging log
            clearButton.click(); // Click the clear button
        } else {
            console.warn("Clear Form button not found."); // Debugging log
        }
        showNotification(`There are no rows to search in the table.`);
        inputElement.value = originalValue; // Restore the original value
        return;
    }

    let found = false;
    rows.forEach(row => {
        try {
            const rowData = JSON.parse(row.getAttribute(`data-${dataName}`)); // Parse data-stock
            
            // Ensure both values are of the same type
            if (String(rowData.id) === inputValue) {
                if (row.classList.contains("clickedTableRow")) {
                    showNotification(`Form is already filled with ID: '${inputValue}'`);
                    found = true;
                    return;
                }

                row.click(); // Programmatically click the row
                showNotification(`Form filled with ID: '${inputValue}'`);
                found = true;
            }
        } catch (error) {
            console.error("Error parsing row data:", error);
        }
    });

    // Click the closest "Clear Form" button if no record is found
    if (!found) {
        console.warn(`No existing record with the ID: '${inputValue}'. Clicking Clear Form.`);
        const clearButton = Array.from(inputElement.closest('.container').querySelectorAll('button'))
            .find(button => button.textContent.trim() === "Clear Form");
        
        if (clearButton) {
            console.log("Clicking the Clear Form button."); // Debugging log
            clearButton.click(); // Click the clear button
        } else {
            console.warn("Clear Form button not found."); // Debugging log
        }
        showNotification(`No existing record with the ID: '${inputValue}'`);
    }

    // Restore the original value after clearing
    inputElement.value = originalValue; // Restore the original value
}

/*============================================================*/

function setComboBoxValue(comboBoxId, valueToMatch) {
    const comboBox = document.getElementById(comboBoxId);
    const options = comboBox.options;
    
    for (let i = 0; i < options.length; i++) {
        if (options[i].textContent === valueToMatch) {
            comboBox.selectedIndex = i;
            return;
        }
    }
}

/*============================================================*/

function getSelectedComboBoxText(comboBoxId) {
    const comboBox = document.getElementById(comboBoxId);
    return comboBox.options[comboBox.selectedIndex].textContent;
}

/*============================================================*/

function animateRowHighlight(row) {
    row.classList.add("highlight-animation");
    row.focus()
    setTimeout(() => row.classList.remove("highlight-animation"), 2000);
}

/*============================================================*/

// function deleteSelectedTableRow(tableID, dataName) {
//     // Get the table by ID
//     const table = document.getElementById(tableID);

//     // Find the currently selected row
//     const selectedRow = table.querySelector(".clickedTableRow");

//     // Check if a row is selected
//     if (!selectedRow) {
//         showNotification(`Select a row to delete from the table.`);
//         return;
//     }

//     // Grab the selected row's data attribute and its ID
//     const selectedRowData = JSON.parse(selectedRow.getAttribute(`data-${dataName}`));
//     const selectedRowID = parseInt(selectedRowData.id); // Ensure ID is a number
    
//     // Check if the table is the ingredient-table
//     if (tableID === "ingredient-table") {
//         // Get the quantity cell and check its class
//         const quantityCell = selectedRow.querySelector("td:last-child span");
//         if (quantityCell && quantityCell.classList.contains('status-lowstockth')) {
//             updateBadge('ingredientPage', -1);
//         }
//     }

//     // Remove the selected row
//     selectedRow.remove();
//     clearFormFields(tableID, `${tableID.replace('-table', '-form')}`);

//     // Show notification for deletion
//     showNotification(`Record ID: '${selectedRowID}' deleted successfully!`);

//     // Update IDs for rows with higher IDs than the deleted one
//     const remainingRows = Array.from(table.querySelectorAll("tbody tr"));
//     remainingRows.forEach(row => {
//         const rowData = JSON.parse(row.getAttribute(`data-${dataName}`));
//         const rowID = parseInt(rowData.id);

//         if (rowID > selectedRowID) {
//             const newID = rowID - 1;

//             // Update the data attribute with the new ID
//             rowData.id = newID;
//             row.setAttribute(`data-${dataName}`, JSON.stringify(rowData));

//             // Find the first cell with textContent matching the current ID
//             for (const cell of row.cells) {
//                 if (parseInt(cell.textContent) === rowID) {
//                     cell.textContent = newID; // Update the cell's content
//                     break; // Stop searching after the first match
//                 }
//             }
//         }
//     });

//     // Call repopulateComboBoxFromTable based on tableID
//     switch (tableID) {
//         case "menu-category-table":
//             repopulateComboBoxFromTable("menu-category-table", "data-menu-category", "menu-category-combobox");
//             break;
//         case "ingredient-table":
//             repopulateComboBoxFromTable("ingredient-table", "data-ingredient", "ingredient-name-combobox");
//             break;
//         case "ingredient-category-table":
//             repopulateComboBoxFromTable("ingredient-category-table", "data-ingredient-category", "ingredient-category-combobox");
//             break;
//         case "ingredient-unit-table":
//             repopulateComboBoxFromTable("ingredient-unit-table", "data-ingredient-unit", "ingredient-unit-combobox");
//             break;
//     }
// }

/*============================================================*/

// Function to show the modal
function openModal(modalID) {
    const modal = document.getElementById(modalID);
    modal.style.display = "flex"; // Show modal using flex
}

// Function to close the modal
function closeModal(modalID) {
    const modal = document.getElementById(modalID);
    modal.style.display = "none"; // Hide the modal
}

/*============================================================*/

function formattedIngredientIDWithExtra(ingredientID,notInnerHTML) {
    const table = document.getElementById("ingredient-table");
    const rows = table.querySelectorAll("tbody tr");

    for (const row of rows) {
        const ingredientData = JSON.parse(row.getAttribute("data-ingredient"));

        if (ingredientData.id === ingredientID) {
            const { id, name, unit } = ingredientData;
            if(notInnerHTML) {
                return `[${id}] ${name} <br><small>(${unit})</small>`;
            } else {
                return `[${id}] ${name} (${unit})`;
            }
        }
    }
}

/*============================================================*/

function grabSpecificDataFromID(pageID, ID, dataAttributeName) {
    const table = document.getElementById(`${pageID}-table`);
    const rows = table.querySelectorAll("tbody tr");

    for (const row of rows) {
        const rowData = JSON.parse(row.getAttribute(`data-${pageID}`));

        if (rowData.id === ID) {
            // Use bracket notation to access the dynamic property
            return rowData[dataAttributeName];
        }
    }

    // Return null if no matching ID is found
    return null;
}

/*============================================================*/

// Function to update the badge count based on category ID
function updateBadge(ID, change) {
    const menuBtn = document.querySelector(`.verticalmenu-btn[id="${ID}"]`);
    
    if (menuBtn) { // Ensure the element is a vertical menu button
        const badge = menuBtn.querySelector('.notification-badge');
        let currentCount = parseInt(badge.textContent) || 0;

        currentCount += change; // Update the count based on the change
        badge.textContent = currentCount; // Set the new count

        // Show the badge if count is 1 or more, otherwise hide it
        badge.style.display = currentCount > 0 ? 'inline-block' : 'none';
    } else {
        console.warn(`No vertical menu button found with ID: ${ID}`);
    }
}

/*============================================================*/












/*============================================================*/

function addSelectedIngredientsToStockInTable(tableBodyID, ifStockIN) {
    const tableBody = document.getElementById(tableBodyID);
    const stockInTableBody = document.querySelector('#stock-in-n-out-table tbody');

    // Get all rows from the provided table
    const rows = tableBody.querySelectorAll('tr');

    // Check if at least one checkbox is selected
    const hasChecked = Array.from(rows).some(row => {
        const checkbox = row.querySelector('input[type="checkbox"]');
        return checkbox && checkbox.checked;
    });

    if (!hasChecked) {
        showNotification(`Select at least one ingredient to stock in/out.`);
        return; // Stop further execution if no ingredients are selected
    }

    openModal("stock-in-n-out-modal");

    // Clear previous entries from the stock-in table
    stockInTableBody.innerHTML = '';

    // Update header and button text based on ifStockIN
    const header = document.getElementById('stock-in-n-out-header');
    const columnHead = document.getElementById('stock-in-n-out-column-head');
    const confirmButton = document.getElementById('confirmed-ings-to-stock-btn');

    if (ifStockIN) {
        header.textContent = "INGREDIENTS TO STOCK IN";
        columnHead.innerHTML = "QUANTITY<br>ADDED"; // Use innerHTML for line break
        confirmButton.textContent = "Confirm Stock In";
    } else {
        header.textContent = "INGREDIENTS TO STOCK OUT";
        columnHead.innerHTML = "QUANTITY<br>REMOVED"; // Use innerHTML for line break
        confirmButton.textContent = "Confirm Stock Out";
    }

    rows.forEach(row => {
        const checkbox = row.querySelector('input[type="checkbox"]');

        let stockID = "?";

        if (checkbox && checkbox.checked) {
            let rowData, ingredientID;

            // Check the source table and extract the appropriate data
            if (tableBodyID === "stock-table_body") {
                rowData = JSON.parse(row.getAttribute("data-stock"));
                stockID = rowData.id;
                ingredientID = rowData.ingredientID;
            } else if (tableBodyID === "ingredient-table_body") {
                rowData = JSON.parse(row.getAttribute("data-ingredient"));
                ingredientID = rowData.id;
            }

            // Create a new row for the stock-in/out table
            const newRow = document.createElement('tr');

            // Add the stock ID (first column)
            const stockIDCell = document.createElement('td');
            stockIDCell.textContent = stockID; 
            newRow.appendChild(stockIDCell);

            // Add the ingredient ID and name with unit as the second column
            const ingredientNameCell = document.createElement('td');
            ingredientNameCell.textContent = `[${ingredientID}] ${grabSpecificDataFromID('ingredient', ingredientID, 'name')}`;
            newRow.appendChild(ingredientNameCell);

            // Add input for Quantity Added/Removed
            const quantityCell = document.createElement('td');
            quantityCell.innerHTML = `<input type="number" min="1" placeholder="Qty" required>`; // Placeholder as "Qty"
            newRow.appendChild(quantityCell);

            // Use grabSpecificDataFromID to get the unit
            const ingredientUnit = grabSpecificDataFromID('ingredient', ingredientID, 'unit');
        
            // Add the unit below the quantity input
            const unitSpan = document.createElement("span");
            unitSpan.textContent = ingredientUnit;
            quantityCell.appendChild(document.createElement("br")); // Line break for better layout
            quantityCell.appendChild(unitSpan);

            // Add input for Expiration Date
            const expirationCell = document.createElement('td');
            const expirationInput = document.createElement('input');
            expirationInput.type = 'date';
            expirationInput.required = true;

            // Set the minimum date to today
            const today = new Date();
            const todayString = today.toISOString().split('T')[0]; // Format to YYYY-MM-DD
            expirationInput.min = todayString;

            // Auto-fill expiration date and disable the input if not stocking in
            if (ifStockIN) {
                if (tableBodyID === "stock-table_body") {
                    expirationInput.value = rowData.expirationDate; // Auto-fill with expiration date from data-stock
                    expirationInput.disabled = true; // Disable the expiration date input
                }
            } else {
                // Auto-fill and disable for stock out
                expirationInput.value = rowData.expirationDate; // Auto-fill with expiration date from data-stock
                expirationInput.disabled = true; // Disable the expiration date input
            }

            expirationCell.appendChild(expirationInput);
            newRow.appendChild(expirationCell);

            // Add input for Days Until Expiration Alert
            const daysAlertCell = document.createElement('td');
            const daysAlertInput = document.createElement('input');
            daysAlertInput.type = 'number';
            daysAlertInput.min = 0;
            daysAlertInput.placeholder = "Days";

            // Auto-fill with alert threshold
            if (tableBodyID === "stock-table_body") {
                daysAlertInput.value = ifStockIN ? rowData.expirationAlertTH : rowData.expirationAlertTH; // Auto-fill with alert threshold from data-stock
            }
            daysAlertCell.appendChild(daysAlertInput);
            newRow.appendChild(daysAlertCell);

            // Add input for Remarks
            const remarksCell = document.createElement('td');
            remarksCell.innerHTML = `<input type="text" placeholder="Remarks">`;
            newRow.appendChild(remarksCell);

            // Append the new row to the stock-in/out table
            stockInTableBody.appendChild(newRow);
        }
    });
}

/*============================================================*/

function confirmedIngsToStock(button) { 
    const stockTableBody = document.querySelector('#stock-table tbody');
    const stockInNOutTableBody = document.querySelector('#stock-in-n-out-table tbody');
    const stockTransactionTableBody = document.querySelector('#stock-transaction-table_body');

    const today = new Date().toISOString().split('T')[0]; // Get today's date in YYYY-MM-DD format
    const isStockOut = button.textContent.trim() === "Confirm Stock Out"; // Check if it's stock-out action

    let hasInvalidInput = false; // Flag for invalid input
    let stocksOutInsufficient = false; // Flag for insufficient stock for stock out
    let firstInvalidInput; // To store the first invalid input element for focusing

    // Use 'for...of' loop to allow early exit
    for (const row of stockInNOutTableBody.rows) {
        const generatedStockID = stockTableBody.rows.length + 1; // Auto-generate Stock ID

        const stockID = row.cells[0].innerText.trim();
        const ingredientID = parseInt(row.cells[1].innerHTML.match(/\[(.*?)\]/)[1]); // Ingredient ID extracted from format
        const quantityAddedInput = row.cells[2].querySelector('input');
        const expirationInput = row.cells[3].querySelector('input');
        const expirationAlertInput = row.cells[4].querySelector('input');
        const remarksInput = row.cells[5].querySelector('input');

        const quantityAdded = parseInt(quantityAddedInput.value) || 0;
        const expirationDate = expirationInput.value || '';
        const expirationAlertTH = parseInt(expirationAlertInput.value) || 0;
        const remarks = remarksInput.value || '';

        // Validate quantity added
        if (!quantityAddedInput.checkValidity()) {
            hasInvalidInput = true;
            firstInvalidInput = firstInvalidInput || quantityAddedInput; // Focus on the first invalid input
            quantityAddedInput.reportValidity(); // Focus on the invalid quantity input
            showNotification(`Invalid quantity for Ingredient ID: '${ingredientID}'`);
            break; // Stop the loop on invalid input
        }

        // Validate expiration date
        if (!expirationInput.checkValidity() || new Date(expirationInput.value) < new Date()) {
            hasInvalidInput = true;
            firstInvalidInput = firstInvalidInput || expirationInput; // Focus on the first invalid input
            expirationInput.reportValidity(); // Focus on the invalid expiration date input
            showNotification(`Invalid expiration date for Ingredient ID: '${ingredientID}'`);
            break; // Stop the loop on invalid input
        }

        // Continue with the logic if inputs are valid
        let currentQtyRemaining = 0;
        let updatedQtyRemaining = quantityAdded;

        // Search for an existing row with the same stock ID
        let existingRow = Array.from(stockTableBody.rows).find(
            stockRow => stockRow.cells[1].textContent.trim() === stockID
        );

        if (existingRow) {
            // Update existing row's quantity and data attribute
            currentQtyRemaining = parseInt(existingRow.cells[3].textContent);
            updatedQtyRemaining = isStockOut
                ? currentQtyRemaining - quantityAdded
                : currentQtyRemaining + quantityAdded;

            // Prevent negative quantity for stock out
            if (isStockOut && updatedQtyRemaining < 0) {
                stocksOutInsufficient = true;
                quantityAddedInput.setCustomValidity(`Insufficient quantity available for Ingredient ID: '${ingredientID}'`);
                quantityAddedInput.reportValidity(); // Focus on the insufficient quantity input to stock out
                showNotification(`Insufficient quantity available for Ingredient ID: '${ingredientID}'`);
                break; // Stop processing if not enough stock is available
            }

            existingRow.cells[3].textContent = 
                `${updatedQtyRemaining} ${grabSpecificDataFromID('ingredient', ingredientID, 'unit')}`;

            const stockData = JSON.parse(existingRow.getAttribute('data-stock'));
            stockData.quantityRemaining = updatedQtyRemaining;
            stockData.expirationDate = expirationDate || stockData.expirationDate; 
            stockData.expirationAlertTH = expirationAlertTH || stockData.expirationAlertTH; 

            existingRow.setAttribute('data-stock', JSON.stringify(stockData));

            // Highlight the updated row for visual feedback
            animateRowHighlight(existingRow);
        } else {
            // Create a new row if no match found
            const newRow = document.createElement('tr');
            newRow.innerHTML = `
                <td><input type="checkbox"></td>
                <td>${generatedStockID}</td>
                <td>${formattedIngredientIDWithExtra(ingredientID, true)}</td>
                <td>${quantityAdded + ' ' + grabSpecificDataFromID('ingredient', ingredientID, 'unit')}</td>
                <td>AVAILABLE</td>
            `;
            newRow.setAttribute('data-stock', JSON.stringify({
                id: generatedStockID,
                ingredientID: ingredientID,
                originalQuantity: quantityAdded,
                quantityRemaining: quantityAdded,
                stockInDate: today,
                expirationDate,
                expirationAlertTH,
                stockStatus: 'AVAILABLE'
            }));

            newRow.addEventListener('click', (event) => {
                const clickedCell = event.target.closest('td');
                const firstCell = newRow.querySelector('td:first-child');
                if (clickedCell === firstCell) return; 
                stock_tableRowClicked('data-stock', newRow);
                highlightClickedTableRow('stock-table', newRow);
            });

            stockTableBody.appendChild(newRow);
        }

        // Create a stock transaction row
        const txnRow = document.createElement('tr');
        const transactionID = stockTransactionTableBody.rows.length + 1;
        const transactionType = isStockOut ? 'STOCK OUT' : 'STOCK IN';
        const qtyChange = isStockOut ? `- ${quantityAdded}` : `+ ${quantityAdded}`;

        txnRow.innerHTML = `
            <td>${transactionID}</td>
            <td>${existingRow ? existingRow.cells[1].textContent : generatedStockID}</td>
            <td>${formattedIngredientIDWithExtra(ingredientID, true)}</td>
            <td>${qtyChange + ' ' + grabSpecificDataFromID('ingredient', ingredientID, 'unit')}</td>
            <td>${transactionType}</td>
        `;
        txnRow.setAttribute('data-stock-transaction', JSON.stringify({
            id: transactionID,
            stock_ID: existingRow ? existingRow.cells[1].textContent : generatedStockID,
            ingredientID: ingredientID,
            quantity_added: isStockOut ? 0 : quantityAdded,
            quantity_removed: isStockOut ? quantityAdded : 0,
            transaction_type: transactionType,
            transactionDateTime: new Date().toISOString(),
            remarks,
            order_ID: '',
            emp_ID: ''
        }));

        txnRow.addEventListener('click', (event) => {
            stockTransaction_tableRowClicked('data-stock-transaction', txnRow);
            highlightClickedTableRow('stock-transaction-table', txnRow);
        });

        stockTransactionTableBody.appendChild(txnRow);

        updateIngredientQuantity(ingredientID, quantityAdded, transactionType);
    }

    if (hasInvalidInput || stocksOutInsufficient) return; // Stop entire operation if any input is invalid

    // Clear stock-in table and uncheck all checkboxes
    document.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
        checkbox.checked = false;
    });

    stockInNOutTableBody.innerHTML = ''; // Clear the modal content
    closeModal('stock-in-n-out-modal'); // Close the modal
}

/*============================================================*/

function updateIngredientQuantity(ingredientID, quantityChange, transactionType) {
    const ingredientTable = document.getElementById("ingredient-table");
    const rows = Array.from(ingredientTable.querySelectorAll("tbody tr"));

    // Find the row with matching ingredient ID
    const matchingRow = rows.find(row => {
        const ingredientData = JSON.parse(row.getAttribute("data-ingredient"));
        return ingredientData.id === ingredientID;
    });

    if (!matchingRow) {
        console.warn(`Ingredient with ID ${ingredientID} not found in the table.`);
        return;
    }

    // Parse the current quantity from the row's data attribute
    const ingredientData = JSON.parse(matchingRow.getAttribute("data-ingredient"));
    let currentQuantity = ingredientData.quantity;

    // Adjust the quantity based on the transaction type
    const newQuantity = transactionType === 'STOCK IN' 
        ? currentQuantity + quantityChange 
        : currentQuantity - quantityChange;

    if (newQuantity < 0) {
        showNotification(`Insufficient quantity for Ingredient ID: '${ingredientID}' to stock out.`);
        return; // Stop if the quantity becomes negative
    }

    // Update the data attribute with the new quantity
    ingredientData.quantity = newQuantity;
    matchingRow.setAttribute("data-ingredient", JSON.stringify(ingredientData));

    // Update the displayed quantity in the table
    const quantityCell = matchingRow.querySelector("td:last-child span");
    const unit = ingredientData.unit;
    quantityCell.textContent = `${newQuantity} ${unit}`;
    
    // Store the previous class
    const previousClass = quantityCell.className;

    // Determine the new class based on the quantity
    const lowStockThreshold = ingredientData.lowStockTH;
    const mediumStockThreshold = ingredientData.mediumStockTH;
    let newClass = 'status-highstockth'; // Default class

    if (newQuantity <= lowStockThreshold) {
        newClass = 'status-lowstockth';
    } else if (newQuantity <= mediumStockThreshold) {
        newClass = 'status-mediumstockth';
    }

    // Update the class only if it has changed
    if (previousClass !== newClass) {
        quantityCell.className = newClass;

        // Update the badge based on the class change
        if (newClass === 'status-lowstockth') {
            updateBadge('ingredientPage', 1);
        } else if (previousClass === 'status-lowstockth') {
            updateBadge('ingredientPage', -1);
        }
    }
}

/*============================================================*/

function stock_tableRowClicked(dataRow, row) {
    // Access the data stored in row's custome attribute
    const rowData = JSON.parse(row.getAttribute(dataRow));

    // Populate the form fields with the data from the clicked row
    document.getElementById('stock-id').value = rowData.id;
    document.getElementById('stock-ingredient-id').value = formattedIngredientIDWithExtra(rowData.ingredientID,false);
    document.getElementById('stock-status').value = rowData.stockStatus;
    document.getElementById('stock-original-qty').value = rowData.originalQuantity;
    document.getElementById('stock-qty-left').value = rowData.quantityRemaining;
    document.getElementById('stock-in-date').value = rowData.stockInDate;
    document.getElementById('stock-expiration-date').value = rowData.expirationDate;
    document.getElementById('stock-expiration-alert-threshold').value = rowData.expirationAlertTH;
}

/*============================================================*/

function stockTransaction_tableRowClicked(dataRow, row) {
    // Access the data stored in row's custome attribute
    const rowData = JSON.parse(row.getAttribute(dataRow));

    // Fill form inputs with transaction data
    document.getElementById('stock-transaction-id').value = rowData.id;
    document.getElementById('stock-transaction-stock-id').value = rowData.stock_ID;
    document.getElementById('stock-transaction-ingredient-id').value = formattedIngredientIDWithExtra(rowData.ingredientID,false);
    document.getElementById('stock-ingredient-qty-added').value = rowData.quantity_added;
    document.getElementById('stock-ingredient-qty-removed').value = rowData.quantity_removed;
    document.getElementById('stock-transaction-transaction-type').value = rowData.transaction_type;
    document.getElementById('stock-ingredient-transaction-date').value = rowData.transactionDateTime.split('T')[0]; // Extract the date only
    document.getElementById('stock-ingredient-remarks').value = rowData.remarks;
    document.getElementById('stock-transaction-staff-id').value = rowData.emp_ID;
    document.getElementById('stock-transaction-order-id').value = rowData.order_ID;
}

/*============================================================*/












/*============================================================*/

function addNewStaff() {
    // Validate form fields
    if (!formValidity('staff-form')) {
        showNotification(`Fill all required fields with valid input.`);
        return;
    }

    // Get the values of the input fields
    const staffID = document.querySelector('#staff-table tbody').rows.length + 1;
    const staffFirstName = document.getElementById("staff-first-name").value;
    const staffMiddleName = document.getElementById("staff-middle-name").value || "";
    const staffLastName = document.getElementById("staff-last-name").value;
    const staffDesignation = document.getElementById("staff-designation").value;
    const staffGender = document.getElementById("staff-gender").value;
    const staffBirthdate = document.getElementById("staff-birthdate").value;
    const staffPhoneNumber = document.getElementById("staff-phonenumber").value;
    const staffAddress = document.getElementById("staff-address").value;
    const staffEmail = document.getElementById("staff-email").value;
    const staffEmailConfirm = document.getElementById("staff-email-confirm").value;
    const staffStatus = document.getElementById("staff-status").value;

    // Check if email and confirm email are the same
    if (staffEmail !== staffEmailConfirm) {
        showNotification(`Emails do not match. Ensure 'Email' and 'Confirm Email' fields are identical.`);
        return;
    }

    // Create full name in the desired format
    const staffFullName = `${staffLastName}, ${staffFirstName} ${staffMiddleName ? staffMiddleName : ''}`.trim();

    // Get the table and search for the row where the 2nd column matches the staff full name
    const table = document.getElementById("staff-table");
    const rows = table.querySelectorAll("tbody tr");

    // Check if the staff already exists in the 2nd column
    for (const row of rows) {
        const cells = row.getElementsByTagName("td");
        const existingStaffFullName = cells[1].textContent.trim();

        if (existingStaffFullName.toLowerCase() === staffFullName.toLowerCase()) {
            showNotification(`Staff Member: '${existingStaffFullName}' already exists!`);
            return; // Exit if the staff already exists
        }
    }

    // Create a new table row element
    const newRow = document.createElement("tr");

    // Create staffData object for consistent storage
    const staffData = {
        id: staffID,
        firstName: staffFirstName,
        middleName: staffMiddleName,
        lastName: staffLastName,
        designation: staffDesignation,
        gender: staffGender,
        birthdate: staffBirthdate,
        phoneNumber: staffPhoneNumber,
        address: staffAddress,
        email: staffEmail,
        status: staffStatus
    };

    // Setting staffData as a custom attribute on the row
    newRow.setAttribute("data-staff", JSON.stringify(staffData));

    // Creating cells
    const staffIDCell = document.createElement("td");
    const staffFullNameCell = document.createElement("td");
    const staffContactInfoCell = document.createElement("td");
    const staffStatusCell = document.createElement("td");

    // Setting cell contents
    staffIDCell.textContent = staffID;
    staffFullNameCell.textContent = staffFullName; // Use the formatted full name
    staffContactInfoCell.innerHTML = `${staffPhoneNumber}<br>${staffEmail}`;

    // Create the status badge
    const statusBadge = document.createElement('span');
    statusBadge.textContent = staffStatus;
    statusBadge.className = `status-${staffStatus.toLowerCase()}`;

    // Clear and append the status badge to the status cell
    staffStatusCell.className = 'status-cell'; // Ensure correct cell styling
    staffStatusCell.innerHTML = ''; // Clear any existing content
    staffStatusCell.appendChild(statusBadge);

    // Append cells to the new row
    newRow.appendChild(staffIDCell);
    newRow.appendChild(staffFullNameCell);
    newRow.appendChild(staffContactInfoCell);
    newRow.appendChild(staffStatusCell);

    // Add click event listener to the new row
    newRow.addEventListener('click', function () {
        staff_tableRowClicked('data-staff', newRow); // Call the callback function when a row is clicked
        highlightClickedTableRow('staff-table', newRow); // Call the callback function when a row is clicked
    });

    // Append the new row to the table body
    table.querySelector("tbody").appendChild(newRow);

    // Clear the input fields of the form
    clearFormFields('staff-table', 'staff-form');

    showNotification(`Staff Member: '${staffFullName}' added successfully!`);
}
  
/*============================================================*/

function staff_tableRowClicked(dataRow, row) {
    // Access the data stored in the row's custom attribute
    const rowData = JSON.parse(row.getAttribute(dataRow));

    // Set form field values using the rowData object
    document.getElementById("staff-id").value = rowData.id;
    document.getElementById("staff-status").value = rowData.status;
    document.getElementById("staff-first-name").value = rowData.firstName;
    document.getElementById("staff-middle-name").value = rowData.middleName || ''; // Default to empty if undefined
    document.getElementById("staff-last-name").value = rowData.lastName;
    document.getElementById("staff-designation").value = rowData.designation;
    document.getElementById("staff-gender").value = rowData.gender;
    document.getElementById("staff-birthdate").value = rowData.birthdate;
    document.getElementById("staff-phonenumber").value = rowData.phoneNumber;
    document.getElementById("staff-address").value = rowData.address;
    document.getElementById("staff-email").value = rowData.email;
}

/*============================================================*/

function updateSelectedStaff() {
    // Find the currently selected row
    const selectedRow = document.querySelector("#staff-table .clickedTableRow");

    // Check if a row is selected
    if (!selectedRow) {
        showNotification(`Select a row to update from the table.`);
        return;
    }

    // Validate form fields
    if (!formValidity('staff-form')) {
        showNotification(`Fill all required fields with valid input.`);
        return;
    }

    // Get the values of the input fields
    const selectedRowData = JSON.parse(selectedRow.getAttribute('data-staff'));
    const staffID = selectedRowData.id;
    
    const staffStatus = document.getElementById("staff-status").value;
    const staffFirstName = document.getElementById("staff-first-name").value;
    const staffMiddleName = document.getElementById("staff-middle-name").value;
    const staffLastName = document.getElementById("staff-last-name").value;
    const staffDesignation = document.getElementById("staff-designation").value;
    const staffGender = document.getElementById("staff-gender").value;
    const staffBirthdate = document.getElementById("staff-birthdate").value;
    const staffPhoneNumber = document.getElementById("staff-phonenumber").value;
    const staffAddress = document.getElementById("staff-address").value;
    const staffEmail = document.getElementById("staff-email").value;
    const staffEmailConfirm = document.getElementById("staff-email-confirm").value;

    // Check if email and confirm email are the same
    if (staffEmail !== staffEmailConfirm) {
        showNotification(`Emails do not match. Ensure 'Email' and 'Confirm Email' fields are identical.`);
        return;
    }

    // Update the data-staff attribute with the new data
    const staffData = {
        id: staffID,
        status: staffStatus,
        firstName: staffFirstName,
        middleName: staffMiddleName,
        lastName: staffLastName,
        designation: staffDesignation,
        gender: staffGender,
        birthdate: staffBirthdate,
        phoneNumber: staffPhoneNumber,
        address: staffAddress,
        email: staffEmail,
    };

    selectedRow.setAttribute("data-staff", JSON.stringify(staffData));

    // Create full name in the desired format
    const staffFullName = `${staffLastName}, ${staffFirstName} ${staffMiddleName ? staffMiddleName : ''}`.trim();

    // Update the displayed cell contents
    const cells = selectedRow.querySelectorAll("td");
    if (cells.length >= 4) {
        cells[0].textContent = staffID; // Update the ID cell
        cells[1].textContent = staffFullName; // Update the name cell
        cells[2].innerHTML = `${staffPhoneNumber}<br>${staffEmail}`; // Update contact info
        
        // Create the status badge (span) if it doesn't exist
        let statusBadge = cells[3].querySelector('span');
        if (!statusBadge) {
            statusBadge = document.createElement('span');
            cells[3].appendChild(statusBadge);
        }

        // Update the status badge text and class
        statusBadge.textContent = staffStatus; // Update the status text
        statusBadge.className = `status-${staffStatus.toLowerCase()}`; // Change class based on new status
        cells[3].className = 'status-cell'; // Ensure correct cell styling

        showNotification(`Staff Member: '${staffFullName}' updated successfully!`);
    }

    // Highlight the updated row for visual feedback
    animateRowHighlight(selectedRow);

    // Clear the input fields of the form
    clearFormFields('staff-table', 'staff-form');
}

/*============================================================*/











/*============================================================*/ /*============================================================*/
/*============================================================*/ /*============================================================*/
/*============================================================*/ /*============================================================*/










// /*============================================================*/

function addNewCustomer() {
    // Validate form fields
    if (!formValidity('customer-form')) {
        showNotification(`Fill all required fields with valid input.`);
        return;
    }

    // Get the values of the input fields
    const customerID = document.querySelector('#customer-table tbody').rows.length + 1
    const customerStatus = document.getElementById("customer-status").value;
    const customerFirstName = document.getElementById("customer-first-name").value;
    const customerMiddleName = document.getElementById("customer-middle-name").value || "";
    const customerLastName = document.getElementById("customer-last-name").value;
    const customerGender = document.getElementById("customer-gender").value;
    const customerBirthdate = document.getElementById("customer-birthdate").value;
    const customerPhoneNumber = document.getElementById("customer-phonenumber").value;
    const customerAddress = document.getElementById("customer-address").value;
    const customerEmail = document.getElementById("customer-email").value;
    const customerEmailConfirm = document.getElementById("customer-email-confirm").value;

    // Check if email and confirm email are the same
    if (customerEmail !== customerEmailConfirm) {
        showNotification(`Emails do not match. Ensure 'Email' and 'Confirm Email' fields are identical.`);
        return;
    }

    // Create full name in the desired format
    const customerFullName = `${customerLastName}, ${customerFirstName} ${customerMiddleName ? customerMiddleName : ''}`.trim();

    // Get the table and search for the row where the 2nd column matches the customer full name
    const table = document.getElementById("customer-table");
    const rows = table.querySelectorAll("tbody tr");

    // Check if the customer already exists in the 2nd column
    for (const row of rows) {
        const cells = row.getElementsByTagName("td");
        const existingCustomerFullName = cells[1].textContent.trim();

        if (existingCustomerFullName.toLowerCase() === customerFullName.toLowerCase()) {
            showNotification(`Customer: '${customerFullName}' already exists!`);
            return; // Exit if the customer already exists
        }
    }

    // Create a new table row element
    const newRow = document.createElement("tr");

    // Creating customerData object
    const customerData = {
        id: customerID,
        status: customerStatus,
        firstName: customerFirstName,
        middleName: customerMiddleName,
        lastName: customerLastName,
        gender: customerGender,
        birthdate: customerBirthdate,
        phoneNumber: customerPhoneNumber,
        address: customerAddress,
        email: customerEmail,
        fullName: customerFullName,
    };

    // Setting customerData as a custom attribute on the row
    newRow.setAttribute("data-customer", JSON.stringify(customerData));

    // Creating cells
    const customerIDCell = document.createElement("td");
    const customerFullNameCell = document.createElement("td");
    const customerContactInfoCell = document.createElement("td");
    const customerStatusCell = document.createElement("td");

    // Setting cell contents
    customerIDCell.textContent = customerData.id;
    customerFullNameCell.textContent = customerData.fullName; // Use the formatted full name
    customerContactInfoCell.innerHTML = `${customerData.phoneNumber}<br>${customerData.email}`;

    // Create the status badge
    const statusBadge = document.createElement('span');
    statusBadge.textContent = customerStatus;
    statusBadge.className = `status-${customerStatus.toLowerCase()}`;

    // Clear and append the status badge to the status cell
    customerStatusCell.className = 'status-cell'; // Ensure correct cell styling
    customerStatusCell.innerHTML = ''; // Clear any existing content
    customerStatusCell.appendChild(statusBadge);

    // Append cells to the new row
    newRow.appendChild(customerIDCell);
    newRow.appendChild(customerFullNameCell);
    newRow.appendChild(customerContactInfoCell);
    newRow.appendChild(customerStatusCell);

    // Add click event listener to the new row
    newRow.addEventListener('click', function () {
        customer_tableRowClicked('data-customer', newRow); // Call the callback function when a row is clicked
        highlightClickedTableRow('customer-table', newRow); // Call the callback function when a row is clicked
    });

    // Append the new row to the table body
    table.querySelector("tbody").appendChild(newRow);

    // Clear the input fields of the form
    clearFormFields('customer-table', 'customer-form');

    showNotification(`Customer: '${customerFullName}' added successfully!`);
}
  
/*============================================================*/

function customer_tableRowClicked(dataRow, row) {
    // Access the data stored in the row's custom attribute
    const rowData = JSON.parse(row.getAttribute(dataRow));

    // Populate the form fields with the values from the rowData object
    document.getElementById("customer-id").value = rowData.id;
    document.getElementById("customer-status").value = rowData.status;
    document.getElementById("customer-first-name").value = rowData.firstName;
    document.getElementById("customer-middle-name").value = rowData.middleName;
    document.getElementById("customer-last-name").value = rowData.lastName;
    document.getElementById("customer-gender").value = rowData.gender;
    document.getElementById("customer-birthdate").value = rowData.birthdate;
    document.getElementById("customer-phonenumber").value = rowData.phoneNumber;
    document.getElementById("customer-address").value = rowData.address;
    document.getElementById("customer-email").value = rowData.email;
}

/*============================================================*/

function updateSelectedCustomer() {
    // Find the currently selected row
    const selectedRow = document.querySelector("#customer-table .clickedTableRow");

    // Check if a row is selected
    if (!selectedRow) {
        showNotification(`Select a row to update from the table.`);
        return;
    }

    // Validate form fields
    if (!formValidity('customer-form')) {
        showNotification(`Fill all required fields with valid input.`);
        return;
    }

    // Get the values of the input fields
    const selectedRowData = JSON.parse(selectedRow.getAttribute('data-customer'));
    const customerID = selectedRowData.id;
    
    const customerData = {
        id: customerID,
        status: document.getElementById("customer-status").value,
        firstName: document.getElementById("customer-first-name").value,
        middleName: document.getElementById("customer-middle-name").value,
        lastName: document.getElementById("customer-last-name").value,
        gender: document.getElementById("customer-gender").value,
        birthdate: document.getElementById("customer-birthdate").value,
        phoneNumber: document.getElementById("customer-phonenumber").value,
        address: document.getElementById("customer-address").value,
        email: document.getElementById("customer-email").value,
        emailConfirm: document.getElementById("customer-email-confirm").value
    };

    // Check if email and confirm email are the same
    if (customerData.email !== customerData.emailConfirm) {
        showNotification(`Emails do not match. Ensure 'Email' and 'Confirm Email' fields are identical.`);
        return;
    }

    // Update the data-customer attribute with the new data
    selectedRow.setAttribute("data-customer", JSON.stringify(customerData));

    // Create full name in the desired format
    const customerFullName = `${customerData.lastName}, ${customerData.firstName} ${customerData.middleName ? customerData.middleName : ''}`.trim();

    // Update the displayed cell contents
    const cells = selectedRow.querySelectorAll("td");
    if (cells.length >= 4) {
        cells[0].textContent = customerData.id; // Update the ID cell
        cells[1].textContent = customerFullName; // Update the name cell
        cells[2].innerHTML = `${customerData.phoneNumber}<br>${customerData.email}`; // Update contact info
        
        // Create the status badge (span) if it doesn't exist
        let statusBadge = cells[3].querySelector('span');
        if (!statusBadge) {
            statusBadge = document.createElement('span');
            cells[3].appendChild(statusBadge);
        }

        // Update the status badge text and class
        statusBadge.textContent = customerData.status; // Update the status text
        statusBadge.className = `status-${customerData.status.toLowerCase()}`; // Change class based on new status
        cells[3].className = 'status-cell'; // Ensure correct cell styling

        showNotification(`Customer: '${customerFullName}' updated successfully!`);
    }

    // Highlight the updated row for visual feedback
    animateRowHighlight(selectedRow);

    // Clear the input fields of the form
    clearFormFields('customer-table', 'customer-form');
}

/*============================================================*/










/*============================================================*/ /*============================================================*/
/*============================================================*/ /*============================================================*/
/*============================================================*/ /*============================================================*/










/*============================================================*/

function addIngredientToList() {
    // Get the selected option from the combobox
    const ingredientComboBox = document.getElementById("ingredient-name-combobox");
    const selectedIndex = ingredientComboBox.selectedIndex;

    if (!ingredientComboBox.checkValidity()) { 
        ingredientComboBox.reportValidity();
        showNotification(`Select an ingredient to add to the recipe.`);
        return; // Exit if no ingredient is selected
    }

    // Get the selected ingredient ID from the value of the selected option
    const selectedIngredientID = parseInt(ingredientComboBox.options[selectedIndex].value);

    // Get the table and search for the row where the ingredient ID matches
    const table = document.getElementById('ingredient-table');
    const rows = table.querySelectorAll("tbody tr");
    let selectedRow = null;

    // Find the matching row by comparing the ingredient ID
    rows.forEach(row => {
        const ingredientData = JSON.parse(row.getAttribute('data-ingredient')); // Parse the data-ingredient attribute
        if (ingredientData.id === selectedIngredientID) {
            selectedRow = row;
        }
    });

    if (!selectedRow) {
        showNotification(`Ingredient not found in the Ingredient Table.`);
        return;
    }

    // Check if the ingredient is already in the "ingredient-list-table"
    const ingredientListTableBody = document.getElementById("ingredient-list-table-body");
    const ingredientListRows = ingredientListTableBody.querySelectorAll("tr");

    // Use a flag to determine if the ingredient is already added
    let isIngredientAlreadyAdded = false;

    for (const row of ingredientListRows) {
        const existingIngredientID = parseInt(row.getAttribute('data-ingredient-id')); // Compare by ID
        if (existingIngredientID === selectedIngredientID) {
            isIngredientAlreadyAdded = true; // Set the flag to true if a match is found
            break; // Exit the loop if ingredient is found
        }
    }

    if (isIngredientAlreadyAdded) {
        showNotification(`This ingredient is already in the list.`);
        return; // Exit if the ingredient is already in the table
    }

    // Add a new row to the "ingredient-list-table"
    const newRow = document.createElement("tr");

    // Create cells for the new row
    const mainIngredientCell = document.createElement("td");
    const qtyCell = document.createElement("td");
    const ingredientCell = document.createElement("td");

    // Create the checkbox for the main ingredient (1st column)
    const mainIngredientCheckbox = document.createElement("input");
    mainIngredientCheckbox.type = "checkbox";
    mainIngredientCheckbox.name = "main-ingredient-checkbox";
    mainIngredientCell.appendChild(mainIngredientCheckbox);

    // Create the input for the quantity (2nd column)
    const qtyInput = document.createElement("input");
    qtyInput.type = "number";
    qtyInput.placeholder = "Qty";
    qtyInput.min = "1";  // Ensure no negative values
    qtyInput.required = true; // Mark as required
    qtyCell.appendChild(qtyInput);

    // Use grabSpecificDataFromID to get the unit
    const ingredientUnit = grabSpecificDataFromID('ingredient', selectedIngredientID, 'unit');

    // Add the unit below the quantity input
    const unitSpan = document.createElement("span");
    unitSpan.textContent = ingredientUnit;
    qtyCell.appendChild(document.createElement("br")); // Line break for better layout
    qtyCell.appendChild(unitSpan);

    // Set the 3rd column with just the [ID] INGREDIENT
    const formattedIngredient = document.createElement("span");
    formattedIngredient.classList.add("formatted-ingredient");
    formattedIngredient.textContent = `[${selectedIngredientID}] ${grabSpecificDataFromID('ingredient', selectedIngredientID, 'name')}`;

    // Create the delete button
    const deleteButton = document.createElement("button");
    deleteButton.textContent = "Remove";
    deleteButton.classList.add("deleteButtons");
    deleteButton.onclick = function() {
        ingredientListTableBody.removeChild(newRow);  // Remove the row when "delete" is clicked
    };

    // Append the formatted ingredient and delete button to the ingredient cell
    ingredientCell.appendChild(formattedIngredient);
    ingredientCell.appendChild(deleteButton);

    // Append the cells to the new row
    newRow.appendChild(mainIngredientCell);
    newRow.appendChild(qtyCell);
    newRow.appendChild(ingredientCell);

    // Set the data-ingredient-id attribute for the new row for later checks
    newRow.setAttribute('data-ingredient-id', selectedIngredientID);

    // Append the new row to the ingredient list table body
    ingredientListTableBody.appendChild(newRow);

    // Reset the combobox to its default state
    ingredientComboBox.selectedIndex = 0;
}

/*============================================================*/

function addNewMenuItem() {
    if (!formValidity('menu-item-form', ['ingredient-name-combobox'])) {
        showNotification(`Fill all required fields with valid input.`);
        return;
    }

    // Gather data from the form fields
    const menuItemId = document.querySelector('#menu-item-table tbody').rows.length + 1;
    const menuItemImage = document.getElementById('preview-image').src;
    const menuItemName = document.getElementById('menu-item-name').value;
    const menuItemAssignedCategory_ComboBox = document.getElementById("menu-category-combobox");
    const menuItemAssignedCategory = menuItemAssignedCategory_ComboBox.options[menuItemAssignedCategory_ComboBox.selectedIndex].textContent;
    let menuItemPrice = document.getElementById('menu-item-price').value;
    const menuItemDescription = document.getElementById('menu-item-description').value;

    // Check for duplicate name in the table
    const table = document.getElementById("menu-item-table");
    const existingRows = table.querySelectorAll("tbody tr");

    for (const row of existingRows) {
        const rowData = JSON.parse(row.getAttribute("data-menu-item"));
        const existingName = rowData.name.trim();

        if (existingName.toLowerCase() === menuItemName.toLowerCase()) {
            showNotification(`Menu Item Name: '${menuItemName}' already exists!`);
            return;
        }
    }

    // Validate price input
    menuItemPrice = parseFloat(menuItemPrice);
    if (isNaN(menuItemPrice) || menuItemPrice <= 0) {
        document.getElementById('menu-item-price').reportValidity();
        showNotification(`Invalid input for Menu Item Price.`);
        return;
    }
    menuItemPrice = `Php ${menuItemPrice.toFixed(2)}`;

    // Gather ingredients from the ingredient list table
    const ingredientsTable = document.getElementById('ingredient-list-table-body');
    const ingredientsData = [];
    if (ingredientsTable.rows.length === 0) {
        document.getElementById('ingredient-list-table-body').focus();
        showNotification(`At least one ingredient is required in the recipe. Please add ingredients.`);
        return;
    }

    let mainIngredientSelected = false;
    let invalidQuantityInputField = null; // To track invalid quantity input field

    for (let row of ingredientsTable.rows) {
        const mainIngredientCheckbox = row.cells[0].querySelector('input[type="checkbox"]');
        const quantityInput = row.cells[1].querySelector('input');
        const quantityConsumed = quantityInput.value;
        const ingredientID = parseInt(row.cells[2].innerHTML.match(/\[(.*?)\]/)[1]);

        if (!quantityConsumed || isNaN(parseFloat(quantityConsumed)) || parseFloat(quantityConsumed) <= 0) {
            invalidQuantityInputField = quantityInput; // Track the invalid input field
            showNotification(`Invalid quantity for Ingredient ID: '${ingredientID}'`);
            return; // Continue checking other rows
        }

        if (mainIngredientCheckbox.checked) {
            mainIngredientSelected = true;
        }

        ingredientsData.push({
            ingredientID,
            quantityConsumed: parseFloat(quantityConsumed),
            isMainIngredient: mainIngredientCheckbox.checked
        });
    }

    if (invalidQuantityInputField) {
        invalidQuantityInputField.reportValidity(); // Focus on the invalid quantity input field
        return;
    }

    if (!mainIngredientSelected) {
        // Focus on the first checkbox if none are selected
        const firstCheckbox = ingredientsTable.querySelector('input[type="checkbox"]');
        if (firstCheckbox) {
            firstCheckbox.focus();
        }
        showNotification(`Select at least ONE KEY ingredient for this Menu Item.`);
        return;
    }

    // Create a new row for the menu item table
    const newRow = document.createElement('tr');

    // Add the data to the table row
    newRow.innerHTML = `
      <td><img src="${menuItemImage}" alt="${menuItemName}" id="menu-item-img-column"></td>
      <td>[${menuItemId}] ${menuItemName}</td>
      <td>${menuItemAssignedCategory}</td>
      <td>${menuItemPrice}</td>
    `;

    // Store all data as a data attribute for the row
    newRow.setAttribute('data-menu-item', JSON.stringify({
        imageSrc: menuItemImage,
        id: menuItemId,
        name: menuItemName,
        category: menuItemAssignedCategory,
        price: menuItemPrice,
        description: menuItemDescription,
        ingredients: ingredientsData
    }));

    // Add click event listener to the new row
    newRow.addEventListener('click', function () {
        menuItem_tableRowClicked('data-menu-item', newRow);
        highlightClickedTableRow('menu-item-table', newRow);
    });

    // Append the new row to the menu item table
    table.querySelector("tbody").appendChild(newRow);

    // Clear the input fields of the form
    clearFormFields('menu-item-table', 'menu-item-form');

    showNotification(`Menu Item: '${menuItemName}' added successfully!`);
}

/*============================================================*/

function menuItem_tableRowClicked(dataRow, row) {
    // Access the data stored in the row's custom attribute
    const rowData = JSON.parse(row.getAttribute(dataRow));

    // Populate the form fields with the data
    document.getElementById('menu-item-id').value = rowData.id;
    document.getElementById('menu-item-name').value = rowData.name;

    const categoryComboBox = document.getElementById('menu-category-combobox');
    for (let i = 0; i < categoryComboBox.options.length; i++) {
        if (categoryComboBox.options[i].textContent === rowData.category) {
            categoryComboBox.selectedIndex = i;
            break;
        }
    }

    // Remove "Php " from the price to get the numeric value
    const rawPrice = rowData.price.replace("Php ", "");
    document.getElementById('menu-item-price').value = rawPrice;
    document.getElementById('menu-item-description').value = rowData.description;

    // Set the image source in the preview image element
    const previewImage = document.getElementById('preview-image');
    previewImage.src = rowData.imageSrc;

    // Clear the existing ingredient list table
    const ingredientListTableBody = document.getElementById('ingredient-list-table-body');
    ingredientListTableBody.innerHTML = '';

    // Repopulate the ingredient list table with the data
    rowData.ingredients.forEach(ingredient => {
        // Add a new row for the ingredient
        const newRow = document.createElement('tr');

        // Create cells for the new row
        const mainIngredientCell = document.createElement('td');
        const qtyCell = document.createElement('td');
        const ingredientCell = document.createElement('td');

        // Create the checkbox for marking the main ingredient (1st column)
        const mainIngredientCheckbox = document.createElement('input');
        mainIngredientCheckbox.type = 'checkbox';
        mainIngredientCheckbox.name = 'main-ingredient-checkbox';
        mainIngredientCheckbox.checked = ingredient.isMainIngredient; // Check if it's the main ingredient
        mainIngredientCell.appendChild(mainIngredientCheckbox);

        // Create the input for the quantity (2nd column)
        const qtyInput = document.createElement('input');
        qtyInput.type = 'number';
        qtyInput.value = ingredient.quantityConsumed;
        qtyInput.min = '1';  // Ensure no negative values
        qtyInput.required = true;
        qtyCell.appendChild(qtyInput);

        // Use grabSpecificDataFromID to get the unit
        const ingredientUnit = grabSpecificDataFromID('ingredient', ingredient.ingredientID, 'unit');

        // Add the unit below the quantity input
        const unitSpan = document.createElement("span");
        unitSpan.textContent = ingredientUnit;
        qtyCell.appendChild(document.createElement("br")); // Line break for better layout
        qtyCell.appendChild(unitSpan);

        // Set the 3rd column with just the [ID] INGREDIENT
        const formattedIngredient = document.createElement('span');
        formattedIngredient.classList.add('formatted-ingredient');
        formattedIngredient.textContent = `[${ingredient.ingredientID}] ${grabSpecificDataFromID('ingredient', ingredient.ingredientID, 'name')}`;

        // Create the delete button
        const deleteButton = document.createElement('button');
        deleteButton.textContent = 'Remove';
        deleteButton.classList.add('deleteButtons');
        deleteButton.onclick = function () {
            ingredientListTableBody.removeChild(newRow);  // Remove the row when "delete" is clicked
        };

        // Append the formatted ingredient and delete button to the ingredient cell
        ingredientCell.appendChild(formattedIngredient);
        ingredientCell.appendChild(deleteButton);

        // Append the cells to the new row
        newRow.appendChild(mainIngredientCell);
        newRow.appendChild(qtyCell);
        newRow.appendChild(ingredientCell);

        // Append the new row to the ingredient list table body
        ingredientListTableBody.appendChild(newRow);
    });
}

/*============================================================*/

function updateSelectedMenuItem() {
    // Get the selected row with the class "clickedTableRow"
    const selectedRow = document.querySelector('#menu-item-table .clickedTableRow');
    
    if (!selectedRow) {
        showNotification(`Select a row to update from the table.`);
        return;
    }
    
    if (!formValidity('menu-item-form', ['ingredient-name-combobox'])) {
        showNotification(`Fill required fields with valid input.`);
        return;
    }

    // Gather updated data from the form fields
    const selectedRowData = JSON.parse(selectedRow.getAttribute('data-menu-item'));
    const updatedMenuItemId = selectedRowData.id;
    
    const updatedMenuItemImage = document.getElementById('preview-image').src;
    const updatedMenuItemName = document.getElementById('menu-item-name').value;
    const updatedMenuItemAssignedCategory_ComboBox = document.getElementById("menu-category-combobox");
    const updatedMenuItemAssignedCategory = updatedMenuItemAssignedCategory_ComboBox.options[updatedMenuItemAssignedCategory_ComboBox.selectedIndex].textContent;
    let updatedMenuItemPrice = document.getElementById('menu-item-price').value;
    const updatedMenuItemDescription = document.getElementById('menu-item-description').value;

    // Validate price input
    updatedMenuItemPrice = parseFloat(updatedMenuItemPrice); // Convert to a number (float)
    
    if (isNaN(updatedMenuItemPrice) || updatedMenuItemPrice <= 0) {
        document.getElementById('menu-item-price').reportValidity();
        showNotification(`Invalid input for Menu Item Price.`);
        return; // Exit the function if the price is invalid
    }

    // Format the price to always show two decimal places and add "Php " prefix
    updatedMenuItemPrice = `Php ${updatedMenuItemPrice.toFixed(2)}`;

    // Gather updated ingredients from the ingredient list table
    const ingredientsTable = document.getElementById('ingredient-list-table-body');
    const updatedIngredientsData = [];

    // Check if the ingredient list is empty
    if (ingredientsTable.rows.length === 0) {
        document.getElementById('ingredient-list-table-body').focus();
        showNotification(`At least one ingredient is required in the recipe. Please add ingredients.`);
        return; // Exit the function if no ingredients are provided
    }

    let mainIngredientSelected = false;
    let invalidQuantityInputField = null; // To track invalid quantity input field
    
    for (let row of ingredientsTable.rows) {
        const mainIngredientCheckbox = row.cells[0].querySelector('input[type="checkbox"]');
        const quantityInput = row.cells[1].querySelector('input');
        const quantityConsumed = quantityInput.value;
        const ingredientID = parseInt(row.cells[2].innerHTML.match(/\[(.*?)\]/)[1]);

        // Check for empty or invalid inputs
        if (!quantityConsumed || isNaN(parseFloat(quantityConsumed)) || parseFloat(quantityConsumed) <= 0) {
            invalidQuantityInputField = quantityInput; // Track the invalid input field
            showNotification(`Invalid quantity for Ingredient ID: '${ingredientID}'`);
            return; // Exit the function if the quantity is invalid
        }

        // Check if the main ingredient checkbox is checked
        if (mainIngredientCheckbox.checked) {
            mainIngredientSelected = true;
        }

        // Store the updated ingredient data (including unit)
        updatedIngredientsData.push({
            ingredientID,
            quantityConsumed: parseFloat(quantityConsumed),  // Ensure it's a number
            isMainIngredient: mainIngredientCheckbox.checked  // Track if it's the main ingredient
        });
    }

    if (invalidQuantityInputField) {
        invalidQuantityInputField.reportValidity(); // Focus on the invalid quantity input field
        return;
    }

    if (!mainIngredientSelected) {
        // Focus on the first checkbox if none are selected
        const firstCheckbox = ingredientsTable.querySelector('input[type="checkbox"]');
        if (firstCheckbox) {
            firstCheckbox.focus();
        }
        showNotification(`Select at least ONE KEY ingredient for this Menu Item.`);
        return;
    }

    // Update the row's HTML with the new values
    selectedRow.innerHTML = `
      <td><img src="${updatedMenuItemImage}" alt="${updatedMenuItemName}" id="menu-item-img-column"></td>
      <td>[${updatedMenuItemId}] ${updatedMenuItemName}</td>
      <td>${updatedMenuItemAssignedCategory}</td>
      <td>${updatedMenuItemPrice}</td>
    `;

    // Update the data attribute for the selected row
    selectedRow.setAttribute('data-menu-item', JSON.stringify({
        imageSrc: updatedMenuItemImage,
        id: updatedMenuItemId,
        name: updatedMenuItemName,
        category: updatedMenuItemAssignedCategory,
        price: updatedMenuItemPrice,
        description: updatedMenuItemDescription,
        ingredients: updatedIngredientsData  // Store the updated ingredient data including unit and main ingredient
    }));

    // Highlight the updated row for visual feedback
    animateRowHighlight(selectedRow);

    // Clear the input fields of the form
    clearFormFields('menu-item-table', 'menu-item-form');

    showNotification(`Menu Item: '${updatedMenuItemName}' updated successfully!`);
}

/*============================================================*/

function deleteSelectedMenuItem(tableID, dataName) {
    // Get the table by ID
    const table = document.getElementById(tableID);

    // Find the currently selected row
    const selectedRow = table.querySelector(".clickedTableRow");

    // Check if a row is selected
    if (!selectedRow) {
        showNotification(`Select a row to delete from the table.`);
        return;
    }

    // Grab the selected row's data attribute and its ID
    const selectedRowData = JSON.parse(selectedRow.getAttribute(`data-${dataName}`));
    const selectedRowID = parseInt(selectedRowData.id); // Ensure ID is a number

    // Remove the selected row
    selectedRow.remove();
    clearFormFields(tableID, `${tableID.replace('-table', '-form')}`);

    // Show notification for deletion
    showNotification(`Record ID: '${selectedRowID}' deleted successfully!`);

    // Update IDs for rows with higher IDs than the deleted one
    const remainingRows = Array.from(table.querySelectorAll("tbody tr"));
    remainingRows.forEach(row => {
        const rowData = JSON.parse(row.getAttribute(`data-${dataName}`));
        const rowID = parseInt(rowData.id);

        if (rowID > selectedRowID) {
            const newID = rowID - 1;

            // Update the data attribute with the new ID
            rowData.id = newID;
            row.setAttribute(`data-${dataName}`, JSON.stringify(rowData));

            // Find the first cell with textContent matching the current ID
            for (const cell of row.cells) {
                if (parseInt(cell.textContent) === rowID) {
                    cell.textContent = newID; // Update the cell's content
                    break; // Stop searching after the first match
                }
            }
        }
    });

}

/*============================================================*/










/*============================================================*/ /*============================================================*/
/*============================================================*/ /*============================================================*/
/*============================================================*/ /*============================================================*/










/*============================================================*/

function addNewMenuCategory() {

    if (!formValidity('menu-category-form')) {
        showNotification(`Fill all required fields with valid input.`);
        return;
    }

    // Get the values of the input fields
    const menuCategoryID = document.querySelector('#menu-category-table tbody').rows.length + 1
    const menuCategoryName = document.getElementById("menu-category-name").value;

    // Check for duplicate name in the table
    const table = document.getElementById("menu-category-table");
    const existingRows = table.querySelectorAll("tbody tr");

    for (const row of existingRows) {
        const rowData = JSON.parse(row.getAttribute("data-menu-category"));
        const existingName = rowData.name.trim();

        if (existingName.toLowerCase() === menuCategoryName.toLowerCase()) {
            showNotification(`Menu Category: '${menuCategoryName}' already exists!`);
            return;
        }
    }

    // Create a new table row element
    const newRow = document.createElement("tr");

    // Store category data as an object with clear keys
    const menuCategoryData = {
        id: menuCategoryID,
        name: menuCategoryName
    };

    // Set the data object as a custom attribute on the row
    newRow.setAttribute("data-menu-category", JSON.stringify(menuCategoryData));

    // Create cells for the row
    newRow.innerHTML = `
        <td>${menuCategoryID}</td>
        <td>${menuCategoryName}</td>
    `;

    // Add a click event listener to the new row
    newRow.addEventListener('click', function () {
        menuCategory_tableRowClicked('data-menu-category', newRow);
        highlightClickedTableRow('menu-category-table', newRow);
    });

    // Append the new row to the table body
    table.querySelector("tbody").appendChild(newRow);

    // Add the new category to the combobox with its ID and name
    const menuCategoryComboBox = document.getElementById("menu-category-combobox");
    menuCategoryComboBox.add(new Option(menuCategoryName, menuCategoryID));

    // Clear the input fields of the form
    clearFormFields('menu-category-table', 'menu-category-form');

    showNotification(`Menu Category: '${menuCategoryName}' added successfully!`);
}

/*============================================================*/

function menuCategory_tableRowClicked(dataRow, row) {
    // Access the data stored in the row's custom attribute as an object
    const rowData = JSON.parse(row.getAttribute(dataRow));

    // Set the input fields with the corresponding values
    document.getElementById("menu-category-id").value = rowData.id;
    document.getElementById("menu-category-name").value = rowData.name;
}

/*============================================================*/

function updateSelectedMenuCategory() {
    // Find the currently selected row
    const selectedRow = document.querySelector("#menu-category-table .clickedTableRow");

    // Check if a row is selected
    if (!selectedRow) {
        showNotification(`Select a row to update from the table.`);
        return;
    }

    // Validate the form inputs
    if (!formValidity('menu-category-form')) {
        showNotification(`Fill all required fields with valid input.`);
        return;
    }

    // Get values from the input fields
    const selectedRowData = JSON.parse(selectedRow.getAttribute('data-menu-category'));
    const menuCategoryID = selectedRowData.id;
    
    const menuCategoryName = document.getElementById("menu-category-name").value;

    // Update the data-menu-category attribute with new data as an object
    const menuCategoryData = {
        id: menuCategoryID,
        name: menuCategoryName
    };
    selectedRow.setAttribute("data-menu-category", JSON.stringify(menuCategoryData));

    // Update the visible content of the row cells
    const cells = selectedRow.querySelectorAll("td");
    if (cells.length >= 2) {
        cells[0].textContent = menuCategoryID;   // Update the ID cell
        cells[1].textContent = menuCategoryName; // Update the Name cell
    }

    // Highlight the updated row for visual feedback
    animateRowHighlight(selectedRow);

    showNotification(`Menu Category: '${menuCategoryName}' updated successfully!`);

    // Update the combo box with the new data
    repopulateComboBoxFromTable("menu-category-table", "data-menu-category", "menu-category-combobox");

    // Clear the input fields of the form
    clearFormFields('menu-category-table', 'menu-category-form');
}

/*============================================================*/

function deleteSelectedMenuCategory(tableID, dataName) {
    // Get the table by ID
    const table = document.getElementById(tableID);

    // Find the currently selected row
    const selectedRow = table.querySelector(".clickedTableRow");

    // Check if a row is selected
    if (!selectedRow) {
        showNotification(`Select a row to delete from the table.`);
        return;
    }

    // Grab the selected row's data attribute and its ID
    const selectedRowData = JSON.parse(selectedRow.getAttribute(`data-${dataName}`));
    const selectedRowID = parseInt(selectedRowData.id); // Ensure ID is a number

    // Remove the selected row
    selectedRow.remove();
    clearFormFields(tableID, `${tableID.replace('-table', '-form')}`);

    // Show notification for deletion
    showNotification(`Record ID: '${selectedRowID}' deleted successfully!`);

    // Update IDs for rows with higher IDs than the deleted one
    const remainingRows = Array.from(table.querySelectorAll("tbody tr"));
    remainingRows.forEach(row => {
        const rowData = JSON.parse(row.getAttribute(`data-${dataName}`));
        const rowID = parseInt(rowData.id);

        if (rowID > selectedRowID) {
            const newID = rowID - 1;

            // Update the data attribute with the new ID
            rowData.id = newID;
            row.setAttribute(`data-${dataName}`, JSON.stringify(rowData));

            // Find the first cell with textContent matching the current ID
            for (const cell of row.cells) {
                if (parseInt(cell.textContent) === rowID) {
                    cell.textContent = newID; // Update the cell's content
                    break; // Stop searching after the first match
                }
            }
        }
    });

    repopulateComboBoxFromTable("menu-category-table", "data-menu-category", "menu-category-combobox");

}

/*============================================================*/










/*============================================================*/ /*============================================================*/
/*============================================================*/ /*============================================================*/
/*============================================================*/ /*============================================================*/










/*============================================================*/

function addNewIngredient() {
    if (!formValidity('ingredient-form')) {
        showNotification(`Fill all required fields with valid input.`);
        return;
    }

    // Get input values
    const ingredientID = document.querySelector('#ingredient-table tbody').rows.length + 1
    const ingredientName = document.getElementById("ingredient-name").value.trim();
    const categoryComboBox = document.getElementById("ingredient-category-combobox");
    const assignedCategory = categoryComboBox.options[categoryComboBox.selectedIndex].textContent;
    const unitComboBox = document.getElementById("ingredient-unit-combobox");
    const assignedUnit = unitComboBox.options[unitComboBox.selectedIndex].textContent;
    const lowStockThreshold = parseFloat(document.getElementById("ingredient-low-stock-threshold").value);
    const mediumStockThreshold = parseFloat(document.getElementById("ingredient-medium-stock-threshold").value);
    const reorderPoint = parseFloat(document.getElementById("ingredient-reorder-point").value);

    if (lowStockThreshold >= mediumStockThreshold) {
        showNotification(`Low Stock Threshold must be less than Medium Stock Threshold.`);
        document.getElementById("ingredient-low-stock-threshold").focus();
        return;
    }

    // Default total quantity
    const totalQuantity = 0;
    const formattedQuantity = `${totalQuantity} ${assignedUnit}`;

    // Check for duplicate ingredient name in the table
    const table = document.getElementById("ingredient-table");
    const existingRows = table.querySelectorAll("tbody tr");

    for (const row of existingRows) {
        const rowData = JSON.parse(row.getAttribute("data-ingredient"));
        const existingName = rowData.name.trim();

        if (existingName.toLowerCase() === ingredientName.toLowerCase()) {
            showNotification(`Ingredient: '${ingredientName}' already exists!`);
            return;
        }
    }

    // Create a new table row
    const newRow = document.createElement("tr");

    // Store ingredient data as a custom attribute
    const ingredientData = {
        id: ingredientID,
        name: ingredientName,
        category: assignedCategory,
        unit: assignedUnit,
        lowStockTH: lowStockThreshold,
        mediumStockTH: mediumStockThreshold,
        reorderPoint: reorderPoint,
        quantity: totalQuantity
    };
    newRow.setAttribute("data-ingredient", JSON.stringify(ingredientData));

    // Create cells
    const checkBoxCell = document.createElement("td");
    const ingredientCell = document.createElement("td");
    const categoryCell = document.createElement("td");
    const quantityCell = document.createElement("td");

    // Add checkbox input
    const checkbox = document.createElement("input");
    checkbox.type = "checkbox";
    checkBoxCell.appendChild(checkbox);

    // Set cell values
    ingredientCell.innerHTML = `[${ingredientID}] ${ingredientName}<br><small>(${assignedUnit})</small>`;
    categoryCell.textContent = assignedCategory;

    // Create the status badge (span) for quantity
    const statusBadge = document.createElement('span');
    statusBadge.textContent = formattedQuantity;
    statusBadge.className = 'status-lowstockth'; // Default class for new ingredients

    // Append the status badge to the quantity cell
    quantityCell.appendChild(statusBadge);

    // Append cells to the row
    newRow.append(checkBoxCell, ingredientCell, categoryCell, quantityCell);
    
    // Add row click event
    newRow.addEventListener('click', function (event) {
        // Check if the click happened inside the first column (td:nth-child(1))
        const clickedCell = event.target.closest('td');
        const firstCell = newRow.querySelector('td:first-child');
    
        if (clickedCell === firstCell) {
            return; // Ignore the click if it happened on the first column
        }
    
        ingredient_tableRowClicked('data-ingredient', newRow); // Handle row selection
        highlightClickedTableRow('ingredient-table', newRow); // Highlight selected row
    });

    // Append the row to the table body
    table.querySelector("tbody").appendChild(newRow);

    // Clear form fields after submission
    clearFormFields('ingredient-table', 'ingredient-form');

    // Add ingredient to the combo box
    const ingredientComboBox = document.getElementById("ingredient-name-combobox");
    ingredientComboBox.add(new Option(ingredientName, ingredientID));

    // Show success notification
    showNotification(`Ingredient: '${ingredientName}' added successfully!`);
    
    updateBadge('ingredientPage', 1);
}

/*============================================================*/

function ingredient_tableRowClicked(dataRow, row) {
    // Parse the data stored in the row's custom attribute
    const rowData = JSON.parse(row.getAttribute(dataRow));

    // Populate the form fields with the ingredient data
    document.getElementById("ingredient-id").value = rowData.id;
    document.getElementById("ingredient-name").value = rowData.name;

    // Match and select the correct category in the combobox
    setComboBoxValue("ingredient-category-combobox", rowData.category);

    // Match and select the correct unit in the combobox
    setComboBoxValue("ingredient-unit-combobox", rowData.unit);

    // Populate threshold values and reorder point
    document.getElementById("ingredient-low-stock-threshold").value = rowData.lowStockTH;
    document.getElementById("ingredient-medium-stock-threshold").value = rowData.mediumStockTH;
    document.getElementById("ingredient-reorder-point").value = rowData.reorderPoint;
}

/*============================================================*/

function updateSelectedIngredient() {
    // Find the currently selected row
    const selectedRow = document.querySelector("#ingredient-table .clickedTableRow");

    // Check if a row is selected
    if (!selectedRow) {
        showNotification(`Select a row to update from the table.`);
        return;
    }

    // Validate the form inputs
    if (!formValidity('ingredient-form')) {
        showNotification(`Fill all required fields with valid input.`);
        return;
    }

    // Retrieve input values
    const selectedRowData = JSON.parse(selectedRow.getAttribute('data-ingredient'));
    const ingredientID = selectedRowData.id;
    
    const ingredientName = document.getElementById("ingredient-name").value.trim();
    const ingredientCategory = getSelectedComboBoxText("ingredient-category-combobox");
    const ingredientUnit = getSelectedComboBoxText("ingredient-unit-combobox");
    const lowStockThreshold = parseInt(document.getElementById("ingredient-low-stock-threshold").value, 10);
    const mediumStockThreshold = parseInt(document.getElementById("ingredient-medium-stock-threshold").value, 10);
    const reorderPoint = parseInt(document.getElementById("ingredient-reorder-point").value, 10);

    if (lowStockThreshold >= mediumStockThreshold) {
        showNotification(`Low Stock Threshold must be less than Medium Stock Threshold.`);
        document.getElementById("ingredient-low-stock-threshold").focus();
        return;
    }

    try {
        // Parse the existing quantity from the row's data attribute
        const existingData = JSON.parse(selectedRow.getAttribute("data-ingredient")) || {};
        const existingQuantity = existingData.quantity || 0;
        const formattedQuantity = `${existingQuantity} ${ingredientUnit}`; // E.g., "0 kg"

        // Prepare the updated ingredient data
        const updatedIngredientData = {
            id: ingredientID,
            name: ingredientName,
            category: ingredientCategory,
            unit: ingredientUnit,
            lowStockTH: lowStockThreshold,
            mediumStockTH: mediumStockThreshold,
            reorderPoint: reorderPoint,
            quantity: existingQuantity
        };

        // Update the row's data attribute
        selectedRow.setAttribute("data-ingredient", JSON.stringify(updatedIngredientData));

        // Update the visible table cells
        const cells = selectedRow.querySelectorAll("td");
        if (cells.length >= 4) {
            cells[1].innerHTML = `[${ingredientID}] ${ingredientName}<br><small>(${ingredientUnit})</small>`;
            cells[2].textContent = ingredientCategory;

            // Create or update the <span> inside the quantity cell
            let quantitySpan = cells[3].querySelector("span");
            if (!quantitySpan) {
                quantitySpan = document.createElement("span");
                cells[3].appendChild(quantitySpan);
            }
            quantitySpan.textContent = formattedQuantity;
        }

        // Highlight the updated row for visual feedback
        animateRowHighlight(selectedRow);

        // Show success notification
        showNotification(`Ingredient: '${ingredientName}' updated successfully!`);

        // Repopulate the combobox with the updated ingredient data
        repopulateComboBoxFromTable("ingredient-table", "data-ingredient", "ingredient-name-combobox");

        // Clear the form fields
        clearFormFields("ingredient-table", "ingredient-form");
    } catch (error) {
        console.error("Error updating ingredient:", error);
    }
}

/*============================================================*/

function deleteSelectedIngredient(tableID, dataName) {
    // Get the table by ID
    const table = document.getElementById(tableID);

    // Find the currently selected row
    const selectedRow = table.querySelector(".clickedTableRow");

    // Check if a row is selected
    if (!selectedRow) {
        showNotification(`Select a row to delete from the table.`);
        return;
    }

    // Grab the selected row's data attribute and its ID
    const selectedRowData = JSON.parse(selectedRow.getAttribute(`data-${dataName}`));
    const selectedRowID = parseInt(selectedRowData.id); // Ensure ID is a number
    
    // Check if the table is the ingredient-table
    if (tableID === "ingredient-table") {
        // Get the quantity cell and check its class
        const quantityCell = selectedRow.querySelector("td:last-child span");
        if (quantityCell && quantityCell.classList.contains('status-lowstockth')) {
            updateBadge('ingredientPage', -1);
        }
    }

    // Remove the selected row
    selectedRow.remove();
    clearFormFields(tableID, `${tableID.replace('-table', '-form')}`);

    // Show notification for deletion
    showNotification(`Record ID: '${selectedRowID}' deleted successfully!`);

    // Update IDs for rows with higher IDs than the deleted one
    const remainingRows = Array.from(table.querySelectorAll("tbody tr"));
    remainingRows.forEach(row => {
        const rowData = JSON.parse(row.getAttribute(`data-${dataName}`));
        const rowID = parseInt(rowData.id);

        if (rowID > selectedRowID) {
            const newID = rowID - 1;

            // Update the data attribute with the new ID
            rowData.id = newID;
            row.setAttribute(`data-${dataName}`, JSON.stringify(rowData));

            // Find the first cell with textContent matching the current ID
            for (const cell of row.cells) {
                if (parseInt(cell.textContent) === rowID) {
                    cell.textContent = newID; // Update the cell's content
                    break; // Stop searching after the first match
                }
            }
        }
    });

    repopulateComboBoxFromTable("ingredient-table", "data-ingredient", "ingredient-name-combobox");

}

/*============================================================*/










/*============================================================*/ /*============================================================*/
/*============================================================*/ /*============================================================*/
/*============================================================*/ /*============================================================*/










/*============================================================*/

function addNewIngredientCategory() {
    if (!formValidity('ingredient-category-form')) {
        showNotification(`Fill all required fields with valid input.`);
        return;
    }

    // Gather data from the form fields
    const ingredientCategoryID = document.querySelector('#ingredient-category-table tbody').rows.length + 1
    const ingredientCategoryName = document.getElementById("ingredient-category-name").value;

    // Check for duplicate name in the table
    const table = document.getElementById("ingredient-category-table");
    const existingRows = table.querySelectorAll("tbody tr");

    for (const row of existingRows) {
        const rowData = JSON.parse(row.getAttribute("data-ingredient-category"));
        const existingName = rowData.name.trim();

        if (existingName.toLowerCase() === ingredientCategoryName.toLowerCase()) {
            showNotification(`Ingredient Category: '${ingredientCategoryName}' already exists!`);
            return;
        }
    }

    // Create a new row for the ingredient category table
    const newRow = document.createElement("tr");

    // Store data as an object and attach it as a data attribute
    const ingredientCategoryData = {
        id: ingredientCategoryID,
        name: ingredientCategoryName
    };
    newRow.setAttribute("data-ingredient-category", JSON.stringify(ingredientCategoryData));

    // Populate the row's cells
    newRow.innerHTML = `
        <td>${ingredientCategoryID}</td>
        <td>${ingredientCategoryName}</td>
    `;

    // Add click event listener to the new row
    newRow.addEventListener('click', function () {
        ingredientCategory_tableRowClicked('data-ingredient-category', newRow);
        highlightClickedTableRow('ingredient-category-table', newRow);
    });

    // Append the new row to the table body
    table.querySelector("tbody").appendChild(newRow);

    // Add the new category to the combo box
    const ingredientCategoryComboBox = document.getElementById("ingredient-category-combobox");
    ingredientCategoryComboBox.add(new Option(ingredientCategoryName, ingredientCategoryID));

    // Clear the form fields
    clearFormFields('ingredient-category-table', 'ingredient-category-form');

    // Show a success notification
    showNotification(`Ingredient Category: '${ingredientCategoryName}' added successfully!`);
}

/*============================================================*/

function ingredientCategory_tableRowClicked(dataRow, row) {
    // Access the data stored in the row's custom attribute
    const rowData = JSON.parse(row.getAttribute(dataRow));

    // Set the form fields with the retrieved data
    document.getElementById("ingredient-category-id").value = rowData.id;
    document.getElementById("ingredient-category-name").value = rowData.name;
}

/*============================================================*/

function updateSelectedIngredientCategory() {
    // Find the currently selected row
    const selectedRow = document.querySelector("#ingredient-category-table .clickedTableRow");

    // Check if a row is selected
    if (!selectedRow) {
        showNotification(`Select a row to update from the table.`);
        return;
    }

    // Validate form input
    if (!formValidity('ingredient-category-form')) {
        showNotification(`Fill all required fields with valid input.`);
        return;
    }

    // Get the values from the input fields
    const selectedRowData = JSON.parse(selectedRow.getAttribute('data-ingredient-category'));
    const ingredientCategoryID = selectedRowData.id;
    
    const ingredientCategoryName = document.getElementById("ingredient-category-name").value;

    // Create an object to store the ingredient category data
    const ingredientCategoryData = {
        id: ingredientCategoryID,
        name: ingredientCategoryName
    };

    // Update the data-ingredient-category attribute with the new data
    selectedRow.setAttribute("data-ingredient-category", JSON.stringify(ingredientCategoryData));

    // Update the displayed cell contents
    const cells = selectedRow.querySelectorAll("td");
    if (cells.length >= 2) {
        cells[0].textContent = ingredientCategoryID; // Update the ID cell
        cells[1].textContent = ingredientCategoryName;    // Update the name cell
        showNotification(`Ingredient Category: '${ingredientCategoryName}' updated successfully!`);
    }

    // Highlight the updated row for visual feedback
    animateRowHighlight(selectedRow);

    // Repopulate the combo box from the table
    repopulateComboBoxFromTable("ingredient-category-table", "data-ingredient-category", "ingredient-category-combobox");

    // Clear the input fields of the form
    clearFormFields('ingredient-category-table', 'ingredient-category-form');
}

/*============================================================*/

function deleteSelectedIngredientCategory(tableID, dataName) {
    // Get the table by ID
    const table = document.getElementById(tableID);

    // Find the currently selected row
    const selectedRow = table.querySelector(".clickedTableRow");

    // Check if a row is selected
    if (!selectedRow) {
        showNotification(`Select a row to delete from the table.`);
        return;
    }

    // Grab the selected row's data attribute and its ID
    const selectedRowData = JSON.parse(selectedRow.getAttribute(`data-${dataName}`));
    const selectedRowID = parseInt(selectedRowData.id); // Ensure ID is a number

    // Remove the selected row
    selectedRow.remove();
    clearFormFields(tableID, `${tableID.replace('-table', '-form')}`);

    // Show notification for deletion
    showNotification(`Record ID: '${selectedRowID}' deleted successfully!`);

    // Update IDs for rows with higher IDs than the deleted one
    const remainingRows = Array.from(table.querySelectorAll("tbody tr"));
    remainingRows.forEach(row => {
        const rowData = JSON.parse(row.getAttribute(`data-${dataName}`));
        const rowID = parseInt(rowData.id);

        if (rowID > selectedRowID) {
            const newID = rowID - 1;

            // Update the data attribute with the new ID
            rowData.id = newID;
            row.setAttribute(`data-${dataName}`, JSON.stringify(rowData));

            // Find the first cell with textContent matching the current ID
            for (const cell of row.cells) {
                if (parseInt(cell.textContent) === rowID) {
                    cell.textContent = newID; // Update the cell's content
                    break; // Stop searching after the first match
                }
            }
        }
    });
    
    repopulateComboBoxFromTable("ingredient-category-table", "data-ingredient-category", "ingredient-category-combobox");

}

/*============================================================*/










/*============================================================*/ /*============================================================*/
/*============================================================*/ /*============================================================*/
/*============================================================*/ /*============================================================*/










/*============================================================*/

function addNewIngredientUnit() {
    if (!formValidity('ingredient-unit-form')) {
        showNotification(`Fill all required fields with valid input.`);
        return;
    }

    // Get the values of the input fields
    const ingredientUnitID = document.querySelector('#ingredient-unit-table tbody').rows.length + 1
    const ingredientUnitName = document.getElementById("ingredient-unit-name").value;

    // Check for duplicate name in the table
    const table = document.getElementById("ingredient-unit-table");
    const existingRows = table.querySelectorAll("tbody tr");

    for (const row of existingRows) {
        const rowData = JSON.parse(row.getAttribute("data-ingredient-unit"));
        const existingName = rowData.name.trim();

        if (existingName.toLowerCase() === ingredientUnitName.toLowerCase()) {
            showNotification(`Ingredient Unit: '${ingredientUnitName}' already exists!`);
            return;
        }
    }

    // Create a new table row element
    const newRow = document.createElement("tr");

    // Create an object for ingredient unit data
    const ingredientUnitData = {
        id: ingredientUnitID,
        name: ingredientUnitName
    };

    // Setting ingredient unit data as a custom attribute on the row
    newRow.setAttribute("data-ingredient-unit", JSON.stringify(ingredientUnitData));  

    // Creating cells
    const ingredientUnitIDCell = document.createElement("td");
    const ingredientUnitCell = document.createElement("td");

    // Setting cell contents
    ingredientUnitIDCell.textContent = ingredientUnitID;
    ingredientUnitCell.textContent = ingredientUnitName;

    // Append cells to the new row
    newRow.appendChild(ingredientUnitIDCell);
    newRow.appendChild(ingredientUnitCell);

    // Add click event listener to the new row
    newRow.addEventListener('click', function () {
        ingredientUnit_tableRowClicked('data-ingredient-unit', newRow); // Call the callback function when a row is clicked
        highlightClickedTableRow('ingredient-unit-table', newRow); // Call the callback function when a row is clicked
    });

    // Append the new row to the table body
    table.querySelector("tbody").appendChild(newRow);

    // Clear the input fields of the form
    clearFormFields('ingredient-unit-table', 'ingredient-unit-form');

    const ingredientUnit_comboBox = document.getElementById("ingredient-unit-combobox");
    ingredientUnit_comboBox.add(new Option(ingredientUnitName, ingredientUnitID));

    showNotification(`Ingredient Unit: '${ingredientUnitName}' added successfully!`);
}

/*============================================================*/

function ingredientUnit_tableRowClicked(dataRow, row) {
    // Access the data stored in the row's custom attribute
    const rowData = JSON.parse(row.getAttribute(dataRow));

    // Set the input fields with the data retrieved from the row
    document.getElementById("ingredient-unit-id").value = rowData.id; // Use the 'id' property
    document.getElementById("ingredient-unit-name").value = rowData.name; // Use the 'name' property
}

/*============================================================*/

function updateSelectedIngredientUnit() {

    // Find the currently selected row
    const selectedRow = document.querySelector("#ingredient-unit-table .clickedTableRow");

    // Check if a row is selected
    if (!selectedRow) {
        showNotification(`Select a row to update from the table.`);
        return;
    }

    if (!formValidity('ingredient-unit-form')) {
        showNotification(`Fill all required fields with valid input.`);
        return;
    }

    // Get the values from the input fields
    const selectedRowData = JSON.parse(selectedRow.getAttribute('data-ingredient-unit'));
    const ingredientUnitID = selectedRowData.id;
    
    const ingredientUnitName = document.getElementById("ingredient-unit-name").value;

    // Create an object to hold the updated data
    const ingredientUnitData = {
        id: ingredientUnitID,
        name: ingredientUnitName
    };

    // Update the data-ingredient-unit attribute with the new data
    selectedRow.setAttribute("data-ingredient-unit", JSON.stringify(ingredientUnitData));

    // Update the displayed cell contents
    const cells = selectedRow.querySelectorAll("td");
    if (cells.length >= 2) {
        cells[0].textContent = ingredientUnitID; // Update the ID cell
        cells[1].textContent = ingredientUnitName;    // Update the name cell
        showNotification(`Ingredient Unit: '${ingredientUnitName}' updated successfully!`);
    }

    // Highlight the updated row for visual feedback
    animateRowHighlight(selectedRow);
    
    // Repopulate the combo box to reflect changes
    repopulateComboBoxFromTable("ingredient-unit-table", "data-ingredient-unit", "ingredient-unit-combobox");

    // Clear the input fields of the form
    clearFormFields('ingredient-unit-table', 'ingredient-unit-form');
}

/*============================================================*/

function deleteSelectedIngredientUnit(tableID, dataName) {
    // Get the table by ID
    const table = document.getElementById(tableID);

    // Find the currently selected row
    const selectedRow = table.querySelector(".clickedTableRow");

    // Check if a row is selected
    if (!selectedRow) {
        showNotification(`Select a row to delete from the table.`);
        return;
    }

    // Grab the selected row's data attribute and its ID
    const selectedRowData = JSON.parse(selectedRow.getAttribute(`data-${dataName}`));
    const selectedRowID = parseInt(selectedRowData.id); // Ensure ID is a number

    // Remove the selected row
    selectedRow.remove();
    clearFormFields(tableID, `${tableID.replace('-table', '-form')}`);

    // Show notification for deletion
    showNotification(`Record ID: '${selectedRowID}' deleted successfully!`);

    // Update IDs for rows with higher IDs than the deleted one
    const remainingRows = Array.from(table.querySelectorAll("tbody tr"));
    remainingRows.forEach(row => {
        const rowData = JSON.parse(row.getAttribute(`data-${dataName}`));
        const rowID = parseInt(rowData.id);

        if (rowID > selectedRowID) {
            const newID = rowID - 1;

            // Update the data attribute with the new ID
            rowData.id = newID;
            row.setAttribute(`data-${dataName}`, JSON.stringify(rowData));

            // Find the first cell with textContent matching the current ID
            for (const cell of row.cells) {
                if (parseInt(cell.textContent) === rowID) {
                    cell.textContent = newID; // Update the cell's content
                    break; // Stop searching after the first match
                }
            }
        }
    });
    
    repopulateComboBoxFromTable("ingredient-unit-table", "data-ingredient-unit", "ingredient-unit-combobox");

}

/*============================================================*/










/*============================================================*/ /*============================================================*/
/*============================================================*/ /*============================================================*/
/*============================================================*/ /*============================================================*/