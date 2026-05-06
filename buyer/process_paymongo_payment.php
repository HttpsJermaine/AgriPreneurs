<?php
session_start();
require_once "../db_connection.php";
require_once "includes/PaymongoHelper.php";

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'buyer') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$buyer_id = (int)$_SESSION['user_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

$paymongo = new PaymongoHelper();

switch ($action) {
    
    case 'create_payment_link':
        // Create payment link for checkout
        $order_id = (int)($_POST['order_id'] ?? 0);
        $amount = (float)($_POST['amount'] ?? 0);
        
        // Get buyer info
        $stmt = $conn->prepare("
            SELECT u.email, bd.full_name, bd.phone, bd.street, bd.city, bd.province, bd.zip 
            FROM users u
            LEFT JOIN buyer_details bd ON bd.user_id = u.id
            WHERE u.id = ?
        ");
        $stmt->bind_param("i", $buyer_id);
        $stmt->execute();
        $buyer = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        $buyer_info = [
            'name' => $buyer['full_name'] ?? 'Buyer',
            'email' => $buyer['email'] ?? '',
            'phone' => $buyer['phone'] ?? '',
            'address' => $buyer['street'] ?? '',
            'city' => $buyer['city'] ?? '',
            'province' => $buyer['province'] ?? '',
            'zip' => $buyer['zip'] ?? '',
            'buyer_id' => $buyer_id
        ];
        
        $description = "PLAMAL Marketplace Order #$order_id";
        
        $result = $paymongo->createPaymentLink($amount, $description, $buyer_info, $order_id);
        
        if ($result['code'] === 200 || $result['code'] === 201) {
            $link_data = $result['body']['data'] ?? [];
            $attributes = $link_data['attributes'] ?? [];
            $checkout_url = $attributes['checkout_url'] ?? '';
            $reference = $attributes['reference_number'] ?? '';
            
            // Store payment reference in session
            $_SESSION['paymongo_reference'] = $reference;
            $_SESSION['paymongo_link_id'] = $link_data['id'] ?? '';
            
            echo json_encode([
                'success' => true,
                'checkout_url' => $checkout_url,
                'reference' => $reference
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'Failed to create payment link',
                'details' => $result['body']
            ]);
        }
        break;
        
    case 'create_payment_intent':
        // Create payment intent for embedded checkout
        $order_id = (int)($_POST['order_id'] ?? 0);
        $amount = (float)($_POST['amount'] ?? 0);
        
        // Get buyer info
        $stmt = $conn->prepare("
            SELECT u.email, bd.full_name, bd.phone 
            FROM users u
            LEFT JOIN buyer_details bd ON bd.user_id = u.id
            WHERE u.id = ?
        ");
        $stmt->bind_param("i", $buyer_id);
        $stmt->execute();
        $buyer = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        $buyer_info = [
            'name' => $buyer['full_name'] ?? 'Buyer',
            'email' => $buyer['email'] ?? '',
            'phone' => $buyer['phone'] ?? '',
            'buyer_id' => $buyer_id
        ];
        
        $description = "PLAMAL Marketplace Order #$order_id";
        
        $result = $paymongo->createPaymentIntent($amount, $description, $buyer_info, $order_id);
        
        if ($result['code'] === 200 || $result['code'] === 201) {
            $intent_data = $result['body']['data'] ?? [];
            $attributes = $intent_data['attributes'] ?? [];
            $client_key = $attributes['client_key'] ?? '';
            
            // Store in session
            $_SESSION['payment_intent_id'] = $intent_data['id'] ?? '';
            
            echo json_encode([
                'success' => true,
                'client_key' => $client_key,
                'intent_id' => $intent_data['id'] ?? ''
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'Failed to create payment intent',
                'details' => $result['body']
            ]);
        }
        break;
        
    case 'check_payment_status':
        // Check payment status for an order
        $order_id = (int)($_GET['order_id'] ?? 0);
        
        $stmt = $conn->prepare("SELECT payment_status FROM orders WHERE id = ? AND buyer_id = ?");
        $stmt->bind_param("ii", $order_id, $buyer_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        echo json_encode([
            'success' => true,
            'status' => $result['payment_status'] ?? 'unknown'
        ]);
        break;
        
    case 'webhook':
        // Handle Paymongo webhook
        $payload = json_decode(file_get_contents('php://input'), true);
        
        if ($payload) {
            $paymongo->handleWebhook($payload);
            http_response_code(200);
            echo json_encode(['success' => true]);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid payload']);
        }
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
}
?>