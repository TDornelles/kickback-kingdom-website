<?php

declare(strict_types=1);

namespace Kickback\BackendV2\DAO;

use Exception;
use Kickback\Backend\Models\Cart;
use Kickback\Backend\Models\Response;
use Kickback\Backend\Views\vAccount;
use Kickback\Backend\Views\vCart;
use Kickback\Backend\Views\vRecordId;
use Kickback\Backend\Views\vStore;
use Kickback\Backend\Views\vTransaction;
use Kickback\BackendV2\Persistance\Database;
use PDOException;

class PDOCartDAO implements CartDAO
{
    private Database $pdo;

    public function __construct(?Database $pdo = null)
    {
        $this->pdo = $pdo ?? new Database();
    }

    public static string $columnsInCartView = "
        ctime,
        crand,
        account_username,
        store_name, 
        store_locator,
        checked_out,
        void,
        account_ctime,
        account_crand,
        store_owner_ctime,
        store_owner_crand,
        store_ctime,
        store_crand,
        transaction_ctime,
        transaction_crand
    ";

    public static string $columnsInCartItemView = "
        cart_product_link_ctime, 
        cart_product_link_crand,
        cart_ctime, 
        cart_crand,
        removed,
        checked_out,
        product_ctime,
        product_crand,
        product_name,
        product_description,
        product_locator,
        product_small_media_path,
        product_large_media_path,
        product_back_media_path,
        product_stock,
        price_component_ctime,
        price_component_crand,
        price_component_amount,
        price_component_currency_code,
        price_component_item_name,
        price_component_item_desc,
        price_component_media_path_small,
        price_component_media_path_large,
        price_component_media_path_back,
        price_component_item_ctime, 
        price_component_item_crand,
        price_component_item_is_fungible,
        coupon_ctime,
        coupon_crand,
        coupon_code,
        coupon_description,
        coupon_required_quantity_of_product,
        coupon_times_used,
        coupon_max_times_used,
        coupon_max_times_used_per_account,
        coupon_expiry_time,
        coupon_removed,
        coupon_assignment_group_ctime,
        coupon_assignment_group_crand
    ";

    public function getOrCreateCartWithStoreId(vRecordId $accountId, vRecordId $storeId) : ?Cart
    {
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
            $conn = $this->pdo->getConnection();

            $stmt = $conn->prepare($sql);
            if ($stmt === false) 
            {
                return null;
            }

            $result = $stmt->execute($params);

            if ($result === false)
            {
                return null;
            }

            return $cart;
        }
        catch (PDOException $e)
        {
            throw new Exception("PDO exception caught while getting cart for account : " . $e->getMessage(), 0, $e);
        }
        catch (Exception $e)
        {
            throw new Exception("Exception caught while getting cart for account : " . $e->getMessage(), 0, $e);
        }
    }

    public function getCartView(vRecordId $cartId) : ?vCart
    {
        $sql = "SELECT ".static::$columnsInCartView." FROM v_cart WHERE ctime = ? AND crand = ? LIMIT 1;";

        $params = [$cartId->ctime, $cartId->crand];
        
        try
        {
            $conn = $this->pdo->getConnection();

            $stmt = $conn->prepare($sql);
            if ($stmt === false) 
            {
                return null;
            }

            $result = $stmt->execute($params);

            if ($result === false)
            {
                return null;
            }

            $cart = static::cartToView($stmt->fetch());

            return $cart;
        }
        catch (PDOException $e)
        {
            throw new Exception("PDO exception caught while getting cart view : " . $e->getMessage(), 0, $e);
        }
        catch (Exception $e)
        {
            throw new Exception("Exception caught while getting cart view : " . $e->getMessage(), 0, $e);
        }
    }

    public function getCartItemViews(vRecordId $cartId) : array
    {
        $resp = new Response(false, "unkown error in getting item for cart", null);

        try
        {
            $sql = "SELECT 
            ".static::$columnsInCartItemView."
            FROM v_cart_item WHERE removed = 0 AND checked_out = 0 AND cart_ctime = ? AND cart_crand = ?;";

            $params = [$cart->ctime, $cart->crand];

            $result = Database::executeSqlQuery($sql, $params);

            if($result)
            {
                if($result->num_rows > 0)
                {
                    $resp->success = true;
                    $resp->message = "Items returned";
                    $resp->data = static::cartItemResultToViews($result);
                }
                else
                {
                    $resp->success = true;
                    $resp->message = "no items in cart";
                    $resp->data = [];
                }
            }
            else
            {
                $resp->message = "failed to select cart items";
            }
        }
        catch(Exception $e)
        {
            throw new Exception("Exception caught while gettig items in cart : $e");
        }

        return $resp;
    }

    private static function cartToView(array $row) : vCart
    {
        $cart = new vCart();

        $cart->account = new vAccount();
        $cart->store = new vStore();
        $cart->transaction = new vTransaction();

        $cart->account->username = $row["account_username"];
        $cart->account->ctime = "";
        $cart->account->crand = $row["account_crand"];

        $cart->store->name = $row["store_name"];
        $cart->store->locator = $row["store_locator"];
        $cart->store->ctime = $row["store_ctime"];
        $cart->store->crand = $row["store_crand"];
            $storeOwner = new vAccount($row["store_owner_ctime"], $row["store_owner_crand"]);
        $cart->store->owner = $storeOwner;

        $cart->checkedOut = boolval($row["checked_out"]);
        $cart->void = boolval($row["void"]);

        $cart->ctime = $row["ctime"];
        $cart->crand = $row["crand"];

        return $cart;
    }
}

?>