<?php
/**
 * Creates a Stripe PaymentIntent for use with Payment Element.
 * Returns the client secret needed to initialize the Payment Element on the frontend.
 *
 * Request body (JSON):
 *   {
 *     "amount": 1999,              // cents (required)
 *     "currency": "USD",           // optional, defaults to USD
 *     "description": "Item purchase", // optional
 *     "metadata": {...}            // optional key-value pairs
 *   }
 *
 * Response:
 *   {
 *     "success": true,
 *     "data": {
 *       "client_secret": "pi_xxx_secret_yyy",
 *       "payment_intent_id": "pi_xxx"
 *     }
 *   }
 */

require_once(($_SERVER['DOCUMENT_ROOT'] ?: __DIR__ . "/../../..") . "/Kickback/init.php");

use Kickback\Services\StripeService;
use Kickback\Services\Session;

header('Content-Type: application/json');

Session::ensureSessionStarted();

if (!Session::isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    StripeService::initialize();

    $input = json_decode(file_get_contents('php://input'), true) ?: [];

    $amount = (int)($input['amount'] ?? 0);
    $currency = strtolower((string)($input['currency'] ?? 'usd'));
    $description = trim((string)($input['description'] ?? ''));
    $metadata = is_array($input['metadata'] ?? null) ? $input['metadata'] : [];

    // Validate amount
    if ($amount <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Amount must be greater than 0']);
        exit;
    }

    // Validate currency
    if (!StripeService::isCurrencySupported(strtoupper($currency))) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Unsupported currency: ' . $currency]);
        exit;
    }

    // Get current user info for metadata
    $currentAccount = Session::getCurrentAccount();
    if ($currentAccount !== null) {
        $metadata['account_id'] = (string)$currentAccount->crand;
        $metadata['account_name'] = $currentAccount->name;
    }

    // Create PaymentIntent
    $paymentIntentParams = [
        'amount' => $amount,
        'currency' => $currency,
        'automatic_payment_methods' => [
            'enabled' => true,
        ],
    ];

    if ($description !== '') {
        $paymentIntentParams['description'] = $description;
    }

    if (!empty($metadata)) {
        $paymentIntentParams['metadata'] = $metadata;
    }

    $paymentIntent = \Stripe\PaymentIntent::create($paymentIntentParams);

    echo json_encode([
        'success' => true,
        'data' => [
            'client_secret' => $paymentIntent->client_secret,
            'payment_intent_id' => $paymentIntent->id,
        ],
    ]);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to create payment intent: ' . $e->getMessage(),
    ]);
}
