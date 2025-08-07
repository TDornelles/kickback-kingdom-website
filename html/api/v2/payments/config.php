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

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
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
    
    // Return Stripe publishable key
    echo json_encode([
        'success' => true,
        'data' => [
            'stripe_publishable_key' => StripeService::getPublishableKey()
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Payment config error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Configuration error']);
}