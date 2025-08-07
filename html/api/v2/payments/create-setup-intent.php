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
    $accountId = Session::getCurrentAccount()->crand;
    $description = $input['description'] ?? 'Payment method setup';
    
    // Create Stripe Setup Intent
    $stripe = StripeService::getClient();
    $setupIntent = $stripe->setupIntents->create([
        'customer' => null, // Not creating customer for minimal implementation
        'usage' => 'off_session',
        'metadata' => [
            'account_id' => $accountId,
            'description' => $description
        ],
        'automatic_payment_methods' => [
            'enabled' => true,
            'allow_redirects' => 'never'
        ],
    ]);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'client_secret' => $setupIntent->client_secret,
            'setup_intent_id' => $setupIntent->id,
            'description' => $description
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Setup Intent creation error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Setup Intent creation failed'
    ]);
}