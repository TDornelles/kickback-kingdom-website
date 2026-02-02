<?php

declare(strict_types=1);

namespace Kickback\BackendV2\Services;

use Exception;
use Kickback\Backend\Models\Cart;
use Kickback\Backend\Models\Response;
use Kickback\Backend\Views\vRecordId;
use Kickback\Backend\Views\vCart;
use Kickback\Backend\Views\vPriceComponent;
use Kickback\BackendV2\DAO\Cart\CartDAO;
use Kickback\BackendV2\DAO\Cart\PDOCartRepository;

class CartService
{
    private CartDAO $dao;

    public function __construct(?CartDAO $cartDao = null)
    {
        $this->dao = is_null($cartDao) ? new PDOCartRepository() : $cartDao;
    }

    /**
    * Gets a cart for an account
    * Either selects an already existing cart or creates a new one
    * 
    * Additionally gets all items in the cart
    * @param vRecordId $accountId the account id to get the cart for
    * @param vRecordId $storeId the store to get the cart for
    * @return Response $resp the response containg the found vCart object in the data field
    */
    public function getCartForAccount(vRecordId $accountId, vRecordId $storeId) : Response
    {
        $resp = new Response(false, "unkown error in getting cart for account", null);
        
        try
        {
            $cart = $this->dao->getOrCreateCart($accountId, $storeId);

            if($cart == null)
            {
                $resp->message = "Failed to get or create cart";
                return $resp;
            }


            $cartView = $this->dao->getCartView($cart);

            $cartItems = $this->dao->getCartItemViews($cartView);

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