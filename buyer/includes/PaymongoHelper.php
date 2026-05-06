<?php
require_once __DIR__ . '/../config/paymongo.php';

class PaymongoHelper {
    
    private $secret_key;
    private $public_key;
    private $api_url;
    
    public function __construct() {
        $this->secret_key = PAYMONGO_SECRET_KEY;
        $this->public_key = PAYMONGO_PUBLIC_KEY;
        $this->api_url = PAYMONGO_API_URL;
    }
    
    /**
     * Create a payment link
     */
    public function createPaymentLink($amount, $description, $buyer_info, $order_id) {
        $amount_in_cents = (int)($amount * 100); // Paymongo accepts amount in cents
        
        $data = [
            'data' => [
                'attributes' => [
                    'amount' => $amount_in_cents,
                    'description' => $description,
                    'remarks' => "Order #$order_id",
                    'billing' => [
                        'name' => $buyer_info['name'] ?? 'Buyer',
                        'email' => $buyer_info['email'] ?? '',
                        'phone' => $buyer_info['phone'] ?? '',
                        'address' => [
                            'line1' => $buyer_info['address'] ?? '',
                            'city' => $buyer_info['city'] ?? '',
                            'state' => $buyer_info['province'] ?? '',
                            'country' => 'PH',
                            'postal_code' => $buyer_info['zip'] ?? ''
                        ]
                    ]
                ]
            ]
        ];
        
        return $this->makeRequest('POST', '/links', $data);
    }
    
    /**
     * Create a payment intent (for embedded payment)
     */
    public function createPaymentIntent($amount, $description, $buyer_info, $order_id) {
        $amount_in_cents = (int)($amount * 100);
        
        $data = [
            'data' => [
                'attributes' => [
                    'amount' => $amount_in_cents,
                    'payment_method_allowed' => ['card', 'gcash', 'grab_pay'],
                    'payment_method_options' => [
                        'card' => ['request_three_d_secure' => 'any']
                    ],
                    'description' => $description,
                    'statement_descriptor' => "PLAMAL Order #$order_id",
                    'currency' => 'PHP',
                    'metadata' => [
                        'order_id' => $order_id,
                        'buyer_id' => $buyer_info['buyer_id'] ?? ''
                    ]
                ]
            ]
        ];
        
        return $this->makeRequest('POST', '/payment_intents', $data);
    }
    
    /**
     * Attach payment method to payment intent
     */
    public function attachPaymentIntent($payment_intent_id, $payment_method_id) {
        $data = [
            'data' => [
                'attributes' => [
                    'payment_method' => $payment_method_id,
                    'client_key' => $this->generateClientKey()
                ]
            ]
        ];
        
        return $this->makeRequest('POST', "/payment_intents/$payment_intent_id/attach", $data);
    }
    
    /**
     * Retrieve payment intent
     */
    public function getPaymentIntent($payment_intent_id) {
        return $this->makeRequest('GET', "/payment_intents/$payment_intent_id");
    }
    
    /**
     * Retrieve payment link
     */
    public function getPaymentLink($link_id) {
        return $this->makeRequest('GET', "/links/$link_id");
    }
    
    /**
     * List payment methods
     */
    public function listPaymentMethods() {
        return $this->makeRequest('GET', '/payment_methods');
    }
    
    /**
     * Make API request to Paymongo
     */
    private function makeRequest($method, $endpoint, $data = null) {
        $url = $this->api_url . $endpoint;
        
        $ch = curl_init();
        
        $headers = [
            'Content-Type: application/json',
            'Authorization: Basic ' . base64_encode($this->secret_key . ':')
        ];
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        
        if ($data && ($method === 'POST' || $method === 'PUT')) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        // For test mode, disable SSL verification
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            throw new Exception('Paymongo API Error: ' . curl_error($ch));
        }
        
        curl_close($ch);
        
        return [
            'code' => $http_code,
            'body' => json_decode($response, true)
        ];
    }
    
    /**
     * Generate client key for frontend
     */
    private function generateClientKey() {
        return bin2hex(random_bytes(16));
    }
    
    /**
     * Handle Paymongo webhook
     */
    public function handleWebhook($payload) {
        $event = $payload['data']['attributes']['type'] ?? '';
        $data = $payload['data']['attributes']['data'] ?? [];
        
        switch ($event) {
            case 'payment.paid':
                // Payment successful
                $this->processSuccessfulPayment($data);
                break;
            case 'payment.failed':
                // Payment failed
                $this->processFailedPayment($data);
                break;
            case 'link.paid':
                // Payment link paid
                $this->processLinkPayment($data);
                break;
        }
        
        return true;
    }
    
    /**
     * Process successful payment
     */
    private function processSuccessfulPayment($data) {
        global $conn;
        
        $payment_intent_id = $data['id'] ?? '';
        $metadata = $data['attributes']['metadata'] ?? [];
        $order_id = $metadata['order_id'] ?? 0;
        
        if ($order_id) {
            // Update order payment status
            $stmt = $conn->prepare("UPDATE orders SET payment_status = 'paid' WHERE id = ?");
            $stmt->bind_param("i", $order_id);
            $stmt->execute();
            $stmt->close();
            
            // Log payment transaction
            $stmt = $conn->prepare("
                INSERT INTO payment_transactions 
                (order_id, payment_intent_id, amount, payment_method, status, created_at)
                VALUES (?, ?, ?, ?, 'paid', NOW())
            ");
            
            $amount = $data['attributes']['amount'] / 100;
            $payment_method = $data['attributes']['payment_method']['type'] ?? 'unknown';
            
            $stmt->bind_param("isds", $order_id, $payment_intent_id, $amount, $payment_method);
            $stmt->execute();
            $stmt->close();
        }
    }
    
    /**
     * Process failed payment
     */
    private function processFailedPayment($data) {
        $payment_intent_id = $data['id'] ?? '';
        $metadata = $data['attributes']['metadata'] ?? [];
        $order_id = $metadata['order_id'] ?? 0;
        
        if ($order_id) {
            global $conn;
            
            // Update order payment status
            $stmt = $conn->prepare("UPDATE orders SET payment_status = 'failed' WHERE id = ?");
            $stmt->bind_param("i", $order_id);
            $stmt->execute();
            $stmt->close();
        }
    }
    
    /**
     * Process link payment
     */
    private function processLinkPayment($data) {
        $link_id = $data['id'] ?? '';
        $attributes = $data['attributes'] ?? [];
        $reference_number = $attributes['reference_number'] ?? '';
        $order_id = $this->extractOrderIdFromReference($reference_number);
        
        if ($order_id) {
            global $conn;
            
            // Update order payment status
            $stmt = $conn->prepare("UPDATE orders SET payment_status = 'paid' WHERE id = ?");
            $stmt->bind_param("i", $order_id);
            $stmt->execute();
            $stmt->close();
        }
    }
    
    /**
     * Extract order ID from reference number
     */
    private function extractOrderIdFromReference($reference) {
        // Example: PLM-12345-67890
        if (preg_match('/PLM-(\d+)/', $reference, $matches)) {
            return (int)$matches[1];
        }
        return 0;
    }
}
?>