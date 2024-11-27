<?php

// management.php
// Prevent any output before JSON response
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0); // Set to 0 to prevent error messages from breaking JSON

// Include the database connection and start the session
include 'login/connect.php';
// if (session_status() === PHP_SESSION_NONE) {
if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

 
 $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
// }



// Fetch employee details from the database
$email = $_SESSION['email'];
$sql = "SELECT firstName, lastName, role FROM employees WHERE email = ?";
$stmt = $conn->prepare($sql);

if($stmt){
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->bind_result($firstName, $lastName, $role);
    $stmt->fetch();
    $stmt->close();
} else {
    // Handle database error
    echo "Database error: " . $conn->error;
    exit();
}

// Function to send JSON response and exit
function sendJsonResponse($data, $statusCode = 200) {
    if (ob_get_length()) {
        ob_clean(); // Clear any existing output
    } else {
        ob_start(); // Start output buffering if not started
    }
    header('Content-Type: application/json');
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

// Function to sanitize input data
function sanitizeInput($data, $conn) {
    return htmlspecialchars(trim($data ?? ''), ENT_QUOTES, 'UTF-8');
}         


/////// ORDER MANAGEMENT FUNCTIONALITY BY SEAN///////////


if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_orders') {
    try {
        // When inserting into order_items
        $pendingStatusId = 1; // Assuming 'PENDING' has ID 1
        $insertOrderItemsSql = "INSERT INTO order_items (order_ID, menu_item_ID, quantity, price_at_time, status_id) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insertOrderItemsSql);
        $stmt->bind_param('iiidi', $orderId, $menuItemId, $quantity, $priceAtTime, $pendingStatusId);
        $status = isset($_GET['status']) ? sanitizeInput($_GET['status'], $conn) : 'PENDING';
        $statusCondition = $status === 'ALL' ? '' : 'WHERE o.order_status = ?';
        
        $sql = "SELECT o.*, c.firstName, c.lastName, c.contactNumber, c.email,
        mi.ID as menu_item_id, mi.name as item_name, mi.price, oi.quantity, oi.status_id as menu_item_status
        FROM `order` o
        LEFT JOIN customer c ON o.customer_ID = c.ID
        LEFT JOIN order_items oi ON o.ID = oi.order_ID
        LEFT JOIN menu_item mi ON oi.menu_item_ID = mi.ID
        $statusCondition
        ORDER BY o.date_ordered DESC";
        
        $stmt = $conn->prepare($sql);
        if ($status !== 'ALL') {
            $stmt->bind_param('s', $status);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        
        $orders = [];
        while ($row = $result->fetch_assoc()) {
            if (!isset($orders[$row['ID']])) {
                $orders[$row['ID']] = [
                    'id' => $row['ID'],
                    'status' => $row['order_status'], // Add this line
                    'order_status' => $row['order_status'],
                    'order_type' => $row['order_type'],
                    'date_ordered' => $row['date_ordered'],
                    'total_price' => $row['total_price'],
                    'discount_code' => $row['discount_code'],
                    'customerName' => $row['firstName'] && $row['lastName'] 
                        ? $row['firstName'] . ' ' . $row['lastName']
                        : 'Guest Customer',
                    'contactInfo' => [
                        'phone' => $row['contactNumber'] ?? 'N/A',
                        'email' => $row['email'] ?? 'N/A',
                        'address' => $row['address'] ?? 'N/A'
                    ],
                    'menuItems' => []
                ];
            }
            
            if ($row['menu_item_id']) {
                $orders[$row['ID']]['menuItems'][] = [
                    'id' => $row['menu_item_id'],
                    'name' => $row['item_name'],
                    'price' => $row['price'],
                    'quantity' => $row['quantity'],
                    'menu_item_status' => $row['menu_item_status'] ?? 'PENDING'
                ];
            }
        }
        
        sendJsonResponse(['success' => true, 'orders' => array_values($orders)]);
        
    } catch (Exception $e) {
        sendJsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

if (isset($_GET['action']) && $_GET['action'] === 'get_order_counts') {
    try {
        $sql = "SELECT order_status, COUNT(*) as count FROM `order` GROUP BY order_status";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception('Prepare failed: ' . $conn->error);
        }
        $stmt->execute();
        $result = $stmt->get_result();

        $counts = ['PENDING' => 0, 'PREPARING' => 0, 'READY_FOR_PICKUP' => 0, 'COMPLETE' => 0, 'CANCELED' => 0];
        while ($row = $result->fetch_assoc()) {
            if (array_key_exists($row['order_status'], $counts)) {
                $counts[$row['order_status']] = intval($row['count']);
            }
        }
        $stmt->close();

        sendJsonResponse(['success' => true, 'counts' => $counts]);
    } catch (Exception $e) {
        sendJsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve and decode JSON input
    $input = json_decode(file_get_contents('php://input'), true);

    if (isset($input['action']) && $input['action'] === 'update_order_status') {
        try {
            $orderId = isset($input['orderId']) ? intval($input['orderId']) : null;
            $newStatus = isset($input['status']) ? strtoupper(trim($input['status'])) : null;

            if (!$orderId || !$newStatus) {
                throw new Exception('Order ID and new status are required.');
            }

            // Validate newStatus
            $allowedStatuses = ['PENDING', 'PREPARING', 'READY_FOR_PICKUP', 'COMPLETE', 'CANCELED'];
            if (!in_array($newStatus, $allowedStatuses)) {
                throw new Exception('Invalid order status.');
            }

            // Fetch current status
            $currentStatusQuery = "SELECT order_status FROM `order` WHERE ID = ?";
            $currentStmt = $conn->prepare($currentStatusQuery);
            if (!$currentStmt) {
                throw new Exception('Prepare failed: ' . $conn->error);
            }
            $currentStmt->bind_param('i', $orderId);
            $currentStmt->execute();
            $currentResult = $currentStmt->get_result();
            $currentOrder = $currentResult->fetch_assoc();
            $currentStmt->close();

            if (!$currentOrder) {
                throw new Exception('Order not found.');
            }

            $currentStatus = $currentOrder['order_status'];

            // Define valid transitions
            $validTransitions = [
                'PENDING' => ['PREPARING', 'CANCELED'],
                'PREPARING' => ['READY_FOR_PICKUP', 'CANCELED', 'PENDING'],
                'READY_FOR_PICKUP' => ['COMPLETE', 'CANCELED', 'PREPARING'],
                'COMPLETE' => [],
                'CANCELED' => []
            ];

            if (!isset($validTransitions[$currentStatus]) || !in_array($newStatus, $validTransitions[$currentStatus])) {
                throw new Exception("Cannot change status from $currentStatus to $newStatus.");
            }

            // Update order status
            $updateQuery = "UPDATE `order` SET order_status = ? WHERE ID = ?";
            $updateStmt = $conn->prepare($updateQuery);
            if (!$updateStmt) {
                throw new Exception('Prepare failed: ' . $conn->error);
            }
            $updateStmt->bind_param('si', $newStatus, $orderId);
            if (!$updateStmt->execute()) {
                throw new Exception('Failed to update order status: ' . $updateStmt->error);
            }
            $updateStmt->close();


            if ($newStatus == 'CANCELED') {
                $updateItemsQuery = "UPDATE `order_items` SET status_id = 'CANCELED' WHERE order_ID = ?";
                $updateItemsStmt = $conn->prepare($updateItemsQuery);
                if (!$updateItemsStmt) {
                    throw new Exception('Prepare failed: ' . $conn->error);
                }
                $updateItemsStmt->bind_param('i', $orderId);
                if (!$updateItemsStmt->execute()) {
                    throw new Exception('Failed to update order items status: ' . $updateItemsStmt->error);
                }
                $updateItemsStmt->close();
            }
            
            // Update menu items status_id when order is completed
            if ($newStatus == 'COMPLETE') {
                $updateItemsQuery = "UPDATE `order_items` SET status_id = 'COMPLETE' WHERE order_ID = ?";
                $updateItemsStmt = $conn->prepare($updateItemsQuery);
                if (!$updateItemsStmt) {
                    throw new Exception('Prepare failed: ' . $conn->error);
                }
                $updateItemsStmt->bind_param('i', $orderId);
                if (!$updateItemsStmt->execute()) {
                    throw new Exception('Failed to update order items status: ' . $updateItemsStmt->error);
                }
                $updateItemsStmt->close();
            }

            // Fetch updated counts for 'PENDING', 'PREPARING', 'READY_FOR_PICKUP'
            $countSql = "SELECT order_status, COUNT(*) as count FROM `order` GROUP BY order_status";
            $countStmt = $conn->prepare($countSql);
            if (!$countStmt) {
             throw new Exception('Prepare failed for count query: ' . $conn->error);
                }
            $countStmt->execute();
            $countResult = $countStmt->get_result();

            $counts = ['PENDING' => 0, 'PREPARING' => 0, 'READY_FOR_PICKUP' => 0, 'COMPLETE' => 0, 'CANCELED' => 0];
            while ($countRow = $countResult->fetch_assoc()) {
            if (array_key_exists($countRow['order_status'], $counts)) {
             $counts[$countRow['order_status']] = intval($countRow['count']);
                 }
                    }
                $countStmt->close();

            // Send success response with updated counts
            sendJsonResponse(['success' => true, 'newStatus' => $newStatus, 'counts' => $counts]);

        } catch (Exception $e) {
            // Return error response
            sendJsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    if (isset($input['action']) && $input['action'] === 'update_menu_item_status') {
        try {
            $orderId = isset($input['orderId']) ? intval($input['orderId']) : null;
            $menuItemId = isset($input['menuItemId']) ? intval($input['menuItemId']) : null;
            $newStatus = isset($input['status']) ? strtoupper(trim($input['status'])) : null;

            if (!$orderId || !$menuItemId || !$newStatus) {
                throw new Exception('Order ID, Menu Item ID, and new status are required.');
            }

            // Validate newStatus
            $allowedStatuses = ['PENDING', 'PREPARING', 'READY FOR PICKUP'];
            if (!in_array($newStatus, $allowedStatuses)) {
                throw new Exception('Invalid menu item status.');
            }

            // Fetch the status ID from menu_item_status table
            $statusQuery = "SELECT ID FROM menu_item_status WHERE status_name = ?";
            $statusStmt = $conn->prepare($statusQuery);
            if (!$statusStmt) {
                throw new Exception('Prepare failed: ' . $conn->error);
            }
            $statusStmt->bind_param('s', $newStatus);
            $statusStmt->execute();
            $statusResult = $statusStmt->get_result();
            $statusRow = $statusResult->fetch_assoc();
            $statusStmt->close();

            if (!$statusRow) {
                throw new Exception('Menu item status not found.');
            }
            $statusId = $statusRow['ID'];

            // Update the order_items status_id
            $updateQuery = "UPDATE order_items SET status_id = ? WHERE order_ID = ? AND menu_item_ID = ?";
            $updateStmt = $conn->prepare($updateQuery);
            if (!$updateStmt) {
                throw new Exception('Prepare failed: ' . $conn->error);
            }
            $updateStmt->bind_param('iii', $statusId, $orderId, $menuItemId);
            if (!$updateStmt->execute()) {
                throw new Exception('Failed to update menu item status: ' . $updateStmt->error);
            }
            $updateStmt->close();

            sendJsonResponse(['success' => true]);

        } catch (Exception $e) {
            sendJsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

}
    

// /////////////////////////////////////////////////////


if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
  if ($_GET['action'] === 'get_menu_categories') {
      try {
          $sql = "SELECT ID, name FROM menu_category ORDER BY ID";
          $result = $conn->query($sql);
          
          if ($result) {
              $categories = [];
              while ($row = $result->fetch_assoc()) {
                  $categories[] = [
                      'ID' => $row['ID'],
                      'name' => $row['name']
                  ];
              }
              sendJsonResponse([
                  'status' => 'success',
                  'categories' => $categories
              ]);
          } else {
              throw new Exception($conn->error);
          }
      } catch (Exception $e) {
          sendJsonResponse([
              'status' => 'error',
              'message' => 'Database error: ' . $e->getMessage()
          ], 500);
      }
  }
}

// Handle AJAX requests for ingredient units
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
  if ($_GET['action'] === 'get_ingredient_units') {
      try {
          $sql = "SELECT ID, name FROM ing_unit ORDER BY ID";
          $result = $conn->query($sql);
          
          if ($result) {
              $ingredient_units = [];
              while ($row = $result->fetch_assoc()) {
                  $ingredient_units[] = [
                      'ID' => $row['ID'],
                      'name' => $row['name']
                  ];
              }
              sendJsonResponse([
                  'status' => 'success',
                  'ingredient_units' => $ingredient_units
              ]);
          } else {
              throw new Exception($conn->error);
          }
      } catch (Exception $e) {
          sendJsonResponse([
              'status' => 'error',
              'message' => 'Database error: ' . $e->getMessage()
          ], 500);
      }
  }
}

// Add this near your other action handlers
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
  if ($_GET['action'] === 'get_ingredients') {
      try {
          $sql = "SELECT i.ID, i.name, c.name as category_name, i.total_qty 
                  FROM ingredient i 
                  LEFT JOIN ing_category c ON i.ing_category = c.ID 
                  ORDER BY i.ID";
          $result = $conn->query($sql);
          
          if ($result) {
              $ingredients = [];
              while ($row = $result->fetch_assoc()) {
                  $ingredients[] = [
                      'ID' => $row['ID'],
                      'name' => $row['name'],
                      'category_name' => $row['category_name'],
                      'total_qty' => $row['total_qty']
                  ];
              }
              sendJsonResponse([
                  'status' => 'success',
                  'ingredients' => $ingredients
              ]);
          } else {
              throw new Exception($conn->error);
          }
      } catch (Exception $e) {
          sendJsonResponse([
              'status' => 'error',
              'message' => 'Database error: ' . $e->getMessage()
          ], 500);
      }
  }
}

// Handle POST requests for ingredient unit operations
if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['action'])) {
  $action = $_POST['action'] ?? $_GET['action'] ?? '';

  switch ($action) {
    case 'add_menu_item':
      try {
          $menu_item_name = trim($_POST['menu-item-name']);
          $menu_item_price = $_POST['menu-item-price'];
          $menu_category_id = $_POST['menu-category-combobox'];
          $menu_item_description = trim($_POST['menu-item-description']);
          
          // Handle image upload
          $image_folder = null;
          if (isset($_FILES['menu-item-img']) && $_FILES['menu-item-img']['error'] == 0) {
              $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
              if (!in_array($_FILES['menu-item-img']['type'], $allowed_types)) {
                  throw new Exception('Invalid image type');
              }
              
              $image_name = time() . '_' . basename($_FILES['menu-item-img']['name']);
              $image_folder = 'uploads/' . $image_name;
              if (!move_uploaded_file($_FILES['menu-item-img']['tmp_name'], $image_folder)) {
                  throw new Exception('Failed to upload image');
              }
          }
  
          $stmt = $conn->prepare("INSERT INTO menu_item (name, menu_category, price, description, image) VALUES (?, ?, ?, ?, ?)");
          $stmt->bind_param("sidss", $menu_item_name, $menu_category_id, $menu_item_price, $menu_item_description, $image_folder);
          
          if (!$stmt->execute()) {
              throw new Exception($stmt->error);
          }
  
          $menu_item_id = $stmt->insert_id;
  
          // Handle recipe ingredients
          if (isset($_POST['key_ingredient_ids']) && isset($_POST['ingredient_quantities'])) {
              $ingredient_ids = $_POST['key_ingredient_ids'];
              $quantities = $_POST['ingredient_quantities'];
              
              $insert_recipe = $conn->prepare("INSERT INTO menu_item (menu_item_id, ingredient_id, quantity) VALUES (?, ?, ?)");
              
              foreach ($ingredient_ids as $index => $ingredient_id) {
                  $quantity = $quantities[$index];
                  $insert_recipe->bind_param("iid", $menu_item_id, $ingredient_id, $quantity);
                  $insert_recipe->execute();
              }
          }
  
          sendJsonResponse([
              'status' => 'success',
              'message' => 'Menu item added successfully'
          ]);
  
      } catch (Exception $e) {
          sendJsonResponse([
              'status' => 'error',
              'message' => $e->getMessage()
          ], 500);
      }
      break;
  
      case 'update_menu_item':
        try {
            if (!isset($_POST['menu-item-id'])) {
                throw new Exception('Menu item ID is required');
            }
    
            $menu_item_id = $_POST['menu-item-id'];
            $menu_item_name = trim($_POST['menu-item-name']);
            $menu_item_price = $_POST['menu-item-price'];
            $menu_category_id = $_POST['menu-category-combobox'];
            $menu_item_description = trim($_POST['menu-item-description']);
            $menu_item_status = $_POST['menu-item-status'];
            $main_ingredient = $_POST['main-ingredient'];
    
            $image_folder = $_POST['existing_image'];
            if (isset($_FILES['menu-item-img']) && $_FILES['menu-item-img']['error'] == 0) {
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                if (!in_array($_FILES['menu-item-img']['type'], $allowed_types)) {
                    throw new Exception('Invalid image type');
                }
                $image_name = time() . '_' . basename($_FILES['menu-item-img']['name']);
                $image_folder = 'uploads/' . $image_name;
                if (!move_uploaded_file($_FILES['menu-item-img']['tmp_name'], $image_folder)) {
                    throw new Exception('Failed to upload image');
                }
            }
    
            $stmt = $conn->prepare("UPDATE menu_item SET name = ?, menu_category = ?, price = ?, description = ?, image = ?, menu_item_status = ?, main_ingredient = ? WHERE ID = ?");
            $stmt->bind_param("sidssiii", $menu_item_name, $menu_category_id, $menu_item_price, $menu_item_description, $image_folder, $menu_item_status, $main_ingredient, $menu_item_id);
            
            if (!$stmt->execute()) {
                throw new Exception($stmt->error);
            }
    
            sendJsonResponse([
                'status' => 'success',
                'message' => 'Menu item updated successfully'
            ]);
    
        } catch (Exception $e) {
            sendJsonResponse([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
        break;
  
        case 'delete_menu_item':
          try {
              if (!isset($_POST['menu-item-id'])) {
                  throw new Exception('Menu item ID is required');
              }
      
              $menu_item_id = $_POST['menu-item-id'];
      
              $stmt = $conn->prepare("SELECT image FROM menu_item WHERE ID = ?");
              $stmt->bind_param("i", $menu_item_id);
              $stmt->execute();
              $result = $stmt->get_result();
              $row = $result->fetch_assoc();
              $image_path = $row['image'];
      
              $delete_item_stmt = $conn->prepare("DELETE FROM menu_item WHERE ID = ?");
              $delete_item_stmt->bind_param("i", $menu_item_id);
              
              if (!$delete_item_stmt->execute()) {
                  throw new Exception($delete_item_stmt->error);
              }
      
              if ($image_path && file_exists($image_path)) {
                  unlink($image_path);
              }
      
              sendJsonResponse([
                  'status' => 'success',
                  'message' => 'Menu item deleted successfully'
              ]);
      
          } catch (Exception $e) {
              sendJsonResponse([
                  'status' => 'error',
                  'message' => $e->getMessage()
              ], 500);
          }
          break;
  
        case 'get_menu_item':
          try {
              $sql = "SELECT m.*, mc.name as category_name 
                      FROM menu_item m 
                      LEFT JOIN menu_category mc ON m.menu_category = mc.ID 
                      ORDER BY m.ID";
              $result = $conn->query($sql);
              
              if ($result) {
                  $items = [];
                  while ($row = $result->fetch_assoc()) {
                      $items[] = [
                          'ID' => $row['ID'],
                          'name' => $row['name'],
                          'category_name' => $row['category_name'],
                          'price' => $row['price'],
                          'description' => $row['description'],
                          'image' => $row['image']
                      ];
                  }
                  sendJsonResponse([
                      'status' => 'success',
                      'items' => $items
                  ]);
              } else {
                  throw new Exception($conn->error);
              }
          } catch (Exception $e) {
              sendJsonResponse([
                  'status' => 'error',
                  'message' => $e->getMessage()
              ], 500);
          }
          break;

    case 'add_menu_category':
      try {
          if (!isset($_POST['menu-category-name']) || empty(trim($_POST['menu-category-name']))) {
              throw new Exception('Menu category name is required');
          }
  
          $name = trim($_POST['menu-category-name']);
  
          // Check if name already exists
          $check_sql = "SELECT ID FROM menu_category WHERE name = ?";
          $check_stmt = $conn->prepare($check_sql);
          $check_stmt->bind_param("s", $name);
          $check_stmt->execute();
          $check_result = $check_stmt->get_result();
  
          if ($check_result->num_rows > 0) {
              throw new Exception('A menu category with this name already exists');
          }
  
          // Insert new menu category
          $sql = "INSERT INTO menu_category (name) VALUES (?)";
          $stmt = $conn->prepare($sql);
          $stmt->bind_param("s", $name);
  
          if ($stmt->execute()) {
              sendJsonResponse([
                  'status' => 'success',
                  'message' => 'Menu category added successfully',
                  'new_id' => $conn->insert_id,
                  'name' => $name
              ]);
          } else {
              throw new Exception($stmt->error);
          }
      } catch (Exception $e) {
          sendJsonResponse([
              'status' => 'error',
              'message' => $e->getMessage()
          ], 500);
      }
      break;
  
  case 'update_menu_category':
      try {
          if (!isset($_POST['menu-category-id']) || !isset($_POST['menu-category-name']) || 
              empty(trim($_POST['menu-category-name']))) {
              throw new Exception('Both ID and name are required for update');
          }
  
          $id = (int)$_POST['menu-category-id'];
          $name = trim($_POST['menu-category-name']);
  
          // Check if name exists for other IDs
          $check_sql = "SELECT ID FROM menu_category WHERE name = ? AND ID != ?";
          $check_stmt = $conn->prepare($check_sql);
          $check_stmt->bind_param("si", $name, $id);
          $check_stmt->execute();
          $check_result = $check_stmt->get_result();
  
          if ($check_result->num_rows > 0) {
              throw new Exception('A menu category with this name already exists');
          }
  
          // Update menu category
          $sql = "UPDATE menu_category SET name = ? WHERE ID = ?";
          $stmt = $conn->prepare($sql);
          $stmt->bind_param("si", $name, $id);
  
          if ($stmt->execute()) {
              sendJsonResponse([
                  'status' => 'success',
                  'message' => 'Menu category updated successfully',
                  'id' => $id,
                  'name' => $name
              ]);
          } else {
              throw new Exception($stmt->error);
          }
      } catch (Exception $e) {
          sendJsonResponse([
              'status' => 'error',
              'message' => $e->getMessage()
          ], 500);
      }
      break;
  
  case 'delete_menu_category':
      try {
          if (!isset($_POST['menu-category-id'])) {
              throw new Exception('Menu category ID is required for deletion');
          }
  
          $id = (int)$_POST['menu-category-id'];
  
          // First check if there are any menu items using this category
          $check_sql = "SELECT COUNT(*) as count FROM menu_item WHERE menu_category = ?";
          $check_stmt = $conn->prepare($check_sql);
          $check_stmt->bind_param("i", $id);
          $check_stmt->execute();
          $result = $check_stmt->get_result();
          $count = $result->fetch_assoc()['count'];
  
          if ($count > 0) {
              throw new Exception('Cannot delete category: It is being used by menu items');
          }
  
          // Delete the menu category
          $sql = "DELETE FROM menu_category WHERE ID = ?";
          $stmt = $conn->prepare($sql);
          $stmt->bind_param("i", $id);
  
          if ($stmt->execute()) {
              sendJsonResponse([
                  'status' => 'success',
                  'message' => 'Menu category deleted successfully',
                  'id' => $id
              ]);
          } else {
              throw new Exception($stmt->error);
          }
      } catch (Exception $e) {
          sendJsonResponse([
              'status' => 'error',
              'message' => $e->getMessage()
          ], 500);
      }
      break;

    case 'add_ingredient':
      try {
          if (!isset($_POST['ingredient-name']) || empty(trim($_POST['ingredient-name']))) {
              throw new Exception('Ingredient name is required');
          }

          $name = trim($_POST['ingredient-name']);
          $category_id = $_POST['ingredient-category-combobox'];
          $unit_id = $_POST['ingredient-unit-combobox'];
          $low_stock_threshold = $_POST['ingredient-low-stock-threshold'];
          $medium_stock_threshold = $_POST['ingredient-medium-stock-threshold'];
          $reorder_point = $_POST['ingredient-reorder-point'];
          $willAutoDeduct = $_POST['ingredient-auto-deduct'] == 'true' ? 1 : 0;
          $total_qty = 0;

          // Check if name already exists
          $check_sql = "SELECT ID FROM ingredient WHERE name = ?";
          $check_stmt = $conn->prepare($check_sql);
          $check_stmt->bind_param("s", $name);
          $check_stmt->execute();
          $check_result = $check_stmt->get_result();

          if ($check_result->num_rows > 0) {
              throw new Exception('An ingredient with this name already exists');
          }

          // Insert new ingredient
          $sql = "INSERT INTO ingredient (name, ing_category, ing_unit, low_stock_th, medium_stock_th, reorder_point, total_qty, willAutoDeduct) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
          $stmt = $conn->prepare($sql);
          $stmt->bind_param("siiiddid", $name, $category_id, $unit_id, $low_stock_threshold, $medium_stock_threshold, $reorder_point, $total_qty, $willAutoDeduct);

          if ($stmt->execute()) {
              sendJsonResponse([
                  'status' => 'success',
                  'message' => 'Ingredient added successfully',
                  'new_id' => $conn->insert_id,
                  'new_name' => $name
              ]);
          } else {
              throw new Exception($stmt->error);
          }
      } catch (Exception $e) {
          sendJsonResponse([
              'status' => 'error',
              'message' => $e->getMessage()
          ], 500);
      }
      break;

      case 'update_ingredient':
        try {
            if (!isset($_POST['ingredient-id']) || !isset($_POST['ingredient-name']) || 
                empty(trim($_POST['ingredient-name']))) {
                throw new Exception('Both ID and name are required for update');
            }

            $id = (int)$_POST['ingredient-id'];
            $name = trim($_POST['ingredient-name']);
            $category_id = $_POST['ingredient-category-combobox'];
            $unit_id = $_POST['ingredient-unit-combobox'];
            $low_stock_threshold = $_POST['ingredient-low-stock-threshold'];
            $medium_stock_threshold = $_POST['ingredient-medium-stock-threshold'];
            $reorder_point = $_POST['ingredient-reorder-point'];
            $willAutoDeduct = $_POST['ingredient-auto-deduct'] == 'true' ? 1 : 0;

            // Check if name exists for different ID
            $check_sql = "SELECT ID FROM ingredient WHERE name = ? AND ID != ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("si", $name, $id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();

            if ($check_result->num_rows > 0) {
                throw new Exception('An ingredient with this name already exists');
            }

            // Update ingredient
            $sql = "UPDATE ingredient SET name = ?, ing_category = ?, ing_unit = ?, low_stock_th = ?, medium_stock_th = ?, reorder_point = ?, willAutoDeduct = ? WHERE ID = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("siiiddii", $name, $category_id, $unit_id, $low_stock_threshold, $medium_stock_threshold, $reorder_point, $willAutoDeduct, $id);

            if ($stmt->execute()) {
                sendJsonResponse([
                    'status' => 'success',
                    'message' => 'Ingredient updated successfully',
                    'id' => $id,
                    'name' => $name
                ]);
            } else {
                throw new Exception($stmt->error);
            }
        } catch (Exception $e) {
            sendJsonResponse([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
        break;

case 'delete_ingredient':
    try {
        if (!isset($_POST['ingredient-id'])) {
            throw new Exception('Ingredient ID is required for deletion');
        }

        $id = (int)$_POST['ingredient-id'];
        

        $sql = "DELETE FROM ingredient WHERE ID = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows === 0) {
                throw new Exception('No ingredient found with the specified ID');
            }
            sendJsonResponse([
                'status' => 'success',
                'message' => 'Ingredient deleted successfully',
                'deleted_id' => $id
            ]);
        } else {
            throw new Exception('Failed to delete ingredient: ' . $stmt->error);
        }
    } catch (Exception $e) {
        sendJsonResponse([
            'status' => 'error',
            'message' => $e->getMessage()
        ], 500);
    }
    break;
      


      case 'add_ingredient_unit':
          try {
              if (!isset($_POST['ingredient_unit_name']) || empty(trim($_POST['ingredient_unit_name']))) {
                  throw new Exception('Ingredient unit name is required');
              }

              $name = trim($_POST['ingredient_unit_name']);
              
              // Check if name already exists
              $check_sql = "SELECT ID FROM ing_unit WHERE name = ?";
              $check_stmt = $conn->prepare($check_sql);
              $check_stmt->bind_param("s", $name);
              $check_stmt->execute();
              $check_result = $check_stmt->get_result();
              
              if ($check_result->num_rows > 0) {
                  throw new Exception('An ingredient unit with this name already exists');
              }
              
              // Insert new ingredient unit
              $sql = "INSERT INTO ing_unit (name) VALUES (?)";
              $stmt = $conn->prepare($sql);
              $stmt->bind_param("s", $name);
              
              if ($stmt->execute()) {
                  sendJsonResponse([
                      'status' => 'success',
                      'message' => 'Ingredient unit added successfully',
                      'new_id' => $conn->insert_id,
                      'new_name' => $name
                  ]);
              } else {
                  throw new Exception($stmt->error);
              }
          } catch (Exception $e) {
              sendJsonResponse([
                  'status' => 'error',
                  'message' => $e->getMessage()
              ], 500);
          }
          break;

      case 'update_ingredient_unit':
          try {
              if (!isset($_POST['ingredient_unit_id']) || !isset($_POST['ingredient_unit_name']) || 
                  empty(trim($_POST['ingredient_unit_name']))) {
                  throw new Exception('Both ID and name are required for update');
              }

              $id = (int)$_POST['ingredient_unit_id'];
              $name = trim($_POST['ingredient_unit_name']);
              
              // Check if name exists for different ID
              $check_sql = "SELECT ID FROM ing_unit WHERE name = ? AND ID != ?";
              $check_stmt = $conn->prepare($check_sql);
              $check_stmt->bind_param("si", $name, $id);
              $check_stmt->execute();
              $check_result = $check_stmt->get_result();
              
              if ($check_result->num_rows > 0) {
                  throw new Exception('An ingredient unit with this name already exists');
              }
              
              // Update ingredient unit
              $sql = "UPDATE ing_unit SET name = ? WHERE ID = ?";
              $stmt = $conn->prepare($sql);
              $stmt->bind_param("si", $name, $id);
              
              if ($stmt->execute()) {
                  sendJsonResponse([
                      'status' => 'success',
                      'message' => 'Ingredient unit updated successfully',
                      'id' => $id,
                      'name' => $name
                  ]);
              } else {
                  throw new Exception($stmt->error);
              }
          } catch (Exception $e) {
              sendJsonResponse([
                  'status' => 'error',
                  'message' => $e->getMessage()
              ], 500);
          }
          break;

      case 'delete_ingredient_unit':
          try {
              if (!isset($_POST['ingredient_unit_id'])) {
                  throw new Exception('Ingredient unit ID is required for deletion');
              }

              $id = (int)$_POST['ingredient_unit_id'];
              
              // Check if the ingredient unit is in use
              $check_sql = "SELECT ID FROM ingredient WHERE ing_unit = ?";
              $check_stmt = $conn->prepare($check_sql);
              $check_stmt->bind_param("i", $id);
              $check_stmt->execute();
              $check_result = $check_stmt->get_result();
              
              if ($check_result->num_rows > 0) {
                  throw new Exception('Cannot delete this ingredient unit as it is being used by ingredients');
              }
              
              // Delete ingredient unit
              $sql = "DELETE FROM ing_unit WHERE ID = ?";
              $stmt = $conn->prepare($sql);
              $stmt->bind_param("i", $id);
              
              if ($stmt->execute()) {
                  sendJsonResponse([
                      'status' => 'success',
                      'message' => 'Ingredient unit deleted successfully',
                      'id' => $id
                  ]);
              } else {
                  throw new Exception($stmt->error);
              }
          } catch (Exception $e) {
              sendJsonResponse([
                  'status' => 'error',
                  'message' => $e->getMessage()
              ], 500);
          }
          break;

          case 'get_ingredient_categories':
            // Set proper JSON header
            header('Content-Type: application/json');
            
            try {
                $sql = "SELECT ID, name FROM ing_category ORDER BY ID";
                $result = $conn->query($sql);
                
                if (!$result) {
                    throw new Exception($conn->error);
                }
                
                $ingredient_categories = array();
                while ($row = $result->fetch_assoc()) {
                    $ingredient_categories[] = array(
                        'ID' => $row['ID'],
                        'name' => $row['name']
                    );
                }
                
                echo json_encode(array(
                    'status' => 'success',
                    'ingredient_categories' => $ingredient_categories
                ));
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(array(
                    'status' => 'error',
                    'message' => 'Failed to fetch ingredient categories: ' . $e->getMessage()
                ));
            }
            exit; 
        
            case 'add_ingredient_category':
              header('Content-Type: application/json');
              try {
                  // Debug line to check POST data
                  error_log('POST data: ' . print_r($_POST, true));
                  
                  if (!isset($_POST['ingredient_category_name'])) {
                      throw new Exception('Category name not provided in request');
                  }
                  
                  $name = trim($_POST['ingredient_category_name']);
                  
                  // Validate input
                  if (empty($name)) {
                      throw new Exception('Ingredient category name cannot be empty');
                  }
                  
                  // Check for duplicate name
                  $stmt = $conn->prepare("SELECT ID FROM ing_category WHERE name = ?");
                  if (!$stmt) {
                      throw new Exception('Prepare failed: ' . $conn->error);
                  }
                  
                  $stmt->bind_param("s", $name);
                  if (!$stmt->execute()) {
                      throw new Exception('Execute failed: ' . $stmt->error);
                  }
                  
                  $result = $stmt->get_result();
                  if ($result->num_rows > 0) {
                      throw new Exception('An ingredient category with this name already exists');
                  }
                  
                  // Insert new category
                  $stmt = $conn->prepare("INSERT INTO ing_category (name) VALUES (?)");
                  if (!$stmt) {
                      throw new Exception('Prepare failed: ' . $conn->error);
                  }
                  
                  $stmt->bind_param("s", $name);
                  
                  if ($stmt->execute()) {
                      $new_id = $conn->insert_id;
                      echo json_encode(array(
                          'status' => 'success',
                          'message' => 'Ingredient category added successfully',
                          'new_id' => $new_id,
                          'new_name' => $name
                      ));
                  } else {
                      throw new Exception('Failed to add ingredient category: ' . $stmt->error);
                  }
              } catch (Exception $e) {
                  echo json_encode(array(
                      'status' => 'error',
                      'message' => $e->getMessage()
                  ));
              }
              exit;
        
        case 'update_ingredient_category':
          header('Content-Type: application/json');
            try {
                $id = $_POST['ingredient_category_id'];
                $name = $_POST['ingredient_category_name'];
                
                // Validate input
                if (empty($id) || empty($name)) {
                    throw new Exception('Both ID and name are required');
                }
                
                // Check if ID exists
                $stmt = $conn->prepare("SELECT ID FROM ing_category WHERE ID = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                if ($stmt->get_result()->num_rows === 0) {
                    throw new Exception('Ingredient category not found');
                }
                
                // Check for duplicate name excluding current ID
                $stmt = $conn->prepare("SELECT ID FROM ing_category WHERE name = ? AND ID != ?");
                $stmt->bind_param("si", $name, $id);
                $stmt->execute();
                if ($stmt->get_result()->num_rows > 0) {
                    throw new Exception('An ingredient category with this name already exists');
                }
                
                // Update category
                $stmt = $conn->prepare("UPDATE ing_category SET name = ? WHERE ID = ?");
                $stmt->bind_param("si", $name, $id);
                
                if ($stmt->execute()) {
                    echo json_encode(array(
                        'status' => 'success',
                        'message' => 'Ingredient category updated successfully',
                        'id' => $id,
                        'name' => $name
                    ));
                } else {
                    throw new Exception('Failed to update ingredient category');
                }
            } catch (Exception $e) {
                echo json_encode(array(
                    'status' => 'error',
                    'message' => $e->getMessage()
                ));
            }
            exit;
        
        case 'delete_ingredient_category':
          header('Content-Type: application/json');
            try {
                $id = $_POST['ingredient_category_id'];
                
                // Validate input
                if (empty($id)) {
                    throw new Exception('Ingredient category ID is required');
                }
                
                // Check if category is in use
                $stmt = $conn->prepare("SELECT ID FROM ingredient WHERE ing_category = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                if ($stmt->get_result()->num_rows > 0) {
                    throw new Exception('Cannot delete: This category is being used by one or more ingredients');
                }
                
                // Delete category
                $stmt = $conn->prepare("DELETE FROM ing_category WHERE ID = ?");
                $stmt->bind_param("i", $id);
                
                if ($stmt->execute()) {
                    echo json_encode(array(
                        'status' => 'success',
                        'message' => 'Ingredient category deleted successfully'
                    ));
                } else {
                    throw new Exception('Failed to delete ingredient category');
                }
            } catch (Exception $e) {
                echo json_encode(array(
                    'status' => 'error',
                    'message' => $e->getMessage()
                ));
            }
            exit;

      default:
          sendJsonResponse([
              'status' => 'error',
              'message' => 'Invalid action specified'
          ], 400);
          break;
  }
}
if (isset($_GET['action']) || isset($_POST['action'])) {
  sendJsonResponse([
      'status' => 'error',
      'message' => 'Invalid request'
  ], 400);
}


// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  if (isset($_POST['action'])) {
    $action = $_POST['action'];

    $current_page = isset($_POST['current_page']) ? $_POST['current_page'] : '';
    $response = ['status' => 'success', 'message' => 'Action completed successfully'];

    // Handle adding Ingredient
    if ($_POST['action'] == 'add_ingredient') {
      // Get the form data
      $ingredient_name = trim($_POST['ingredient-name']);
      $ingredient_category_id = $_POST['ingredient-category-combobox'];
      $ingredient_unit_id = $_POST['ingredient-unit-combobox'];
      $low_stock_threshold = $_POST['ingredient-low-stock-threshold'];
      $medium_stock_threshold = $_POST['ingredient-medium-stock-threshold'];
      $reorder_point = $_POST['ingredient-reorder-point'];
      $willAutoDeduct = $_POST['ingredient-auto-deduct'] == 'true' ? 1 : 0;
      $total_qty = 0; // Initially zero

      // Prepare statement
      $stmt = $conn->prepare("INSERT INTO ingredient (name, ing_category, ing_unit, low_stock_th, medium_stock_th, reorder_point, total_qty, willAutoDeduct) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
      $stmt->bind_param("siiiddid", $ingredient_name, $ingredient_category_id, $ingredient_unit_id, $low_stock_threshold, $medium_stock_threshold, $reorder_point, $total_qty, $willAutoDeduct);
      if ($stmt->execute()) {
        echo "<script>alert('Ingredient added successfully');</script>";
      } else {
        echo "<script>alert('Error adding ingredient');</script>";
      }
      $stmt->close();
    }    

    // Handle updating Ingredient
    if ($_POST['action'] == 'update_ingredient') {
      $ingredient_id = $_POST['ingredient-id'];
      $ingredient_name = trim($_POST['ingredient-name']);
      $ingredient_category_id = $_POST['ingredient-category-combobox'];
      $ingredient_unit_id = $_POST['ingredient-unit-combobox'];
      $low_stock_threshold = $_POST['ingredient-low-stock-threshold'];
      $medium_stock_threshold = $_POST['ingredient-medium-stock-threshold'];
      $reorder_point = $_POST['ingredient-reorder-point'];
      $willAutoDeduct = $_POST['ingredient-auto-deduct'] == 'true' ? 1 : 0;

      // Prepare statement
      $stmt = $conn->prepare("UPDATE ingredient SET name = ?, ing_category = ?, ing_unit = ?, low_stock_th = ?, medium_stock_th = ?, reorder_point = ?, willAutoDeduct = ? WHERE ID = ?");
      $stmt->bind_param("siiiddii", $ingredient_name, $ingredient_category_id, $ingredient_unit_id, $low_stock_threshold, $medium_stock_threshold, $reorder_point, $willAutoDeduct, $ingredient_id);
      if ($stmt->execute()) {
        echo "<script>alert('Ingredient updated successfully');</script>";
      } else {
        echo "<script>alert('Error updating ingredient');</script>";
      }
      $stmt->close();
    }

    // Handle deleting Ingredient
    if ($_POST['action'] == 'delete_ingredient') {
      $ingredient_id = $_POST['ingredient-id'];
      if (!empty($ingredient_id)) {

          $stmt = $conn->prepare("DELETE FROM menu_item WHERE ingredient_id = ?");
          $stmt->bind_param("i", $ingredient_id);
          $stmt->execute();
          $stmt->close();
  
          // Then delete the ingredient
          $stmt = $conn->prepare("DELETE FROM ingredient WHERE ID = ?");
          $stmt->bind_param("i", $ingredient_id);
          if ($stmt->execute()) {
              echo "<script>alert('Ingredient deleted successfully');</script>";
          } else {
              echo "<script>alert('Error deleting ingredient');</script>";
          }
          $stmt->close();
      }
  }


      // Handle adding menu category
      if ($_POST['action'] == 'add_menu_category') {
          $menu_category_name = trim($_POST['menu-category-name']);
          if (!empty($menu_category_name)) {
              // Prepare statement
              $stmt = $conn->prepare("INSERT INTO menu_category (name) VALUES (?)");
              $stmt->bind_param("s", $menu_category_name);
              if ($stmt->execute()) {
                  echo "<script>alert('Menu category added successfully');</script>";
              } else {
                  echo "<script>alert('Error adding menu category');</script>";
              }
              $stmt->close();
          }
      }

      // Handle updating menu category
      if ($_POST['action'] == 'update_menu_category') {
        try {
            $menu_category_id = $_POST['menu-category-id'];
            $menu_category_name = trim($_POST['menu-category-name']);
            
            if (empty($menu_category_id) || empty($menu_category_name)) {
                throw new Exception('Menu category ID and name are required');
            }
    
            // Check if name already exists for different ID
            $check_sql = "SELECT ID FROM menu_category WHERE name = ? AND ID != ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("si", $menu_category_name, $menu_category_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
    
            if ($check_result->num_rows > 0) {
                throw new Exception('A menu category with this name already exists');
            }
    
            // Update menu category
            $stmt = $conn->prepare("UPDATE menu_category SET name = ? WHERE ID = ?");
            $stmt->bind_param("si", $menu_category_name, $menu_category_id);
            
            if ($stmt->execute()) {
                sendJsonResponse([
                    'status' => 'success',
                    'message' => 'Menu category updated successfully'
                ]);
            } else {
                throw new Exception($stmt->error);
            }
        } catch (Exception $e) {
            sendJsonResponse([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

      // Handle deleting menu category
      if ($_POST['action'] == 'delete_menu_category') {
        try {
            $menu_category_id = $_POST['menu-category-id'];
            
            if (empty($menu_category_id)) {
                throw new Exception('Menu category ID is required');
            }
    
            // Check if category is in use
            $check_sql = "SELECT ID FROM menu_item WHERE menu_category = ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("i", $menu_category_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
    
            if ($check_result->num_rows > 0) {
                throw new Exception('Cannot delete: This category is being used by menu items');
            }
    
            // Delete menu category
            $stmt = $conn->prepare("DELETE FROM menu_category WHERE ID = ?");
            $stmt->bind_param("i", $menu_category_id);
            
            if ($stmt->execute()) {
                sendJsonResponse([
                    'status' => 'success',
                    'message' => 'Menu category deleted successfully'
                ]);
            } else {
                throw new Exception($stmt->error);
            }
        } catch (Exception $e) {
            sendJsonResponse([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }


// Handle adding menu item
if ($_POST['action'] == 'add_menu_item') {
  // Get the form data
  $menu_item_name = trim($_POST['menu-item-name']);
  $menu_item_price = $_POST['menu-item-price'];
  $menu_category_id = $_POST['menu-category-combobox'];
  $menu_item_description = trim($_POST['menu-item-description']);

  // Handle image upload (same as before)
  if (isset($_FILES['menu-item-img']) && $_FILES['menu-item-img']['error'] == 0) {
      // Validate image
      $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
      if (in_array($_FILES['menu-item-img']['type'], $allowed_types)) {
          $image_name = basename($_FILES['menu-item-img']['name']);
          $image_tmp = $_FILES['menu-item-img']['tmp_name'];
          $upload_dir = 'uploads/';

          // Ensure the uploads directory exists
          if (!is_dir($upload_dir)) {
              mkdir($upload_dir, 0755, true);
          }

          $image_folder = $upload_dir . $image_name;
          move_uploaded_file($image_tmp, $image_folder);
      } else {
          echo "<script>alert('Invalid image type');</script>";
          exit();
      }
  } else {
      $image_folder = null; // No image uploaded
  }

  // Prepare and execute statement to insert into menu_item table
  $stmt = $conn->prepare("INSERT INTO menu_item (name, menu_category, price, description, image) VALUES (?, ?, ?, ?, ?)");
  $stmt->bind_param("sidss", $menu_item_name, $menu_category_id, $menu_item_price, $menu_item_description, $image_folder);

  if ($stmt->execute()) {
      // Now, handle updating ingredient quantities
      if (isset($_POST['key_ingredient_ids']) && isset($_POST['ingredient_quantities'])) {
          $ingredient_ids = $_POST['key_ingredient_ids']; // IDs of the ingredients
          $ingredient_quantities = $_POST['ingredient_quantities'];

          // Ensure arrays are of the same length
          if (count($ingredient_ids) === count($ingredient_quantities)) {
              for ($i = 0; $i < count($ingredient_ids); $i++) {
                  $ingredient_id = $ingredient_ids[$i];
                  $quantity = $ingredient_quantities[$i];

                  // Update the total_qty of the ingredient
                  $stmt2 = $conn->prepare("UPDATE ingredient SET total_qty = total_qty - ? WHERE ID = ?");
                  $stmt2->bind_param("di", $quantity, $ingredient_id);
                  $stmt2->execute();
                  $stmt2->close();
              }
          }
      }

      echo "<script>alert('Menu item added and ingredient quantities updated successfully');</script>";
  } else {
      echo "<script>alert('Error adding menu item');</script>";
  }
  $stmt->close();
}



      // Handle updating menu item
      if ($_POST['action'] == 'update_menu_item') {
          // Get the form data
          $menu_item_id = $_POST['menu-item-id'];
          $menu_item_name = trim($_POST['menu-item-name']);
          $menu_item_price = $_POST['menu-item-price'];
          $menu_category_id = $_POST['menu-category-combobox'];
          $menu_item_description = trim($_POST['menu-item-description']);
          // Handle image upload
          if (isset($_FILES['menu-item-img']) && $_FILES['menu-item-img']['error'] == 0) {
              // Validate image
              $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
              if (in_array($_FILES['menu-item-img']['type'], $allowed_types)) {
                  $image_name = basename($_FILES['menu-item-img']['name']);
                  $image_tmp = $_FILES['menu-item-img']['tmp_name'];
                  $image_folder = 'uploads/' . $image_name;
                  move_uploaded_file($image_tmp, $image_folder);
              } else {
                  echo "<script>alert('Invalid image type');</script>";
                  exit();
              }
          } else {
              $image_folder = $_POST['existing_image'] ?? null; // Use existing image if no new image is uploaded
          }

          // Prepare statement
          $stmt = $conn->prepare("UPDATE menu_item SET name = ?, menu_category = ?, price = ?, description = ?, image = ? WHERE ID = ?");
          $stmt->bind_param("sidsi", $menu_item_name, $menu_category_id, $menu_item_price, $menu_item_description, $image_folder, $menu_item_id);
          if ($stmt->execute()) {
              echo "<script>alert('Menu item updated successfully');</script>";
          } else {
              echo "<script>alert('Error updating menu item');</script>";
          }
          $stmt->close();
      }

      // Handle deleting menu item
      if ($_POST['action'] == 'delete_menu_item') {
          $menu_item_id = $_POST['menu-item-id'];
          if (!empty($menu_item_id)) {
              // Prepare statement
              $stmt = $conn->prepare("DELETE FROM menu_item WHERE ID = ?");
              $stmt->bind_param("i", $menu_item_id);
              if ($stmt->execute()) {
                  echo "<script>alert('Menu item deleted successfully');</script>";
              } else {
                  echo "<script>alert('Error deleting menu item');</script>";
              }
              $stmt->close();
          }
      }




      // Handle adding Ingredient Unit
      if ($action === 'add_ingredient_unit') {
        if (isset($_POST['ingredient_unit_name'])) {
            $ingredient_unit_name = sanitizeInput($_POST['ingredient_unit_name'], $conn);
    
            if (empty($ingredient_unit_name)) {
                $response = ['status' => 'error', 'message' => 'Ingredient Unit name cannot be empty.'];
            } else {
                $insert_sql = "INSERT INTO ing_unit (name) VALUES (?)";
                $insert_stmt = $conn->prepare($insert_sql);
    
                if ($insert_stmt) {
                    $insert_stmt->bind_param("s", $ingredient_unit_name);
                    if ($insert_stmt->execute()) {
                        $new_id = $insert_stmt->insert_id;
                        $response = [
                            'status' => 'success',
                            'message' => 'Ingredient Unit added successfully.',
                            'new_id' => $new_id,
                            'new_name' => $ingredient_unit_name
                        ];
                    } else {
                        $response = ['status' => 'error', 'message' => 'Failed to add Ingredient Unit: ' . $insert_stmt->error];
                    }
                    $insert_stmt->close();
                } else {
                    $response = ['status' => 'error', 'message' => 'Database error: ' . $conn->error];
                }
            }
        } else {
            $response = ['status' => 'error', 'message' => 'Ingredient Unit name is required.'];
        }
    }

    // Handle Update Ingredient Unit
    elseif ($action === 'update_ingredient_unit') {
      // Handle updating Ingredient Unit
      if (isset($_POST['ingredient_unit_id']) && isset($_POST['ingredient_unit_name'])) {
          $ingredient_unit_id = intval($_POST['ingredient_unit_id']);
          $ingredient_unit_name = sanitizeInput($_POST['ingredient_unit_name'], $conn);
          
          if ($ingredient_unit_id <= 0) {
              $response = ['status' => 'error', 'message' => 'Invalid Ingredient Unit ID.'];
          } elseif (empty($ingredient_unit_name)) {
              $response = ['status' => 'error', 'message' => 'Ingredient Unit name cannot be empty.'];
          } else {
              // Prepare the UPDATE statement
              $update_sql = "UPDATE ing_unit SET name = ? WHERE ID = ?";
              $update_stmt = $conn->prepare($update_sql);
              
              if ($update_stmt) {
                  $update_stmt->bind_param("si", $ingredient_unit_name, $ingredient_unit_id);
                  if ($update_stmt->execute()) {
                      if ($update_stmt->affected_rows > 0) {
                          $response = [
                              'status' => 'success',
                              'message' => 'Ingredient Unit updated successfully.',
                              'id' => $ingredient_unit_id,
                              'name' => $ingredient_unit_name
                          ];
                      } else {
                          $response = ['status' => 'error', 'message' => 'No changes made or Ingredient Unit not found.'];
                      }
                  } else {
                      $response = ['status' => 'error', 'message' => 'Failed to update Ingredient Unit: ' . $update_stmt->error];
                  }
                  $update_stmt->close();
              } else {
                  $response = ['status' => 'error', 'message' => 'Database error: ' . $conn->error];
              }
          }
      } else {
          $response = ['status' => 'error', 'message' => 'Ingredient Unit ID and name are required for update.'];
      }
  }
  elseif ($action === 'delete_ingredient_unit') {
      // Handle deleting Ingredient Unit
      if (isset($_POST['ingredient_unit_id'])) {
          $ingredient_unit_id = intval($_POST['ingredient_unit_id']);
          
          if ($ingredient_unit_id <= 0) {
              $response = ['status' => 'error', 'message' => 'Invalid Ingredient Unit ID.'];
          } else {
              // Prepare the DELETE statement
              $delete_sql = "DELETE FROM ing_unit WHERE ID = ?";
              $delete_stmt = $conn->prepare($delete_sql);
              
              if ($delete_stmt) {
                  $delete_stmt->bind_param("i", $ingredient_unit_id);
                  if ($delete_stmt->execute()) {
                      if ($delete_stmt->affected_rows > 0) {
                          $response = ['status' => 'success', 'message' => 'Ingredient Unit deleted successfully.', 'id' => $ingredient_unit_id];
                      } else {
                          $response = ['status' => 'error', 'message' => 'Ingredient Unit not found.'];
                      }
                  } else {
                      $response = ['status' => 'error', 'message' => 'Failed to delete Ingredient Unit: ' . $delete_stmt->error];
                  }
                  $delete_stmt->close();
              } else {
                  $response = ['status' => 'error', 'message' => 'Database error: ' . $conn->error];
              }
          }
      } else {
          $response = ['status' => 'error', 'message' => 'Ingredient Unit ID is required for deletion.'];
      }
  }
  else {
      $response = ['status' => 'error', 'message' => 'Unknown action requested.'];
  }

  // Respond based on request type
  if ($isAjax) {
      sendJsonResponse($response);
  } else {
      // For standard requests, set session messages or use other methods to display messages after redirect
      // Here, we'll use a simple meta-refresh to redirect back to the current page

      // Sanitize the current_page to prevent header injection
      $allowed_pages = ['ingredientUnit', /* Add other allowed pages here */];
      if (!in_array($current_page, $allowed_pages)) {
          $current_page = 'ingredientUnit'; // Default page
      }

      // Optionally, you can pass messages via query parameters or session
      // For simplicity, we'll just redirect to the current page
      echo "<meta http-equiv='refresh' content='0;url=management.php?page=" . urlencode($current_page) . "'>";
      exit();
  }
}
}
?>

<!DOCTYPE html>
<html>

<head>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
      <link rel="stylesheet" href="restaurant-management-system.css">
      <script src="restaurant-management-system.js"></script>
      <title> Mgmt. System | Cara's Food Haven </title>
      <link rel="shortcut icon" type="x-icon" href="../RestaurantLogo-Cut.png">
  </head>

<body>
    
    <!-- ============================================================ -->
    <!-- ============================================================ -->
    <!-- ============================================================ -->
    
    <nav>
      <div id="nav-header">CARA'S FOOD HAVEN: MANAGEMENT SYSTEM</div>
      <ul>
        <li class="account-dropdown">
          <div class="account-info">
            <!-- You can dynamically fetch and display the employee's avatar if available -->
            <img id="account_img" src="manager-pic-cut.png" alt="Avatar">
            <div>
              <span class="account-name"><?php echo htmlspecialchars($firstName . ' ' . $lastName); ?></span>
              <span class="account-role"><?php echo htmlspecialchars($role); ?></span>
            </div>
            <i class="fas fa-caret-down" id="dropdown-arrow"></i>
          </div>
          <div class="dropdown-content">
            <a href="login/logout.php">
              <i class="fas fa-sign-out-alt"></i> Logout
            </a>
          </div>
        </li>
      </ul>
    </nav>
      
    <!-- ============================================================ -->
    <!-- ============================================================ -->
    <!-- ============================================================ -->

    <!-- Vertical Menu -->
    <div class="verticalmenu_container">
      <br>
      <div class="verticalmenu-section">
        <label class="verticalmenu-label">RESTAURANT<BR>MANAGEMENT</label>
        <button class="verticalmenu-btn" id="orderManagementPage">
          <i class="fas fa-receipt"></i> Order Management
        </button>
        <button class="verticalmenu-btn" id="analyticsPage">
          <i class="fas fa-chart-line"></i> Analytics
        </button>
        <button class="verticalmenu-btn" id="stockPage">
          <i class="fas fa-boxes"></i> Stock
          <span class="notification-badge" style="display: none;">0</span>
        </button>
        <button class="verticalmenu-btn" id="stockTransactionPage">
          <i class="fas fa-exchange-alt"></i> Stock Transaction
        </button>
        <button class="verticalmenu-btn" id="staffPage">
          <i class="fas fa-user-tie"></i> Staff
        </button>
        <button class="verticalmenu-btn" id="customerPage">
          <i class="fas fa-users"></i> Customer
        </button>
      </div>
      <br>
      <div class="verticalmenu-section">
        <label class="verticalmenu-label">MENU ITEM</label>
        <button class="verticalmenu-btn" id="menuItemPage">
          <i class="fas fa-utensils"></i> Menu Item List
        </button>
        <button class="verticalmenu-btn" id="menuCategoryPage">
          <i class="fas fa-tags"></i> Menu Category
        </button>
      </div>
      <br>
      <div class="verticalmenu-section">
        <label class="verticalmenu-label">INGREDIENT</label>
        <button class="verticalmenu-btn" id="ingredientPage">
          <i class="fas fa-carrot"></i> Ingredient List
          <span class="notification-badge" style="display: none;">0</span>
        </button>
        <button class="verticalmenu-btn" id="ingredientCategoryPage">
          <i class="fas fa-list"></i> Ingredient Category
        </button>
        <button class="verticalmenu-btn" id="ingredientUnitPage">
          <i class="fas fa-balance-scale"></i> Ingredient Unit
        </button>
      </div>
    </div>
        
    <!-- ============================================================ -->
    <!-- ============================================================ -->
    <!-- ============================================================ -->

    <div id="stock-in-n-out-modal" class="modal">
      <div id="stock-in-n-out-modal-content" class="modal-content">

          <span class="close-button" onclick="closeModal('stock-in-n-out-modal')">X</span>

          <br>

          <h2 id="stock-in-n-out-header">INGREDIENTS TO STOCK IN</h2>
          
          <div id="stock-in-n-out-table-container" class="table-container">
            <table id="stock-in-n-out-table">
                <thead>
                    <tr>
                      <th class="id-columns">STOCK ID</th>
                      <th>[ID] INGREDIENT</th>
                      <th id="stock-in-n-out-column-head">QUANTITY<br>ADDED</th>
                      <th>EXPIRATION DATE</th>
                      <th>DAYS UNTIL<br>EXP-DATE ALERT</th>
                      <th>REMARKS</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
          </div>

          <button type="button" id="confirmed-ings-to-stock-btn" onclick="confirmedIngsToStock(this)">Confirm Stock In</button>

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

    <div id="cancel-order-modal" class="modal">
      <div id="cancel-order-modal-content" class="modal-content">
  
        <h2>Cancel Order?</h2>
        <br>
        <p>This action cannot be undone.</p>
        <br>
        <div class="action-buttons">
          <div class="right-buttons"></div>
            <button id="closeModal" class="action-btn">No, Go Back</button>
            <button id="confirmCancel" class="action-btn">Yes, Cancel</button>
          </div>
        </div>

      </div>
    </div>



    <div class="container" id="analytics">
      <div class="container-full">
        
        <!-- ============================== -->

        <div class="analytics-card-info">
        
          <!-- ============================== -->
          
          <!-- New Chart for Total Paid Orders -->
          <div class="analytics-card">
            <h2>
              <i class="fas fa-dollar-sign"></i>
              TOTAL PAID ORDERS
              <i class="fas fa-dollar-sign"></i>
            </h2>
            <br>
            <label for="totalPaidOrders-yearSelect">Select Year:</label>
            <select id="totalPaidOrders-yearSelect">
              <option value="2024">2024</option>
              <option value="2025">2025</option>
              <option value="2026">2026</option>
            </select>
            <br>
            <br>
            <canvas id="ordersChart"></canvas>
          </div>
        
          <!-- ============================== -->

          <!-- New Chart for Sales by Order Type -->
          <div class="analytics-card">
            <h2>
              <i class="fas fa-chart-pie"></i>
              SALES BY ORDER TYPE
              <i class="fas fa-chart-pie"></i>
            </h2>
            <br>
            <select id="salesOrderType-yearSelect">
              <option value="2024">2024</option>
              <option value="2025">2025</option>
              <option value="2026">2026</option>
            </select>
            <select id="salesOrderType-monthSelect">
              <option value="0">January</option>
              <option value="1">February</option>
              <option value="2">March</option>
              <option value="3">April</option>
              <option value="4">May</option>
              <option value="5">June</option>
              <option value="6">July</option>
              <option value="7">August</option>
              <option value="8">September</option>
              <option value="9">October</option>
              <option value="10">November</option>
              <option value="11">December</option>
            </select>
            <br>
            <br>
            <canvas id="orderTypeChart"></canvas>
          </div>

          <!-- ============================== -->

          <!-- New Chart for Menu Item Orders -->
          <div class="analytics-card">
            <h2>
              <i class="fas fa-utensils"></i>
              MENU ITEM ORDERS
              <i class="fas fa-utensils"></i>
            </h2>
            <br>
            <select id="menuItemsOrdered-yearSelect">
              <option value="2024">2024</option>
              <option value="2025">2025</option>
              <option value="2026">2026</option>
            </select>
            <select id="menuItemsOrdered-monthSelect">
              <option value="0">January</option>
              <option value="1">February</option>
              <option value="2">March</option>
              <option value="3">April</option>
              <option value="4">May</option>
              <option value="5">June</option>
              <option value="6">July</option>
              <option value="7">August</option>
              <option value="8">September</option>
              <option value="9">October</option>
              <option value="10">November</option>
              <option value="11">December</option>
            </select>
            <select id="menuItemsOrdered-categorySelect">
              <option value="category1">Menu Category #1</option>
              <option value="category2">Menu Category #2</option>
              <option value="category3">Menu Category #3</option>
            </select>
            <br>
            <br>
            <canvas id="menuItemOrdersChart"></canvas>
          </div>          
        
          <!-- ============================== -->

          <!-- New Chart for Menu Item Profit Margin -->
          <div class="analytics-card">
            <h2>
              <i class="fas fa-chart-line"></i>
              MENU ITEM PROFIT MARGIN
              <i class="fas fa-chart-line"></i>
            </h2>
            <br>
            <select id="profitMargin-yearSelect">
              <option value="2024">2024</option>
              <option value="2025">2025</option>
              <option value="2026">2026</option>
            </select>
            <select id="profitMargin-categorySelect">
              <option value="category1">Menu Category #1</option>
              <option value="category2">Menu Category #2</option>
              <option value="category3">Menu Category #3</option>
            </select>
            <select id="profitMargin-itemSelect">
              <!-- Options will be populated dynamically -->
            </select>
            <br>
            <br>
            <canvas id="profitMarginChart"></canvas>
          </div>          
        
          <!-- ============================== -->

          <!-- New Chart for Staff Orders -->
          <div class="analytics-card">
            <h2>
                <i class="fas fa-user-tie"></i>
                STAFF ORDERS ACCEPTED
                <i class="fas fa-user-tie"></i>
            </h2>
            <br>
            <label for="staffOrders-yearSelect">Select Year:</label>
            <select id="staffOrders-yearSelect">
                <option value="2024">2024</option>
                <option value="2025">2025</option>
                <option value="2026">2026</option>
            </select>
            <br>
            <br>
            <canvas id="staffOrdersChart"></canvas>
          </div>

          <!-- ============================== -->

          <!-- New Chart for Ingredient Usage Trends -->
          <div class="analytics-card">
            <h2>
              <i class="fas fa-carrot"></i>
              INGREDIENT USAGE TRENDS
              <i class="fas fa-carrot"></i>
            </h2>
            <br>
            <select id="ingredientUsage-yearSelect">
              <option value="2024">2024</option>
              <option value="2025">2025</option>
              <option value="2026">2026</option>
            </select>
            <select id="ingredientUsage-monthSelect">
              <option value="0">January</option>
              <option value="1">February</option>
              <option value="2">March</option>
              <option value="3">April</option>
              <option value="4">May</option>
              <option value="5">June</option>
              <option value="6">July</option>
              <option value="7">August</option>
              <option value="8">September</option>
              <option value="9">October</option>
              <option value="10">November</option>
              <option value="11">December</option>
            </select>
            <br>
            <br>
            <canvas id="ingredientUsageChart"></canvas>
          </div>

          <!-- ============================== -->

          <!-- New Chart for Peak Hours and Days -->
          <div class="analytics-card">
            <h2>
              <i class="fas fa-clock"></i>
              PEAK HOURS AND DAYS
              <i class="fas fa-clock"></i>
            </h2>
            <br>
            <label for="peakHours-daySelect">Select Day:</label>
            <select id="peakHours-daySelect">
              <option value="Sunday">Sunday</option>
              <option value="Monday">Monday</option>
              <option value="Tuesday">Tuesday</option>
              <option value="Wednesday">Wednesday</option>
              <option value="Thursday">Thursday</option>
              <option value="Friday">Friday</option>
              <option value="Saturday">Saturday</option>
            </select>
            <br>
            <br>
            <canvas id="peakHoursChart"></canvas>
          </div>

          <!-- ============================== -->

          <!-- New Chart for Order Status Times -->
          <div class="analytics-card">
            <h2>
              <i class="fas fa-clock"></i>
              AVERAGE ORDER STATUS TIMES
              <i class="fas fa-clock"></i>
            </h2>
            <br>
            <label for="timeFormat">Time Format:</label>
            <p id="timeFormat" style="margin: 5px;">MM:SS (Minutes:Seconds)</p>
            <br>
            <select id="orderStatus-yearSelect">
              <option value="2024">2024</option>
              <option value="2025">2025</option>
              <option value="2026">2026</option>
            </select>
            <select id="orderStatus-monthSelect">
              <option value="0">January</option>
              <option value="1">February</option>
              <option value="2">March</option>
              <option value="3">April</option>
              <option value="4">May</option>
              <option value="5">June</option>
              <option value="6">July</option>
              <option value="7">August</option>
              <option value="8">September</option>
              <option value="9">October</option>
              <option value="10">November</option>
              <option value="11">December</option>
            </select>
            <br>
            <br>
            <br>
            <div class="status-chart">
              <div class="status" style="background-color: orange;">
                <i class="fas fa-hourglass-start"></i> <!-- Icon for Pending -->
                <span>Pending</span>
              </div>
              <div class="arrow">
                <span id="pendingToPreparing"></span> <!-- Average time will be displayed here -->
              </div>
              <div class="status" style="background-color: orangered;">
                <i class="fas fa-fire"></i> <!-- Icon for Preparing -->
                <span>Preparing</span>
              </div>
              <div class="arrow">
                <span id="preparingToReady"></span> <!-- Average time will be displayed here -->
              </div>
              <div class="status" style="background-color: blue;">
                <i class="fas fa-concierge-bell"></i> <!-- Icon for Ready For Pickup -->
                <br>
                <span class="single-line">Ready For Pickup</span>
              </div>
              <div class="arrow">
                <span id="readyToComplete"></span> <!-- Average time will be displayed here -->
              </div>
              <div class="status" style="background-color: green;">
                <i class="fas fa-check-circle"></i> <!-- Icon for Complete -->
                <span>Complete</span>
              </div>
            </div>
          </div>

          <!-- ============================== -->

          <!-- New Chart for Popular Menu Pairings (Heat Map) -->
          <div class="analytics-card">
            <h2>
                <i class="fas fa-network-wired"></i>
                POPULAR MENU PAIRINGS HEATMAP
                <i class="fas fa-network-wired"></i>
            </h2>
            <br>
            <br>
            <div class="heatmap-container">
              <label>COLOR LEGEND</label>
              <div class="heatmap-legend">
                <span id="min-value">?</span> <!-- Minimum value span -->
                <div id="legend-gradient"></div>
                <span id="max-value">?</span> <!-- Maximum value span -->
              </div>
              <br>
              <br>
              <p>Each cell shows the frequency of two menu items being ordered together. Higher values indicate more common pairings.</p>
              <br>
              <br>
              <div id="menu-pairings-heatmap"></div> <!-- Container for the heatmap -->
            </div>
          </div>

          <!-- ============================== -->

        </div>
        
        <!-- ============================== -->

      </div>
    </div>   


    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script src="analytics-graphs.js"></script>



    
    <!-- ============================================================ -->
    <!-- ============================================================ -->
    <!-- ============================================================ -->

    <div class="container" id="stock">

      <!-- ============================================================ -->

      <div class="container-left-side">

        <!-- ============================== -->

        <div class="tool-bar">
          <div class="left-section">
            <select id="stock-attribute-input">
              <option value="" disabled selected>--- Select an Attribute to Filter ---</option>
            </select>
            <button type="button" onclick="">Reset</button>
          </div>
          <div class="right-section">
            <button class="formFeaturesButtons" type="button" onclick="fillForm('stock-id', 'stock-table', 'stock', 'stock-form')">Fill Form with ID</button>
            <button class="formFeaturesButtons" type="button" onclick="clearFormFields('stock-table','stock-form','YES')">Clear Form</button>
          </div>
        </div>
        
        <div class="tool-bar">
          <div class="left-section">
            <select id="stock-sort-options">
              <option value="" disabled selected>--- Select an Attribute to Sort By ---</option>
              <option value="">Stock ID</option>
              <option value="">Ingredient ID</option>
              <option value="">Quantity Left</option>
              <option value="">Stock In Date</option>
              <option value="">Expiration Date</option>
            </select>
            <button id="stock-sort-btn" type="button" onclick="sortTableByColumn('-table', 'stock-sort-options', this, 'YES')">🡹</button>
          </div>
          <div class="right-section">
            <button class="updateButtons" type="button" onclick="addSelectedIngredientsToStockInTable('stock-table_body',true)">Stock In</button>
            <button class="updateButtons" type="button" onclick="addSelectedIngredientsToStockInTable('stock-table_body',false)">Stock Out</button>
          </div>
        </div>

        <br>
        <br>

        <h1>STOCK TABLE</h1>

        <div class="table-container">
          <table id="stock-table">
            <thead>
              <tr>
                <th class="id-columns"></th>
                <th class="id-columns">ID</th>
                <th>[ID] INGREDIENT</th>
                <th>QTY LEFT</th>
                <th>STATUS</th>
              </tr>
            </thead>
            <tbody id="stock-table_body">
            </tbody>
          </table>
        </div>

        <!-- ============================== -->

      </div>

      <!-- ============================================================ -->

      <div class="container-right-side">

        <!-- ============================== -->
        
        <h2>STOCK FORM<br>(VIEW ONLY)</h2>
        
        <div class="details-display-container">

          <form id="stock-form">
  
            <label for="stock-id">Stock ID:</label>
            <input type="number" id="stock-id" name="stock-id" min="1" disabled>
            
            <br>
  
            <label for="stock-ingredient-id">Ingredient ID, Name, and Unit:</label>
            <input type="text" id="stock-ingredient-id" name="stock-ingredient-id" min="1" disabled>
            
            <br>
  
            <label for="stock-status">Stock Status:</label>
            <select id="stock-status">
              <option>--- Click to View Different Status ---</option>
              <option value="AVAILABLE">AVAILABLE</option>
              <option value="EXPIRED">EXPIRED</option>
              <option value="SPOILED">SPOILED</option>
              <option value="RESERVED">RESERVED</option>
            </select>
  
            <br>
  
            <label for="stock-original-qty">Original Quantity:</label>
            <input type="number" id="stock-original-qty" name="stock-original-qty" disabled>
  
            <br>
  
            <label for="stock-qty-left">Quantity Remaining:</label>
            <input type="number" id="stock-qty-left" name="stock-qty-left" disabled>
  
            <br>
  
            <label for="stock-in-date">Stock In Date:</label>
            <input type="date" id="stock-in-date" name="stock-in-date" disabled>
  
            <br>
  
            <label for="stock-expiration-date">Expiration Date:</label>
            <input type="date" id="stock-expiration-date" name="stock-expiration-date" disabled>
  
            <br>
  
            <label for="stock-expiration-alert-threshold">Expiration Alert Threshold:</label>
            <input type="number" id="stock-expiration-alert-threshold" name="stock-expiration-alert-threshold" disabled>
  
            <br>

          </form>

        </div>

        <!-- ============================== -->
        
      </div>

      <!-- ============================================================ -->

    </div>
      
    <!-- ============================================================ -->
    <!-- ============================================================ -->
    <!-- ============================================================ -->









    
    <!-- ============================================================ -->
    <!-- ============================================================ -->
    <!-- ============================================================ -->

    <div class="container" id="stockTransaction">

      <!-- ============================================================ -->

      <div class="container-left-side">

        <!-- ============================== -->

        <div class="tool-bar">
          <div class="left-section">
            <select id="stock-transaction-attribute-input">
              <option value="" disabled selected>--- Select an Attribute to Filter ---</option>
            </select>
            <button type="button" onclick="">Reset</button>
          </div>
          <div class="right-section">
            <button class="formFeaturesButtons" type="button" onclick="fillForm('stock-transaction-id', 'stock-transaction-table', 'stock-transaction', 'stock-transaction-form')">Fill Form with ID</button>
            <button class="formFeaturesButtons" type="button" onclick="clearFormFields('stock-transaction-table','stock-transaction-form','YES')">Clear Form</button>
          </div>
        </div>
        
        <div class="tool-bar">
          <div class="left-section">
            <select id="stock-transaction-sort-options">
              <option>--- Select an Attribute to Sort By ---</option>
              <option value="">Transaction ID</option>
              <option value="">Stock ID</option>
              <option value="">Ingredient ID</option>
              <option value="">Transaction Date</option>
            </select>
            <button id="stock-transaction-sort-btn" type="button" onclick="sortTableByColumn('-table', 'stock-transaction-sort-options', this, 'YES')">🡹</button>
          </div>
        </div>

        <br>
        <br>  

        <h1>STOCK TRANSACTION TABLE</h1>

        <div class="table-container">
          <table id="stock-transaction-table">
            <thead>
              <tr>
                <th class="id-columns">ID</th>
                <th>STOCK ID</th>
                <th>[ID] INGREDIENT</th>
                <th>QTY CHANGES</th>
                <th>TXN TYPE</th>
              </tr>
            </thead>
            <tbody id="stock-transaction-table_body">
            </tbody>
          </table>
        </div>

        <!-- ============================== -->

      </div>

      <!-- ============================================================ -->

      <div class="container-right-side">

        <!-- ============================== -->
        
        <h2>STOCK TXN FORM<br>(VIEW ONLY)</h2>
        
        <div class="details-display-container">

          <form id="stock-transaction-form">
  
            <label for="stock-transaction-id">Stock Transaction ID:</label>
            <input type="number" id="stock-transaction-id" name="stock-transaction-id" min="1" disabled>
            
            <br>
  
            <label for="stock-transaction-stock-id">Stock ID:</label>
            <input type="number" id="stock-transaction-stock-id" name="stock-transaction-stock-id" disabled>
            
            <br>
  
            <label for="stock-transaction-ingredient-id">[ID] Ingredient (Unit):</label>
            <input type="text" id="stock-transaction-ingredient-id" name="stock-transaction-ingredient-id" disabled>
            
            <br>
  
            <label for="stock-ingredient-qty-added">Quantity Added:</label>
            <input type="number" id="stock-ingredient-qty-added" name="stock-ingredient-qty-added" disabled>
  
            <br>
  
            <label for="stock-ingredient-qty-removed">Quantity Removed:</label>
            <input type="number" id="stock-ingredient-qty-removed" name="stock-ingredient-qty-removed" disabled>
  
            <br>
            
            <label for="stock-ingredient-transaction-type">Transaction Type:</label>
            <select id="stock-transaction-transaction-type">
              <option>--- Click to View Different Status ---</option>
              <option value="STOCK IN">STOCK IN</option>  
              <option value="STOCK OUT">STOCK OUT</option>
              <option value="STOCK ADJUSTMENT">STOCK ADJUSTMENT</option>
              <option value="ORDER CANCELLED">ORDER CANCELLED</option>
            </select>

            <br>
  
            <label for="stock-ingredient-transaction-date">Transaction Date:</label>
            <input type="date" id="stock-ingredient-transaction-date" name="stock-ingredient-transaction-date" disabled>
  
            <br>
    
            <label for="stock-ingredient-remarks">Remarks:</label>
            <textarea id="stock-ingredient-remarks" name="stock-ingredient-remarks" rows="8" disabled></textarea>
  
            <br>
  
            <label for="stock-transaction-staff-id">Staff ID and Full Name:</label>
            <input type="number" id="stock-transaction-staff-id" name="stock-transaction-staff-id" placeholder="For the Staff that cancelled the Order" disabled>
            
            <br>
  
            <label for="stock-transaction-order-id">Order ID:</label>
            <input type="number" id="stock-transaction-order-id" name="stock-transaction-order-id" placeholder="For 'ORDER CANCELLED'" disabled>
            
            <br>

          </form>

        </div>

        <!-- ============================== -->
        
      </div>

      <!-- ============================================================ -->

    </div>
      
    <!-- ============================================================ -->
    <!-- ============================================================ -->
    <!-- ============================================================ -->









    
    <!-- ============================================================ -->
    <!-- ============================================================ -->
    <!-- ============================================================ -->

    <div class="container" id="staff">

      <!-- ============================================================ -->

      <div class="container-left-side">

        <!-- ============================== -->

        <div class="tool-bar">
          <div class="left-section">
            <select id="staff-attribute-input">
              <option value="" disabled selected>--- Select an Attribute to Filter ---</option>
            </select>
            <button type="button" onclick="">Reset</button>
          </div>
          <div class="right-section">
            <button class="formFeaturesButtons" type="button" onclick="fillForm('staff-id', 'staff-table', 'staff', 'staff-form')">Fill Form with ID</button>
            <button class="formFeaturesButtons" type="button" onclick="clearFormFields('staff-table','staff-form','YES')">Clear Form</button>
          </div>
        </div>
        
        <div class="tool-bar">
          <div class="left-section">
            <select id="staff-sort-options">
              <option value="" disabled selected>--- Select an Attribute to Sort By ---</option>
              <option value="">Staff ID</option>
              <option value="">First Name</option>
              <option value="">Middle Name</option>
              <option value="">Last Name</option>
            </select>
            <button id="staff-sort-btn" type="button" onclick="sortTableByColumn('staff-table', 'staff-sort-options', this, 'YES')">🡹</button>
          </div>
          <div class="right-section">
            <button class="addButtons" type="button" onclick="addNewStaff()">Add</button>
            <button class="updateButtons" type="button" onclick="updateSelectedStaff()">Update</button>
            <!-- <button class="deleteButtons" type="button" onclick="deleteSelectedStaff()">Delete</button> -->
          </div>
        </div>

        <br>
        <br>

        <h1>STAFF TABLE</h1>

        <div class="table-container">
          <table id="staff-table">
            <thead>
              <tr>
                <th class="id-columns">ID</th>
                <th>FULL NAME</th>
                <th>CONTACT INFO</th>
                <th>STATUS</th>
              </tr>
            </thead>
            <tbody id="staff-table-body">
            </tbody>
          </table>
        </div>

        <!-- ============================== -->

      </div>

      <!-- ============================================================ -->

      <div class="container-right-side">

        <!-- ============================== -->
        
        <h2>STAFF FORM</h2>
        
        <div class="details-display-container">

          <form id="staff-form">
  
            <label for="staff-id">Staff ID:</label>
            <input type="number" id="staff-id" name="staff-id" placeholder="Enter the Staff's ID" min="1" tabindex="-1">
  
            <br>
  
            <label for="staff-status">Status:</label>
            <select id="staff-status" name="staff-status" required>
              <option value="ACTIVE">ACTIVE</option>
              <option value="RETIRED">RETIRED</option>
              <option value="VOIDED">VOIDED</option>
            </select>
  
            <br>
  
            <label for="staff-first-name">First Name:</label>
            <input type="text" id="staff-first-name" name="staff-first-name" placeholder="Enter the Staff's First Name" required>
  
            <br>
    
            <label for="staff-middle-name">Middle Name:</label>
            <input type="text" id="staff-middle-name" name="staff-middle-name" placeholder="Enter the Staff's Middle Name (Optional)">
  
            <br>
    
            <label for="staff-last-name">Last Name:</label>
            <input type="text" id="staff-last-name" name="staff-last-name" placeholder="Enter the Staff's Last Name" required>
  
            <br>
    
            <label for="staff-designation">Designation:</label>
            <input type="text" id="staff-designation" name="staff-designation" placeholder="Enter the Staff's Designation" required>
  
            <br>
  
            <label for="staff-gender">Gender:</label>
            <select id="staff-gender" name="staff-gender" required>
              <option value="" disabled selected>--- Select the Gender of this Staff ---</option>
              <option value="Male">Male</option>
              <option value="Female">Female</option>
            </select>
  
            <br>
  
            <label for="staff-birthdate">Birth Date:</label>
            <input type="date" id="staff-birthdate" name="staff-birthdate" placeholder="Enter the Staff's Birthdate" required max="">
  
            <br>
    
            <label for="staff-phonenumber">Contact Number (09xxxxxxxxx):</label>
            <input type="number" id="staff-phonenumber" name="staff-phonenumber" placeholder="Enter the Staff's Phone Number" required minlength="11" maxlength="11" pattern="\d{11}">
  
            <br>
    
            <label for="staff-address">Address:</label>
            <textarea id="staff-address" name="staff-address" placeholder="Enter the Staff's Address" rows="8" required></textarea>
  
            <br>
  
            <label for="staff-email">Email:</label>
            <input type="email" id="staff-email" name="staff-email" placeholder="Enter the Staff's Email" required pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$" title="Please enter a valid email address." autocomplete="off" maxlength="254">
            
            <br>

            <label for="staff-email-confirm">Confirm Email:</label>
            <input type="email" id="staff-email-confirm" name="staff-email-confirm" placeholder="Re-enter Email for Confirmation" required pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$" title="Please enter a valid email address." autocomplete="off" maxlength="254">
  
            <br>
            <br>  
            <span>====================================</span>
            <br>

            <div class="form-buttons-container">
              <button class="addButtons" type="button" onclick="addNewStaff()">Add</button>
              <button class="updateButtons" type="button" onclick="updateSelectedStaff()">Update</button>
            </div>

            <br>

          </form>

        </div>

        <!-- ============================== -->
        
      </div>

      <!-- ============================================================ -->

    </div>
      
    <!-- ============================================================ -->
    <!-- ============================================================ -->
    <!-- ============================================================ -->









    
    <!-- ============================================================ -->
    <!-- ============================================================ -->
    <!-- ============================================================ -->

    <div class="container" id="customer">

      <!-- ============================================================ -->

      <div class="container-left-side">

        <!-- ============================== -->

        <div class="tool-bar">
          <div class="left-section">
            <select id="customer-attribute-input">
              <option value="" disabled selected>--- Select an Attribute to Filter ---</option>
            </select>
            <button type="button" onclick="">Reset</button>
          </div>
          <div class="right-section">
            <button class="formFeaturesButtons" type="button" onclick="fillForm('customer-id', 'customer-table', 'customer', 'customer-form')">Fill Form with ID</button>
            <button class="formFeaturesButtons" type="button" onclick="clearFormFields('customer-table','customer-form','YES')">Clear Form</button>
          </div>
        </div>
        
        <div class="tool-bar">
          <div class="left-section">
            <select id="customer-sort-options">
              <option value="" disabled selected>--- Select an Attribute to Sort By ---</option>
              <option value="">Customer ID</option>
              <option value="">First Name</option>
              <option value="">Middle Name</option>
              <option value="">Last Name</option>
            </select>
            <button id="customer-sort-btn" type="button" onclick="sortTableByColumn('customer-table', 'customer-sort-options', this, 'YES')">🡹</button>
          </div>
          <div class="right-section">
            <button class="addButtons" type="button" onclick="addNewCustomer()">Add</button>
            <button class="updateButtons" type="button" onclick="updateSelectedCustomer()">Update</button>
            <!-- <button class="deleteButtons" type="button" onclick="deleteSelectedCustomer()">Delete</button> -->
          </div>
        </div>

        <br>
        <br>

        <h1>CUSTOMER TABLE</h1>

        <div class="table-container">
          <table id="customer-table">
            <thead>
              <tr>
                <th class="id-columns">ID</th>
                <th>FULL NAME</th>
                <th>CONTACT INFO</th>
                <th>STATUS</th>
              </tr>
            </thead>
            <tbody id="customer-table-body">
            </tbody>
          </table>
        </div>

        <!-- ============================== -->

      </div>

      <!-- ============================================================ -->

      <div class="container-right-side">

        <!-- ============================== -->
        
        <h2>CUSTOMER FORM</h2>
        
        <div class="details-display-container">

          <form id="customer-form">

            <label for="customer-id">Customer ID:</label>
            <input type="number" id="customer-id" name="customer-id" placeholder="Enter the Customer's ID" min="1" tabindex="-1"> 
  
            <br> 
  
            <label for="customer-status">Status:</label>
            <select id="customer-status" name="customer-status" required>
              <option value="ACTIVE">ACTIVE</option>
              <option value="INACTIVE">INACTIVE</option>
              <option value="SUSPENDED">SUSPENDED</option>
              <option value="BANNED">BANNED</option>
              <option value="LOST">LOST</option>
              <option value="VIP">VIP</option>
              <option value="GUEST">GUEST</option>
            </select>
  
            <br>
  
            <label for="customer-first-name">First Name:</label>
            <input type="text" id="customer-first-name" name="customer-first-name" placeholder="Enter the Customer's First Name" required>
  
            <br>
    
            <label for="customer-middle-name">Middle Name:</label>
            <input type="text" id="customer-middle-name" name="customer-middle-name" placeholder="Enter the Customer's Middle Name (Optional)">
  
            <br>
    
            <label for="customer-last-name">Last Name:</label>
            <input type="text" id="customer-last-name" name="customer-last-name" placeholder="Enter Customer's Last Name" required>
  
            <br>
  
            <label for="customer-gender">Gender:</label>
            <select id="customer-gender" name="customer-gender" required>
              <option value="" disabled selected>--- Select the Gender of this Customer ---</option>
              <option value="Male">Male</option>
              <option value="Female">Female</option>
            </select>
  
            <br>
  
            <label for="customer-birthdate">Birth Date:</label>
            <input type="date" id="customer-birthdate" name="customer-birthdate" placeholder="Enter the Customer's Birthdate" required max="">
  
            <br>
    
            <label for="customer-phonenumber">Contact Number (09xxxxxxxxx):</label>
            <input type="text" id="customer-phonenumber" name="customer-phonenumber" placeholder="Enter the Customer's Phone Number" required minlength="11" maxlength="11" pattern="\d{11}">
  
            <br>
    
            <label for="customer-address">Address:</label>
            <textarea id="customer-address" name="customer-address" placeholder="Enter the Customer's Address" rows="8" required></textarea>
  
            <br>
  
            <label for="customer-email">Email:</label>
            <input type="email" id="customer-email" name="customer-email" placeholder="Enter the Customer's Email" required pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$" title="Please enter a valid email address." autocomplete="off" maxlength="254">
            
            <br>

            <label for="customer-email-confirm">Confirm Email:</label>
            <input type="email" id="customer-email-confirm" name="customer-email-confirm" placeholder="Re-enter Customer's Email for Confirmation" required pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$" title="Please enter a valid email address." autocomplete="off" maxlength="254">
  
            <br>
            <br>  
            <span>====================================</span>
            <br>

            <div class="form-buttons-container">
              <button class="addButtons" type="button" onclick="addNewCustomer()">Add</button>
              <button class="updateButtons" type="button" onclick="updateSelectedCustomer()">Update</button>
            </div>
            
            <br>

          </form>

        </div>

        <!-- ============================== -->
        
      </div>

      <!-- ============================================================ -->

    </div>
      
    <!-- ============================================================ -->
    <!-- ============================================================ -->
    <!-- ============================================================ -->









    
    <!-- ============================================================ -->
    <!-- ============================================================ -->
    <!-- ============================================================ -->

    <!-- ============================================================ -->
    <!-- Menu Item Container -->
    <div class="container" id="menuItem">

      <!-- Left Side -->
      <div class="container-left-side">

        <!-- Tool Bar -->
        <div class="tool-bar">
      <div class="left-section">
        <select id="menu-item-attribute-input">
          <option value="" disabled selected>--- Select an Attribute to Filter ---</option>
          <!-- Add filter options if needed -->
        </select>
        <button type="button" onclick="">Reset</button>
      </div>
      <div class="right-section">
                <button class="formFeaturesButtons" type="button" onclick="toggleInputField(this, 'menu-item-id')">Fill Form with ID</button>
                <button class="formFeaturesButtons" type="button" onclick="clearFormFields('menu-item-table','menu-item-form','YES')">Clear Form</button>
      </div>
    </div>
        
    <div class="tool-bar">
      <div class="left-section">
        <select id="menu-item-sort-options">
          <option value="" disabled selected>--- Select an Attribute to Sort By ---</option>
          <option value="">Menu Item ID</option>
          <option value="">Menu Item Name</option>
          <option value="">Menu Item Price</option>
        </select>
        <button id="menu-item-sort-btn" type="button" onclick="sortTableByColumn('menu-item-table', 'menu-item-sort-options', this, 'YES')">🡹</button>
      </div>
      <div class="form-buttons-container">
    <button type="button" class="addButtons" onclick="handleMenuItemAction('add')">Add</button>
    <button type="button" class="updateButtons" onclick="handleMenuItemAction('update')">Update</button>
    <button type="button" class="deleteButtons" onclick="handleMenuItemAction('delete')">Delete</button>
</div>
    </div>

        <br>
        <br>

        <h1>MENU ITEM TABLE</h1>

        <div class="table-container">
          <table id="menu-item-table">
            <thead>
              <tr>
                <th>IMAGE</th>
                <th>[ID] MENU ITEM</th>
                <th>MENU CATEGORY</th>
                <th>PRICE</th>
              </tr>
            </thead>
            <tbody id="menu-item-table-body">
          <!-- Fetch and display menu items from the database -->
          <?php
          $sql = "SELECT mi.ID, mi.name, mi.price, mi.image, mc.name AS category_name
                  FROM menu_item mi
                  LEFT JOIN menu_category mc ON mi.menu_category = mc.ID";
          $result = $conn->query($sql);
          while ($row = $result->fetch_assoc()) {
              echo '<tr onclick="fillMenuItemForm(this)">';
              echo '<td><img src="' . htmlspecialchars($row['image']) . '" alt="Image" width="50" height="50"></td>';
              echo '<td>' . htmlspecialchars($row['ID']) . ' - ' . htmlspecialchars($row['name']) . '</td>';
              echo '<td>' . htmlspecialchars($row['category_name']) . '</td>';
              echo '<td>Php ' . htmlspecialchars($row['price']) . '</td>';
              echo '</tr>';
          }
          ?>
        </tbody>
          </table>
        </div>

      </div>

      <!-- ============================================================ -->

      <!-- Right Side -->
      <div class="container-right-side">
        
        <h2>MENU ITEM FORM</h2>
        
        <div class="details-display-container">
            <form id="menu-item-form" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" id="menu-item-form-action" value="">
                <input type="hidden" name="current_page" value="menuItem">
                <input type="hidden" name="existing_image" id="existing_image" value="">

            <div id="imgBox_Display">
              <img src="../img_placeholder.png" id="preview-image" alt="Menu Item Preview">
            </div>

            <br>
            <br>

            <label for="menu-item-img">Menu Item Image:</label>
            <input type="file" id="menu-item-img" name="menu-item-img" accept="image/*" onchange="previewImage(event)" required>

            <br>

            <label for="menu-item-name">Menu Item Name:</label>
            <div class="input-group"> 
              <input type="number" id="menu-item-id" name="menu-item-id" placeholder="ID" min="1" tabindex="-1" disabled oninput="fillForm('menu-item-id', 'menu-item-table', 'menu-item', 'menu-item-form')">
              <input type="text" id="menu-item-name" name="menu-item-name" placeholder="Enter the Menu Item's Name" required>
            </div>
            
            <br>

            <label for="menu-category-combobox">Menu Item Category:</label>
            <select id="menu-category-combobox" name="menu-category-combobox" required>
              <option value="" disabled selected>--- Select a Menu Category for this Menu Item ---</option>
              <?php
          // Fetch menu categories from the database
          $sql = "SELECT ID, name FROM menu_category";
          $result = $conn->query($sql);
          while ($row = $result->fetch_assoc()) {
              echo '<option value="' . $row['ID'] . '">' . htmlspecialchars($row['name']) . '</option>';
          }
          ?>
            </select>

            <br>

            <label for="menu-item-price">Menu Item Price (Php):</label>
            <input type="number" id="menu-item-price" name="menu-item-price" placeholder="Enter the Menu Item's Price" required min="1">
            
            <br>
    
            <label for="menu-item-description">Menu Item Description:</label>
            <textarea id="menu-item-description" name="menu-item-description" placeholder="Enter the Menu Item's Description" rows="8"></textarea>

            <br>
            <br>  
            <span>====================================</span>
            <br>
            <br>
  
            <div id="ingredient-list-table-label">
              <h2>RECIPE INGREDIENTS</h2>
            </div>
  
            <div class="table-container" id="ingredient-list-table-container">  
              <table id="ingredient-list-table">
                <thead>
                  <tr>
                    <th>KEY<br>ING.</th>
                    <th>QUANTITY<br>USED</th>
                    <th>[ID] INGREDIENT<br>( ING. UNIT )</th>
                  </tr>
                </thead>
                <tbody id="ingredient-list-table-body">
                </tbody>
              </table>
            </div>
  
            <label for="ingredient-name-combobox">Ingredient to Add:</label>
            <select id="ingredient-name-combobox" name="ingredient-name-combobox">
              <option value="" disabled selected>--- Select an Ingredient to Add ---</option>
              <?php
              // Fetch ingredients from the database
              $sql = "SELECT ID, name FROM ingredient";
              $result = $conn->query($sql);
              while ($row = $result->fetch_assoc()) {
                  echo '<option value="' . $row['ID'] . '">' . htmlspecialchars($row['name']) . '</option>';
              }
              ?>
            </select>
            
            <br>
  
            <button type="button" onclick="addIngredientToList()">Add to the Recipe Ingredients</button>
            
            <br>
            <br>  
            <span>====================================</span>
            <br>

            <div class="form-buttons-container">
    <button class="addButtons" type="button" onclick="handleMenuItemAction('add')">Add</button>
    <button class="updateButtons" type="button" onclick="handleMenuItemAction('update')">Update</button>
    <button class="deleteButtons" type="button" onclick="handleMenuItemAction('delete')">Delete</button>
            </div>

            <br>
            
          </form>

        </div>

        <!-- ============================== -->
        
      </div>

      <!-- ============================================================ -->

    </div>
      
    <!-- ============================================================ -->
    <!-- ============================================================ -->
    <!-- ============================================================ -->









    
    <!-- ============================================================ -->
    <!-- ============================================================ -->
    <!-- ============================================================ -->

    <!-- ============================================================ -->
    <!-- Menu Category Container -->
    <div class="container" id="menuCategory">

      <!-- Left Side -->
      <div class="container-left-side">

        <!-- Tool Bar -->
        <div class="tool-bar">
          <div class="left-section">
            <select id="menu-category-attribute-input">
              <option value="" disabled selected>--- Select an Attribute to Filter ---</option>
            </select>
            <button type="button" onclick="">Reset</button>
          </div>
          <div class="right-section">
                <button class="formFeaturesButtons" type="button" onclick="toggleInputField(this, 'menu-category-id')">Fill Form with ID</button>
                <button class="formFeaturesButtons" type="button" onclick="clearFormFields('menu-category-table','menu-category-form','YES')">Clear Form</button>
            </div>
        </div>
        
        <div class="tool-bar">
          <div class="left-section">
            <select id="menu-category-sort-options">
              <option value="" disabled selected>--- Select an Attribute to Sort By ---</option>
              <option value="">Menu Category ID</option>
              <option value="">Menu Category</option>
            </select>
            <button id="menu-category-sort-btn" type="button" onclick="sortTableByColumn('menu-category-table', 'menu-category-sort-options', this, 'YES')">🡹</button>
          </div>
          <div class="right-section">
        <button class="addButtons" type="button" onclick="handleMenuCategoryAction('add')">Add</button>
        <button class="updateButtons" type="button" onclick="handleMenuCategoryAction('update')">Update</button>
        <button class="deleteButtons" type="button" onclick="handleMenuCategoryAction('delete')">Delete</button>
    </div>
        </div>

        <br>
        <br>

        <h1>MENU CATEGORY TABLE</h1>

        <div class="table-container">
          <table id="menu-category-table">
            <thead>
              <tr>
                <th class="id-columns">ID</th>
                <th>MENU CATEGORY</th>
              </tr>
            </thead>
            <tbody id="menu-category-table-body">
            </tbody>
          </table>
        </div>

      </div>

      <!-- ============================================================ -->

        <!-- Right Side -->
        <div class="container-right-side">

            <h2>MENU CATEGORY FORM</h2>

            <div class="details-display-container">

                <!-- Menu Category Form -->
                <form id="menu-category-form" method="POST" action="">
                    <!-- Hidden input for action -->
                    <input type="hidden" name="action" id="menu-category-form-action" value="">
                    <input type="hidden" name="current_page" value="menuCategory">

                    <label for="menu-category-name">Menu Category Name:</label>
                    <div class="input-group">
                    <input type="number" id="menu-category-id" name="menu-category-id" placeholder="ID" min="1" tabindex="-1" disabled>
                    <input type="text" id="menu-category-name" name="menu-category-name" placeholder="Enter the Menu Category's Name" required>
                    </div>

                    <br>
                    <br>
                    <span>====================================</span>
                    <br>

                    <div class="form-buttons-container">
    <button type="button" onclick="handleMenuCategoryAction('add')" class="addButtons">Add</button>
    <button type="button" onclick="handleMenuCategoryAction('update')" class="updateButtons">Update</button>
    <button type="button" onclick="handleMenuCategoryAction('delete')" class="deleteButtons">Delete</button>
</div>

                    <br>

                </form>

            </div>

        </div>

    </div>
      
    <!-- ============================================================ -->
    <!-- ============================================================ -->
    <!-- ============================================================ -->









    
    <!-- ============================================================ -->
    <!-- ============================================================ -->
    <!-- ============================================================ -->

    <div class="container" id="ingredient">

      <!-- ============================================================ -->

      <div class="container-left-side">

        <!-- ============================== -->

        <div class="tool-bar">
          <div class="left-section">
            <select id="ingredient-stock-attribute-input">
              <option value="" disabled selected>--- Select an Attribute to Filter ---</option>
            </select>
            <button type="button" onclick="">Reset</button>
          </div>
          <div class="right-section">
            <button class="formFeaturesButtons" type="button" onclick="fillForm('ingredient-id', 'ingredient-table', 'ingredient', 'ingredient-form')">Fill Form with ID</button>
            <button class="formFeaturesButtons" type="button" onclick="clearFormFields('ingredient-table','ingredient-form','YES')">Clear Form</button>
          </div>
        </div>
        
        <div class="tool-bar">
          <div class="left-section">
            <select id="ingredient-sort-options">
              <option value="" disabled selected>--- Select an Attribute to Sort By ---</option>
              <option value="">Ingredient ID</option>
              <option value="">Ingredient Name</option>
              <option value="">Total Quantity</option>
            </select>
            <button id="ingredient-sort-btn" type="button" onclick="sortTableByColumn('ingredient-table', 'ingredient-sort-options', this, 'YES')">🡹</button>
          </div>
          <div class="right-section">
        <button class="addButtons" type="button" onclick="handleIngredientAction('add')">Add</button>
        <button class="updateButtons" type="button" onclick="handleIngredientAction('update')">Update</button>
        <button class="deleteButtons" type="button" onclick="handleIngredientAction('delete')">Delete</button>
    </div>
        </div>

        <br>
        <br>

        <h1>INGREDIENT TABLE</h1>

        <div class="table-container">
          <table id="ingredient-table">
            <thead>
              <tr>
                <th><button class="updateButtons" type="button" onclick="addSelectedIngredientsToStockInTable('ingredient-table_body',true)">Stock In</button></th>
                <th>[ID] INGREDIENT</th>
                <th>ING. CATEGORY</th>
                <th>TOTAL QUANTITY</th>
              </tr>
            </thead>
            <tbody id="ingredient-table_body">
              <?php
              // Fetch and display ingredients from the database
              $sql = "SELECT i.ID, i.name, c.name as category_name, i.total_qty
        FROM ingredient i
        LEFT JOIN ing_category c ON i.ing_category = c.ID";
              $result = $conn->query($sql);
              while ($row = $result->fetch_assoc()) {
                  echo '<tr onclick="fillIngredientForm(this)">';
                  echo '<td><input type="checkbox" name="ingredient_ids[]" value="' . htmlspecialchars($row['ID']) . '"></td>';
                  echo '<td>' . htmlspecialchars($row['ID']) . ' - ' . htmlspecialchars($row['name']) . '</td>';
                  echo '<td>' . htmlspecialchars($row['category_name']) . '</td>';
                  echo '<td>' . htmlspecialchars($row['total_qty']) . '</td>';
                  echo '</tr>';
              }
              ?>
            </tbody>

          </table>
        </div>

        <!-- ============================== -->

      </div>

      <!-- ============================================================ -->

      <div class="container-right-side">

        <!-- ============================== -->
        
        <h2>INGREDIENT FORM</h2>
        
        <div class="details-display-container">

        <form id="ingredient-form">

        <input type="hidden" name="action" id="ingredient-form-action" value="">
        <input type="hidden" name="current_page" value="ingredient">
  
        <label for="ingredient-name">Ingredient Name:</label>
        <div class="input-group">
            <input type="number" id="ingredient-id" name="ingredient-id" placeholder="ID" min="1" tabindex="-1" disabled>
            <input type="text" id="ingredient-name" name="ingredient-name" placeholder="Enter the Ingredient's Name" required>
        </div>
  
            <label for="ingredient-category-combobox">Ingredient Category:</label>
        <select id="ingredient-category-combobox" name="ingredient-category-combobox" required>
          <option value="" disabled selected>--- Select an Ingredient Category for this Ingredient ---</option>
          <?php
          // Fetch ingredient categories from the database
          $sql = "SELECT ID, name FROM ing_category";
          $result = $conn->query($sql);
          while ($row = $result->fetch_assoc()) {
              echo '<option value="' . $row['ID'] . '">' . htmlspecialchars($row['name']) . '</option>';
          }
          ?>
        </select>
            
            <br>
  
        <label for="ingredient-unit-combobox">Ingredient Unit:</label>
        <select id="ingredient-unit-combobox" name="ingredient-unit-combobox" required>
          <option value="" disabled selected>--- Select an Ingredient Unit for this Ingredient ---</option>
          <?php
          // Fetch ingredient units from the database
          $sql = "SELECT ID, name FROM ing_unit";
          $result = $conn->query($sql);
          while ($row = $result->fetch_assoc()) {
              echo '<option value="' . $row['ID'] . '">' . htmlspecialchars($row['name']) . '</option>';
          }
          ?>
        </select>
        <br>
  
            <label for="ingredient-low-stock-threshold">Low Stock Threshold:</label>
            <input type="number" id="ingredient-low-stock-threshold" name="ingredient-low-stock-threshold" placeholder="Enter a Number" required min="1">
  
            <br>
  
            <label for="ingredient-medium-stock-threshold">Medium Stock Threshold:</label>
            <input type="number" id="ingredient-medium-stock-threshold" name="ingredient-medium-stock-threshold" placeholder="Enter a Number" required min="1">
  
            <br>
  
            <label for="ingredient-reorder-point">Reorder Point:</label>
            <input type="number" id="ingredient-reorder-point" name="ingredient-reorder-point" placeholder="Enter a Number" required min="1">
            
            <br>

            <label for="ingredient-auto-deduct">System Auto-Deducts Stocks</label>
            <select id="ingredient-auto-deduct" name="ingredient-auto-deduct" required>
              <option value="true">TRUE</option>
              <option value="false">FALSE</option>
            </select>
  
            <br>
            <br>  
            <span>====================================</span>
            <br>

            <div class="form-buttons-container">
    <button type="button" class="addButtons" onclick="handleIngredientAction('add')">Add</button>
    <button type="button" class="updateButtons" onclick="handleIngredientAction('update')">Update</button>
    <button type="button" class="deleteButtons" onclick="handleIngredientAction('delete')">Delete</button>
    <button type="button" onclick="clearFormFields()">Clear</button>
</div>

            <br>

          </form>

        </div>

        <!-- ============================== -->
        
      </div>

      <!-- ============================================================ -->

    </div>
      
    <!-- ============================================================ -->
    <!-- ============================================================ -->
    <!-- ============================================================ -->









    
    <!-- ============================================================ -->
    <!-- ============================================================ -->
    <!-- ============================================================ -->

    <div class="container" id="ingredientCategory">

      <!-- ============================================================ -->

      <div class="container-left-side">

        <!-- ============================== -->

        <div class="tool-bar">
          <div class="left-section">
            <select id="ingredient-category-attribute-input">
              <option value="" disabled selected>--- Select an Attribute to Filter ---</option>
            </select>
            <button type="button" onclick="">Reset</button>
          </div>
      <div class="right-section">
        <button class="formFeaturesButtons" type="button" onclick="toggleInputField(this, 'ingredient-category-id')">Fill Form with ID</button>
        <button class="formFeaturesButtons" type="button" onclick="clearFormFields('ingredient-category-table','ingredient-category-form','YES')">Clear Form</button>
      </div>
        </div>
        
        <div class="tool-bar">
          <div class="left-section">
            <select id="ingredient-category-sort-options">
              <option value="" disabled selected>--- Select an Attribute to Sort By ---</option>
              <option value="">Ingredient Category ID</option>
              <option value="">Ingredient Category</option>
            </select>
            <button id="ingredient-category-sort-btn" type="button" onclick="sortTableByColumn('ingredient-category-table', 'ingredient-category-sort-options', this, 'YES')">🡹</button>
          </div>
          <div class="form-buttons-container">
    <button class="addButtons" type="button" onclick="addNewIngredientCategory()">Add</button>
    <button class="updateButtons" type="button" onclick="updateSelectedIngredientCategory()">Update</button>
    <button class="deleteButtons" type="button" onclick="deleteSelectedIngredientCategory()">Delete</button>
        </div>
        </div>

        <br>
        <br>

        <h1>INGREDIENT CATEGORY TABLE</h1>

        <div class="table-container">
          <table id="ingredient-category-table">
            <thead>
              <tr>
                <th class="id-columns">ID</th>
                <th>INGREDIENT CATEGORY</th>
              </tr>
            </thead>
            <tbody id="ingredient-category-table-body">
          <!-- Fetch and display ingredient categories from the database -->
          <?php
          $sql = "SELECT ID, name FROM ing_category";
          $result = $conn->query($sql);
          while ($row = $result->fetch_assoc()) {
              echo '<tr onclick="fillIngredientCategoryForm(this)">';
              echo '<td>' . htmlspecialchars($row['ID']) . '</td>';
              echo '<td>' . htmlspecialchars($row['name']) . '</td>';
              echo '</tr>';
          }
          ?>
        </tbody>
          </table>
        </div>

        <!-- ============================== -->

      </div>

      <!-- ============================================================ -->

      <div class="container-right-side">

        <!-- ============================== -->
        
        <h2>INGREDIENT CATEGORY FORM</h2>
        
        <div class="details-display-container">

        <form id="ingredient-category-form" method="POST" action="">
        <!-- Hidden input for action -->
        <input type="hidden" name="action" id="ingredient-category-form-action" value="">
        <input type="hidden" name="current_page" value="ingredientCategory">  
        <label for="ingredient-category-name">Ingredient Category Name:</label>
        <div class="input-group">
        <input type="number" id="ingredient-category-id" name="ingredient_category_id" placeholder="ID" min="1" tabindex="-1" disabled>
        <input type="text" id="ingredient-category-name" name="ingredient_category_name" placeholder="Enter the Ingredient Category's Name" required>
    </div>

        <br>
        <br>  
        <span>====================================</span>
        <br>

        <div class="form-buttons-container">
    <button class="addButtons" type="button" onclick="addNewIngredientCategory()">Add</button>
    <button class="updateButtons" type="button" onclick="updateSelectedIngredientCategory()">Update</button>
    <button class="deleteButtons" type="button" onclick="deleteSelectedIngredientCategory()">Delete</button>
        </div>

        <br>

      </form>

        </div>

        <!-- ============================== -->
        
      </div>

      <!-- ============================================================ -->

    </div>
      
    <!-- ============================================================ -->
    <!-- ============================================================ -->
    <!-- ============================================================ -->









    
    <!-- ============================================================ -->
    <!-- ============================================================ -->
    <!-- ============================================================ -->

    <div class="container" id="ingredientUnit">

      <!-- ============================================================ -->

      <div class="container-left-side">

        <!-- ============================== -->

        <div class="tool-bar">
          <div class="left-section">
            <select id="ingredient-unit-attribute-input">
              <option value="" disabled selected>--- Select an Attribute to Filter ---</option>
            </select>
            <button type="button" onclick="">Reset</button>
          </div>
          <div class="right-section">
        <button class="formFeaturesButtons" type="button" onclick="toggleInputField(this, 'ingredient-unit-id')">Fill Form with ID</button>
        <button class="formFeaturesButtons" type="button" onclick="clearFormFields('ingredient-unit-table','ingredient-unit-form','YES')">Clear Form</button>
      </div>
        </div>
        
        <div class="tool-bar">
      <div class="left-section">
        <select id="ingredient-unit-sort-options">
          <option value="" disabled selected>--- Select an Attribute to Sort By ---</option>
          <option value="">Ingredient Unit ID</option>
          <option value="">Ingredient Unit</option>
        </select>
        <button id="ingredient-unit-sort-btn" type="button" onclick="sortTableByColumn('ingredient-unit-table', 'ingredient-unit-sort-options', this, 'YES')">🡹</button>
      </div>
      <div class="right-section">
        <button class="addButtons" type="button" onclick="submitIngredientUnitForm('add')">Add</button>
        <button class="updateButtons" type="button" onclick="submitIngredientUnitForm('update')">Update</button>
        <button class="deleteButtons" type="button" onclick="submitIngredientUnitForm('delete')">Delete</button>
      </div>
    </div>


        <br>
        <br>

        <h1>INGREDIENT UNIT TABLE</h1>

        <div class="table-container">
          <table id="ingredient-unit-table">
            <thead>
              <tr>
                <th class="id-columns">ID</th>
                <th>INGREDIENT UNIT</th>
              </tr>
            </thead>
            <tbody id="ingredient-unit-table-body">
          <!-- Fetch and display ingredient units from the database -->
          <?php
          $sql = "SELECT ID, name FROM ing_unit";
          $result = $conn->query($sql);
          while ($row = $result->fetch_assoc()) {
              echo '<tr onclick="fillIngredientUnitForm(this)">';
              echo '<td>' . htmlspecialchars($row['ID']) . '</td>';
              echo '<td>' . htmlspecialchars($row['name']) . '</td>';
              echo '</tr>';
          }
          ?>
        </tbody>
          </table>
        </div>

        <!-- ============================== -->

      </div>

      <!-- ============================================================ -->

      <div class="container-right-side">

        <!-- ============================== -->
        
        <h2>INGREDIENT UNIT FORM</h2>
        
        <div class="details-display-container">
    <form id="ingredient-unit-form" method="POST" action="">
        <input type="hidden" name="action" id="ingredient-unit-form-action" value="">
        <input type="hidden" name="current_page" value="ingredientUnit">

        <label for="ingredient-unit-name">Ingredient Unit Name:</label>
        <div class="input-group">
            <input type="number" id="ingredient-unit-id" name="ingredient_unit_id" placeholder="ID" min="1" tabindex="-1" disabled>
            <input type="text" id="ingredient-unit-name" name="ingredient_unit_name" placeholder="Enter the Ingredient Unit's Name" required>
        </div>

        <br>
        <br>
        <span>====================================</span>
        <br>

        <!-- Form Buttons -->
        <div class="form-buttons-container">
            <button type="button" class="addButtons" onclick="addNewIngredientUnit()">Add</button>
            <button type="button" class="updateButtons" onclick="updateSelectedIngredientUnit()">Update</button>
            <button type="button" class="deleteButtons" onclick="deleteSelectedIngredientUnit()">Delete</button>
            <button type="button" onclick="clearFormFields()">Clear</button>
        </div>

        <br>

    </form>
</div>

        <!-- ============================== -->
        
      </div>

      <!-- ============================================================ -->

    </div>
      
    <!-- ============================================================ -->
    <!-- ============================================================ -->
    <!-- ============================================================ -->





<script>


document.addEventListener("DOMContentLoaded", function() {
// Hide all div containers initially
const divContainers = document.querySelectorAll(".container");
divContainers.forEach(container => {
    container.style.display = "none";
});

/*==============================*/

// Function to show the selected div container and hide others
function showContainer(containerId) {

  // Select and remove all existing notifications in the document
  const allNotifications = document.querySelectorAll('.notification');
  allNotifications.forEach(notification => notification.remove());

  divContainers.forEach(container => {
      if (container.id === containerId) {
          container.style.display = "block";
      } else {
          container.style.display = "none";
      }
  });

}

/*==============================*/

// Add event listeners to vertical menu buttons
const verticalMenuButtons = document.querySelectorAll(".verticalmenu-btn");
verticalMenuButtons.forEach(button => {
    button.addEventListener("click", function() {
        const containerId = button.id.replace("Page", "");
        showContainer(containerId);
        // Make the clicked button active and others inactive
        verticalMenuButtons.forEach(btn => {
            btn.classList.remove("active");
        });
        button.classList.add("active");
    });
});

/*==============================*/

// Set the "?Page" container as active by default
const defaultContainerId = "orderManagement";
const defaultButton = document.getElementById(defaultContainerId + "Page");

showContainer(defaultContainerId);
defaultButton.classList.add("active");

/*==============================*/

document.getElementById('staff-birthdate').max = new Date().toISOString().split("T")[0];
document.getElementById('customer-birthdate').max = new Date().toISOString().split("T")[0];

/*==============================*/

});




///////////////// LOGIN //////////////////////////////

document.addEventListener("DOMContentLoaded", function() {
        const accountDropdown = document.querySelector('.account-dropdown');
        const dropdownArrow = document.getElementById('dropdown-arrow');

        accountDropdown.addEventListener('click', function(event) {
          event.stopPropagation(); // Prevent click from bubbling up
          accountDropdown.classList.toggle('active'); // Toggle the active class

          // Toggle the arrow direction
          if (accountDropdown.classList.contains('active')) {
            dropdownArrow.classList.remove('fa-caret-down');
            dropdownArrow.classList.add('fa-caret-up');
          } else {
            dropdownArrow.classList.remove('fa-caret-up');
            dropdownArrow.classList.add('fa-caret-down');
          }
        });

        document.addEventListener('click', function(event) {
          if (!accountDropdown.contains(event.target)) {
            accountDropdown.classList.remove('active'); // Remove active class when clicking outside
            dropdownArrow.classList.remove('fa-caret-up');
            dropdownArrow.classList.add('fa-caret-down');
          }
        });
      });


/////////////// SEAN SCRIPT FOR MENU ITEM AND INGREDIENT CRUD ///////////////////////////

function handleMenuItemAction(action) {
    
    const form = document.getElementById('menu-item-form');
    const formData = new FormData(form);
    
    const menuItemId = document.getElementById('menu-item-id').value;
    
    if (action === 'update' || action === 'delete') {
        if (!menuItemId) {
            showNotification('Please select a menu item first', 'error');
            return;
        }
        formData.append('menu-item-id', menuItemId);
    }

    if (action !== 'delete' && !validateMenuItemForm()) {
        return;
    }

    if (action === 'delete' && !confirm('Are you sure you want to delete this menu item?')) {
        return;
    }

    formData.append('action', action + '_menu_item');

    fetch('management.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        showNotification(data.message, data.status);
        if (data.status === 'success') {
            refreshMenuItemTable();
            if (action === 'add' || action === 'delete') {
                clearFormFields('menu-item-table', 'menu-item-form', 'YES');
                document.getElementById('preview-image').src = '../img_placeholder.png';
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred: ' + error.message, 'error');
    });
}


function validateMenuItemForm() {
    const requiredFields = ['menu-item-name', 'menu-category-combobox', 'menu-item-price'];
    
    for (let fieldId of requiredFields) {
        const field = document.getElementById(fieldId);
        if (!field.value.trim()) {
            showNotification(`Please fill in the ${fieldId.replace(/-/g, ' ')}`, 'error');
            field.focus();
            return false;
        }
    }
    
    const price = parseFloat(document.getElementById('menu-item-price').value);
    if (isNaN(price) || price <= 0) {
        showNotification('Please enter a valid price', 'error');
        return false;
    }
    
    return true;
}

function refreshMenuItemTable() {
    fetch('management.php?action=get_menu_item')
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                const tableBody = document.getElementById('menu-item-table-body');
                tableBody.innerHTML = '';
                
                data.items.forEach(item => {
                    const row = document.createElement('tr');
                    row.onclick = () => fillMenuItemForm(row);
                    
                    const imageSrc = item.image || '../img_placeholder.png';
                    row.innerHTML = `
                        <td><img src="${imageSrc}" alt="Menu Item Image" width="50" height="50"></td>
                        <td>${item.ID} - ${item.name}</td>
                        <td>${item.category_name}</td>
                        <td>Php ${item.price}</td>
                    `;
                    
                    tableBody.appendChild(row);
                });
            } else {
                showNotification('Error refreshing menu items: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error refreshing menu items table', 'error');
        });
}

// Initialize when the page loads
document.addEventListener('DOMContentLoaded', function() {
    refreshMenuItemTable();
});



///////////////// MenuCategory //////////////////////////////



function handleMenuCategoryAction(action) {
    console.log('Action:', action); // Debug log
    
    const form = document.getElementById('menu-category-form');
    const formData = new FormData(form);
    
    // Get the ID for update/delete operations
    const categoryId = document.getElementById('menu-category-id').value;
    
    // Validate based on action
    if (action === 'update' || action === 'delete') {
        if (!categoryId) {
            showNotification('Please select a menu category first', 'error');
            return;
        }
        formData.append('menu-category-id', categoryId);
    }
    
    // Validate form if not deleting
    if (action !== 'delete' && !validateMenuCategoryForm()) {
        return;
    }

    // If deleting, confirm with user
    if (action === 'delete') {
        if (!confirm('Are you sure you want to delete this menu category?')) {
            return;
        }
    }

    // Add the action to formData
    formData.append('action', action === 'add' ? 'add_menu_category' : 
                            action === 'update' ? 'update_menu_category' : 
                            'delete_menu_category');

    // Make the AJAX request
    fetch('management.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        console.log('Response data:', data);
        
        showNotification(data.message, data.status);

        if (data.status === 'success') {
            refreshMenuCategoryTable();
            
            // Clear form after successful operation
            if (action === 'add' || action === 'delete') {
                clearFormFields('menu-category-table', 'menu-category-form', 'YES');
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred: ' + error.message, 'error');
    });
}

// Function to validate the menu category form
function validateMenuCategoryForm() {
    const nameInput = document.getElementById('menu-category-name');
    if (!nameInput.value.trim()) {
        showNotification('Menu category name is required', 'error');
        return false;
    }
    return true;
}

// Function to refresh the menu category table
function refreshMenuCategoryTable() {
    fetch('management.php?action=get_menu_categories')
        .then(response => response.json())
        .then(data => {
            const tbody = document.querySelector('#menu-category-table tbody');
            tbody.innerHTML = '';
            
            data.categories.forEach(category => {
                const row = document.createElement('tr');
                row.onclick = function() { fillMenuCategoryForm(this); };
                row.innerHTML = `
                    <td>${category.ID}</td>
                    <td>${category.name}</td>
                `;
                tbody.appendChild(row);
            });
        })
        .catch(error => {
            console.error('Error refreshing table:', error);
            showNotification('Error refreshing menu category table', 'error');
        });
}

// Function to fill the form when a table row is clicked
function fillMenuCategoryForm(row) {
    const cells = row.cells;
    document.getElementById('menu-category-id').value = cells[0].textContent;
    document.getElementById('menu-category-name').value = cells[1].textContent;
    
    // Highlight the selected row
    const previouslySelected = document.querySelector('#menu-category-table .selected');
    if (previouslySelected) {
        previouslySelected.classList.remove('selected');
    }
    row.classList.add('selected');
}

// Initialize the table when the page loads
document.addEventListener('DOMContentLoaded', function() {
    refreshMenuCategoryTable();
});


///////////////// IngredientUnit //////////////////////////////


 // Function to fetch and display all Ingredient Units when the page loads
    document.addEventListener('DOMContentLoaded', function() {
            fetchIngredientUnits();
    });

        // Function to fetch all Ingredient Units from the server
    function fetchIngredientUnits() {
      fetch('management.php?action=get_ingredient_units')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.status === 'success') {
                populateIngredientUnitTable(data.ingredient_units);
            } else {
                console.error('Server error:', data.message);
                alert('Failed to fetch ingredient units: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error fetching ingredient units: ' + error.message);
        });
}

        // Function to populate the Ingredient Unit table with data
        function populateIngredientUnitTable(units) {
            const tableBody = document.getElementById('ingredient-unit-table-body');
            tableBody.innerHTML = ''; // Clear existing rows

            units.forEach(unit => {
                const row = document.createElement('tr');
                row.setAttribute('data-id', unit.ID);
                row.innerHTML = `
                    <td>${unit.ID}</td>
                    <td>${unit.name}</td>
                `;
                row.addEventListener('click', () => fillIngredientUnitForm(row));
                tableBody.appendChild(row);
            });
        }

        // Function to fill the form fields when a table row is clicked
        function fillIngredientUnitForm(row) {
            const id = row.getAttribute('data-id');
            const name = row.children[1].innerText;

            document.getElementById('ingredient-unit-id').value = id;
            document.getElementById('ingredient-unit-name').value = name;
        }

        // Function to clear the form fields
        function clearFormFields() {
            document.getElementById('ingredient-unit-form').reset();
            document.getElementById('ingredient-unit-id').disabled = true;
            document.getElementById('ingredient-unit-id').value = '';
        }

        // Function to add a new Ingredient Unit via AJAX
    function addNewIngredientUnit() {
    const name = document.getElementById('ingredient-unit-name').value.trim();
    
    if (!name) {
        alert('Please enter a name for the ingredient unit');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'add_ingredient_unit');
    formData.append('ingredient_unit_name', name);
    
    fetch('management.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.status === 'success') {
            alert(data.message);
            appendIngredientUnitToTable(data.new_id, data.new_name);
            clearFormFields();
            // Refresh the table
            fetchIngredientUnits();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error adding ingredient unit: ' + error.message);
    });
}

        // Function to append a new Ingredient Unit to the table
        function appendIngredientUnitToTable(id, name) {
            const tableBody = document.getElementById('ingredient-unit-table-body');
            const row = document.createElement('tr');
            row.setAttribute('data-id', id);
            row.innerHTML = `
                <td>${id}</td>
                <td>${name}</td>
            `;
            row.addEventListener('click', () => fillIngredientUnitForm(row));
            tableBody.appendChild(row);
        }

        // Function to update the selected Ingredient Unit via AJAX
        function updateSelectedIngredientUnit() {
    const id = document.getElementById('ingredient-unit-id').value;
    const name = document.getElementById('ingredient-unit-name').value.trim();

    if (id === '') {
        alert('Please select an Ingredient Unit to update.');
        return;
    }

    if (name === '') {
        alert('Ingredient Unit name cannot be empty.');
        return;
    }

    const formData = new FormData();
    formData.append('action', 'update_ingredient_unit');
    formData.append('current_page', 'ingredientUnit');
    formData.append('ingredient_unit_id', id);
    formData.append('ingredient_unit_name', name);

    fetch('management.php', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            alert(data.message);
            // Update the corresponding row in the table
            updateIngredientUnitInTable(data.id, data.name);
            clearFormFields();
        } else {
            alert(data.message || 'Failed to update Ingredient Unit.');
        }
    })
    .catch(error => {
        console.error('Error updating Ingredient Unit:', error);
        alert('Error updating Ingredient Unit.');
    });
}

        // Function to update an Ingredient Unit in the table
        function updateIngredientUnitInTable(id, name) {
            const row = document.querySelector(`#ingredient-unit-table-body tr[data-id="${id}"]`);
            if (row) {
                row.children[1].innerText = name;
            }
        }

        // Function to delete the selected Ingredient Unit via AJAX
        function deleteSelectedIngredientUnit() {
            const id = document.getElementById('ingredient-unit-id').value;

            if (id === '') {
                alert('Please select an Ingredient Unit to delete.');
                return;
            }

            if (!confirm('Are you sure you want to delete this Ingredient Unit?')) {
                return;
            }

            const formData = new FormData();
            formData.append('action', 'delete_ingredient_unit');
            formData.append('current_page', 'ingredientUnit');
            formData.append('ingredient_unit_id', id);

            fetch('management.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    alert(data.message);
                    // Remove the corresponding row from the table
                    removeIngredientUnitFromTable(data.id);
                    clearFormFields();
                } else {
                    alert(data.message || 'Failed to delete Ingredient Unit.');
                }
            })
            .catch(error => {
                console.error('Error deleting Ingredient Unit:', error);
                alert('Error deleting Ingredient Unit.');
            });
        }

        // Function to remove an Ingredient Unit from the table
        function removeIngredientUnitFromTable(id) {
            const row = document.querySelector(`#ingredient-unit-table-body tr[data-id="${id}"]`);
            if (row) {
                row.remove();
            }
        }




        
///////////////// Ingredient Category //////////////////////////////

    function fetchIngredientCategories() {
    fetch('management.php?action=get_ingredient_categories')
        .then(response => {
            if (!response.ok) {
                return response.text().then(text => {
                    throw new Error(`Server returned ${response.status}: ${text}`);
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.status === 'success') {
                populateIngredientCategoryTable(data.ingredient_categories);
            } else {
                console.error('Server error:', data.message);
                alert('Failed to fetch ingredient categories: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error fetching ingredient categories: ' + error.message);
        });
}

function populateIngredientCategoryTable() {
    const tableBody = document.getElementById('ingredient-category-table-body');
    
    // Clear existing rows
    tableBody.innerHTML = '';
    
    // Fetch current data
    fetch('management.php?action=get_ingredient_categories')
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success' && data.ingredient_categories) {
                data.ingredient_categories.forEach(category => {
                    const row = document.createElement('tr');
                    row.onclick = function() { fillIngredientCategoryForm(this); };
                    row.innerHTML = `
                        <td>${category.ID}</td>
                        <td>${category.name}</td>
                    `;
                    tableBody.appendChild(row);
                });
            }
        })
        .catch(error => console.error('Error fetching ingredient categories:', error));
}

function addNewIngredientCategory() {
    const name = document.getElementById('ingredient-category-name').value.trim();
    
    if (!name) {
        alert('Please enter a name for the ingredient category');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'add_ingredient_category');
    formData.append('ingredient_category_name', name);
    
    fetch('management.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            alert(data.message);
            clearFormFields('ingredient-category-table', 'ingredient-category-form', 'YES');
            fetchIngredientCategories();
        } else {
            throw new Error(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error adding ingredient category: ' + error.message);
    });
}



function updateSelectedIngredientCategory() {
    const id = document.getElementById('ingredient-category-id').value;
    const name = document.getElementById('ingredient-category-name').value.trim();

    if (!id) {
        alert('Please select an ingredient category to update');
        return;
    }

    if (!name) {
        alert('Ingredient category name cannot be empty');
        return;
    }

    const formData = new FormData();
    formData.append('action', 'update_ingredient_category');
    formData.append('ingredient_category_id', id);
    formData.append('ingredient_category_name', name);

    fetch('management.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            alert(data.message);
            clearFormFields('ingredient-category-table', 'ingredient-category-form', 'YES');
            fetchIngredientCategories();
        } else {
            throw new Error(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error updating ingredient category: ' + error.message);
    });
}

function deleteSelectedIngredientCategory() {
    const id = document.getElementById('ingredient-category-id').value;
    if (!id) {
        alert('Please select an ingredient category to delete');
        return;
    }

    if (!confirm('Are you sure you want to delete this ingredient category?')) {
        return;
    }

    const formData = new FormData();
    formData.append('action', 'delete_ingredient_category');
    formData.append('ingredient_category_id', id);

    fetch('management.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            alert(data.message);
            clearFormFields('ingredient-category-table', 'ingredient-category-form', 'YES');
            fetchIngredientCategories();
        } else {
            throw new Error(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error deleting ingredient category: ' + error.message);
    });
}

document.addEventListener('DOMContentLoaded', function() {
    // Add event listeners to buttons
    const addBtn = document.getElementById('addIngredientBtn');
    const updateBtn = document.getElementById('updateIngredientBtn');
    const deleteBtn = document.getElementById('deleteIngredientBtn');

    if (addBtn) {
        addBtn.addEventListener('click', () => handleIngredientAction('add'));
    }
    if (updateBtn) {
        updateBtn.addEventListener('click', () => handleIngredientAction('update'));
    }
    if (deleteBtn) {
        deleteBtn.addEventListener('click', () => handleIngredientAction('delete'));
    }
});


///////////////// Ingredient //////////////////////////////

function handleIngredientAction(action) {
    const form = document.getElementById('ingredient-form');
    const formData = new FormData(form);

    if (action !== 'add') {
        const ingredientId = document.getElementById('ingredient-id').value;
        if (!ingredientId) {
            showNotification('Please select an ingredient first', 'error');
            return;
        }
        formData.append('ingredient-id', ingredientId);
    }

    formData.append('action', action + '_ingredient');

    fetch('management.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        showNotification(data.message, data.status);
        if (data.status === 'success') {
            refreshIngredientTable();
            if (action === 'add' || action === 'delete') {
                clearFormFields('ingredient-table', 'ingredient-form', 'YES');
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred: ' + error.message, 'error');
    });
}
// Add this to ensure the function is available globally
window.handleIngredientAction = handleIngredientAction;

// Remove the event listener approach since we're using onclick attributes
document.addEventListener('DOMContentLoaded', function() {
    // Initial table load
    refreshIngredientTable();
});

function deleteSelectedIngredient() {
    const id = document.getElementById('ingredient-id').value;
    if (!id) {
        alert('Please select an ingredient to delete');
        return;
    }

    if (confirm('Are you sure you want to delete this ingredient?')) {
        document.getElementById('ingredient-form-action').value = 'delete_ingredient';
        document.getElementById('ingredient-id').disabled = false;
        
        // Submit form using fetch to prevent page reload
        const form = document.getElementById('ingredient-form');
        const formData = new FormData(form);
        
        fetch('management.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            alert('Ingredient deleted successfully');
            // Remove the row from the table
            const row = document.querySelector(`#ingredient-table tr[onclick*="'${id}'"]`);
            if (row) row.remove();
            // Clear the form
            clearFormFields('ingredient-table', 'ingredient-form', 'YES');
        })
        .catch(error => {
            alert('Error deleting ingredient');
            console.error('Error:', error);
        });
    }
}
// Validate ingredient form
function validateIngredientForm() {
    const form = document.getElementById('ingredient-form');
    
    // Required fields
    const requiredFields = [
        'ingredient-name',
        'ingredient-category-combobox',
        'ingredient-unit-combobox',
        'ingredient-low-stock-threshold',
        'ingredient-medium-stock-threshold',
        'ingredient-reorder-point'
    ];

    for (const fieldId of requiredFields) {
        const field = form.querySelector(`#${fieldId}`);
        if (!field.value.trim()) {
            showNotification(`Please fill in all required fields`, 'error');
            field.focus();
            return false;
        }
    }

    // Validate thresholds
    const lowStock = parseInt(form.querySelector('#ingredient-low-stock-threshold').value);
    const mediumStock = parseInt(form.querySelector('#ingredient-medium-stock-threshold').value);
    const reorderPoint = parseInt(form.querySelector('#ingredient-reorder-point').value);

    if (lowStock >= mediumStock) {
        showNotification('Low stock threshold must be less than medium stock threshold', 'error');
        return false;
    }

    if (reorderPoint >= lowStock) {
        showNotification('Reorder point must be less than low stock threshold', 'error');
        return false;
    }

    console.log('Form validation passed'); // Debug log
    return true;
}

function refreshIngredientTable() {
    console.log('Refreshing ingredient table...'); // Debug log
    
    fetch('management.php?action=get_ingredients')
        .then(response => response.json())
        .then(data => {
            console.log('Refresh data:', data); // Debug log
            
            const tbody = document.querySelector('#ingredient-table_body');
            if (!tbody) {
                console.error('Table body not found!');
                return;
            }

            tbody.innerHTML = '';
            
            data.ingredients.forEach(ingredient => {
                const row = document.createElement('tr');
                row.onclick = function() { fillIngredientForm(this); };
                row.innerHTML = `
                    <td><input type="checkbox" name="ingredient_ids[]" value="${ingredient.ID}"></td>
                    <td>${ingredient.ID} - ${ingredient.name}</td>
                    <td>${ingredient.category_name}</td>
                    <td>${ingredient.total_qty}</td>
                `;
                tbody.appendChild(row);
            });
        })
        .catch(error => {
            console.error('Error refreshing table:', error);
            showNotification('Error refreshing ingredient table', 'error');
        });
}


function updateIngredientTable(data, action) {
    const tbody = document.querySelector('#ingredient-table_body');
    
    switch (action) {
        case 'add':
            const newRow = document.createElement('tr');
            newRow.onclick = function() { fillIngredientForm(this); };
            newRow.innerHTML = `
                <td><input type="checkbox" name="ingredient_ids[]" value="${data.new_id}"></td>
                <td>${data.new_id} - ${data.name}</td>
                <td>${data.category_name}</td>
                <td>${data.total_qty || 0}</td>
            `;
            tbody.insertBefore(newRow, tbody.firstChild);
            break;
            
        case 'update':
            const rows = tbody.getElementsByTagName('tr');
            for (let row of rows) {
                const idCell = row.cells[1].textContent;
                if (idCell.startsWith(data.id + ' -')) {
                    row.cells[1].textContent = `${data.id} - ${data.name}`;
                    row.cells[2].textContent = data.category_name;
                    row.cells[3].textContent = data.total_qty;
                    break;
                }
            }
            break;
            
        case 'delete':
            const rowsToSearch = tbody.getElementsByTagName('tr');
            for (let row of rowsToSearch) {
                const idCell = row.cells[1].textContent;
                if (idCell.startsWith(data.id + ' -')) {
                    row.remove();
                    break;
                }
            }
            break;
    }
}


function createIngredientTableRow(id, name, category, qty) {
    const tr = document.createElement('tr');
    tr.setAttribute('data-id', id);
    tr.onclick = function() { fillIngredientForm(this); };
    
    tr.innerHTML = `
        <td><input type="checkbox" name="ingredient_ids[]" value="${id}"></td>
        <td>${id} - ${name}</td>
        <td>${category}</td>
        <td>${qty}</td>
    `;
    
    return tr;
}

// Handle ingredient form submission
function handleIngredientSubmit(action) {
    // Get the form
    const form = document.getElementById('ingredient-form');
    const formData = new FormData(form);
    
    // Set the action
    switch (action) {
        case 'add':
            formData.set('action', 'add_ingredient');
            break;
        case 'update':
            formData.set('action', 'update_ingredient');
            break;
        case 'delete':
            formData.set('action', 'delete_ingredient');
            break;
    }

    // Validate form if not deleting
    if (action !== 'delete' && !validateIngredientForm()) {
        return;
    }

    // Send AJAX request
    fetch('management.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        console.log('Response:', data); // For debugging
        
        if (data.status === 'success') {
            // Show success message
            showNotification(data.message, 'success');
            
            // Update the table
            refreshIngredientTable();
            
            // Clear form if adding
            if (action === 'add') {
                clearFormFields('ingredient-table', 'ingredient-form', 'YES');
            }
        } else {
            showNotification(data.message || 'An error occurred', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred while processing your request.', 'error');
    });
}

// Show notification
function showNotification(message, type) {
    // Remove any existing notifications first
    const existingNotifications = document.querySelectorAll('.notification');
    existingNotifications.forEach(notification => notification.remove());
    
    // Create notification container if it doesn't exist
    let container = document.getElementById('notification-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'notification-container';
        document.body.appendChild(container);
    }

    // Create and show new notification
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;
    
    container.appendChild(notification);
    
    // Remove notification after 3 seconds
    setTimeout(() => {
        notification.remove();
        // Remove container if empty
        if (container.children.length === 0) {
            container.remove();
        }
    }, 3000);
}

// Add event listener to refresh table when page loads
document.addEventListener('DOMContentLoaded', function() {
    fetchIngredientCategories();
});

document.addEventListener("DOMContentLoaded", function() {
    // Get 'page' parameter from URL
    const urlParams = new URLSearchParams(window.location.search);
    const currentPage = urlParams.get('page') || 'orderManagement';

    // Hide all div containers initially
    const divContainers = document.querySelectorAll(".container");
    divContainers.forEach(container => {
        container.style.display = "none";
    });

    // Function to show the selected div container and hide others
    function showContainer(containerId) {
        divContainers.forEach(container => {
            if (container.id === containerId) {
                container.style.display = "block";
            } else {
                container.style.display = "none";
            }
        });

        // Update the active state of the vertical menu buttons
        const verticalMenuButtons = document.querySelectorAll(".verticalmenu-btn");
        verticalMenuButtons.forEach(button => {
            if (button.id === containerId + "Page") {
                button.classList.add("active");
            } else {
                button.classList.remove("active");
            }
        });
    }

    // Set the current page as active
    const defaultContainerId = currentPage;
    const defaultButton = document.getElementById(defaultContainerId + "Page");

    showContainer(defaultContainerId);
    if (defaultButton) {
        defaultButton.classList.add("active");
    }

    // Add event listeners to vertical menu buttons
    const verticalMenuButtons = document.querySelectorAll(".verticalmenu-btn");
    verticalMenuButtons.forEach(button => {
        button.addEventListener("click", function() {
            const containerId = button.id.replace("Page", "");
            showContainer(containerId);
            // Update the URL without reloading the page
            history.pushState(null, '', '?page=' + containerId);
        });
    });
});





  function fillIngredientForm(row) {
  const cells = row.cells;
  const idName = cells[1].innerText.split(' - ');
  const id = idName[0];
  const name = idName[1];
  const category = cells[2].innerText;

  document.getElementById('ingredient-id').value = id;
  document.getElementById('ingredient-name').value = name;

  // Set category in the dropdown
  const categoryDropdown = document.getElementById('ingredient-category-combobox');
  for (let i = 0; i < categoryDropdown.options.length; i++) {
    if (categoryDropdown.options[i].text === category) {
      categoryDropdown.selectedIndex = i;
      break;
    }
  }

  // Fetch additional details via AJAX
  fetch('get_ingredient_details.php?id=' + id)
    .then(response => response.json())
    .then(data => {
      document.getElementById('ingredient-unit-combobox').value = data.ing_unit;
      document.getElementById('ingredient-low-stock-threshold').value = data.low_stock_th;
      document.getElementById('ingredient-medium-stock-threshold').value = data.medium_stock_th;
      document.getElementById('ingredient-reorder-point').value = data.reorder_point;
      document.getElementById('ingredient-auto-deduct').value = data.willAutoDeduct ? 'true' : 'false';
    });
}


      function addNewIngredient() {
        submitIngredientForm('add');
      }

      function updateSelectedIngredient() {
        submitIngredientForm('update');
      }

      function submitIngredientForm(action) {
        document.getElementById('ingredient-form-action').value = action + '_ingredient';
        if (action !== 'add') {
          document.getElementById('ingredient-id').disabled = false;
        } else {
          document.getElementById('ingredient-id').disabled = true;
        }
        document.getElementById('ingredient-form').submit();
      }


      function addIngredientToList() {
    const ingredientSelect = document.getElementById('ingredient-name-combobox');
    const selectedOption = ingredientSelect.options[ingredientSelect.selectedIndex];

    // Check if an ingredient is selected
    if (selectedOption.value === "") {
        alert("Please select an ingredient to add.");
        return;
    }

    const ingredientId = selectedOption.value;
    const ingredientName = selectedOption.text;

    // Prompt the user for quantity used
    const quantityUsed = prompt("Enter the quantity used:");

    if (quantityUsed === null || quantityUsed === "" || isNaN(quantityUsed) || quantityUsed <= 0) {
        alert("Please enter a valid quantity.");
        return;
    }

    // Add the ingredient to the table
    const tableBody = document.getElementById('ingredient-list-table-body');

    // Check if the ingredient is already added
    if (document.getElementById('ingredient-row-' + ingredientId)) {
        alert("This ingredient is already added.");
        return;
    }

    const newRow = document.createElement('tr');
    newRow.id = 'ingredient-row-' + ingredientId;

    newRow.innerHTML = `
        <td><input type="checkbox" name="key_ingredient_ids[]" value="${ingredientId}"></td>
        <td><input type="number" name="ingredient_quantities[]" value="${quantityUsed}" required></td>
        <td>${ingredientId} - ${ingredientName}</td>
    `;

    tableBody.appendChild(newRow);
}


      function submitMenuCategoryForm(action) {
        document.getElementById('menu-category-form-action').value = action + '_menu_category';
        // Enable ID field if action is update or delete
        if (action !== 'add') {
          document.getElementById('menu-category-id').disabled = false;
        } else {
          document.getElementById('menu-category-id').disabled = true;
        }
  document.getElementById('menu-category-form').submit();
}

function submitIngredientForm(action) {
  document.getElementById('ingredient-form-action').value = action + '_ingredient';
  if (action !== 'add') {
    document.getElementById('ingredient-id').disabled = false;
  } else {
    document.getElementById('ingredient-id').disabled = true;
  }
  document.getElementById('ingredient-form').submit();
}


      function submitIngredientUnitForm(action) {
    document.getElementById('ingredient-unit-form-action').value = action + '_ingredient_unit';
    // Enable ID field if action is update or delete
    if (action !== 'add') {
      document.getElementById('ingredient-unit-id').disabled = false;
    } else {
      document.getElementById('ingredient-unit-id').disabled = true;
    }
    document.getElementById('ingredient-unit-form').submit();
  }

  // Function to fill the form when a table row is clicked
  function fillIngredientUnitForm(row) {
    const cells = row.cells;
    const id = cells[0].innerText;
    const name = cells[1].innerText;

    document.getElementById('ingredient-unit-id').value = id;
    document.getElementById('ingredient-unit-name').value = name;
  }

  function submitIngredientCategoryForm(action) {
    // Prevent normal form submission
    event.preventDefault();
    
    const id = document.getElementById('ingredient-category-id').value;
    const name = document.getElementById('ingredient-category-name').value.trim();

    if (action === 'add') {
        addNewIngredientCategory();
    } else if (action === 'update') {
        updateSelectedIngredientCategory();
    } else if (action === 'delete') {
        deleteSelectedIngredientCategory();
    }
}

  // Function to fill the form when a table row is clicked
  function fillIngredientCategoryForm(row) {
    const cells = row.cells;
    const id = cells[0].innerText;
    const name = cells[1].innerText;

    document.getElementById('ingredient-category-id').value = id;
    document.getElementById('ingredient-category-name').value = name;
  }


  // Function to fill the form when a table row is clicked
  function fillMenuItemForm(row) {
    const cells = row.cells;
    const idName = cells[1].innerText.split(' - ');
    const id = idName[0];
    const name = idName[1];
    const category = cells[2].innerText;
    const price = cells[3].innerText.replace('Php ', '');

    document.getElementById('menu-item-id').value = id;
    document.getElementById('menu-item-name').value = name;
    document.getElementById('menu-item-price').value = price;

    // Set category in the dropdown
    const categoryDropdown = document.getElementById('menu-category-combobox');
    for (let i = 0; i < categoryDropdown.options.length; i++) {
        if (categoryDropdown.options[i].text === category) {
            categoryDropdown.selectedIndex = i;
            break;
        }
    }

    // Fetch additional details via AJAX
    fetch(`management.php?action=get_menu_item_details&id=${id}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('menu-item-description').value = data.description;
            document.getElementById('preview-image').src = data.image || '../img_placeholder.png';
            document.getElementById('existing_image').value = data.image || '';

            // Update recipe ingredients
            const tableBody = document.getElementById('ingredient-list-table-body');
            tableBody.innerHTML = '';

            if (data.ingredients) {
                data.ingredients.forEach(ingredient => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td><input type="checkbox" name="key_ingredient_ids[]" value="${ingredient.ingredient_id}" checked></td>
                        <td><input type="number" name="ingredient_quantities[]" value="${ingredient.quantity}" required></td>
                        <td>${ingredient.ingredient_id} - ${ingredient.name} (${ingredient.unit})</td>
                    `;
                    tableBody.appendChild(row);
                });
            }
        })
        .catch(error => console.error('Error:', error));
}

      
      /*============================================================*/


      function previewImage(event) {
          const file = event.target.files[0]; // Get the selected file
          const previewImage = document.getElementById('preview-image'); // Get the image element

          if (file) {
              const reader = new FileReader(); // Create a new FileReader instance

              // Define what happens when the file is read
              reader.onload = function(e) {
                  previewImage.src = e.target.result; // Set the image src to the file's data URL
              }

              // Read the file as a data URL
              reader.readAsDataURL(file);
          } else {
              previewImage.src = '../img_placeholder.png'; // Reset to placeholder if no file is selected
          }
      }
      
      /*============================================================*/

      console.log('Script loaded');
    
    // You can also test the functions are available
    if (typeof handleIngredientAction === 'function') {
        console.log('handleIngredientAction is available');
    } else {
        console.error('handleIngredientAction is not defined!');
    }

    document.addEventListener('DOMContentLoaded', function() {
    // Add event listeners to buttons
    const addBtn = document.querySelector('#addIngredientBtn');
    const updateBtn = document.querySelector('#updateIngredientBtn');
    const deleteBtn = document.querySelector('#deleteIngredientBtn');

    if (addBtn) {
        addBtn.addEventListener('click', function(e) {
            e.preventDefault();
            handleIngredientAction('add');
        });
    }

    if (updateBtn) {
        updateBtn.addEventListener('click', function(e) {
            e.preventDefault();
            handleIngredientAction('update');
        });
    }

    if (deleteBtn) {
        deleteBtn.addEventListener('click', function(e) {
            e.preventDefault();
            handleIngredientAction('delete');
        });
    }
});    



/////////////////////////SEAN order management//////////////////////////////////////////////////////////////////////////////////


document.addEventListener('DOMContentLoaded', function() {
    // First handle all container visibility and menu buttons
    const divContainers = document.querySelectorAll(".container");
    divContainers.forEach(container => {
        container.style.display = "none";
    });

    function showContainer(containerId) {
        const allNotifications = document.querySelectorAll('.notification');
        allNotifications.forEach(notification => notification.remove());

        divContainers.forEach(container => {
            container.style.display = container.id === containerId ? "block" : "none";
        });
    }

    // Handle vertical menu buttons
    const verticalMenuButtons = document.querySelectorAll(".verticalmenu-btn");
    verticalMenuButtons.forEach(button => {
        button.addEventListener("click", function() {
            const containerId = button.id.replace("Page", "");
            showContainer(containerId);
            verticalMenuButtons.forEach(btn => btn.classList.remove("active"));
            button.classList.add("active");
            history.pushState(null, '', '?page=' + containerId);
        });
    });

    // Set default container
    const defaultContainerId = "orderManagement";
    const defaultButton = document.getElementById(defaultContainerId + "Page");
    if (defaultButton) {
        showContainer(defaultContainerId);
        defaultButton.classList.add("active");
    }

});

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
    const initialStatus = activeStatusButton ? activeStatusButton.getAttribute('data-status') : 'PENDING';
    loadOrders(initialStatus);
});

