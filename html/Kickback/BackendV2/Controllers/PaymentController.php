<?php

declare(strict_types=1);

namespace Kickback\BackendV2\Controllers;

use Exception;
use Kickback\Backend\Models\Response;
use Kickback\Backend\Models\Enums\CurrencyCode;
use Kickback\Backend\Views\vAccount;
use Kickback\Backend\Views\vRecordId;

use Kickback\BackendV2\DAO\Cart\CartDAO;
use Kickback\BackendV2\DAO\Cart\PDOCartDAO;
use Kickback\BackendV2\Services\Cart\CartService;
use Kickback\BackendV2\Services\Cart\DAOCartService;
use Kickback\BackendV2\Services\Payment\PaymentService;
use Kickback\BackendV2\Services\Payment\StripePaymentService;

class PaymentController
{
    private PaymentService $paymentService;
    private CartService $cartService;
    private CartDAO $cartDAO;

    public function __construct(
        ?PaymentService $paymentService = null,
        ?CartService $cartService = null,
        ?CartDAO $cartDAO = null
    )
    {
        $this->paymentService = $paymentService ?? new StripePaymentService();
        $this->cartService = $cartService ?? new DAOCartService();
        $this->cartDAO = $cartDAO ?? new PDOCartDAO();
    }

    /**
     * Initiates the checkout process for a cart.
     * If the cart has USD-priced items, creates a Stripe checkout session.
     * If it only has loot/item-priced items, executes checkout directly.
     *
     * @param ?vAccount $account the authenticated account
     * @param ?string $jsonRequest JSON with {"storeLocator": "..."}
     * @param ?Response $response the out response
     *
     * @return int HTTP status code
     */
    public function initiateCheckout(?vAccount $account, ?string $jsonRequest, ?Response &$response) : int
    {
        $response = new Response(false, "Unknown error initiating checkout", null);

        try
        {
            $assocRequest = json_decode((string)$jsonRequest, true);

            if (!is_array($assocRequest))
            {
                $response->message = "Failed to decode request body as JSON";
                return 400;
            }

            if (!array_key_exists("storeLocator", $assocRequest))
            {
                $response->message = "key \"storeLocator\" is missing from request body";
                return 400;
            }

            $storeLocator = $assocRequest["storeLocator"];

            if (is_null($storeLocator) || !is_string($storeLocator))
            {
                $response->message = "storeLocator must be a non-null string";
                return 400;
            }

            // Get the cart with all products and totals
            $getCartResp = $this->cartService->getCartForAccountWithStoreLocator($account, $storeLocator);

            if (!$getCartResp->success || is_null($getCartResp->data))
            {
                $response->message = "Failed to get cart for store \"$storeLocator\"";
                return 500;
            }

            $cart = $getCartResp->data;

            if (empty($cart->cartProducts))
            {
                $response->message = "Cart is empty";
                return 400;
            }

            // Calculate USD total from cart totals
            $usdTotalCents = $this->calculateUsdTotalCents($cart->totals);

            if ($usdTotalCents > 0)
            {
                // Cart has USD components — create Stripe checkout session
                $sessionResult = $this->paymentService->createCheckoutSession($cart, $usdTotalCents);

                // Store the Stripe session ID on the cart
                $this->cartDAO->setStripeSessionId($cart, $sessionResult['sessionId']);
                $this->cartDAO->createStripeTransaction($cart, $sessionResult['sessionId']);

                $response->success = true;
                $response->message = "Stripe checkout session created";
                $response->data = [
                    'requiresPayment' => true,
                    'redirectUrl' => $sessionResult['url'],
                    'sessionId' => $sessionResult['sessionId'],
                ];
                return 200;
            }
            else
            {
                // No USD — only loot/item prices. Execute checkout immediately.
                $checkoutResult = $this->cartDAO->checkoutCart($cart);

                if ($checkoutResult !== true)
                {
                    if ($checkoutResult === false)
                    {
                        $response->message = "You don't have the required loot to checkout these products!";
                        $response->data = false;
                        return 403;
                    }

                    $response->message = "Failed to checkout cart";
                    return 500;
                }

                $response->success = true;
                $response->message = "Cart checked out successfully";
                $response->data = [
                    'requiresPayment' => false,
                    'success' => true,
                ];
                return 200;
            }
        }
        catch (Exception $e)
        {
            $response->message = "Exception initiating checkout: " . $e->getMessage();
            return 500;
        }
    }

