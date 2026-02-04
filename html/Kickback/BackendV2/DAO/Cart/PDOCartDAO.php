<?php

declare(strict_types=1);

namespace Kickback\BackendV2\DAO\Cart;

use Exception;
use Kickback\Backend\Models\Cart;
use Kickback\Backend\Models\CartProductLink;
use Kickback\Backend\Models\CartProductPriceComponentLink;
use Kickback\Backend\Views\vAccount;
use Kickback\Backend\Views\vCart;
use Kickback\Backend\Views\vCartItem;
use Kickback\Backend\Views\vItem;
use Kickback\Backend\Views\vMedia;
use Kickback\Backend\Views\vPriceComponent;
use Kickback\Backend\Views\vProduct;
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

    public function addProductToCart(vRecordId $cartId, vRecordId $productId): ?bool
    {
        try
        {
            $conn = $this->pdo->getConnection();
            if ($conn === null)
            {
                return null;
            }

            $conn->beginTransaction();

            $amountAvailable = $this->getProductAmountAvailable($conn, $productId);
            if ($amountAvailable === null)
            {
                $conn->rollBack();
                return null;
            }

            $quantityInCart = $this->getProductQuantityInCart($conn, $cartId, $productId);
            if ($quantityInCart === null)
            {
                $conn->rollBack();
                return null;
            }

            $effectiveStock = $amountAvailable - $quantityInCart;
            if ($effectiveStock <= 0)
            {
                $conn->rollBack();
                return false;
            }

            $cartProductLink = $this->insertCartProductLink($conn, $cartId, $productId);
            if ($cartProductLink === null)
            {
                $conn->rollBack();
                return null;
            }

            $priceComponents = $this->getPriceComponentIdsForProduct($conn, $productId);
            if (empty($priceComponents))
            {
                $conn->rollBack();
                return null;
            }

            $linked = $this->insertCartProductPriceComponents($conn, $cartProductLink, $priceComponents);
            if (!$linked)
            {
                $conn->rollBack();
                return null;
            }

            $conn->commit();
            return true;
        }
        catch (PDOException $e)
        {
            if (isset($conn) && $conn->inTransaction())
            {
                $conn->rollBack();
            }

            throw new Exception("PDO exception caught while adding product to cart : " . $e->getMessage(), 0, $e);
        }
        catch (Exception $e)
        {
            if (isset($conn) && $conn->inTransaction())
            {
                $conn->rollBack();
            }

            throw new Exception("Exception caught while adding product to cart : " . $e->getMessage(), 0, $e);
        }
    }

    private function getProductAmountAvailable(\PDO $conn, vRecordId $productId) : ?int
    {
        $sql = "SELECT amount_available FROM v_product WHERE ctime = ? AND crand = ? LIMIT 1;";
        $stmt = $conn->prepare($sql);
        if ($stmt === false)
        {
            return null;
        }

        $result = $stmt->execute([$productId->ctime, $productId->crand]);
        if ($result === false)
        {
            return null;
        }

        $row = $stmt->fetch();
        if ($row === false)
        {
            return null;
        }

        return (int)$row["amount_available"];
    }

    private function getProductQuantityInCart(\PDO $conn, vRecordId $cartId, vRecordId $productId) : ?int
    {
        $sql = "SELECT COUNT(*) AS qty
            FROM v_cart_item
            WHERE removed = 0
                AND checked_out = 0
                AND cart_ctime = ?
                AND cart_crand = ?
                AND product_ctime = ?
                AND product_crand = ?;";
        $stmt = $conn->prepare($sql);
        if ($stmt === false)
        {
            return null;
        }

        $result = $stmt->execute([
            $cartId->ctime,
            $cartId->crand,
            $productId->ctime,
            $productId->crand
        ]);
        if ($result === false)
        {
            return null;
        }

        $row = $stmt->fetch();
        if ($row === false)
        {
            return 0;
        }

        return (int)$row["qty"];
    }

    private function insertCartProductLink(\PDO $conn, vRecordId $cartId, vRecordId $productId) : ?CartProductLink
    {
        $cartProductLink = new CartProductLink();
        $cartProductLink->productId = $productId;
        $cartProductLink->cartId = $cartId;

        $sql = "INSERT INTO cart_product_link (
            ctime,
            crand,
            removed,
            checked_out,
            ref_cart_ctime,
            ref_cart_crand,
            ref_product_ctime,
            ref_product_crand
        ) VALUES (?,?,?,?,?,?,?,?);";

        $stmt = $conn->prepare($sql);
        if ($stmt === false)
        {
            return null;
        }

        $result = $stmt->execute([
            $cartProductLink->ctime,
            $cartProductLink->crand,
            0,
            0,
            $cartProductLink->cartId->ctime,
            $cartProductLink->cartId->crand,
            $cartProductLink->productId->ctime,
            $cartProductLink->productId->crand
        ]);
        if ($result === false)
        {
            return null;
        }

        return $cartProductLink;
    }

    private function getPriceComponentIdsForProduct(\PDO $conn, vRecordId $productId) : ?array
    {
        $sql = "SELECT 
            vp.ctime,
            vp.crand
            FROM v_price_component vp
            JOIN product_price_component_link ppl
                ON ppl.ref_price_component_ctime = vp.ctime
                AND ppl.ref_price_component_crand = vp.crand
            WHERE ppl.ref_product_ctime = ?
                AND ppl.ref_product_crand = ?;";

        $stmt = $conn->prepare($sql);
        if ($stmt === false)
        {
            return null;
        }

        $result = $stmt->execute([$productId->ctime, $productId->crand]);
        if ($result === false)
        {
            return null;
        }

        return $stmt->fetchAll();
    }

    private function insertCartProductPriceComponents(\PDO $conn, CartProductLink $cartProductLink, array $priceComponents) : bool
    {
        $sql = "INSERT INTO cart_product_price_component_link (
            ctime,
            crand,
            ref_cart_product_link_ctime,
            ref_cart_product_link_crand,
            ref_price_component_ctime,
            ref_price_component_crand,
            removed,
            checked_out
        ) VALUES (?,?,?,?,?,?,0,0);";

        $stmt = $conn->prepare($sql);
        if ($stmt === false)
        {
            return false;
        }

        foreach ($priceComponents as $priceComponent)
        {
            $cartProductPriceComponentLink = new CartProductPriceComponentLink();
            $cartProductPriceComponentLink->cartProductLinkId = new vRecordId(
                $cartProductLink->ctime,
                $cartProductLink->crand
            );
            $cartProductPriceComponentLink->priceComponentId = new vRecordId(
                $priceComponent["ctime"],
                $priceComponent["crand"]
            );

            $result = $stmt->execute([
                $cartProductPriceComponentLink->ctime,
                $cartProductPriceComponentLink->crand,
                $cartProductLink->ctime,
                $cartProductLink->crand,
                $priceComponent["ctime"],
                $priceComponent["crand"]
            ]);
            if ($result === false)
            {
                return false;
            }
        }

        return true;
    }

    public function getCartProductsViews(vRecordId $cartId) : array
    {
        try
        {
            $sql = "SELECT 
            ".static::$columnsInCartItemView."
            FROM v_cart_item WHERE removed = 0 AND checked_out = 0 AND cart_ctime = ? AND cart_crand = ?;";

            $params = [$cartId->ctime, $cartId->crand];

            $conn = $this->pdo->getConnection();
            $stmt = $conn->prepare($sql);
            if ($stmt === false)
            {
                return [];
            }

            $result = $stmt->execute($params);

            if ($result === false)
            {
                return [];
            }

            return static::cartItemResultToViews($stmt);
        }
        catch (PDOException $e)
        {
            throw new Exception("PDO exception caught while getting items in cart : " . $e->getMessage(), 0, $e);
        }
        catch(Exception $e)
        {
            throw new Exception("Exception caught while getting items in cart : " . $e->getMessage(), 0, $e);
        }

        return [];
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

    private static function cartItemResultToViews(\PDOStatement $stmt) : array
    {
        $cartItems = [];

        while($row = $stmt->fetch())
        {
            $cartItemAlreadyProccessed = null;
            
            foreach($cartItems as $cartItem)
            {
                if($row["cart_product_link_ctime"] == $cartItem->ctime && $row["cart_product_link_crand"] == $cartItem->crand)
                {
                    $cartItemAlreadyProccessed = $cartItem;
                    break;
                }
            }

            if(is_null($cartItemAlreadyProccessed))
            {
                array_push($cartItems, static::cartItemToView($row));
            }
            else
            {
                $priceComponent = static::cartItemToPriceComponentView($row);

                array_push($cartItemAlreadyProccessed->price, $priceComponent);
            }
        }

        return $cartItems;
    }

    private static function cartItemToPriceComponentView(array $row) : vPriceComponent
    {
        $priceComponent = new vPriceComponent();

        if(!is_null($row["price_component_item_ctime"]) && !is_null($row["price_component_item_crand"]))
        {
            $item = new vItem();
            $item->ctime = $row["price_component_item_ctime"];
            $item->crand = $row["price_component_item_crand"];
            $item->name = $row["price_component_item_name"];
            $item->description = $row["price_component_item_desc"]; 
                $smallMedia = new vMedia();
                $smallMedia->setMediaPath($row["price_component_media_path_small"]);
                $largeMedia = new vMedia();
                $largeMedia->setMediaPath($row["price_component_media_path_large"]);
                $backMedia = new vMedia();
                if(!empty($row["price_component_media_path_back"]))$backMedia->setMediaPath($row["price_component_media_path_back"]);
            $item->iconSmall = $smallMedia;
            $item->iconBig = $largeMedia;
            $item->iconBack = $backMedia;

            $item->applyMediaFallbacks();

            $item->fungible = boolval($row["price_component_item_is_fungible"]);

            $priceComponent->item = $item;
        }
        
        if(!empty($row["price_component_currency_code"]))
        {
            $priceComponent = CurrencyCode::from($row["price_component_currency_code"]);
        }

        $priceComponent->ctime = $row["price_component_ctime"];
        $priceComponent->crand = $row["price_component_crand"];
        $priceComponent->amount = $row["price_component_amount"];

        return $priceComponent;
        
    }

    private static function cartItemToView(array $row) : vCartItem
    {
        $cartItem = new vCartItem();

        $priceComponent = static::cartItemToPriceComponentView($row);

        $product = new vProduct($row["product_ctime"], $row["product_crand"]);
            $product->price = [$priceComponent];
            $product->stock = $row["product_stock"];
            $product->locator = $row["product_locator"];
            $product->name = $row["product_name"];
            $product->description = $row["product_description"];

            $product->mediaSmall = new vMedia();
            $product->mediaSmall->setMediaPath($row["product_small_media_path"]);
            $product->mediaLarge = new vMedia();
            $product->mediaLarge->setMediaPath($row["product_large_media_path"]);
            $product->mediaBack = new vMedia();
            $product->mediaBack->setMediaPath($row["product_back_media_path"]);
        $cartItem->product = $product;

        $cart = new vCart();
            $cart->ctime = $row["cart_ctime"];
            $cart->crand = $row["cart_crand"];
        $cartItem->cart = $cart;

        if(!is_null($row["coupon_ctime"]) && !is_null($row["coupon_crand"]))
        {
            $coupon = new vCoupon($row["coupon_ctime"], $row["coupon_crand"]);
                $coupon->code = $row["coupon_code"];
                $coupon->description = $row["coupon_description"];
                $coupon->requiredQuantityOfProduct = $row["coupon_required_quantity_of_product"];
                $coupon->productId = new vRecordId($row["product_ctime"], $row["product_crand"]);
                $coupon->timesUsed = $row["coupon_times_used"];
                $coupon->maxTimesUsed = $row["coupon_max_times_used"];
                $coupon->maxTimesUsedPerAccount = $row["coupon_max_times_used_per_account"];
                $coupon->expiryTime = is_null($row["coupon_expiry_time"]) ? null : DateTime::createFromFormat('Y-m-d H:i:s.u', $row["expiry_time"]);
                $coupon->removed = boolval($row["coupon_removed"]);

            $cartItem->coupon = $coupon;

            $cartItem->couponGroupAssignmentId = new vRecordId($row["coupon_assignment_group_ctime"], $row["coupon_assignment_group_crand"]);
        }
        
        $cartItem->ctime = $row["cart_product_link_ctime"];
        $cartItem->crand = $row["cart_product_link_crand"];
        $cartItem->removed = boolval($row["removed"]);
        $cartItem->checkedOut = boolval($row["checked_out"]);
        $cartItem->price = [$priceComponent];

        return $cartItem;
    }
}

?>
