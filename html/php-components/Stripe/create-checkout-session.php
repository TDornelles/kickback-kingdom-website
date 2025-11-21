<?php

declare(strict_types=1);

require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__) . "/Kickback/init.php");

use \Kickback\Backend\Config\ServiceCredentials;
use \Kickback\Backend\Controllers\CartController;
use \Kickback\Backend\Models\Response;
use \Kickback\Backend\Models\Enums\CurrencyCode;
use \Kickback\Backend\Views\vRecordId;

use \Stripe\StripeClient;

header('Content-Type: application/json');

$resp = new Response(false, "Unknown error creating checkout session");

try {
    // Get cart from POST data
    if (!isset($_POST["cartCtime"]) || !isset($_POST["cartCrand"])) {
        $resp->message = "Cart ID not provided";
        echo json_encode($resp);
        exit;
    }

    $cartId = new vRecordId($_POST["cartCtime"], (int)$_POST["cartCrand"]);

    // Get cart
    $getCartResp = CartController::get($cartId);
    if (!$getCartResp->success) {
        $resp->message = "Error getting cart: " . $getCartResp->message;
        echo json_encode($resp);
        exit;
    }

    $cart = $getCartResp->data;

    // Get cart totals
    $totalsResp = CartController::getItemTotals($cart);
    if (!$totalsResp->success) {
        $resp->message = "Error getting cart totals: " . $totalsResp->message;
        echo json_encode($resp);
        exit;
    }

    $totals = $totalsResp->data;

    // Find USD total (the simplest approach - only handle USD)
    $usdTotal = 0;
    foreach ($totals as $total) {
        if ($total->currency === CurrencyCode::USD) {
            $usdTotal = $total->price->smallUnitValue; // Amount in cents
            break;
        }
    }

    // Check if cart has items
    if ($usdTotal <= 0) {
        $resp->message = "Cart is empty or has no USD items";
        echo json_encode($resp);
        exit;
    }

    // Create Stripe Checkout Session
    $stripe = new StripeClient(ServiceCredentials::get("stripe_secret_key"));

    $checkout_session = $stripe->checkout->sessions->create([
        'payment_method_types' => ['card'],
        'line_items' => [[
            'price_data' => [
                'currency' => 'usd',
                'product_data' => [
                    'name' => 'Kickback Kingdom Store Purchase',
                ],
                'unit_amount' => $usdTotal,
            ],
            'quantity' => 1,
        ]],
        'mode' => 'payment',
        'success_url' => 'https://' . $_SERVER['HTTP_HOST'] . '/checkout-success.php?session_id={CHECKOUT_SESSION_ID}',
        'cancel_url' => 'https://' . $_SERVER['HTTP_HOST'] . '/checkout.php',
    ]);

    $resp->success = true;
    $resp->message = "Checkout session created successfully";
    $resp->data = [
        'sessionId' => $checkout_session->id
    ];

} catch (Exception $e) {
    $resp->message = "Exception creating checkout session: " . $e->getMessage();
}

echo json_encode($resp);

?>
