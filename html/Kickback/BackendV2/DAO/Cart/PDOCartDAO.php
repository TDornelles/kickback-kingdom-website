<?php

declare(strict_types=1);

namespace Kickback\BackendV2\DAO\Cart;

use Exception;
use Kickback\Backend\Models\Cart;
use Kickback\Backend\Models\Response;
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
use Kickback\BackendV2\DAO\Coupon\PDOCouponDAO;
use Kickback\BackendV2\DAO\Loot\PDOLootDAO;
use Kickback\BackendV2\DAO\Product\PDOProductDAO;
use Kickback\BackendV2\Persistance\Database;
use PDOException;
use DateTime;

class PDOCartDAO implements CartDAO
{
    private static int $productReservationTimeInSeconds = 10;

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

            $productDao = new PDOProductDAO($this->pdo);
            $amountAvailable = $productDao->getProductAmountAvailable($productId);
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

            $productDao = new PDOProductDAO($this->pdo);
            $priceComponents = $productDao->getBasePriceComponentIdsForProduct($productId);
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

    public function removeProductFromCart(vCartItem $cartProduct) : ?bool
    {
        try
        {
            $conn = $this->pdo->getConnection();
            if ($conn === null)
            {
                return null;
            }

            $conn->beginTransaction();

            if (!$this->markCartProductRemoved($conn, $cartProduct))
            {
                $conn->rollBack();
                return null;
            }

            $couponDao = new PDOCouponDAO($this->pdo);
            if (!$couponDao->removeCartProductCoupons($cartProduct))
            {
                $conn->rollBack();
                return null;
            }

            if (!$this->removeCartProductPriceComponents($conn, $cartProduct))
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

            throw new Exception("PDO exception caught while removing product from cart : " . $e->getMessage(), 0, $e);
        }
        catch (Exception $e)
        {
            if (isset($conn) && $conn->inTransaction())
            {
                $conn->rollBack();
            }

            throw new Exception("Exception caught while removing product from cart : " . $e->getMessage(), 0, $e);
        }
    }

    public function checkoutCart(vCart $cart) : Response
    {
        $resp = new Response(false, "unkown error in checking out cart", null);

        try
        {
            $validationResp = $this->validateCartForCheckout($cart);
            if (!is_null($validationResp))
            {
                return $validationResp;
            }

            $affordResp = $this->canAccountAffordItemPriceInCart($cart);
            if (!$affordResp->success)
            {
                $resp->message = "Error in getting if account can afford cart price : $affordResp->message";
                return $resp;
            }

            if ($affordResp->data === false)
            {
                $resp->message = "Account cannot afford item price to checkout cart";
                $resp->data = false;
                return $resp;
            }

            $stockResp = $this->reserveProductStockInCart($cart);
            if (!$stockResp->success)
            {
                $resp->message = "failed to reserve product stock : $stockResp->message";
                $resp->data = $stockResp->data;
                return $resp;
            }

            $lootEntryQuantities = [];
            $lootReserved = $this->reserveLootForPriceInCart($cart, $lootEntryQuantities);
            if ($lootReserved !== true)
            {
                $productDao = new PDOProductDAO($this->pdo);
                $productDao->closeProductReservations($stockResp->data);
                $resp->message = "failed to reserve loot for cart";
                $resp->data = $lootReserved;
                return $resp;
            }

            $lootDao = new PDOLootDAO($this->pdo);
            $lootIds = $this->extractLootIdsFromEntries($lootEntryQuantities);
            $lootReservations = $lootDao->getActiveLootReservationsForLoots($lootIds);
            $lootDao->closeLootReservations($lootReservations);

            $productDao = new PDOProductDAO($this->pdo);
            $productDao->closeProductReservations($stockResp->data);
            $this->markCartCheckedOut($cart);
            $this->markCartProductsCheckedOut($cart);
            $this->markCartProductPricesCheckedOut($cart);

            $resp->success = true;
            $resp->message = "Checked Out Cart";
            $resp->data = true;
            return $resp;
        }
        catch (Exception $e)
        {
            throw new Exception("Exception caught while checking out cart : " . $e->getMessage(), 0, $e);
        }
    }

