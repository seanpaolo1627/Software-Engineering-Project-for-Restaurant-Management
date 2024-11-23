<?php
include 'login/connect.php';

if (isset($_GET['id'])) {
  $id = intval($_GET['id']);

  $stmt = $conn->prepare("SELECT ing_unit, low_stock_th, medium_stock_th, reorder_point, willAutoDeduct FROM ingredient WHERE ID = ?");
  $stmt->bind_param("i", $id);
  $stmt->execute();
  $result = $stmt->get_result();

  $ingredient = $result->fetch_assoc();

  $stmt->close();
  $conn->close();

  header('Content-Type: application/json');
  echo json_encode($ingredient);
}
?>
