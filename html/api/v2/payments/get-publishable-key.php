<?php
/**
 * Returns the Stripe publishable key for client-side use.
 * This endpoint is safe to call from the frontend.
 *
 * Response:
 *   {
 *     "success": true,
 *     "data": {
 *       "publishable_key": "pk_test_..."
 *     }
 *   }
 */

require_once(($_SERVER['DOCUMENT_ROOT'] ?: __DIR__ . "/../../..") . "/Kickback/init.php");

use Kickback\Services\StripeService;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $publishableKey = StripeService::getPublishableKey();

    echo json_encode([
        'success' => true,
        'data' => [
            'publishable_key' => $publishableKey,
        ],
    ]);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to retrieve publishable key: ' . $e->getMessage(),
    ]);
}