    private function validateCartForCheckout(vCart $cart) : ?Response
    {
        if ($cart->account->equals($cart->store->owner))
        {
            return new Response(false, "Cannot checkout cart for a store you own", null);
        }

        if (empty($cart->cartProducts))
        {
            return new Response(false, "Cart does not have items to checkout", null);
        }

        return null;
    }

    private function canAccountAffordItemPriceInCart(vCart $cart) : Response
    {
        $resp = new Response(false, "Unknown error in checking if account can afford item price in cart", null);

        $totals = $this->getItemTotals($cart->totals);
        if (empty($totals))
        {
            $resp->success = true;
            $resp->message = "No item totals to check";
            $resp->data = true;
            return $resp;
        }

        $lootForCart = $this->getLootAmountsForTotals($cart, $totals);

        foreach($totals as $total)
        {
            $itemId = $total->item->crand;
            $amountNeeded = $total->amount;
            $amountAvailable = $lootForCart[$itemId] ?? 0;

            if(($amountAvailable - $amountNeeded) < 0)
            {
                $resp->success = true;
                $resp->message = "Account cannot afford item price";
                $resp->data = false;
                return $resp;
            }
        }

        $resp->success = true;
        $resp->message = "Account can afford item price";
        $resp->data = true;
        return $resp;
    }

    private function getLootAmountsForTotals(vCart $cart, array $totals) : array
    {
        $itemIds = $this->getItemIdsFromTotals($totals);
        if (empty($itemIds))
        {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($itemIds), '?'));
        $sql = "SELECT l.item_id, SUM(l.Quantity) AS amount
            FROM loot l
            LEFT JOIN raffle_submissions rs ON l.Id = rs.loot_id
            WHERE rs.loot_id IS NULL AND l.account_id = ? AND l.item_id IN ($placeholders)
            GROUP BY l.item_id;";

        $params = array_merge([$cart->account->crand], $itemIds);

        $conn = $this->pdo->getConnection();
        $stmt = $conn->prepare($sql);
        if ($stmt === false)
        {
            throw new Exception("Failed to prepare loot amount query");
        }

        $result = $stmt->execute($params);
        if ($result === false)
        {
            throw new Exception("Failed to execute loot amount query");
        }

        $lootAmounts = [];
        while ($row = $stmt->fetch())
        {
            $lootAmounts[(int)$row["item_id"]] = (int)$row["amount"];
        }

