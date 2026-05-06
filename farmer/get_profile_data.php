<?php
session_start();
require_once "../db_connection.php";

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'farmer') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$farmerId = (int)$_SESSION['user_id'];

// 1. Get user data
$userQuery = "SELECT id, username, email, created_at, status FROM users WHERE id = ?";
$stmt = $conn->prepare($userQuery);
$stmt->bind_param("i", $farmerId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    echo json_encode(['success' => false, 'error' => 'User not found']);
    exit;
}

// 2. Get farmer details
$farmerQuery = "SELECT 
                    farmer_name, 
                    farm_area, 
                    phone, 
                    registry_num, 
                    photo, 
                    full_address,
                    street,
                    barangay,
                    city,
                    province
                FROM farmer_details 
                WHERE user_id = ?";
$stmt = $conn->prepare($farmerQuery);
$stmt->bind_param("i", $farmerId);
$stmt->execute();
$farmer = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Build address
$completeAddress = '';
if ($farmer) {
    if (!empty($farmer['full_address'])) {
        $completeAddress = $farmer['full_address'];
    } else {
        $addressParts = array_filter([
            $farmer['street'] ?? '',
            $farmer['barangay'] ?? '',
            $farmer['city'] ?? '',
            $farmer['province'] ?? ''
        ]);
        $completeAddress = !empty($addressParts) ? implode(', ', $addressParts) : 'No address provided';
    }
}

// 3. Get products from product_requests table (not farmer_products)
$productsQuery = "SELECT 
                    id, 
                    rice_variety, 
                    price_per_sack, 
                    product_image as image, 
                    created_at, 
                    status 
                FROM product_requests 
                WHERE user_id = ? 
                ORDER BY created_at DESC";
$stmt = $conn->prepare($productsQuery);
$stmt->bind_param("i", $farmerId);
$stmt->execute();
$products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Calculate statistics
$memberSince = date('F Y', strtotime($user['created_at']));
$totalProducts = count($products);
$activeCount = 0;
$pendingCount = 0;
$rejectedCount = 0;

foreach ($products as $product) {
    switch ($product['status']) {
        case 'active':
            $activeCount++;
            break;
        case 'pending':
            $pendingCount++;
            break;
        case 'rejected':
            $rejectedCount++;
            break;
    }
}

// Prepare response
$response = [
    'success' => true,
    'user' => [
        'username' => $user['username'],
        'email' => $user['email'] ?? 'No email',
        'created_at' => $user['created_at'],
        'status' => $user['status'] ?? 'Active'
    ],
    'farmer' => [
        'farmer_name' => $farmer['farmer_name'] ?? $user['username'],
        'registry_num' => $farmer['registry_num'] ?? 'Not registered',
        'phone' => $farmer['phone'] ?? 'Not provided',
        'farm_area' => $farmer['farm_area'] ?? 'Not specified',
        'full_address' => $completeAddress,
        'photo' => $farmer['photo'] ?? null
    ],
    'products' => $products,
    'stats' => [
        'memberSince' => $memberSince,
        'totalProducts' => $totalProducts,
        'activeCount' => $activeCount,
        'pendingCount' => $pendingCount,
        'rejectedCount' => $rejectedCount
    ]
];

echo json_encode($response);
?>