<?php
declare(strict_types=1);

require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__) . "/../../Kickback/init.php");

use Kickback\Services\Session;

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
    // Get payment ID from query parameter
    $payment_id = $_GET['payment_id'] ?? null;
    
    if (!$payment_id) {
        throw new Exception('Payment ID is required');
    }
    
    // Check runtime payments array
    if (!isset($GLOBALS['runtime_payments'][$payment_id])) {
        throw new Exception('Payment not found');
    }
    
    $payment_data = $GLOBALS['runtime_payments'][$payment_id];
    
    // Return current status
    echo json_encode([
        'payment_id' => $payment_id,
        'status' => $payment_data['status'],
        'amount' => $payment_data['amount'],
        'currency' => $payment_data['currency'],
        'product_name' => $payment_data['product_name'],
        'webhook_received' => $payment_data['webhook_received'],
        'created_at' => $payment_data['created_at']
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}