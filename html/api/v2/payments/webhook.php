<?php
declare(strict_types=1);

require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__) . "/../../Kickback/init.php");

use Kickback\Services\StripeService;

// Set content type
header('Content-Type: application/json');

try {
    // Get the raw POST body
    $payload = @file_get_contents('php://input');
    $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
    
    if (empty($payload) || empty($sig_header)) {
        throw new Exception('Missing payload or signature');
    }
    
    // Initialize Stripe for webhook verification
    StripeService::initialize();
    
    // Get webhook secret from credentials
    $webhook_secret = \Kickback\Backend\Config\ServiceCredentials::get('stripe_webhook_secret');
    if (empty($webhook_secret)) {
        throw new Exception('Webhook secret not configured');
    }
    
    // Verify webhook signature
    $event = \Stripe\Webhook::constructEvent(
        $payload,
        $sig_header,
        $webhook_secret
    );
    
    // Handle the event
    switch ($event->type) {
        case 'payment_intent.succeeded':
            handlePaymentSucceeded($event->data->object);
            break;
            
        case 'payment_intent.payment_failed':
            handlePaymentFailed($event->data->object);
            break;
            
        case 'payment_intent.canceled':
            handlePaymentCanceled($event->data->object);
            break;
            
        default:
            // Log unhandled event types
            error_log("Unhandled webhook event type: {$event->type}");
    }
    
    // Return success response
    http_response_code(200);
    echo json_encode(['status' => 'success']);
    
} catch (\Stripe\Exception\SignatureVerificationException $e) {
    // Invalid signature
    http_response_code(400);
    echo json_encode(['error' => 'Invalid signature']);
    error_log("Stripe webhook signature verification failed: " . $e->getMessage());
    
} catch (Exception $e) {
    // Other errors
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
    error_log("Stripe webhook error: " . $e->getMessage());
}

/**
 * Handle successful payment
 */
function handlePaymentSucceeded($payment_intent) {
    $payment_id = $payment_intent->id;
    
    // Update runtime payment tracking
    if (isset($GLOBALS['runtime_payments'][$payment_id])) {
        $GLOBALS['runtime_payments'][$payment_id]['status'] = 'succeeded';
        $GLOBALS['runtime_payments'][$payment_id]['webhook_received'] = true;
        $GLOBALS['runtime_payments'][$payment_id]['webhook_at'] = time();
        
        // Log successful payment
        error_log("Payment succeeded: {$payment_id} - Amount: {$payment_intent->amount} {$payment_intent->currency}");
        
        // Here you could trigger additional business logic:
        // - Send confirmation email
        // - Grant access to digital products
        // - Update user account
        // - Send notifications
        
    } else {
        error_log("Payment succeeded but not found in runtime tracking: {$payment_id}");
    }
}

/**
 * Handle failed payment
 */
function handlePaymentFailed($payment_intent) {
    $payment_id = $payment_intent->id;
    
    // Update runtime payment tracking
    if (isset($GLOBALS['runtime_payments'][$payment_id])) {
        $GLOBALS['runtime_payments'][$payment_id]['status'] = 'failed';
        $GLOBALS['runtime_payments'][$payment_id]['webhook_received'] = true;
        $GLOBALS['runtime_payments'][$payment_id]['webhook_at'] = time();
        $GLOBALS['runtime_payments'][$payment_id]['failure_reason'] = $payment_intent->last_payment_error->message ?? 'Unknown error';
        
        // Log failed payment
        error_log("Payment failed: {$payment_id} - Reason: " . ($payment_intent->last_payment_error->message ?? 'Unknown'));
        
    } else {
        error_log("Payment failed but not found in runtime tracking: {$payment_id}");
    }
}

/**
 * Handle canceled payment
 */
function handlePaymentCanceled($payment_intent) {
    $payment_id = $payment_intent->id;
    
    // Update runtime payment tracking
    if (isset($GLOBALS['runtime_payments'][$payment_id])) {
        $GLOBALS['runtime_payments'][$payment_id]['status'] = 'canceled';
        $GLOBALS['runtime_payments'][$payment_id]['webhook_received'] = true;
        $GLOBALS['runtime_payments'][$payment_id]['webhook_at'] = time();
        
        // Log canceled payment
        error_log("Payment canceled: {$payment_id}");
        
    } else {
        error_log("Payment canceled but not found in runtime tracking: {$payment_id}");
    }
}