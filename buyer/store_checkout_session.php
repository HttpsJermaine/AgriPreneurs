<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'buyer') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'error' => 'Invalid data']);
    exit;
}

// Store checkout data in session
$_SESSION['pending_checkout'] = [
    'data' => $input,
    'expires' => time() + 3600, // 1 hour expiry
    'created_at' => time()
];

echo json_encode(['success' => true]);
?>