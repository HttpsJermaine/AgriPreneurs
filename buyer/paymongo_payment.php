<?php
session_start();
require_once "../db_connection.php";
require_once "config/paymongo.php";

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'buyer') {
    header("Location: ../login.php?error=Access denied");
    exit;
}

// Check if we have pending checkout data
if (!isset($_SESSION['pending_checkout'])) {
    header("Location: checkout.php?error=Session expired");
    exit;
}

$checkout = $_SESSION['pending_checkout']['data'];
$buyer_id = (int)$_SESSION['user_id'];

// If this is a return after payment success (via redirect)
if (isset($_GET['payment']) && $_GET['payment'] === 'success') {
    // Create the order now
    require_once "process_order.php";
    
    // Clear the pending checkout
    unset($_SESSION['pending_checkout']);
    
    // Set success message
    $_SESSION['flash_msg'] = "Payment successful! Your order has been placed.";
    $_SESSION['flash_type'] = "success";
    
    // Redirect to orders list
    header("Location: orders_list.php");
    exit;
}

// Handle manual completion (user clicked "I've Completed the Payment")
if (isset($_POST['manual_complete'])) {
    // Create the order now
    require_once "process_order.php";
    
    // Clear the pending checkout
    unset($_SESSION['pending_checkout']);
    
    // Set success message
    $_SESSION['flash_msg'] = "Payment confirmed! Your order has been placed.";
    $_SESSION['flash_type'] = "success";
    
    // Redirect to orders list
    header("Location: orders_list.php");
    exit;
}

// Get buyer details for Paymongo
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

$buyer_name = $buyer['full_name'] ?? 'Customer';
$buyer_email = $buyer['email'] ?? '';
$buyer_phone = $buyer['phone'] ?? '';

$total = (float)($checkout['total'] ?? 0);
$amount_in_cents = (int)($total * 100);

// Create Paymongo payment link
$ch = curl_init();

$payment_data = [
    'data' => [
        'attributes' => [
            'amount' => $amount_in_cents,
            'description' => "PLAMAL Order",
            'remarks' => "Marketplace Purchase",
            'billing' => [
                'name' => $buyer_name,
                'email' => $buyer_email,
                'phone' => $buyer_phone
            ],
            // Return to THIS page with success parameter
            'success_url' => 'http://localhost/AgriPreneurs/buyer/paymongo_payment.php?payment=success',
            'failure_url' => 'http://localhost/AgriPreneurs/buyer/paymongo_payment.php?payment=failed',
            'cancel_url' => 'http://localhost/AgriPreneurs/buyer/paymongo_payment.php?payment=cancelled'
        ]
    ]
];

curl_setopt($ch, CURLOPT_URL, PAYMONGO_API_URL . '/links');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payment_data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Basic ' . base64_encode(PAYMONGO_SECRET_KEY . ':')
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code !== 200 && $http_code !== 201) {
    echo "<h3>Error creating payment link</h3>";
    echo "<pre>";
    print_r(json_decode($response, true));
    echo "</pre>";
    exit;
}