/**
 * Loads orders based on the provided status.
 * @param {string} status - The status to filter orders by.
 */
function loadOrders(status = 'PENDING') {
    fetch(`management.php?action=get_orders&status=${status}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const ordersContainer = document.querySelector('.orders');
                if (!ordersContainer) {
                    console.error('Orders container not found');
                    return;
                }
                ordersContainer.innerHTML = ''; // Clear existing orders
                
                // Process all orders first
                data.orders.forEach(order => {
                    console.log('Order received:', order);
                    
                    const orderData = {
                        id: order.id,
                        status: order.status,
                        statusText: order.order_status.replace(/_/g, ' '),
                        orderDateTime: order.date_ordered,
                        orderType: order.order_type,
                        total: parseFloat(order.total_price),
                        discountCode: order.discount_code,
                        menuItems: order.menuItems || [],
                        customerInfo: {
                         name: order.customerName,
                         phone: order.contactInfo?.phone || 'N/A',
                         email: order.contactInfo?.email || 'N/A',
                         address: order.contactInfo?.address || 'N/A'
                        }
                    };
                    
                    addNewOrderCard(orderData);
                    fetchOrderCounts();
                });
                
                // Fetch and update order counts
                
            } else {
                console.error('Failed to load orders:', data.message);
            }
        })
        .catch(error => console.error('Error loading orders:', error));
}






/*============================================================*/

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

    const nextStatusClass = getNextStatusClass(orderData.status); // Get the next status class

    // Sample menu items for the order with clickable icon
    const menuItems = orderData.menuItems.map(item => {
    // Determine which icon to display based on item.menu_item_status
    let hourglassDisplay = 'none';
    let fireDisplay = 'none';
    let bellDisplay = 'none';
    let wasteDisplay = 'none';
    let cursorStyle = 'default';

    switch (item.menu_item_status) {
        case 'PENDING':
            hourglassDisplay = 'inline';
            cursorStyle = 'pointer';
            break;
        case 'PREPARING':
            fireDisplay = 'inline';
            cursorStyle = 'pointer';
            break;
        case 'READY FOR PICKUP':
            bellDisplay = 'inline';
            cursorStyle = 'pointer';
            break;
        case 'CANCELED':
        case 'COMPLETE':
            wasteDisplay = 'inline';
            cursorStyle = 'pointer';
            break;
        default:
            hourglassDisplay = 'none';
    }

    return `
    <tr class="menu-item-row"
        onclick="toggleIcon(event)"
        style="cursor: ${cursorStyle};"
        data-wasted-ingredients=""
        data-menu-item-id="${item.id}"
        data-menu-item-status="${item.menu_item_status}">
        <td>
            <div class="icon-container">
                <span class="icon hourglass-icon" data-state="hourglass" style="display:${hourglassDisplay};">
                    <i class="fas fa-hourglass-start"></i>
                    <span class="tooltip">Click to change status of menu item</span>
                </span>
                <span class="icon fire-icon" data-state="fire" style="display:${fireDisplay};">
                    <i class="fas fa-fire"></i>
                    <span class="tooltip">Click to change status of menu item</span>
                </span>
                <span class="icon bell-icon" data-state="bell" style="display:${bellDisplay};">
                    <i class="fas fa-concierge-bell"></i>
                    <span class="tooltip">Click to change status of menu item</span>
                </span>
                <span class="icon waste-icon" data-state="waste" style="display:${wasteDisplay};">
                    <i class="fas fa-trash-alt"></i>
                    <span class="tooltip">Click to assign wasted ingredients (if any)</span>
                </span>
                ${item.name}
            </div>
        </td>
        <td>Php ${parseFloat(item.price).toFixed(2)}</td>
        <td>${item.quantity}</td>
        <td>Php${(parseFloat(item.price) * item.quantity).toFixed(2)}</td>
    </tr>
    `;
    }).join('');

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
    const card = event.currentTarget.closest('.order-card');
    const currentRow = event.currentTarget.closest('tr');
    const status = card.getAttribute('data-status');
    const menuItemId = currentRow.getAttribute('data-menu-item-id');
    let currentStatus = currentRow.getAttribute('data-menu-item-status');

    // Prevent toggling if the order is canceled or menu item is in CANCELED or COMPLETE status
    if (status === 'CANCELED' || currentStatus === 'CANCELED' || currentStatus === 'COMPLETE') {
        return;
    }

    const icons = event.currentTarget.querySelectorAll('.icon');
    const hourglassIcon = icons[0];
    const fireIcon = icons[1];
    const bellIcon = icons[2];

    let newStatus = '';

    if (hourglassIcon.style.display !== 'none') {
        hourglassIcon.style.display = 'none';
        fireIcon.style.display = 'inline';
        bellIcon.style.display = 'none';
        newStatus = 'PREPARING';
    } else if (fireIcon.style.display !== 'none') {
        hourglassIcon.style.display = 'none';
        fireIcon.style.display = 'none';
        bellIcon.style.display = 'inline';
        newStatus = 'READY FOR PICKUP';
    } else {
        hourglassIcon.style.display = 'inline';
        fireIcon.style.display = 'none';
        bellIcon.style.display = 'none';
        newStatus = 'PENDING';
    }

    // Update the status in the database
    const orderId = card.getAttribute('data-id');
    updateMenuItemStatusInDatabase(orderId, menuItemId, newStatus);

    // Update the data attribute
    currentRow.setAttribute('data-menu-item-status', newStatus);

    updateUpdateButtonState(card);
}

function updateMenuItemStatusInDatabase(orderId, menuItemId, newStatus) {
    // Send an AJAX request to update the menu item status in the database
    fetch('management.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'update_menu_item_status',
            orderId: orderId,
            menuItemId: menuItemId,
            status: newStatus
        })
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            alert('Failed to update menu item status: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error updating menu item status:', error);
        alert('An error occurred while updating the menu item status.');
    });
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
        const orderId = card.getAttribute('data-id'); // Get the order ID from data-id attribute

        // Convert status to title case for display
        const formattedStatus = newStatus.replace(/_/g, ' ').toLowerCase().replace(/(^|\s)\S/g, letter => letter.toUpperCase());

        // Send AJAX request to update status in the database
        updateOrderStatusInDB(orderId, newStatus)
            .then(response => {
                if (response.success) {
                    updateOrderStatus(card, newStatus, formattedStatus, getButtonText(newStatus)); // Update status in UI
                    showNotification(`Order ID #${orderId} has been reverted to "${formattedStatus}".`); // Notify user
                } else {
                    showNotification(`Failed to revert Order ID #${orderId}: ${response.message}`);
                }
            })
            .catch(error => {
                console.error('Error reverting order status:', error);
                showNotification('An error occurred while reverting the order status.');
            });
    }
}

