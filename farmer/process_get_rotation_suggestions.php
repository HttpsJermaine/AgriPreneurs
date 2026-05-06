<?php
session_start();
header('Content-Type: application/json');
require_once "../db_connection.php";

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'farmer') {
  echo json_encode(["success"=>false, "error"=>"Access denied"]);
  exit;
}

$farmerId = (int)$_SESSION['user_id'];
$year = (int)($_GET['year'] ?? date("Y"));

function getSeasonSuggestion($conn, $farmerId, $year, $months, $label){
  $in = implode(',', array_fill(0, count($months), '?'));

  $sql = "
    SELECT
      fp.id AS product_id,
      fp.product_name,
      fp.unit,
      fp.image,
      SUM(so.quantity) AS total_sold_qty
    FROM stock_outflows so
    INNER JOIN farmer_products fp ON fp.id = so.product_id
    WHERE so.farmer_id = ?
      AND so.reason IN ('Sold','Order completed')
      AND YEAR(so.date) = ?
      AND MONTH(so.date) IN ($in)
    GROUP BY fp.id, fp.product_name, fp.unit, fp.image
    ORDER BY total_sold_qty DESC
    LIMIT 1
  ";

  $stmt = $conn->prepare($sql);

  $types = "ii" . str_repeat("i", count($months));
  $params = array_merge([$farmerId, $year], $months);
  $stmt->bind_param($types, ...$params);

  $stmt->execute();
  $res = $stmt->get_result();
  $row = $res->fetch_assoc();
  $stmt->close();

  if (!$row) return null;

  return [
    "product_id" => $row["product_id"],
    "product_name" => $row["product_name"],
    "unit" => $row["unit"],
    "total_sold_qty" => (int)$row["total_sold_qty"],
    "image_url" => !empty($row["image"])
        ? "../uploads/products/" . $row["image"]
        : "../images/no-image.png",
    "why" =>
      "Recommended because this variety had the highest total SOLD quantity "
      . "during $label ($year). Based on your sales trend, this crop shows strong "
      . "market demand and is ideal for reuse in the next season."
  ];
}

$wet = getSeasonSuggestion($conn, $farmerId, $year, [11,12], "November–December");
$dry = getSeasonSuggestion($conn, $farmerId, $year, [3,4], "March–April");

echo json_encode([
  "success" => true,
  "wet" => $wet,
  "dry" => $dry
]);
