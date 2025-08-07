<?php
require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__ . "/../../..") . "/Kickback/init.php");

use Kickback\Services\Session;
use Kickback\Services\StripeService;

header('Content-Type: application/json');

// Only allow logged-in users
if (!Session::isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    // Check if Stripe is configured
    if (!StripeService::isConfigured()) {
        http_response_code(503);
        echo json_encode(['success' => false, 'message' => 'Payment system not configured']);
        exit;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate input
    if (!isset($input['payment_method_id']) || !isset($input['amount'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Payment method ID and amount are required']);
        exit;
    }
    
    $paymentMethodId = $input['payment_method_id'];
    $amount = (float)$input['amount'];
    $currency = $input['currency'] ?? 'USD';
    $description = $input['description'] ?? 'Payment';
    $accountId = Session::getCurrentAccount()->crand;
    
    if ($amount <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Amount must be greater than 0']);
        exit;
    }
    
    // Create and confirm Payment Intent immediately
    $stripe = StripeService::getClient();
    $paymentIntent = $stripe->paymentIntents->create([
        'amount' => (int)round($amount * 100), // Convert to cents
        'currency' => strtolower($currency),
        'payment_method' => $paymentMethodId,
        'confirmation_method' => 'manual',
        'confirm' => true,
        'return_url' => $input['return_url'] ?? 'https://example.com',
        'metadata' => [
            'account_id' => $accountId,
            'description' => $description
        ],
    ]);
    
    // Check payment status
    if ($paymentIntent->status === 'succeeded') {
        // Payment successful - no database operations
        echo json_encode([
            'success' => true,
            'message' => 'Payment processed successfully',
            'data' => [
                'payment_intent_id' => $paymentIntent->id,
                'status' => $paymentIntent->status,
                'amount' => $amount,
                'currency' => $currency,
                'description' => $description
            ]
        ]);
    } elseif ($paymentIntent->status === 'requires_action') {
        // 3D Secure or other authentication required
        echo json_encode([
            'success' => false,
            'requires_action' => true,
            'data' => [
                'payment_intent_id' => $paymentIntent->id,
                'client_secret' => $paymentIntent->client_secret,
                'next_action' => $paymentIntent->next_action
            ]
        ]);
    } else {
        // Payment failed
        echo json_encode([
            'success' => false,
            'message' => 'Payment failed: ' . ($paymentIntent->last_payment_error->message ?? 'Unknown error'),
            'data' => [
                'payment_intent_id' => $paymentIntent->id,
                'status' => $paymentIntent->status
            ]
        ]);
    }
    
} catch (Exception $e) {
    error_log("Payment processing error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Payment processing failed'
    ]);
}