/*==============================*/

// Function to handle the update button click
function handleUpdate(card) {
    const updateButton = card.querySelector('.action-btn.update');
    const status = card.getAttribute('data-status');
    const orderIdMatch = card.querySelector('.order-header h2').textContent.match(/\d+/);
    const orderId = orderIdMatch ? orderIdMatch[0] : null;

    if (!orderId) {
        showNotification('Order ID not found.');
        return;
    }

    let newStatus = '';

    switch (status) {
        case 'PENDING':
            newStatus = 'PREPARING';
            break;
        case 'PREPARING':
            if (areAllIconsBell(card)) {
                newStatus = 'READY FOR PICKUP';
            } else {
                showNotification('Please ensure all menu items are marked as "Ready For Pickup" before proceeding.');
                return;
            }
            break;
        case 'READY FOR PICKUP':
            newStatus = 'COMPLETE';
            break;
        default:
            console.warn('Unexpected status:', status);
            return;
    }

    // Send AJAX request to update status in the database
    updateOrderStatusInDB(orderId, newStatus)
        .then(response => {
            if (response.success) {
                updateOrderStatus(card, newStatus, newStatus.replace(/_/g, ' '), getButtonText(newStatus));
                showNotification(`Order ID #${orderId} has been updated to "${newStatus.replace(/_/g, ' ')}".`);
            } else {
                showNotification(`Failed to update Order ID #${orderId}: ${response.message}`);
            }
        })
        .catch(error => {
            console.error('Error updating order status:', error);
            showNotification('An error occurred while updating the order status.');
        });
}

