<?php
session_start();
require_once "../db_connection.php";
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
  echo json_encode(['success' => false, 'error' => 'Unauthorized']);
  exit;
}

$sql = "
  SELECT
    u.id AS user_id,
    COALESCE(fd.farmer_name, u.username) AS farmer_name,
    fd.registry_num,
    fd.phone,
    fd.full_address,
    fd.photo,

    SUM(CASE WHEN pr.status = 'active' THEN 1 ELSE 0 END) AS active_count,
    SUM(CASE WHEN pr.status = 'pending' THEN 1 ELSE 0 END) AS pending_count,
    SUM(CASE WHEN pr.status = 'rejected' THEN 1 ELSE 0 END) AS rejected_count

  FROM users u
  LEFT JOIN farmer_details fd ON fd.user_id = u.id
  LEFT JOIN product_requests pr ON pr.user_id = u.id
  WHERE u.role = 'farmer'
  GROUP BY u.id
  ORDER BY farmer_name ASC
";

$res = $conn->query($sql);
if (!$res) {
  echo json_encode(['success' => false, 'error' => $conn->error]);
  exit;
}

$farmers = [];
while ($row = $res->fetch_assoc()) {
  $farmers[] = [
    'id' => (int)$row['user_id'],
    'name' => $row['farmer_name'],
    'registry' => $row['registry_num'] ?? '',
    'phone' => $row['phone'] ?? '',
    'address' => $row['full_address'] ?? '',
    'photo' => $row['photo'] ?? null,
    'stats' => [
      'active' => (int)$row['active_count'],
      'pending' => (int)$row['pending_count'],
      'rejected' => (int)$row['rejected_count'],
    ],
  ];
}

echo json_encode(['success' => true, 'farmers' => $farmers]);
