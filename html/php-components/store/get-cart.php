<?php

declare(strict_types=1);

require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__) . "/Kickback/init.php");

use \Kickback\Backend\Controllers\CartController;
use \Kickback\Backend\Models\Response;
use \Kickback\Backend\Models\Enums\CurrencyCode;
use \Kickback\Backend\Views\vRecordId;

header('Content-Type: application/json');

$resp = new Response(false, "Unknown error getting cart");

try {
    // Get cart ID from POST data
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

    // Generate cart items HTML
    $cartHtml = '';
    if (empty($cart->cartProducts)) {
        $cartHtml = '<p class="text-center text-muted">Your cart is empty</p>';
    } else {
        $cartHtml .= '<div class="list-group">';
        foreach ($cart->cartProducts as $item) {
            $productName = htmlspecialchars($item->product->name ?? 'Unknown Product');
            $cartHtml .= '<div class="list-group-item">';
            $cartHtml .= '<div class="d-flex justify-content-between align-items-center">';
            $cartHtml .= '<div class="fw-bold">' . $productName . '</div>';
            $cartHtml .= '</div>';
            $cartHtml .= '</div>';
        }
        $cartHtml .= '</div>';
    }

    // Generate totals HTML
    $totalsHtml = '';
    if (!empty($cart->totals)) {
        $totalsHtml .= '<div class="card">';
        $totalsHtml .= '<div class="card-body">';
        $totalsHtml .= '<h5 class="card-title">Cart Totals</h5>';
        $totalsHtml .= '<div class="table-responsive">';
        $totalsHtml .= '<table class="table table-sm">';

        foreach ($cart->totals as $total) {
            $currency = '';
            $amount = 0;

            // Handle different price component types
            if (isset($total->currencyCode) && $total->currencyCode !== null) {
                $currency = $total->currencyCode;
                $amount = $total->amount ?? 0;

                // Format amount based on currency
                if ($currency === CurrencyCode::USD) {
                    $formattedAmount = '$' . number_format($amount / 100, 2);
                } else {
                    $formattedAmount = $amount . ' ' . $currency;
                }
            } elseif (isset($total->item) && $total->item !== null) {
                $currency = htmlspecialchars($total->item->name ?? 'Items');
                $amount = $total->amount ?? 0;
                $formattedAmount = $amount . ' ' . $currency;
            } else {
                continue; // Skip unknown price component types
            }

            $totalsHtml .= '<tr>';
            $totalsHtml .= '<td class="text-end fw-bold">' . $formattedAmount . '</td>';
            $totalsHtml .= '</tr>';
        }

        $totalsHtml .= '</table>';
        $totalsHtml .= '</div>';
        $totalsHtml .= '</div>';
        $totalsHtml .= '</div>';
    } else {
        $totalsHtml = '<p class="text-muted">No totals available</p>';
    }

    $resp->success = true;
    $resp->message = "Cart retrieved successfully";
    $resp->data = [
        'cartHtml' => $cartHtml,
        'totalsHtml' => $totalsHtml
    ];

} catch (Exception $e) {
    $resp->message = "Exception getting cart: " . $e->getMessage();
}

echo json_encode($resp);

?>