function updateOrderStatusInDB(orderId, status) {
    return fetch('management.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'update_order_status',
            orderId: orderId,
            status: status 
        })
    })
    .then(response => response.json());
}

function fetchOrderCounts() {
    fetch('management.php?action=get_order_counts')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const counts = data.counts;
                updateCountsInUI(counts);
            } else {
                console.error('Failed to fetch order counts:', data.message);
            }
        })
        .catch(error => console.error('Error fetching order counts:', error));
}

function updateCountsInUI(counts) {
    const statuses = ['ALL', 'PENDING', 'PREPARING', 'READY_FOR_PICKUP', 'COMPLETE', 'CANCELED'];
    statuses.forEach(status => {
        const count = status === 'ALL'
            ? Object.values(counts).reduce((sum, val) => sum + val, 0)
            : counts[status] || 0;
        const countElement = document.getElementById(`count-${status}`);
        if (countElement) {
            countElement.textContent = count;
        }
    });
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
    const updateButton = card.querySelector('.action-btn.update');
    if (updateButton) {
        const status = card.getAttribute('data-status');
        if (status === 'PREPARING' && areAllIconsBell(card)) {
            updateButton.disabled = false;
            updateButton.style.filter = 'none';
        } else if (status === 'PREPARING') {
            updateButton.disabled = true;
            updateButton.style.filter = 'brightness(0.5)';
        }
    }
}