    /**
     * Handles a Stripe webhook callback.
     * Verifies the signature, then processes the checkout.session.completed event.
     *
     * @param string $payload the raw POST body
     * @param string $sigHeader the Stripe-Signature header
     * @param ?Response $response the out response
     *
     * @return int HTTP status code
     */
    public function handleWebhook(string $payload, string $sigHeader, ?Response &$response) : int
    {
        $response = new Response(false, "Unknown error handling webhook", null);

        try
        {
            $event = $this->paymentService->verifyWebhookSignature($payload, $sigHeader);

            if ($event->type !== 'checkout.session.completed')
            {
                // Acknowledge non-checkout events without processing
                $response->success = true;
                $response->message = "Event type '{$event->type}' acknowledged";
                return 200;
            }

            $session = $event->data->object;
            $sessionId = $session->id;

            // Look up the cart by Stripe session ID
            $cart = $this->cartDAO->getCartByStripeSessionId($sessionId);

            if (is_null($cart))
            {
                $response->message = "No cart found for Stripe session '$sessionId'";
                return 400;
            }

            // Check if cart is already checked out (idempotent handling)
            if ($cart->checkedOut)
            {
                $response->success = true;
                $response->message = "Cart already checked out";
                return 200;
            }

            // Calculate totals for the loaded cart
            $cart->totals = $this->calculateCartTotalPriceComponents($cart->cartProducts);

            // Execute the full checkout (loot transfers + mark checked out)
            $checkoutResult = $this->cartDAO->checkoutCart($cart);

            if ($checkoutResult !== true)
            {
                $response->message = "Failed to execute checkout for Stripe session '$sessionId'";
                return 500;
            }

            $response->success = true;
            $response->message = "Checkout completed for session '$sessionId'";
            return 200;
        }
        catch (\Stripe\Exception\SignatureVerificationException $e)
        {
            $response->message = "Invalid webhook signature";
            return 400;
        }
        catch (Exception $e)
        {
            $response->message = "Webhook error: " . $e->getMessage();
            return 500;
        }
    }

    /**
     * Checks the payment status for a cart's Stripe session.
     *
     * @param ?vAccount $account the authenticated account
     * @param ?string $jsonRequest JSON with {"storeLocator": "..."}
     * @param ?Response $response the out response
     *
     * @return int HTTP status code
     */
    public function getCheckoutStatus(?vAccount $account, ?string $jsonRequest, ?Response &$response) : int
    {
        $response = new Response(false, "Unknown error getting checkout status", null);

        try
        {
            $assocRequest = json_decode((string)$jsonRequest, true);

            if (!is_array($assocRequest))
            {
                $response->message = "Failed to decode request body as JSON";
                return 400;
            }

            if (!array_key_exists("storeLocator", $assocRequest))
            {
                $response->message = "key \"storeLocator\" is missing from request body";
                return 400;
            }

            $storeLocator = $assocRequest["storeLocator"];

            $getCartResp = $this->cartService->getCartForAccountWithStoreLocator($account, $storeLocator);

            if (!$getCartResp->success || is_null($getCartResp->data))
            {
                $response->message = "Failed to get cart";
                return 500;
            }

            $cart = $getCartResp->data;

            if ($cart->checkedOut)
            {
                $response->success = true;
                $response->message = "Cart is checked out";
                $response->data = ['status' => 'completed', 'checkedOut' => true];
                return 200;
            }

            if (empty($cart->stripeSessionId))
            {
                $response->success = true;
                $response->message = "No payment session found";
                $response->data = ['status' => 'no_session', 'checkedOut' => false];
                return 200;
            }

            $paymentStatus = $this->paymentService->getSessionStatus($cart->stripeSessionId);

            $response->success = true;
            $response->message = "Payment status retrieved";
            $response->data = [
                'status' => $paymentStatus,
                'checkedOut' => $cart->checkedOut,
                'sessionId' => $cart->stripeSessionId,
            ];
            return 200;
        }
        catch (Exception $e)
        {
            $response->message = "Exception getting checkout status: " . $e->getMessage();
            return 500;
        }
    }

    /**
     * Calculates the USD total in cents from an array of price component totals.
     */
    private function calculateUsdTotalCents(array $totals) : int
    {
        $usdTotal = 0;

        foreach ($totals as $total)
        {
            if (!is_null($total->currencyCode) && $total->currencyCode === CurrencyCode::USD)
            {
                $usdTotal += $total->amount;
            }
        }

        return $usdTotal;
    }

    /**
     * Re-calculates cart total price components from cart products.
     * Duplicated from DAOCartService to allow webhook processing without a full service call.
     */
    private function calculateCartTotalPriceComponents(array $cartItems) : array
    {
        $totals = [];

        foreach ($cartItems as $cartItem)
        {
            $price = $cartItem->product->price;

            foreach ($price as $priceComponent)
            {
                $alreadyExistingTotal = null;

                foreach ($totals as $total)
                {
                    if (
                        (!is_null($priceComponent->item) && !is_null($total->item) &&
                        $priceComponent->item->ctime == $total->item->ctime && $priceComponent->item->crand == $total->item->crand)
                        ||
                        (!is_null($priceComponent->currencyCode) && !is_null($total->currencyCode) &&
                        $priceComponent->currencyCode == $total->currencyCode)
                    )
                    {
                        $alreadyExistingTotal = $total;
                        break;
                    }
                }

                if (is_null($alreadyExistingTotal))
                {
                    $totalComponent = new \Kickback\Backend\Views\vPriceComponent(
                        '', 0, $priceComponent->amount, $priceComponent->item, $priceComponent->currencyCode
                    );
                    $totals[] = $totalComponent;
                }
                else
                {
                    $alreadyExistingTotal->amount += $priceComponent->amount;
                }
            }
        }

        return $totals;
    }
}

?>
