<?php
session_start();
require_once "../db_connection.php";

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'farmer') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$farmerId = (int)$_SESSION['user_id'];

// Get POST data - but we'll IGNORE farmerName and registryNum from the form
// Instead, fetch the existing values from database to preserve them
$phone = $_POST['phone'] ?? '';
$farmArea = $_POST['farmArea'] ?? '';
$fullAddress = $_POST['fullAddress'] ?? '';

// Validate required fields
if (empty($phone) || empty($farmArea) || empty($fullAddress)) {
    echo json_encode(['success' => false, 'error' => 'Phone, farm area, and address are required']);
    exit;
}

// Get current farmer details to preserve name and registry
$stmt = $conn->prepare("SELECT farmer_name, registry_num FROM farmer_details WHERE user_id = ?");
$stmt->bind_param("i", $farmerId);
$stmt->execute();
$current = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Use existing values or set defaults if not found
$farmerName = $current['farmer_name'] ?? $_POST['farmerName'] ?? '';
$registryNum = $current['registry_num'] ?? $_POST['registryNum'] ?? '';

// Handle photo upload
$photo = null;
if (isset($_FILES['profilePhoto']) && $_FILES['profilePhoto']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = '../uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    // Validate file type
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
    $fileType = $_FILES['profilePhoto']['type'];
    
    if (!in_array($fileType, $allowedTypes)) {
        echo json_encode(['success' => false, 'error' => 'Only JPG, PNG, and GIF images are allowed']);
        exit;
    }
    
    // Validate file size (max 2MB)
    if ($_FILES['profilePhoto']['size'] > 2 * 1024 * 1024) {
        echo json_encode(['success' => false, 'error' => 'Image size must be less than 2MB']);
        exit;
    }
    
    $fileExt = pathinfo($_FILES['profilePhoto']['name'], PATHINFO_EXTENSION);
    $fileName = 'profile_' . $farmerId . '_' . time() . '.' . $fileExt;
    $uploadPath = $uploadDir . $fileName;
    
    if (move_uploaded_file($_FILES['profilePhoto']['tmp_name'], $uploadPath)) {
        $photo = $fileName;
    }
}

// Check if farmer details exist
$stmt = $conn->prepare("SELECT id FROM farmer_details WHERE user_id = ?");
$stmt->bind_param("i", $farmerId);
$stmt->execute();
$exists = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($exists) {
    // Update existing - ONLY update editable fields (phone, farm_area, full_address, photo)
    // Preserve existing farmer_name and registry_num
    if ($photo) {
        $stmt = $conn->prepare("UPDATE farmer_details SET phone = ?, farm_area = ?, full_address = ?, photo = ? WHERE user_id = ?");
        $stmt->bind_param("ssssi", $phone, $farmArea, $fullAddress, $photo, $farmerId);
    } else {
        $stmt = $conn->prepare("UPDATE farmer_details SET phone = ?, farm_area = ?, full_address = ? WHERE user_id = ?");
        $stmt->bind_param("sssi", $phone, $farmArea, $fullAddress, $farmerId);
    }
} else {
    // Insert new - use provided values or defaults
    if ($photo) {
        $stmt = $conn->prepare("INSERT INTO farmer_details (user_id, farmer_name, registry_num, phone, farm_area, full_address, photo) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issssss", $farmerId, $farmerName, $registryNum, $phone, $farmArea, $fullAddress, $photo);
    } else {
        $stmt = $conn->prepare("INSERT INTO farmer_details (user_id, farmer_name, registry_num, phone, farm_area, full_address) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssss", $farmerId, $farmerName, $registryNum, $phone, $farmArea, $fullAddress);
    }
}

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $conn->error]);
}
$stmt->close();
$conn->close();
?>