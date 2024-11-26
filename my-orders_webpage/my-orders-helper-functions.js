

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