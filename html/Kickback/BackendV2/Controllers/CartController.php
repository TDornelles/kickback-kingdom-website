<?php

declare(strict_types=1);

namespace Kickback\BackendV2\Controllers;

use Exception;
use Kickback\Backend\Models\Response;
use Kickback\Backend\Views\vAccount;
use Kickback\Backend\Views\vRecordId;
use Kickback\BackendV2\Services\Cart\CartService;
use Kickback\BackendV2\Services\Cart\DAOCartService;
use Kickback\BackendV2\Services\Product\DAOProductService;
use Kickback\BackendV2\Services\Product\ProductService;

class CartController
{
    private CartService $cartService;
    private ProductService $productService;

    public function __construct(?CartService $cartService = null, ?ProductService $productService = null)
    {
        $this->cartService = $cartService ?? new DAOCartService();
        $this->productService = $productService ?? new DAOProductService();
    }

    /**
     * Add a product to the cart by the product locator
     * @param ?vAccount $account the account authenticated to call the function
     * @param ?string $jsonRequest the json encoded request for the function eg:
     *      {
     *        "productLocator" : "locator"
     *      }
     * @param ?Response $response the out response of the function
     * 
     * @return int the HTTP code for the request
     * 
     */
    public function addProductToCartByProductLocator(?vAccount $account, ?string $jsonRequest, ?Response &$response) : int
    {
        $response = new Response(false, "Unkown error in adding product to cart", null);

        try
        {
            $assocRequest = json_decode((string)$jsonRequest, true);

            if(!is_array($assocRequest))
            {
                $response->message = "failed to decode request body as JSON";
                return 400; 
            }

            if(!array_key_exists("productLocator", $assocRequest))
            {
                $response->message = "key \"productLocator\" is missing from request body";
                return 400;
            }

            $productLocator = $assocRequest["productLocator"];

            if(is_null($productLocator) || !is_string($productLocator))
            {
                $response->message = "productLocator must be a non-null string";
                return 400;
            }

            $getProductResp = $this->productService->getProductByLocator($productLocator);

            if(!$getProductResp->success)
            {
                $response->message = "Failed to get product by locator \"$productLocator\"";
                return 500;
            }

            $product = $getProductResp->data;

            $addProductResp = $this->cartService->addProductToCart($account, $product);

            if(!$addProductResp->success)
            {
                $response->message = "Failed to add product with locator \"$productLocator\" to cart";
                return 500;
            }

            $response->success = true;
            $response->message = "Successfully added product with locator \"$productLocator\" to cart";
            $response->data = $addProductResp->data;
            return 200;
        }
        catch(Exception $e)
        {
            $response->message = "Exception caught while adding product to cart by locator";
            $response->data = $e;
            return 500;
        }
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
            $assocRequest = json_decode((string)$jsonRequest, true);

            if(!is_array($assocRequest))
            {
                $response->message = "failed to decode request body as JSON";
                return 400; 
            }

            if(!array_key_exists("storeLocator", $assocRequest))
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
