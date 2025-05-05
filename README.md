# Cara's Food Haven - Restaurant Management System

## Overview

This project is a comprehensive web application designed for managing restaurant operations and providing a seamless online ordering experience for customers. It consists of three main components: a customer-facing menu and ordering system, a customer order tracking portal, and a restaurant management dashboard.

## Technologies Used

*   **Backend:** PHP
*   **Frontend:** HTML, CSS, JavaScript
*   **Database:** MySQL/MariaDB (via XAMPP)
*   **Payment Gateway:** PayMongo API
*   **Authentication:** Google Sign-In API
*   **Dependency Management:** Composer (for PHP packages like PayMongo SDK)
*   **Web Server:** Apache (via XAMPP)

## Features

### 1. Menu & Ordering (`menu_webpage`)

*   **Browse Menu:** Dynamically displays the restaurant's menu with items, details, and images.
*   **Shopping Cart:** Allows users to add/remove items and view their current selection.
*   **Google Sign-In:** Option for customers to sign in using their Google accounts.
*   **Place Orders:** Submits the cart for order processing.
*   **PayMongo Integration:** Secure online payment processing via PayMongo checkout. Handles payment success, failure, and status checks.
*   **Order Confirmation:** Displays order confirmation details to the customer.
*   **Customer Account Management:** Allows customers to view and update their account details.

### 2. My Orders (`my-orders_webpage`)

*   **Order History:** Registered customers can log in and view their complete order history.
*   **Order Status Tracking:** Displays the current status of each order (e.g., Pending, Preparing, Out for Delivery, Delivered).
*   **Detailed Order View:** Provides a detailed breakdown of items, quantities, and costs for each past order.

### 3. Restaurant Management System (`restaurant-management-system_webpage`)

*   **Dashboard Overview:** Central interface for managing restaurant operations.
*   **Order Management:** View incoming orders in real-time, update order statuses, manage fulfillment workflow.
*   **Menu Management:** Interface for adding, updating, deleting, and categorizing menu items.
*   **Inventory/Recipe Management:** Track ingredients, view recipe details, and manage stock levels (inferred functionality).
*   **Analytics & Reporting:** Visualizes sales data, popular items, and other key metrics using graphs (likely via Chart.js or similar).
*   **Staff & Customer Profiling:** Manage staff accounts and view customer details/history.
*   **User Management:** Administer access and permissions for different user roles (staff, managers).





