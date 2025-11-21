<?php

declare(strict_types=1);

namespace Kickback\Backend\Controllers;

use \Kickback\Backend\Models\Response;
use \Kickback\Backend\Controllers\StoreController;
use \Kickback\Backend\Views\vRecordId;
use \Kickback\Backend\Views\vCart;
use \Kickback\Backend\Views\vAccount;
use \Kickback\Backend\Views\vStore;
use \Kickback\Backend\Views\vTransaction;
use \Exception;

/**
 * CartController - Adapter layer for Stripe integration
 *
 * This controller provides a simplified interface to the cart system
 * specifically for the Stripe checkout integration.
 */
class CartController
{
    /**
     * Get a cart by its ID
     *
     * @param vRecordId $cartId The cart record ID
     * @return Response Response with vCart data or error
     */
    public static function get(vRecordId $cartId): Response
    {
        $resp = new Response(false, "Unknown error getting cart");

        try {
            // Check if cart exists
            $existsResp = StoreController::doesCartExistById($cartId);
            if (!$existsResp->success || !$existsResp->data) {
                $resp->message = "Cart not found";
                return $resp;
            }

            // Get cart items
            $itemsResp = StoreController::getItemsInCart($cartId);
            if (!$itemsResp->success) {
                $resp->message = "Error getting cart items: " . $itemsResp->message;
                return $resp;
            }

            $cartItems = $itemsResp->data;

            // Build vCart object
            $cart = new vCart();
            $cart->ctime = $cartId->ctime;
            $cart->crand = $cartId->crand;
            $cart->cartProducts = $cartItems;
            $cart->checkedOut = false;
            $cart->void = false;

            // Initialize related objects (minimal for Stripe use case)
            $cart->account = new vAccount();
            $cart->store = new vStore();
            $cart->transaction = new vTransaction();

            // Calculate totals
            $cart->totals = StoreController::calculateCartTotalPriceCompnents($cartItems);

            $resp->success = true;
            $resp->message = "Cart retrieved successfully";
            $resp->data = $cart;

        } catch (Exception $e) {
            $resp->message = "Exception getting cart: " . $e->getMessage();
        }

        return $resp;
    }

    /**
     * Get item totals from a cart
     *
     * @param vCart $cart The cart object
     * @return Response Response with totals array
     */
    public static function getItemTotals(vCart $cart): Response
    {
        $resp = new Response(false, "Unknown error getting cart totals");

        try {
            // If totals are already calculated, return them
            if (isset($cart->totals) && is_array($cart->totals)) {
                $resp->success = true;
                $resp->message = "Totals retrieved successfully";
                $resp->data = $cart->totals;
            } else {
                // Otherwise calculate them from cart products
                $totals = StoreController::calculateCartTotalPriceCompnents($cart->cartProducts);
                $resp->success = true;
                $resp->message = "Totals calculated successfully";
                $resp->data = $totals;
            }

        } catch (Exception $e) {
            $resp->message = "Exception getting totals: " . $e->getMessage();
        }

        return $resp;
    }
}

?>
