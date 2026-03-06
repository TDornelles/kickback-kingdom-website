<?php
declare(strict_types=1);

require_once(($_SERVER['DOCUMENT_ROOT'] ?: __DIR__ . "/../../../..") . "/Kickback/init.php");

use Kickback\Backend\Models\Response;
use Kickback\BackendV2\Controllers\PaymentController;

\header('Content-Type: application/json');

$response = null;

try
{
    $payload = file_get_contents('php://input');
    $sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

    if (empty($payload) || empty($sigHeader))
    {
        \http_response_code(400);
        $response = new Response(false, 'Missing payload or signature', null);
        echo \json_encode($response);
        exit;
    }

    $paymentController = new PaymentController();
    $response_code = $paymentController->handleWebhook($payload, $sigHeader, $response);
    \http_response_code($response_code);
}
catch( \Throwable $e )
{
    \http_response_code(500);
    $response = new Response(false, 'Webhook processing error', null);
}

echo \json_encode($response);
