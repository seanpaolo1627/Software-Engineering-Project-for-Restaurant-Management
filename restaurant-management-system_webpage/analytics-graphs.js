document.addEventListener("DOMContentLoaded", function() {

    //=============================================
    //=============================================
    //=============================================
    //=============================================

    // Base configuration for charts
    const baseChartOptions = {
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    color: 'white' // General label color
                },
                grid: {
                    color: 'rgba(47, 79, 79, 0.8)' // General grid color
                }
            },
            x: {
                ticks: {
                    color: 'white' // General label color
                },
                grid: {
                    color: 'rgba(47, 79, 79, 0.8)' // General grid color
                }
            }
        },
        plugins: {
            legend: {
                labels: {
                    color: 'white' // General legend label color
                }
            }
        }
    };

    //=============================================
    //=============================================
    //=============================================

    // Function to generate random data
    function generateRandomData(length) {
        return Array.from({ length }, () => Math.floor(Math.random() * 100)); // Random values between 0 and 99 for each month
    }

    //=============================================
    //=============================================
    //=============================================

    // Function to initialize a new chart
    function createChart(ctx, type, data, backgroundColor, borderColor) {
        const chartOptions = {
            type: type,
            data: data,
            options: {
                ...baseChartOptions, // Spread the base options
                elements: {
                    bar: {
                        backgroundColor: backgroundColor, // Specific background color
                        borderColor: borderColor, // Specific border color
                    }
                }
            }
        };

        return new Chart(ctx, chartOptions);
    }

    //=============================================
    //=============================================
    //=============================================









    
    //=============================================
    //=============================================
    //=============================================

    // Initialize the Total Paid Orders Chart
    const ctxOrders = document.getElementById('ordersChart').getContext('2d');
    const ordersData = {
        labels: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
        datasets: [{
            label: 'Total Paid Orders for 2024',
            data: generateRandomData(12), // Generate initial random data
            backgroundColor: 'rgba(75, 192, 192, 0.2)', // Specific background color
            borderColor: 'rgba(75, 192, 192, 1)', // Specific border color
            borderWidth: 1
        }]
    };

    // Create the Total Paid Orders Chart
    const ordersChart = createChart(ctxOrders, 'bar', ordersData);

    // Update chart data based on selected year
    document.getElementById('totalPaidOrders-yearSelect').addEventListener('change', updateTotalPaidOrdersChart);
        
    function updateTotalPaidOrdersChart() {
        const selectedYear = document.getElementById('totalPaidOrders-yearSelect').value;
        let data = generateRandomData(12); // Generate new random data

        // Update the chart with new data
        ordersChart.data.datasets[0].data = data;
        ordersChart.data.datasets[0].label = `Total Paid Orders for ${selectedYear}`;
        ordersChart.update();
    };

    // Initial update for the total paid orders chart
    updateTotalPaidOrdersChart();

    //=============================================
    //=============================================
    //=============================================

    // Initialize the Sales by Order Type Chart
    const ctxOrderType = document.getElementById('orderTypeChart').getContext('2d');
    const orderTypeData = {
        labels: ['Dine In', 'Take Out', 'Delivery'],
        datasets: [{
            label: 'Sales by Order Type for 2024',
            data: [30, 50, 20], // Example data for each order type
            backgroundColor: [
                'rgba(255, 99, 132, 0.2)', // Dine In
                'rgba(54, 162, 235, 0.2)', // Take Out
                'rgba(255, 206, 86, 0.2)'  // Delivery
            ],
            borderColor: [
                'rgba(255, 99, 132, 1)',
                'rgba(54, 162, 235, 1)',
                'rgba(255, 206, 86, 1)'
            ],
            borderWidth: 1
        }]
    };

    // Create the Sales by Order Type Chart
    const orderTypeChart = createChart(ctxOrderType, 'pie', orderTypeData);

    // Add event listeners for year and month select elements
    document.getElementById('salesOrderType-yearSelect').addEventListener('change', updateSalesOrderTypeChart);
    document.getElementById('salesOrderType-monthSelect').addEventListener('change', updateSalesOrderTypeChart);

    // Function to generate random sales data
    function generateRandomSalesData() {
        return [Math.floor(Math.random() * 100), Math.floor(Math.random() * 100), Math.floor(Math.random() * 100)];
    }

    // Function to update the Sales by Order Type Chart
    function updateSalesOrderTypeChart() {
        const newData = generateRandomSalesData(); // Generate new random data
        orderTypeChart.data.datasets[0].data = newData; // Update the chart data
        orderTypeChart.data.datasets[0].label = `Sales by Order Type for ${document.getElementById('salesOrderType-yearSelect').value}`; // Update label
        orderTypeChart.update(); // Refresh the chart
    }

    updateSalesOrderTypeChart();
    
    //=============================================
    //=============================================
    //=============================================

    // Initialize the Menu Item Orders Chart
    const ctxMenuItems = document.getElementById('menuItemOrdersChart').getContext('2d');
    const menuItemsData = {
        datasets: [{
            label: 'Menu Item Orders for 2024',
            backgroundColor: 'rgba(153, 102, 255, 0.2)', // Specific background color
            borderColor: 'rgba(153, 102, 255, 1)', // Specific border color
            borderWidth: 1
        }]
    };

    // Create the Menu Item Orders Chart
    const menuItemOrdersChart = createChart(ctxMenuItems, 'bar', menuItemsData);

    // Update chart data based on selected year, month, and category
    document.getElementById('menuItemsOrdered-yearSelect').addEventListener('change', updateMenuItemChart);
    document.getElementById('menuItemsOrdered-monthSelect').addEventListener('change', updateMenuItemChart);
    document.getElementById('menuItemsOrdered-categorySelect').addEventListener('change', updateMenuItemChart);

    function updateMenuItemChart() {
        const selectedYear = document.getElementById('menuItemsOrdered-yearSelect').value;
        const selectedMonth = document.getElementById('menuItemsOrdered-monthSelect').value;
        const selectedCategory = document.getElementById('menuItemsOrdered-categorySelect').value;

        let labels = [];
        let data = [];

        // Set labels and data based on selected category
        if (selectedCategory === 'category1') {
            labels = ['Item #1', 'Item #2', 'Item #3', 'Item #4', 'Item #5', 'Item #6', 'Item #7', 'Item #8'];
            data = generateRandomData(labels.length); // Generate random data based on the number of labels
        } else if (selectedCategory === 'category2') {
            labels = ['Item #9', 'Item #10', 'Item #11', 'Item #12', 'Item #13', 'Item #14', 'Item #15', 'Item #16'];
            data = generateRandomData(labels.length); // Generate random data based on the number of labels
        } else if (selectedCategory === 'category3') {
            labels = ['Item #17', 'Item #18', 'Item #19', 'Item #20', 'Item #21', 'Item #22', 'Item #23', 'Item #24'];
            data = generateRandomData(labels.length); // Generate random data based on the number of labels
        }

        // Update the chart with new labels and data
        menuItemOrdersChart.data.labels = labels;
        menuItemOrdersChart.data.datasets[0].data = data;
        menuItemOrdersChart.data.datasets[0].label = `Menu Item Orders for ${selectedYear} - ${document.getElementById('menuItemsOrdered-monthSelect').options[selectedMonth].text}`;
        menuItemOrdersChart.update();
    }

    // Initial update for the menu item chart
    updateMenuItemChart();
    
    //=============================================
    //=============================================
    //=============================================

    // Function to initialize the Profit Margin Chart
    function initializeProfitMarginChart() {
        const ctxProfitMargin = document.getElementById('profitMarginChart').getContext('2d');
        const profitMarginData = {
            labels: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
            datasets: [{
                label: 'Profit Margin (%)',
                data: [], // Will be filled with calculated profit margins
                backgroundColor: 'rgba(144, 238, 144, 0.3)', // Background color for the line
                borderColor: 'rgba(34, 139, 34, 1)', // Border color for the line
                borderWidth: 1,
                fill: true // Fill under the line
            }, {
                label: 'Total Profit ($)',
                data: [], // Will be filled with total profit for each month
                backgroundColor: 'rgba(255, 215, 0, 0.3)', // Background color for total profit line
                borderColor: 'rgba(255, 215, 0, 1)', // Border color for total profit line
                borderWidth: 1,
                fill: true // Fill under the line
            }]
        };

        // Create the Profit Margin Chart
        const profitMarginChart = createChart(ctxProfitMargin, 'line', profitMarginData);

        // Update chart data based on selected year, category, and item
        document.getElementById('profitMargin-yearSelect').addEventListener('change', updateProfitMarginChart);
        document.getElementById('profitMargin-categorySelect').addEventListener('change', updateProfitMarginChart);
        document.getElementById('profitMargin-itemSelect').addEventListener('change', updateProfitMarginChart);

        function updateProfitMarginChart() {
            const selectedYear = document.getElementById('profitMargin-yearSelect').value;
            const selectedCategory = document.getElementById('profitMargin-categorySelect').value;
            const selectedItem = document.getElementById('profitMargin-itemSelect').value;

            // Calculate profit margins and total profits for each month
            const profitMargins = [];
            const totalProfits = [];
            
            for (let month = 0; month < 12; month++) {
                const cost = Math.floor(Math.random() * 100); // Random cost between 0 and 99
                const sellingPrice = cost + Math.floor(Math.random() * 201) + 50; // Selling price between cost + 50 and cost + 250
                const profitPerOrder = sellingPrice - cost; // Profit per order
                const numberOfOrders = Math.floor(Math.random() * 100) + 1; // Random number of orders between 1 and 100
                const totalProfit = profitPerOrder * numberOfOrders; // Total profit for the month

                // Calculate profit margin percentage
                const profitMargin = ((sellingPrice - cost) / sellingPrice) * 100; // Calculate profit margin percentage

                // Store the calculated values
                profitMargins.push(parseFloat(profitMargin.toFixed(2))); // Profit margin rounded to 2 decimal places
                totalProfits.push(totalProfit); // Total profit for the month
            }

            // Update the chart with new data
            profitMarginChart.data.datasets[0].data = profitMargins;
            profitMarginChart.data.datasets[1].data = totalProfits; // Update total profit dataset
            profitMarginChart.data.datasets[0].label = `Profit Margin for ${selectedYear}`;
            profitMarginChart.data.datasets[1].label = `Total Profit for ${selectedYear}`;
            profitMarginChart.update();
        }

        // Initial update for the profit margin chart
        updateProfitMarginChart();
    }

    const categorySelect = document.getElementById('profitMargin-categorySelect');
    const itemSelect = document.getElementById('profitMargin-itemSelect');

    // Function to update item options based on selected category
    function updateItemOptions() {
        const selectedCategory = categorySelect.value;
        let items = [];

        if (selectedCategory === 'category1') {
            items = ['Item #1', 'Item #2', 'Item #3', 'Item #4', 'Item #5', 'Item #6', 'Item #7', 'Item #8'];
        } else if (selectedCategory === 'category2') {
            items = ['Item #9', 'Item #10', 'Item #11', 'Item #12', 'Item #13', 'Item #14', 'Item #15', 'Item #16'];
        } else if (selectedCategory === 'category3') {
            items = ['Item #17', 'Item #18', 'Item #19', 'Item #20', 'Item #21', 'Item #22', 'Item #23', 'Item #24'];
        }

        // Clear existing options
        itemSelect.innerHTML = '';

        // Populate new options
        items.forEach(item => {
            const option = document.createElement('option');
            option.value = item;
            option.textContent = item;
            itemSelect.appendChild(option);
        });
    }

    // Event listener for category change
    categorySelect.addEventListener('change', updateItemOptions);

    // Initial population of items based on the default selected category
    updateItemOptions();

    // Call the function to initialize the Profit Margin Chart
    initializeProfitMarginChart();
    
    //=============================================
    //=============================================
    //=============================================

    // Define the number of staff members
    const NUMBER_OF_STAFF = 5; // Change this value to adjust the number of staff members

    // Function to initialize the Staff Orders Chart
    function initializeStaffOrdersChart() {
        const ctxStaffOrders = document.getElementById('staffOrdersChart').getContext('2d');
        const staffOrdersData = {
            labels: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
            datasets: []
        };

        // Create datasets for the defined number of staff members
        for (let i = 1; i <= NUMBER_OF_STAFF; i++) {
            staffOrdersData.datasets.push({
                label: `Staff Member ${i}`,
                data: [], // Will be filled with random order counts
                borderColor: `rgba(${Math.floor(Math.random() * 255)}, ${Math.floor(Math.random() * 255)}, ${Math.floor(Math.random() * 255)}, 1)`, // Random color for each staff member
                borderWidth: 2,
                fill: false // No fill for line chart
            });
        }

        // Create the Staff Orders Chart
        const staffOrdersChart = createChart(ctxStaffOrders, 'line', staffOrdersData);

        // Update chart data based on selected year
        document.getElementById('staffOrders-yearSelect').addEventListener('change', updateStaffOrdersChart);

        function updateStaffOrdersChart() {
            const selectedYear = document.getElementById('staffOrders-yearSelect').value;

            // Generate random order counts for each staff member for each month
            const staffOrdersCounts = Array.from({ length: NUMBER_OF_STAFF }, () => []); // Array to hold counts for the defined number of staff members
            for (let month = 0; month < 12; month++) {
                for (let staffIndex = 0; staffIndex < NUMBER_OF_STAFF; staffIndex++) {
                    const randomOrders = Math.floor(Math.random() * 50); // Random orders between 0 and 49
                    staffOrdersCounts[staffIndex].push(randomOrders);
                }
            }

            // Update the chart with new data
            staffOrdersCounts.forEach((counts, index) => {
                staffOrdersChart.data.datasets[index].data = counts; // Update each staff member's data
            });
            staffOrdersChart.update();
        }

        // Initial update for the staff orders chart
        updateStaffOrdersChart();
    }

    // Call the function to initialize the Staff Orders Chart
    initializeStaffOrdersChart();
    
    //=============================================
    //=============================================
    //=============================================

    // Initialize the Ingredient Usage Trends Chart
    const ctxIngredientUsage = document.getElementById('ingredientUsageChart').getContext('2d');
    const ingredientUsageData = {
        labels: [
            'Ing. #1', 'Ing. #2', 'Ing. #3', 'Ing. #4', 'Ing. #5', 'Ing. #6',
            'Ing. #7', 'Ing. #8', 'Ing. #9', 'Ing. #10', 'Ing. #11', 'Ing. #12',
            'Ing. #13', 'Ing. #14', 'Ing. #15', 'Ing. #16', 'Ing. #17', 'Ing. #18'
        ],
        datasets: [{
            label: 'Ingredient Usage for 2024',
            backgroundColor: 'rgba(255, 159, 64, 0.2)', // Specific background color
            borderColor: 'rgba(255, 159, 64, 1)', // Specific border color
            borderWidth: 1
        }]
    };

    // Create the Ingredient Usage Trends Chart
    const ingredientUsageChart = createChart(ctxIngredientUsage, 'bar', ingredientUsageData);

    // Update chart data based on selected year and month
    document.getElementById('ingredientUsage-yearSelect').addEventListener('change', updateIngredientUsageChart);
    document.getElementById('ingredientUsage-monthSelect').addEventListener('change', updateIngredientUsageChart);

    function updateIngredientUsageChart() {
        const selectedYear = document.getElementById('ingredientUsage-yearSelect').value;
        const selectedMonth = document.getElementById('ingredientUsage-monthSelect').value;

        // Generate random data for ingredient usage
        const data = generateRandomData(18); // Generate random data for 18 ingredients

        // Update the chart with new data
        ingredientUsageChart.data.datasets[0].data = data;
        ingredientUsageChart.data.datasets[0].label = `Ingredient Usage for ${selectedYear} - ${document.getElementById('ingredientUsage-monthSelect').options[selectedMonth].text}`;
        ingredientUsageChart.update();
    }

    // Initial update for the ingredient usage chart
    updateIngredientUsageChart();
    
    //=============================================
    //=============================================
    //=============================================

    // Initialize the Peak Hours and Days Chart
    const ctxPeakHours = document.getElementById('peakHoursChart').getContext('2d');
    const peakHoursData = {
        labels: Array.from({ length: 24 }, (_, i) => `${i + 1}h`), // Labels for 1-24 hours
        datasets: [{
            label: 'Orders for Sunday', // Default label
            data: generateRandomOrdersData(), // Generate initial random data
            backgroundColor: 'rgba(75, 192, 192, 0.2)', // Background color
            borderColor: 'rgba(75, 192, 192, 1)', // Border color
            borderWidth: 1,
            fill: true // Fill under the line
        }]
    };

    // Create the Peak Hours and Days Chart
    const peakHoursChart = createChart(ctxPeakHours, 'line', peakHoursData);

    // Add event listener for the day select element
    document.getElementById('peakHours-daySelect').addEventListener('change', updatePeakHoursChart);

    // Function to generate random orders data for 24 hours
    function generateRandomOrdersData() {
        return Array.from({ length: 24 }, () => Math.floor(Math.random() * 100)); // Random values between 0 and 99 for each hour
    }

    // Function to update the Peak Hours and Days Chart
    function updatePeakHoursChart() {
        const newData = generateRandomOrdersData(); // Generate new random data
        peakHoursChart.data.datasets[0].data = newData; // Update the chart data
        peakHoursChart.data.datasets[0].label = `Orders for ${document.getElementById('peakHours-daySelect').value}`; // Update label
        peakHoursChart.update(); // Refresh the chart
    }

    // Initial update for the peak hours chart
    updatePeakHoursChart();
    
    //=============================================
    //=============================================
    //=============================================

    // Add event listeners for year and month select elements
    document.getElementById('orderStatus-yearSelect').addEventListener('change', updateOrderStatusTimesChart);
    document.getElementById('orderStatus-monthSelect').addEventListener('change', updateOrderStatusTimesChart);

    // Function to generate random average times for each status in seconds
    function generateRandomAverageTimes() {
        return {
            pendingToPreparing: Math.floor(Math.random() * 481), // Random time in seconds (up to 8 minutes)
            preparingToReady: Math.floor(Math.random() * 481), // Random time in seconds (up to 8 minutes)
            readyToComplete: Math.floor(Math.random() * 481) // Random time in seconds (up to 8 minutes)
        };
    }

    // Function to format seconds into MM:SS
    function formatTime(seconds) {
        const minutes = Math.floor(seconds / 60);
        const secs = seconds % 60;
        return `${String(minutes).padStart(2, '0')}:${String(secs).padStart(2, '0')}`; // Format as MM:SS
    }

    // Function to update the Order Status Times Chart
    function updateOrderStatusTimesChart() {
        const averageTimes = generateRandomAverageTimes(); // Generate new random average times
        document.getElementById('pendingToPreparing').innerText = formatTime(averageTimes.pendingToPreparing);
        document.getElementById('preparingToReady').innerText = formatTime(averageTimes.preparingToReady);
        document.getElementById('readyToComplete').innerText = formatTime(averageTimes.readyToComplete);
    }

    // Initial update for the order status times chart
    updateOrderStatusTimesChart();
    
    //=============================================
    //=============================================
    //=============================================

    // Example menu items (replace with your actual menu items)
    const menuItems = ['Item #1', 'Item #2', 'Item #3', 'Item #4', 'Item #5', 'Item #6', 'Item #7', 'Item #8', 'Item #9', 'Item #10'];

    // Variables to track min and max values
    let minValue = Infinity;
    let maxValue = -Infinity;

    // Function to create and populate the heatmap
    function createHeatmap() {
        const heatmapContainer = document.getElementById('menu-pairings-heatmap');
        const table = document.createElement('table');
        table.className = 'heatmap-table';

        // Create table header
        const headerRow = document.createElement('tr');
        headerRow.appendChild(document.createElement('th')); // Empty top-left cell
        menuItems.forEach(item => {
            const th = document.createElement('th');
            th.textContent = item;
            headerRow.appendChild(th);
        });
        table.appendChild(headerRow);

        // Create table body
        menuItems.forEach((rowItem, rowIndex) => {
            const row = document.createElement('tr');
            const th = document.createElement('th');
            th.textContent = rowItem; // Row header
            row.appendChild(th);

            menuItems.forEach((colItem, colIndex) => {
                const cell = document.createElement('td');
                if (rowIndex !== colIndex) { // Avoid self-pairing
                    const value = Math.floor(Math.random() * 100); // Replace with actual data

                    minValue = Math.min(minValue, value); // Update min value
                    maxValue = Math.max(maxValue, value); // Update max value

                    document.getElementById('min-value').textContent = '(Lowest) ' + minValue;
                    document.getElementById('max-value').textContent = maxValue + ' (Highest)';
            
                    // Set a timeout to delay the color setting
                    setTimeout(() => {
                        setCellColor(cell, value); // Set cell color based on value
                        cell.textContent = value; // Optional: Show the value in the cell
                    }, 1000); // 1000 milliseconds = 1 second
                }
                row.appendChild(cell);
            });

            table.appendChild(row);
        });

        heatmapContainer.appendChild(table);
    }

    // Function to set the background color based on value
    function setCellColor(cell, value) {
        const color = valueToColor(value, minValue, maxValue);
        cell.style.backgroundColor = color;
        cell.style.color = 'black'; // Ensure text is readable
    }

    // Function to calculate a soothing color transition
    function valueToColor(value, min, max) {
        const ratio = (value - min) / (max - min);

        // Transition from light pastel yellow to soft orange to muted red
        const red = Math.floor(255 * ratio); // Red increases as value increases
        const green = Math.floor(235 * (1 - ratio) + 170 * ratio); // Green transitions from pastel yellow to orange
        const blue = Math.floor(200 * (1 - ratio)); // Blue fades as value increases

        return `rgb(${red}, ${green}, ${blue})`;
    }

    // Function to create a color gradient for the legend
    function generateLegendGradient() {
        const legend = document.getElementById('legend-gradient');
        const gradientSteps = 100; // Number of color stops in the gradient
        const colorStops = [];

        for (let i = 0; i <= gradientSteps; i++) {
            const ratio = i / gradientSteps; // Normalize between 0 and 1
            const value = ratio * 100; // Convert ratio to the range [0, 100]
            const color = valueToColor(value, minValue, maxValue); // Use the same color function for heatmap
            colorStops.push(`${color} ${(i / gradientSteps) * 100}%`);
        }

        legend.style.background = `linear-gradient(to right, ${colorStops.join(', ')})`;
    }

    // Call the function to create the heatmap
    createHeatmap();

    // Call the function to generate the heatmap legend gradient after heatmap is created
    generateLegendGradient();

    //=============================================
    //=============================================
    //=============================================

});

//=============================================