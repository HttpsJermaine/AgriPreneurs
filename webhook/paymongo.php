<?php
require_once "../db_connection.php";

// Get the webhook payload
$payload = file_get_contents('php://input');
$data = json_decode($payload, true);

// Log webhook for debugging
file_put_contents('paymongo_webhook.log', date('Y-m-d H:i:s') . " - " . $payload . "\n", FILE_APPEND);

if ($data && isset($data['data'])) {
    $event_type = $data['data']['attributes']['type'] ?? '';
    $payment_data = $data['data']['attributes']['data'] ?? [];
    
    // Handle successful payment
    if ($event_type === 'payment.paid' || $event_type === 'link.paid') {
        $reference_number = $payment_data['attributes']['reference_number'] ?? '';
        $amount = $payment_data['attributes']['amount'] ?? 0;
        
        // Find order by reference number
        if ($reference_number) {
            $conn = new mysqli("localhost", "root", "", "agripreuners_db");
            
            // Update order payment status
            $stmt = $conn->prepare("UPDATE orders SET payment_status = 'paid' WHERE payment_reference = ?");
            $stmt->bind_param("s", $reference_number);
            $stmt->execute();
            $stmt->close();
            
            $conn->close();
        }
    }
}

// Return success response
http_response_code(200);
echo json_encode(['success' => true]);
?>