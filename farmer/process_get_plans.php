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
$year     = (int)($_GET['year'] ?? date("Y"));
$quarter  = (int)($_GET['quarter'] ?? 1);

$stmt = $conn->prepare("
  SELECT id, plan_year, quarter, rice_variety, planting_date, notes, image_path
  FROM farmer_plans
  WHERE farmer_id = ? AND plan_year = ? AND quarter = ?
  ORDER BY planting_date ASC, id DESC
");
$stmt->bind_param("iii", $farmerId, $year, $quarter);
$stmt->execute();
$res = $stmt->get_result();

$plans = [];
while ($row = $res->fetch_assoc()) {
  $plans[] = $row;
}
$stmt->close();

// IMPORTANT: even if no rows, return success true
echo json_encode(["success"=>true, "plans"=>$plans]);