<?php
include 'login/connect.php';

if (isset($_GET['menu_item_id'])) {
    $menu_item_id = intval($_GET['menu_item_id']);

    // Prepare statement
    $stmt = $conn->prepare("SELECT ri.ingredient_id, ri.quantity, i.name as ingredient_name, u.name as unit_name
                            FROM recipe_ingredient ri
                            JOIN ingredient i ON ri.ingredient_id = i.ID
                            JOIN ing_unit u ON i.ing_unit = u.ID
                            WHERE ri.menu_item_id = ?");
    $stmt->bind_param("i", $menu_item_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $ingredients = [];
    while ($row = $result->fetch_assoc()) {
        $ingredients[] = [
            'ingredient_id' => $row['ingredient_id'],
            'quantity' => $row['quantity'],
            'ingredient_name' => $row['ingredient_name'],
            'unit_name' => $row['unit_name']
        ];
    }

    $stmt->close();
    $conn->close();

    header('Content-Type: application/json');
    echo json_encode($ingredients);
}
?>