$result = json_decode($response, true);
$payment_link = $result['data']['attributes']['checkout_url'] ?? null;
$reference_number = $result['data']['attributes']['reference_number'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Payment</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #e9f0ec 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .payment-container {
            max-width: 600px;
            width: 100%;
            background: white;
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
            overflow: hidden;
            animation: slideUp 0.5s ease;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .payment-header {
            background: linear-gradient(135deg, #124131 0%, #1e6b5c 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .payment-header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .payment-header p {
            opacity: 0.9;
            font-size: 18px;
        }
        
        .warning-box {
            background: #fff3cd;
            border: 1px solid #ffe69c;
            color: #856404;
            padding: 20px;
            margin: 20px;
            border-radius: 12px;
            font-size: 14px;
        }
        
        .warning-box strong {
            color: #533f03;
        }
        
        .test-card-info {
            background: #e8f4f0;
            border: 1px solid #a7d7c5;
            color: #124131;
            padding: 20px;
            margin: 20px;
            border-radius: 12px;
            font-size: 14px;
        }
        
        .test-card-details {
            background: white;
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
            font-family: monospace;
            font-size: 15px;
            border: 1px dashed #124131;
        }
        
        .button-group {
            display: flex;
            gap: 15px;
            padding: 20px;
            flex-wrap: wrap;
        }
        
        .btn {
            flex: 1;
            min-width: 200px;
            padding: 16px 24px;
            border: none;
            border-radius: 40px;
            font-weight: 700;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            text-align: center;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .btn-primary {
            background: #124131;
            color: white;
            box-shadow: 0 4px 12px rgba(18,65,49,0.2);
        }
        
        .btn-primary:hover {
            background: #1e6b5c;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(18,65,49,0.3);
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-success:hover:not(:disabled) {
            background: #34ce57;
            transform: translateY(-2px);
        }
        
        .btn-success:disabled {
            background: #cccccc;
            color: #666666;
            cursor: not-allowed;
            transform: none;
            opacity: 0.6;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c82333;
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }
        
        .status-box {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
            padding: 15px;
            margin: 20px;
            border-radius: 8px;
            text-align: center;
            display: none;
        }
        
        .status-box.info {
            display: block;
            background: #d1ecf1;
            border-color: #bee5eb;
            color: #0c5460;
        }
        
        .spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(18,65,49,0.3);
            border-radius: 50%;
            border-top-color: #124131;
            animation: spin 1s linear infinite;
            margin-right: 10px;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .note {
            text-align: center;
            padding: 20px;
            color: #666;
            font-size: 14px;
            border-top: 1px solid #e0f0e8;
        }
        
        .reference {
            text-align: center;
            font-size: 12px;
            color: #999;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="payment-container">
        <div class="payment-header">
            <h1>Complete Your Payment</h1>
            <p>Total: ₱<?= number_format($total, 2) ?></p>
        </div>

        <?php if ($reference_number): ?>
        <div class="reference">
            Reference: <?= htmlspecialchars($reference_number) ?>
        </div>
        <?php endif; ?>
        
        <div class="warning-box">
            <strong>⚠️ Important:</strong><br><br>
            Your order is NOT yet placed. It will only be created AFTER successful payment.<br><br>
            If you leave this page without completing payment, no order will be created.
        </div>
    
        <div class="status-box" id="paymentStatus"></div>
        
        <div class="button-group">
            <button class="btn btn-primary" id="payButton">
                 Continue to Payment
            </button>
            
            <button class="btn btn-success" id="completeButton" disabled>
                Payment Completed
            </button>
        </div>
        
        <div class="button-group">
            <a href="clear_checkout_session.php" class="btn btn-danger" 
               onclick="return confirm('Are you sure? No order will be created.');">
                <span>✖</span> Cancel & Return to Cart
            </a>
        </div>
        
     
    
    </div>
    
    <script>
        const payButton = document.getElementById('payButton');
        const completeButton = document.getElementById('completeButton');
        const statusBox = document.getElementById('paymentStatus');
        const paymentLink = '<?= $payment_link ?>';
        
        // Track whether Paymongo was opened
        let paymongoOpened = false;
        
        // Open Paymongo in new tab when clicking Pay button
        payButton.addEventListener('click', function() {
            // Open Paymongo in a new tab
            window.open(paymentLink, '_blank');
            
            // Mark that Paymongo was opened
            paymongoOpened = true;
            
            // Enable the complete button
            completeButton.disabled = false;
            
            // Show status message
            statusBox.className = 'status-box info';
            statusBox.innerHTML = `
                <div style="display: flex; align-items: center; justify-content: center; gap: 10px;">
                    <span class="spinner"></span>
                    <span>Payment window opened! Complete your payment there, then click "I've Completed the Payment" below.</span>
                </div>
            `;
            
            // Disable pay button to prevent multiple clicks
            payButton.disabled = true;
            payButton.style.opacity = '0.5';
        });
        
        // Handle manual completion
        completeButton.addEventListener('click', function() {
            if (!paymongoOpened) {
                alert('Please click "Pay with Paymongo" first to complete the payment.');
                return;
            }
            
            // Show loading state
            completeButton.disabled = true;
            completeButton.innerHTML = '<span class="spinner"></span> Processing...';
            
            // Create a form and submit it to mark as complete
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = window.location.href;
            
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'manual_complete';
            input.value = '1';
            
            form.appendChild(input);
            document.body.appendChild(form);
            form.submit();
        });
        
        // Check if we're returning from Paymongo (if this tab was opened as a popup)
        if (window.opener) {
            // This tab was opened by the parent - close it and notify parent
            if (window.opener.paymentComplete) {
                window.opener.paymentComplete();
            }
            window.close();
        }
        
        // Check if URL has success parameters (handles rare redirect cases)
        if (window.location.search.includes('payment=success')) {
            // Auto-submit the manual completion
            setTimeout(function() {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = window.location.href;
                
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'manual_complete';
                input.value = '1';
                
                form.appendChild(input);
                document.body.appendChild(form);
                form.submit();
            }, 2000);
        }
        
        // Warn user if they try to leave without completing
        window.addEventListener('beforeunload', function(e) {
            if (paymongoOpened && !completeButton.disabled) {
                // Cancel the event
                e.preventDefault();
                // Chrome requires returnValue to be set
                e.returnValue = 'Have you completed your payment? Click "I\'ve Completed the Payment" if you have.';
                return e.returnValue;
            }
        });
    </script>
</body>
</html>