        return $lootAmounts;
    }

    private function getItemTotals(array $totals) : array
    {
        $itemTotals = [];

        foreach($totals as $total)
        {
            if(is_null($total->item) || $total->amount === 0) continue;
            $itemTotals[] = $total;
        }

        return $itemTotals;
    }

    private function getItemIdsFromTotals(array $totals) : array
    {
        $itemIds = [];

        foreach($totals as $total)
        {
            if(is_null($total->item)) continue;
            $itemIds[] = $total->item->crand;
        }

        return $itemIds;
    }

    private function reserveProductStockInCart(vCart $cart) : Response
    {
        $resp = new Response(false, "unknown error in reserving stock", null);

        $availabilityResp = $this->areCartProductsAvailable($cart);
        if(!$availabilityResp->success)
        {
            $resp->message = "Error in checking if cart items are available : $availabilityResp->message";
            return $resp;
        }

        if(!$availabilityResp->data)
        {
            $resp->message = "Some items in cart are out-of-stock";
            $resp->data = $availabilityResp->data;
            return $resp;
        }

        $productEntryQuantities = $this->buildProductReservationEntries($cart);
        if (empty($productEntryQuantities))
        {
            $resp->message = "No reservations created";
            return $resp;
        }

        $productDao = new PDOProductDAO($this->pdo);
        $reserveResult = $productDao->reserveStockForProducts($productEntryQuantities, $cart);
        if ($reserveResult !== true)
        {
            $resp->message = "Failed to reserve product stock";
            $resp->data = $reserveResult;
            return $resp;
        }

        $resp->success = true;
        $resp->message = "Reserved the cart products";
        $resp->data = $productDao->getActiveProductReservationsForCart($cart);
        return $resp;
    }

    private function areCartProductsAvailable(vCart $cart) : Response
    {
        $resp = new Response(false, "unknown error in checking if cart items are still in stock", null);

        $cartProducts = $cart->cartProducts;
        if (empty($cartProducts))
        {
            $resp->success = true;
            $resp->message = "Cart is empty";
            $resp->data = false;
            return $resp;
        }

        $counts = $this->countProductsInCart($cartProducts);
        $productDao = new PDOProductDAO($this->pdo);
        $productIds = $this->getProductIdsFromCartProducts($cartProducts);
        $availability = $productDao->areProductsAvailableInStore($cart->store, $productIds);
        if ($availability === null)
        {
            $resp->message = "Failed to check product availability";
            return $resp;
        }

        $available = $this->areProductCountsWithinAvailability($counts, $availability);

        $resp->success = true;
        $resp->message = $available ? "All cart products available" : "One or more products are out of availability";
        $resp->data = $available;
        return $resp;
    }

    private function countProductsInCart(array $cartProducts) : array
    {
        $counts = [];
        foreach($cartProducts as $cartProduct)
        {
            $key = $cartProduct->product->ctime . '|' . $cartProduct->product->crand;
            $counts[$key] = ($counts[$key] ?? 0) + 1;
        }

        return $counts;
    }

    private function getProductIdsFromCartProducts(array $cartProducts) : array
    {
        $productIds = [];
        foreach ($cartProducts as $cartProduct)
        {
            $product = $cartProduct->product ?? null;
            if (!($product instanceof vRecordId))
            {
                continue;
            }

            $productIds[] = $product;
        }

        return $productIds;
    }

    private function areProductCountsWithinAvailability(array $productCounts, array $availability) : bool
    {
        $availabilityMap = [];
        foreach ($availability as $entry)
        {
            $productId = $entry["productId"] ?? null;
            if (!($productId instanceof vRecordId))
            {
                continue;
            }

            $key = $productId->ctime . '|' . $productId->crand;
            $availabilityMap[$key] = (int)($entry["quantityAvailable"] ?? 0);
        }

        foreach ($productCounts as $key => $qty)
        {
            $availableQty = $availabilityMap[$key] ?? 0;
            if (($availableQty - $qty) < 0)
            {
                return false;
            }
        }

        return true;
    }

    private function buildProductReservationEntries(vCart $cart) : array
    {
        $entriesByKey = [];

        foreach($cart->cartProducts as $cartProduct)
        {
            $product = $cartProduct->product ?? null;
            if (!($product instanceof vRecordId))
            {
                continue;
            }

            $key = $product->ctime . '|' . $product->crand;
            if (!isset($entriesByKey[$key]))
            {
                $entriesByKey[$key] = [
                    "productId" => $product,
                    "quantity" => 0
                ];
            }

            $entriesByKey[$key]["quantity"]++;
        }

        return array_values($entriesByKey);
    }

    private function reserveLootForPriceInCart(vCart $cart, array &$lootEntryQuantities) : ?bool
    {
        $lootDao = new PDOLootDAO($this->pdo);
        $lootEntryQuantities = $lootDao->getLootFromAccountForItems($cart->account, $cart->totals);
        return $lootDao->reserveLoots($lootEntryQuantities, static::$productReservationTimeInSeconds);
    }

    private function extractLootIdsFromEntries(array $lootEntryQuantities) : array
    {
        $lootIds = [];

        foreach ($lootEntryQuantities as $entry)
        {
            if (!is_array($entry))
            {
                continue;
            }

            $lootId = $entry["lootId"] ?? null;
            if ($lootId instanceof vRecordId)
            {
                $lootIds[] = $lootId;
            }
        }

        return $lootIds;
    }

    private function markCartCheckedOut(vCart $cart) : void
    {
        $sql = "UPDATE cart SET checked_out = 1 WHERE ctime = ? AND crand = ?";
        $params = [$cart->ctime, $cart->crand];

        $conn = $this->pdo->getConnection();
        $stmt = $conn->prepare($sql);
        if ($stmt === false)
        {
            throw new Exception("Failed to prepare mark cart checked out");
        }

        $result = $stmt->execute($params);
        if ($result === false)
        {
            throw new Exception("Failed to mark cart checked out");
        }
    }

    private function markCartProductsCheckedOut(vCart $cart) : void
    {
        $sql = "UPDATE cart_product_link SET checked_out = 1 WHERE ref_cart_ctime = ? AND ref_cart_crand = ?";
        $params = [$cart->ctime, $cart->crand];

        $conn = $this->pdo->getConnection();
        $stmt = $conn->prepare($sql);
        if ($stmt === false)
        {
            throw new Exception("Failed to prepare mark cart products checked out");
        }

        $result = $stmt->execute($params);
        if ($result === false)
        {
            throw new Exception("Failed to mark cart products checked out");
        }
    }

    private function markCartProductPricesCheckedOut(vCart $cart) : void
    {
        if (empty($cart->cartProducts))
        {
            return;
        }

        $whereClause = $this->buildCartProductIdWhereClause($cart->cartProducts);
        $params = $this->buildCartProductIdParams($cart->cartProducts);

        $sql = "UPDATE cart_product_price_component_link SET checked_out = 1 WHERE $whereClause";

        $conn = $this->pdo->getConnection();
        $stmt = $conn->prepare($sql);
        if ($stmt === false)
        {
            throw new Exception("Failed to prepare mark cart product prices checked out");
        }

        $result = $stmt->execute($params);
        if ($result === false)
        {
            throw new Exception("Failed to mark cart product prices checked out");
        }
    }

    private function buildCartProductIdWhereClause(array $cartProducts) : string
    {
        $clauses = [];
        foreach($cartProducts as $cartProduct)
        {
            $clauses[] = "(ref_cart_product_link_ctime = ? AND ref_cart_product_link_crand = ?)";
        }

        return implode(" OR ", $clauses);
    }

    private function buildCartProductIdParams(array $cartProducts) : array
    {
        $params = [];
        foreach($cartProducts as $cartProduct)
        {
            $params[] = $cartProduct->ctime;
            $params[] = $cartProduct->crand;
        }

        return $params;
    }

    private function markCartProductRemoved(\PDO $conn, vCartItem $cartProduct) : bool
    {
        $sql = "UPDATE cart_product_link SET removed = 1 WHERE ctime = ? AND crand = ?;";
        $stmt = $conn->prepare($sql);
        if ($stmt === false)
        {
            return false;
        }

        $result = $stmt->execute([$cartProduct->ctime, $cartProduct->crand]);
        return $result !== false;
    }

    private function removeCartProductPriceComponents(\PDO $conn, vCartItem $cartProduct) : bool
    {
        $sql = "UPDATE cart_product_price_component_link SET removed = 1 WHERE ref_cart_product_link_ctime = ? AND ref_cart_product_link_crand = ?;";
        $stmt = $conn->prepare($sql);
        if ($stmt === false)
        {
            return false;
        }

        $result = $stmt->execute([$cartProduct->ctime, $cartProduct->crand]);
        return $result !== false;
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

    private function insertCartProductPriceComponents(\PDO $conn, CartProductLink $cartProductLink, array $priceComponentIds) : bool
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

        foreach ($priceComponentIds as $priceComponentId)
        {
            $cartProductPriceComponentLink = new CartProductPriceComponentLink();
            $cartProductPriceComponentLink->cartProductLinkId = new vRecordId(
                $cartProductLink->ctime,
                $cartProductLink->crand
            );
            $cartProductPriceComponentLink->priceComponentId = $priceComponentId;

            $result = $stmt->execute([
                $cartProductPriceComponentLink->ctime,
                $cartProductPriceComponentLink->crand,
                $cartProductLink->ctime,
                $cartProductLink->crand,
                $priceComponentId->ctime,
                $priceComponentId->crand
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