/*==============================*/

// Function to handle the cancel button click
function handleCancel(card) {
  const modal = document.getElementById('cancel-order-modal');
  if (!modal) {
    console.error('Cancel Order Modal not found');
    return;
  }

  // Show the modal
  modal.style.display = 'block';

  // Get the confirm and close buttons
  const confirmCancelButton = document.getElementById('confirmCancel');
  const closeModalButton = document.getElementById('closeModal');

  if (!confirmCancelButton || !closeModalButton) {
    console.error('Confirm or Close buttons not found in the modal');
    return;
  }

  // Confirm cancel action
  confirmCancelButton.onclick = function() {
    const orderId = card.getAttribute('data-id');

    // Send AJAX request to update status in the database
    updateOrderStatusInDB(orderId, 'CANCELED')
      .then(response => {
        if (response.success) {
          updateOrderStatus(card, 'CANCELED', 'Canceled', null);
          showNotification(`Order ID #${orderId} has been canceled.`); // Notify user
        } else {
          showNotification(`Failed to cancel Order ID #${orderId}: ${response.message}`);
        }
      })
      .catch(error => {
        console.error('Error canceling order:', error);
        showNotification('An error occurred while canceling the order.');
      });

    modal.style.display = 'none'; // Close the modal
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
    menuItemRows.forEach(row => {
        const icons = row.querySelectorAll('.icon');
        const itemStatus = row.getAttribute('data-menu-item-status');

        icons.forEach(icon => icon.style.display = 'none');

        if (newStatus === 'CANCELED') {
            // Show waste icon
            icons[3].style.display = 'inline';
            row.style.cursor = 'pointer';
        } else if (newStatus === 'PREPARING') {
            // Show icons based on item status
            if (itemStatus === 'PENDING') {
                icons[0].style.display = 'inline';
            } else if (itemStatus === 'PREPARING') {
                icons[1].style.display = 'inline';
            } else if (itemStatus === 'READY FOR PICKUP') {
                icons[2].style.display = 'inline';
            }
            row.style.cursor = 'pointer';
        } else if (newStatus === 'COMPLETE') {
            // Show waste icon
            icons[3].style.display = 'inline';
            row.style.cursor = 'pointer';
        } else {
            row.style.cursor = 'default';
        }
    });

    updateCounts();  // Update the filter button counts
    reapplyCurrentFilter();  // Apply the active filter to update the UI

    // Update the update button state based on icons
    updateUpdateButtonState(card);
}

/*==============================*/

function updateCounts() {
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


    </script>
      
    <!-- ============================================================ -->
    <!-- ============================================================ -->
    <!-- ============================================================ -->
  

  </body>

</html>