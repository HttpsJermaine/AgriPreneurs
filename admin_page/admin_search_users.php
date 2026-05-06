<?php
// admin_search_users.php
session_start();
require_once "../db_connection.php";

header('Content-Type: application/json');

// Only admin can search
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$qraw = trim($_GET['q'] ?? '');
$q = "%$qraw%";

// Build query: match id exactly OR username LIKE OR role LIKE
// We'll search across all statuses but group them after fetching
$sql = "SELECT id, username, role, status FROM users
        WHERE username LIKE ? OR role LIKE ?";

$params = [$q, $q];
$types = "ss";

// If q looks like a positive integer, include id exact match
if ($qraw !== '' && ctype_digit($qraw)) {
    $sql .= " OR id = ?";
    $types .= "i";
    $params[] = intval($qraw);
}

$sql .= " ORDER BY id DESC LIMIT 200"; // limit to prevent large payloads

$stmt = $conn->prepare($sql);

// Bind dynamic params
$bind_names = [];
$bind_names[] = $types;
for ($i = 0; $i < count($params); $i++) {
    $bind_names[] = &$params[$i];
}
call_user_func_array([$stmt, 'bind_param'], $bind_names);

$stmt->execute();
$result = $stmt->get_result();

$active = [];
$disabled = [];
$pending = [];
$archived = [];

while ($row = $result->fetch_assoc()) {
    // normalize status
    $st = $row['status'] ?? 'pending';
    if ($st === 'active') $active[] = $row;
    elseif ($st === 'disabled') $disabled[] = $row;
    elseif ($st === 'archived') $archived[] = $row;
    else $pending[] = $row; // pending or other
}

$stmt->close();

echo json_encode([
    'success' => true,
    'data' => [
        'active' => $active,
        'disabled' => $disabled,
        'pending' => $pending,
        'archived' => $archived
    ]
]);
