<?php
declare(strict_types=1);

require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__) . "/../../Kickback/init.php");

use Kickback\Services\Session;
use Kickback\Services\StripeService;

// Ensure session is started
Session::ensureSessionStarted();

// Check authentication
if (!Session::isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required']);
    exit;
}

// Set content type
header('Content-Type: application/json');

try {
    // Get request data
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON input');
    }
    
    // Validate required fields
    $required_fields = ['amount', 'currency', 'product_name'];
    foreach ($required_fields as $field) {
        if (!isset($input[$field]) || empty($input[$field])) {
            throw new Exception("Missing required field: {$field}");
        }
    }
    
    $amount = (int) $input['amount'];
    $currency = strtolower($input['currency']);
    $product_name = $input['product_name'];
    
    // Validate amount (minimum $0.50)
    if ($amount < 50) {
        throw new Exception('Amount must be at least $0.50');
    }
    
    // Validate currency
    if (!StripeService::isCurrencySupported($currency)) {
        throw new Exception('Unsupported currency: ' . $currency);
    }
    
    // Initialize Stripe
    StripeService::initialize();
    $stripe = StripeService::getClient();
    
    // Create Payment Intent
    $paymentIntent = $stripe->paymentIntents->create([
        'amount' => $amount,
        'currency' => $currency,
        'description' => "Purchase: {$product_name}",
        'metadata' => [
            'product_name' => $product_name,
            'user_id' => Session::getAccountId(),
            'user_username' => Session::getUsername()
        ],
        'automatic_payment_methods' => [
            'enabled' => true,
        ],
    ]);
    
    // Store payment in runtime tracking (in-memory)
    $payment_id = $paymentIntent->id;
    $payment_data = [
        'payment_intent_id' => $payment_id,
        'amount' => $amount,
        'currency' => $currency,
        'product_name' => $product_name,
        'user_id' => Session::getAccountId(),
        'status' => 'created',
        'created_at' => time(),
        'webhook_received' => false
    ];
    
    // Store in global runtime array (this will be lost on server restart)
    if (!isset($GLOBALS['runtime_payments'])) {
        $GLOBALS['runtime_payments'] = [];
    }
    $GLOBALS['runtime_payments'][$payment_id] = $payment_data;
    
    // Return response
    echo json_encode([
        'client_secret' => $paymentIntent->client_secret,
        'payment_id' => $payment_id,
        'status' => 'created'
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}