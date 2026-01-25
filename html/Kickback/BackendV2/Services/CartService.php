<?php

declare(strict_types=1);

use Kickback\Backend\Models\Cart;
use Kickback\Backend\Models\Response;
use Kickback\BackendV2\Repositories\Cart\CartRepository;
use Kickback\BackendV2\Repositories\Cart\PDOCartRepository;

class CartService
{
    private CartRepository $repo;

    public function __construct(?CartRepository $cartRepository = null)
    {
        $repo = is_null($cartRepository) ? new PDOCartRepository() : $cartRepository;
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

        $cart = new Cart($accountId->ctime, $accountId->crand, $storeId->ctime, $storeId->crand);

        $sql = "INSERT INTO cart (
            ctime, crand, checked_out, void,
            ref_account_ctime, ref_account_crand,
            ref_store_ctime, ref_store_crand
        )
        SELECT ?, ?, 0, 0, ?, ?, ?, ?
        WHERE NOT EXISTS (
            SELECT 1
            FROM cart
            WHERE ref_account_crand = ? AND ref_store_ctime = ? AND ref_store_crand = ? AND checked_out = 0 AND void = 0
        );";

        $params = [$cart->ctime, $cart->crand, $accountId->ctime, $accountId->crand, $storeId->ctime, $storeId->crand, $accountId->crand, $storeId->ctime, $storeId->crand];

        
        try
        {
            $result = Database::executeSqlQuery($sql, $params);

            if(!$result)
            {
                $resp->message = "Failed to run duplicate-tolerant insert command for inserting cart";
                return $resp;
            }


            $selectSql = "SELECT
                ".static::$columnsInCartView."
                FROM v_cart
                WHERE account_crand = ? AND store_ctime = ? AND store_crand = ? AND checked_out = 0 AND void = 0;
            ";

            $params = [$accountId->crand, $storeId->ctime, $storeId->crand];

            $selectResult = Database::executeSqlQuery($selectSql, $params);

            if(!$selectResult)
            {
                $resp->message = "Failed to select cart for account";
                return $resp;
            } 

            if($selectResult->num_rows <= 0)
            {
                $resp->message = "cart not found after insertion";
                return $resp;
            }

            $row = $selectResult->fetch_assoc();

            $cartView = static::cartToView($row);

            $cartItemsResp = static::getItemsInCart($cartView);

            if(!$cartItemsResp->success)
            {
                $resp->message = "Failed to get cart items : $cartItemsResp->message";
                return $resp;
            }

            $cartView->cartProducts = $cartItemsResp->data;

            $cartView->totals = static::calculateCartTotalPriceCompnents($cartView->cartProducts);

            $resp->success = true;
            $resp->message = "Cart returned for account";
            $resp->data = $cartView; 
        }
        catch(Exception $e)
        {
            throw new Exception("exception caught while getting cart for account : $e");
        }

        return $resp;
    }
}

?>