<?php

declare(strict_types=1);

namespace Kickback\BackendV2\Controllers;

use Exception;
use Kickback\Backend\Models\Response;
use Kickback\Backend\Views\vAccount;
use Kickback\BackendV2\Services\CartService;
use Kickback\BackendV2\Services\DAOCartService;

class CartController
{
    private CartService $cartService;

    public function __construct(?CartService $cartService = null)
    {
        $this->cartService = $cartService ?? new DAOCartService();
    }

    /**
     * Returns the HTTP code for the request along with the reference out parameter response
     * 
     * @param ?vAccount $account the account authenticated to call the function
     * @param ?Response $response the out response of the function
     * @param ?string $jsonRequest the json encoded request for the function eg:
     *      {
     *          "storeLocator" : "locator"
     *      }
     * 
     * @return int the HTTP code for the request
     */
    public function getCartForAccountWithStoreLocator(?vAccount $account, ?string $jsonRequest, ?Response &$response) : int
    {
        $response = new Response(false, "Unkown error in getting cart for account", null);

        try
        {
            $assocRequest = json_decode($jsonRequest);

            if(!$assocRequest->key_exists("storeLocator"))
            {
                $response->message = "key \"storeLocator\" is missing from request body";
                return 400;
            }

            $storeLocator = $assocRequest["storeLocator"];

            $getCartResp = $this->cartService->getCartForAccountWithStoreLocator($account, $storeLocator);

            if(!$getCartResp->success)
            {
                $response->message = "Failed to get cart for account with store locator \"$storeLocator\"";
                return 500;
            }

            $response->success = true;
            $response->message = "Successfully retreived cart for account with store locator \"$storeLocator\"";
            $response->data = $getCartResp->data;
            return 200;
        }
        catch(Exception $e)
        {
            $response->message = "Exception caught while getting cart for account";
            $response->data = $e;
            return 500;
        }
    }
}

?>