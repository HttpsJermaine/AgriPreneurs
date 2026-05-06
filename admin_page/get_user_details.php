<?php
session_start();
require_once "../db_connection.php";
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
  http_response_code(403);
  echo json_encode(['success' => false, 'error' => 'Unauthorized']);
  exit;
}

$user_id = (int)($_GET['user_id'] ?? 0);
if ($user_id <= 0) {
  echo json_encode(['success' => false, 'error' => 'No user ID']);
  exit;
}

$stmt = $conn->prepare("SELECT id, username, role, status, email FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
  echo json_encode(['success' => false, 'error' => 'User not found']);
  exit;
}

$response = [
  'success' => true,
  'id' => $user['id'],
  'username' => $user['username'],
  'role' => $user['role'],
  'status' => $user['status'],
  'email' => $user['email'],

  // common output used by modal
  'display_name' => $user['username'],
  'photo_url' => '',

  // farmer keys
  'registry_num' => '',
  'farm_area' => '',

  // common contact/address keys
  'phone' => '',
  'street' => '',
  'barangay' => '',
  'city' => '',
  'province' => '',
  'zip' => '',
  'full_address' => '',

  // admin key
  'position' => ''
];

$role = $user['role'];

// ---------- FARMER ----------
if ($role === 'farmer') {
  $stmt = $conn->prepare("
    SELECT farmer_name, farm_area, phone, registry_num, photo,
           street, barangay, full_address, city, province, zip
    FROM farmer_details
    WHERE user_id = ?
    LIMIT 1
  ");
  $stmt->bind_param("i", $user_id);
  $stmt->execute();
  $d = $stmt->get_result()->fetch_assoc();
  $stmt->close();

  if ($d) {
    $response['display_name'] = $d['farmer_name'] ?: $user['username'];
    $response['registry_num'] = $d['registry_num'] ?? '';
    $response['farm_area'] = $d['farm_area'] ?? '';
    $response['phone'] = $d['phone'] ?? '';
    $response['street'] = $d['street'] ?? '';
    $response['barangay'] = $d['barangay'] ?? '';
    $response['city'] = $d['city'] ?? '';
    $response['province'] = $d['province'] ?? '';
    $response['zip'] = $d['zip'] ?? '';
    $response['full_address'] = $d['full_address'] ?? '';

    // farmer photo stored as filename in ../uploads/
    if (!empty($d['photo'])) $response['photo_url'] = "../uploads/" . $d['photo'];
  }
}

// ---------- BUYER ----------
else if ($role === 'buyer') {
  $stmt = $conn->prepare("
    SELECT full_name, phone, photo,
           street, barangay, full_address, city, province, zip
    FROM buyer_details
    WHERE user_id = ?
    LIMIT 1
  ");
  $stmt->bind_param("i", $user_id);
  $stmt->execute();
  $d = $stmt->get_result()->fetch_assoc();
  $stmt->close();

  if ($d) {
    $response['display_name'] = $d['full_name'] ?: $user['username'];
    $response['phone'] = $d['phone'] ?? '';
    $response['street'] = $d['street'] ?? '';
    $response['barangay'] = $d['barangay'] ?? '';
    $response['city'] = $d['city'] ?? '';
    $response['province'] = $d['province'] ?? '';
    $response['zip'] = $d['zip'] ?? '';
    $response['full_address'] = $d['full_address'] ?? '';

    // buyer photo stored as filename in ../uploads/
    if (!empty($d['photo'])) $response['photo_url'] = "../uploads/" . $d['photo'];
  }
}

// ---------- ADMIN ----------
else if ($role === 'admin') {
  $stmt = $conn->prepare("
    SELECT full_name, position, phone, photo
    FROM admin_details
    WHERE user_id = ?
    LIMIT 1
  ");
  $stmt->bind_param("i", $user_id);
  $stmt->execute();
  $d = $stmt->get_result()->fetch_assoc();
  $stmt->close();

  if ($d) {
    $response['display_name'] = $d['full_name'] ?: $user['username'];
    $response['position'] = $d['position'] ?? '';
    $response['phone'] = $d['phone'] ?? '';

    // admin photo stored in ../uploads/admins/
    if (!empty($d['photo'])) $response['photo_url'] = "../uploads/admins/" . $d['photo'];
  }
}

echo json_encode($response);
