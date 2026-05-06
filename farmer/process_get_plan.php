<?php
session_start();
require_once "../db_connection.php";

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'farmer') {
  http_response_code(403);
  echo json_encode(["success"=>false,"error"=>"Access denied"]);
  exit;
}

$farmerId = (int)$_SESSION['user_id'];
$id = (int)($_GET['id'] ?? 0);

$stmt = $conn->prepare("
  SELECT id, rice_variety, planting_date, notes, image_path
  FROM farmer_plans
  WHERE id=? AND farmer_id=?
  LIMIT 1
");
$stmt->bind_param("ii", $id, $farmerId);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) {
  echo json_encode(["success"=>false,"error"=>"Plan not found"]);
  exit;
}

echo json_encode(["success"=>true,"plan"=>$row]);