<?php

declare(strict_types=1);

namespace Kickback\BackendV2\Services\Cart;

use Exception;
use Kickback\Backend\Models\Cart;
use Kickback\Backend\Models\Response;
use Kickback\Backend\Views\vRecordId;
use Kickback\Backend\Views\vCart;
use Kickback\Backend\Views\vPriceComponent;

use Kickback\BackendV2\DAO\Cart\CartDAO;
use Kickback\BackendV2\DAO\Cart\PDOCartDAO;
use Kickback\BackendV2\DAO\Store\PDOStoreDAO;
use Kickback\BackendV2\DAO\Store\StoreDAO;

class DAOCartService implements CartService
{
    private CartDAO $cartDAO;
    private StoreDAO $storeDAO;

    public function __construct(?CartDAO $cartDao = null, ?StoreDAO $storeDAO = null)
    {
        $this->cartDAO = is_null($cartDao) ? new PDOCartDAO() : $cartDao;
        $this->storeDAO = is_null($storeDAO) ? new PDOStoreDAO() : $storeDAO;
    }

    public function getCartForAccountWithStoreId(vRecordId $accountId, vRecordId $storeId) : Response
    {
        $resp = new Response(false, "unkown error in getting cart for account", null);
        
        try
        {

            $cart = $this->cartDAO->getOrCreateCartWithStoreId($accountId, $storeId);

            if($cart == null)
            {
                $resp->message = "Failed to get or create cart";
                return $resp;
            }


            $cartView = $this->cartDAO->getCartView($cart);

            $cartItems = $this->cartDAO->getCartProductsViews($cartView);

            $cartView->cartProducts = $cartItems;

            $cartView->totals = static::calculateCartTotalPriceCompnents($cartView->cartProducts);

            $resp->success = true;
            $resp->message = "Cart returned for account";
            $resp->data = $cartView; 
        }
        catch(Exception $e)
        {
            $resp->message = "Exception caught while trying to get cart for account : $e";
        }

        return $resp;
    }

    public function getCartForAccountWithStoreLocator(vRecordId $accountId, string $storeLocator) : Response
    {
        $resp = new Response(false, "unkown error in getting cart for account with store locator \"$storeLocator\"", null);
        
        try
        {
            $storeId = $this->storeDAO->getStoreByLocator($storeLocator);

            if(is_null($storeId))
            {
                $resp->message = "Failed to resolve store locator \"$storeLocator\" to store view";
                return $resp;
            }

            $cart = $this->cartDAO->getOrCreateCartWithStoreId($accountId, $storeId);

            if($cart == null)
            {
                $resp->message = "Failed to get or create cart for account with store locator \"$storeLocator\"";
                return $resp;
            }


            $cartView = $this->cartDAO->getCartView($cart);

            $cartItems = $this->cartDAO->getCartProductsViews($cartView);

            $cartView->cartProducts = $cartItems;

            $cartView->totals = static::calculateCartTotalPriceCompnents($cartView->cartProducts);

            $resp->success = true;
            $resp->message = "Cart returned for account";
            $resp->data = $cartView; 
        }
        catch(Exception $e)
        {
            $resp->message = "Exception caught while trying to get cart for account with store locator \"$storeLocator\" : $e";
        }

        return $resp;
    }

    public function addProductToCart(vRecordId $cartId, vRecordId $productId) : Response
    {
        $resp = new Response(false, "unkown error in adding product to cart", null);

        try
        {
            $added = $this->cartDAO->addProductToCart($cartId, $productId);

            if(!$added)
            {
                $resp->message = "Failed to add product \"$productId\" to cart \"$cartId\"";
                return $resp;
            }

            $resp->success = true;
            $resp->message = "Successfully added product \"$productId\" to cart \"$cartId\"";
        }
        catch(Exception $e)
        {
            $resp->message = "Exception caught while trying to add product \"$productId\" to cart \"$cartId\" : $e";
        }

        return $resp;
    }

    /**
     * Calculates the totals for the price in the cart
     * @param array $cartItems all of the items in the cart, an array of vCartItems
     * @return array $totalprice the returned array which contains the price of the totals in vPriceComponent objects
     */
    private static function calculateCartTotalPriceCompnents(array $cartItems) : array
    {
        $totals = [];

        foreach($cartItems as $cartItem)
        {
            $price = $cartItem->product->price;

            foreach($price as $priceComponent)
            {
                $alreadyExistingTotal = null;

                foreach($totals as $total)
                {

                    //Does priceComponent being checked match an already existing total
                    if(
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

                //Add amount of already existing total or create new total
                if(is_null($alreadyExistingTotal))
                {
                    //Clone price component so we don't affect the idividual price of items in the cart
                    $totalComponent = new vPriceComponent('', 0, $priceComponent->amount, $priceComponent->item, $priceComponent->currencyCode);

                    array_push($totals, $totalComponent);
                }
                else
                {
                    $alreadyExistingTotal->amount = $alreadyExistingTotal->amount + $priceComponent->amount;
                }
            }
        }

        return $totals;
    }
}

?>