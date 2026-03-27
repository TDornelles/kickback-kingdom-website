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
use Kickback\BackendV2\DAO\Coupon\PDOCouponDAO;
use Kickback\BackendV2\DAO\Loot\PDOLootDAO;
use Kickback\BackendV2\DAO\Product\PDOProductDAO;
use Kickback\BackendV2\Persistance\Database;
use PDOException;
use DateTime;
use Kickback\Backend\Models\Enums\CurrencyCode;

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
        transaction_crand,
        stripe_session_id
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

    public function getOrCreateCartIdWithStoreId(vRecordId $accountId, vRecordId $storeId) : ?vRecordId
    {
        $cart = new Cart($accountId->ctime, $accountId->crand, $storeId->ctime, $storeId->crand);

        $insertSql = "
            INSERT INTO cart (
                ctime, crand, checked_out, void,
                ref_account_ctime, ref_account_crand,
                ref_store_ctime, ref_store_crand
            )
            SELECT ?, ?, 0, 0, ?, ?, ?, ?
            WHERE NOT EXISTS (
                SELECT 1
                FROM cart
                WHERE ref_account_crand = ?
                AND ref_store_ctime = ?
                AND ref_store_crand = ?
                AND checked_out = 0
                AND void = 0
            );
        ";

        $insertParams = [
            $cart->ctime, $cart->crand,
            $accountId->ctime, $accountId->crand,
            $storeId->ctime, $storeId->crand,
            $accountId->crand, $storeId->ctime, $storeId->crand
        ];

        $selectSql = "
            SELECT ctime, crand
            FROM cart
            WHERE ref_account_crand = ?
            AND ref_store_ctime = ?
            AND ref_store_crand = ?
            AND checked_out = 0
            AND void = 0
            ORDER BY ctime DESC, crand DESC
            LIMIT 1;
        ";

        $selectParams = [$accountId->crand, $storeId->ctime, $storeId->crand];

        try
        {
            $conn = $this->pdo->getConnection();
            $conn->beginTransaction();

            $stmt = $conn->prepare($insertSql);

            if ($stmt === false) 
            { 
                $conn->rollBack(); return null; 
            }
            if ($stmt->execute($insertParams) === false) 
            { 
                $conn->rollBack(); return null; 
            }

            $stmt = $conn->prepare($selectSql);
            if ($stmt === false) 
            { 
                $conn->rollBack(); 
                return null; 
            }
            if ($stmt->execute($selectParams) === false) 
            {   
                $conn->rollBack(); 
                return null;    
            }

            $row = $stmt->fetch();
            if (!$row) 
            { 
                $conn->rollBack(); return null; 
            }

            $conn->commit();

            $cartId = new vRecordId($row['ctime'], (int)$row['crand']);

            return $cartId;
        }
        catch (PDOException $e)
        {
            if (isset($conn) && $conn->inTransaction()) { $conn->rollBack(); }
            throw new Exception("PDO exception caught while getting cart for account : " . $e->getMessage(), 0, $e);
        }
        catch (Exception $e)
        {
            if (isset($conn) && $conn->inTransaction()) { $conn->rollBack(); }
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

            $row = $stmt->fetch();

            if($row === false)
            {
                return null;
            }

            $cart = static::cartToView($row);

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

            $quantityInCart = $this->getProductQuantityInCart($cartId, $productId);
            if ($quantityInCart === null)
            {
                $conn->rollBack();
                return null;
            }

            if ($quantityInCart >= $amountAvailable) //need at least one more availabe to continue with adding product to cart
            {
                $conn->rollBack();
                return false; //Not enough stock was available
            }

            $cartProductLink = $this->insertCartProductLink($cartId, $productId);
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

            $linked = $this->insertCartProductPriceComponents($cartProductLink, $priceComponents);
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

            if (!$this->markCartProductRemoved($cartProduct))
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

            if (!$this->removeCartProductPriceComponents($cartProduct))
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

    public function checkoutCart(vCart $cart) : ?bool
    {
        try
        {
            $conn = $this->pdo->getConnection();
            if ($conn === null)
            {
                return null;
            }

            $conn->beginTransaction();

            $isCartValid = $this->validateCartForCheckout($cart);
            if ($isCartValid !== true)
            {
                $conn->rollBack();
                return null;
            }

            $canAfford = $this->canAccountAffordItemPriceInCart($cart);
            if ($canAfford !== true)
            {
                $conn->rollBack();
                return $canAfford;
            }

            $productReservations = [];
            $stockReserved = $this->reserveProductStockInCart($cart, $productReservations);
            if ($stockReserved !== true)
            {
                $conn->rollBack();
                return null;
            }

            $lootEntryQuantities = [];
            $lootReserved = $this->reserveLootForPriceInCart($cart, $lootEntryQuantities);
            if ($lootReserved !== true)
            {
                $conn->rollBack();
                return null;
            }

            $lootDao = new PDOLootDAO($this->pdo);
            $lootIds = $this->extractLootIdsFromEntries($lootEntryQuantities);
            $lootReservations = $lootDao->getActiveLootReservationsForLoots($lootIds);

            $transactedLootsAndTrades = $this->executeCheckoutLootTransfersAndTradeRows($cart, $productReservations, $lootEntryQuantities);
            if ($transactedLootsAndTrades !== true)
            {
                $conn->rollBack();
                return null;
            }

            $sanityCheckPassed = $this->validateCheckoutLootAndTrades($cart, $productReservations, $lootEntryQuantities);
            if ($sanityCheckPassed !== true)
            {
                $conn->rollBack();
                return null;
            }

            $productDao = new PDOProductDAO($this->pdo);
            $productDao->closeProductReservations($productReservations);
            $lootDao->closeLootReservations($lootReservations);
            $this->markCartCheckedOut($cart);
            $this->markCartProductsCheckedOut($cart);
            $this->markCartProductPricesCheckedOut($cart);

            $conn->commit();
            return true;
        }
        catch (Exception $e)
        {
            if (isset($conn) && $conn->inTransaction())
            {
                $conn->rollBack();
            }

            throw new Exception("Exception caught while checking out cart : " . $e->getMessage(), 0, $e);
        }
    }

    private function executeCheckoutLootTransfersAndTradeRows(vCart $cart, array $productReservations, array $lootEntryQuantities) : ?bool
    {
        try
        {
            $expectedProductQuantities = $this->buildExpectedProductQuantitiesFromReservations($productReservations);
            if (empty($expectedProductQuantities))
            {
                return false;
            }

            $productLootTransfers = $this->buildProductLootTransferEntries($expectedProductQuantities);
            if (empty($productLootTransfers))
            {
                return false;
            }

            $priceLootTransfers = $this->buildPriceLootTransferEntries($lootEntryQuantities);

            $this->insertTradeRowsForLootTransfers($cart->store->owner, $cart->account, $productLootTransfers);
            $this->reassignLootOwnershipForTransfers($cart->store->owner, $cart->account, $productLootTransfers);
            $this->markProductLootLinksRemovedForTransfers($productLootTransfers);

            if (!empty($priceLootTransfers))
            {
                $this->insertTradeRowsForLootTransfers($cart->account, $cart->store->owner, $priceLootTransfers);
                $this->reassignLootOwnershipForTransfers($cart->account, $cart->store->owner, $priceLootTransfers);
            }

            return true;
        }
        catch (Exception $e)
        {
            return null;
        }
    }

    private function buildPriceLootTransferEntries(array $lootEntryQuantities) : array
    {
        $expectedByLootId = $this->buildExpectedPriceLootByLootId($lootEntryQuantities);
        if (empty($expectedByLootId))
        {
            return [];
        }

        $entries = [];
        foreach ($expectedByLootId as $lootId => $quantity)
        {
            $normalizedLootId = (int)$lootId;
            $normalizedQuantity = (int)$quantity;
            if ($normalizedLootId <= 0 || $normalizedQuantity <= 0)
            {
                continue;
            }

            $entries[] = [
                "lootId" => $normalizedLootId,
                "quantity" => $normalizedQuantity
            ];
        }

        return $entries;
    }

    private function buildProductLootTransferEntries(array $expectedProductQuantities) : array
    {
        if (empty($expectedProductQuantities))
        {
            return [];
        }

        $selectTable = $this->buildProductQuantitySelectTable($expectedProductQuantities);
        if (empty($selectTable))
        {
            return [];
        }

        $params = $this->buildProductQuantitySelectTableParams($expectedProductQuantities);
        if (empty($params))
        {
            return [];
        }

        $sql = "SELECT
            ppl.ref_loot_ctime AS loot_ctime,
            ppl.ref_loot_crand AS loot_id,
            SUM(ppl.quantity * pq.quantity) AS quantity
            FROM product_loot_link ppl
            JOIN ($selectTable) pq
            ON ppl.ref_product_ctime = pq.product_ctime
            AND ppl.ref_product_crand = pq.product_crand
            WHERE ppl.removed = 0
            GROUP BY ppl.ref_loot_ctime, ppl.ref_loot_crand";

        $conn = $this->pdo->getConnection();
        $stmt = $conn->prepare($sql);
        if ($stmt === false)
        {
            throw new Exception("Failed to prepare product loot transfer query");
        }

        $result = $stmt->execute($params);
        if ($result === false)
        {
            throw new Exception("Failed to execute product loot transfer query");
        }

        $entries = [];
        while($row = $stmt->fetch())
        {
            $lootCtime = $row["loot_ctime"] ?? null;
            $lootId = (int)$row["loot_id"];
            $quantity = (int)$row["quantity"];
            if (!is_string($lootCtime) || empty($lootCtime) || $lootId <= 0 || $quantity <= 0)
            {
                continue;
            }

            $entries[] = [
                "lootCtime" => $lootCtime,
                "lootId" => $lootId,
                "quantity" => $quantity
            ];
        }

        return $entries;
    }

    private function buildProductQuantitySelectTable(array $expectedProductQuantities) : string
    {
        $selectTable = "";

        foreach($expectedProductQuantities as $entry)
        {
            $productId = $entry["productId"] ?? null;
            $quantity = isset($entry["quantity"]) ? (int)$entry["quantity"] : 0;
            if (!($productId instanceof vRecordId) || $quantity <= 0)
            {
                continue;
            }

            if (!empty($selectTable))
            {
                $selectTable .= " UNION ALL ";
            }

            $selectTable .= "SELECT ? AS product_ctime, ? AS product_crand, ? AS quantity";
        }

        return $selectTable;
    }

    private function buildProductQuantitySelectTableParams(array $expectedProductQuantities) : array
    {
        $params = [];

        foreach($expectedProductQuantities as $entry)
        {
            $productId = $entry["productId"] ?? null;
            $quantity = isset($entry["quantity"]) ? (int)$entry["quantity"] : 0;
            if (!($productId instanceof vRecordId) || $quantity <= 0)
            {
                continue;
            }

            $params[] = $productId->ctime;
            $params[] = $productId->crand;
            $params[] = $quantity;
        }

        return $params;
    }

    private function insertTradeRowsForLootTransfers(vRecordId $fromAccountId, vRecordId $toAccountId, array $lootTransfers) : void
    {
        if (empty($lootTransfers))
        {
            return;
        }

        $valueClause = "";
        $params = [];

        foreach($lootTransfers as $entry)
        {
            if (!is_array($entry))
            {
                continue;
            }

            $lootId = isset($entry["lootId"]) ? (int)$entry["lootId"] : 0;
            $quantity = isset($entry["quantity"]) ? (int)$entry["quantity"] : 0;
            if ($lootId <= 0 || $quantity <= 0)
            {
                continue;
            }

            if (!empty($valueClause))
            {
                $valueClause .= " UNION ALL ";
            }

            $valueClause .= "SELECT ?, ?, ?, (SELECT dateObtained FROM loot WHERE Id = ? LIMIT 1), ?";
            $params[] = $fromAccountId->crand;
            $params[] = $toAccountId->crand;
            $params[] = $lootId;
            $params[] = $lootId;
            $params[] = $quantity;
        }

        if (empty($valueClause))
        {
            return;
        }

        $sql = "INSERT INTO trade (
            from_account_id,
            to_account_id,
            loot_id,
            from_account_obtain_date,
            quantity
        ) $valueClause";

        $conn = $this->pdo->getConnection();
        $stmt = $conn->prepare($sql);
        if ($stmt === false)
        {
            throw new Exception("Failed to prepare insert trade rows");
        }

        $result = $stmt->execute($params);
        if ($result === false)
        {
            throw new Exception("Failed to insert trade rows");
        }
    }

    private function reassignLootOwnershipForTransfers(vRecordId $fromAccountId, vRecordId $newOwnerId, array $lootTransfers) : void
    {
        if (empty($lootTransfers))
        {
            return;
        }

        $lootIds = [];
        foreach($lootTransfers as $entry)
        {
            if (!is_array($entry))
            {
                continue;
            }

            $lootId = isset($entry["lootId"]) ? (int)$entry["lootId"] : 0;
            $quantity = isset($entry["quantity"]) ? (int)$entry["quantity"] : 0;
            if ($lootId <= 0 || $quantity <= 0)
            {
                continue;
            }

            $lootIds[$lootId] = true;
        }

        $lootIds = array_keys($lootIds);
        if (empty($lootIds))
        {
            return;
        }

        $placeholders = implode(',', array_fill(0, count($lootIds), '?'));
        $sql = "UPDATE loot
            SET account_id = ?, dateObtained = NOW()
            WHERE account_id = ? AND Id IN ($placeholders)";
        $params = array_merge([$newOwnerId->crand, $fromAccountId->crand], $lootIds);

        $conn = $this->pdo->getConnection();
        $stmt = $conn->prepare($sql);
        if ($stmt === false)
        {
            throw new Exception("Failed to prepare loot ownership reassignment query");
        }

        $result = $stmt->execute($params);
        if ($result === false)
        {
            throw new Exception("Failed to reassign loot ownership");
        }
    }

    private function markProductLootLinksRemovedForTransfers(array $productLootTransfers) : void
    {
        if (empty($productLootTransfers))
        {
            return;
        }

        $keys = [];
        $whereClause = "";
        $params = [];

        foreach($productLootTransfers as $entry)
        {
            if (!is_array($entry))
            {
                continue;
            }

            $lootCtime = $entry["lootCtime"] ?? null;
            $lootId = isset($entry["lootId"]) ? (int)$entry["lootId"] : 0;
            if (!is_string($lootCtime) || empty($lootCtime) || $lootId <= 0)
            {
                continue;
            }

            $key = $lootCtime . '|' . $lootId;
            if (isset($keys[$key]))
            {
                continue;
            }

            $keys[$key] = true;
            if (!empty($whereClause))
            {
                $whereClause .= " OR ";
            }

            $whereClause .= "(ref_loot_ctime = ? AND ref_loot_crand = ?)";
            $params[] = $lootCtime;
            $params[] = $lootId;
        }

        if (empty($whereClause))
        {
            return;
        }

        $sql = "UPDATE product_loot_link
            SET removed = 1
            WHERE removed = 0 AND ($whereClause)";

        $conn = $this->pdo->getConnection();
        $stmt = $conn->prepare($sql);
        if ($stmt === false)
        {
            throw new Exception("Failed to prepare mark product_loot_link removed query");
        }

        $result = $stmt->execute($params);
        if ($result === false)
        {
            throw new Exception("Failed to mark product_loot_link rows removed");
        }
    }

    private function validateCartForCheckout(vCart $cart) : ?bool
    {
        if ($cart->account->equals($cart->store->owner))
        {
            return false;
        }

        if (empty($cart->cartProducts))
        {
            return false;
        }

        return true;
    }

    private function canAccountAffordItemPriceInCart(vCart $cart) : ?bool
    {
        try
        {
            $totals = $this->getItemTotals($cart->totals);
            if (empty($totals))
            {
                return true;
            }

            $lootForCart = $this->getLootAmountsForTotals($cart, $totals);

            foreach($totals as $total)
            {
                $itemId = $total->item->crand;
                $amountNeeded = $total->amount;
                $amountAvailable = $lootForCart[$itemId] ?? 0;

                if(($amountAvailable - $amountNeeded) < 0)
                {
                    return false;
                }
            }

            return true;
        }
        catch (Exception $e)
        {
            return null;
        }
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

    private function reserveProductStockInCart(vCart $cart, array &$reservations) : ?bool
    {
        $reservations = [];

        $areAvailable = $this->areCartProductsAvailable($cart);
        if($areAvailable !== true)
        {
            return $areAvailable;
        }

        $productEntryQuantities = $this->buildProductReservationEntries($cart);
        if (empty($productEntryQuantities))
        {
            return false;
        }

        $productDao = new PDOProductDAO($this->pdo);
        $reserveResult = $productDao->reserveStockForProducts($productEntryQuantities, $cart);
        if ($reserveResult !== true)
        {
            return $reserveResult;
        }

        $reservations = $productDao->getActiveProductReservationsForCart($cart);
        return true;
    }

    private function areCartProductsAvailable(vCart $cart) : ?bool
    {
        $cartProducts = $cart->cartProducts;
        if (empty($cartProducts))
        {
            return false;
        }
        

        $counts = $this->countProductsInCart($cartProducts);
        $productDao = new PDOProductDAO($this->pdo);
        $productIds = $this->getProductIdsFromCartProducts($cartProducts);
        $availability = $productDao->areProductsAvailableInStore($cart->store, $productIds);
        if ($availability === null)
        {
            return null;
        }

        $areProductsAvaiable = $this->areProductCountsWithinAvailability($counts, $availability);

        return $areProductsAvaiable;
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
            if ($availableQty < $qty)
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

    private function validateCheckoutLootAndTrades(vCart $cart, array $productReservations, array $lootEntryQuantities) : ?bool
    {
        try
        {
            if (!$this->validateLootTransactedForAccounts($cart, $productReservations, $lootEntryQuantities))
            {
                return false;
            }

            if (!$this->validateTradeRowsForCheckout($cart, $productReservations, $lootEntryQuantities))
            {
                return false;
            }

            return true;
        }
        catch (Exception $e)
        {
            return null;
        }
    }

    private function validateLootTransactedForAccounts(vCart $cart, array $productReservations, array $lootEntryQuantities) : bool
    {
        $buyerExpectedProductQuantities = $this->buildExpectedProductQuantitiesFromReservations($productReservations);
        if (empty($buyerExpectedProductQuantities))
        {
            return false;
        }

        if (!$this->doesBuyerOwnExpectedProductLoot($cart->account, $buyerExpectedProductQuantities))
        {
            return false;
        }

        if (!$this->doesSellerOwnExpectedPriceLoot($cart->store->owner, $lootEntryQuantities))
        {
            return false;
        }

        return true;
    }

    private function validateTradeRowsForCheckout(vCart $cart, array $productReservations, array $lootEntryQuantities) : bool
    {
        if (!$this->doPriceLootTradesExist($cart, $lootEntryQuantities))
        {
            return false;
        }

        $expectedProductQuantities = $this->buildExpectedProductQuantitiesFromReservations($productReservations);
        if (empty($expectedProductQuantities))
        {
            return false;
        }

        if (!$this->doProductLootTradesExist($cart, $expectedProductQuantities))
        {
            return false;
        }

        return true;
    }

    private function doesSellerOwnExpectedPriceLoot(vRecordId $sellerId, array $lootEntryQuantities) : bool
    {
        $expectedByLootId = $this->buildExpectedPriceLootByLootId($lootEntryQuantities);
        if (empty($expectedByLootId))
        {
            return true;
        }

        $lootIds = array_keys($expectedByLootId);
        $placeholders = implode(',', array_fill(0, count($lootIds), '?'));
        $sql = "SELECT Id AS loot_id, item_id
            FROM loot
            WHERE Id IN ($placeholders)";

        $conn = $this->pdo->getConnection();
        $stmt = $conn->prepare($sql);
        if ($stmt === false)
        {
            throw new Exception("Failed to prepare seller loot lookup query");
        }

        $result = $stmt->execute($lootIds);
        if ($result === false)
        {
            throw new Exception("Failed to execute seller loot lookup query");
        }

        $expectedByItemId = [];
        $resolvedLootIds = [];
        while($row = $stmt->fetch())
        {
            $lootId = (int)$row["loot_id"];
            if (!array_key_exists($lootId, $expectedByLootId))
            {
                continue;
            }

            $itemId = (int)$row["item_id"];
            $resolvedLootIds[$lootId] = true;
            $expectedByItemId[$itemId] = ($expectedByItemId[$itemId] ?? 0) + $expectedByLootId[$lootId];
        }

        if (empty($expectedByItemId))
        {
            return false;
        }

        foreach($expectedByLootId as $lootId => $_quantity)
        {
            if (!isset($resolvedLootIds[$lootId]))
            {
                return false;
            }
        }

        $sellerInventory = $this->getAccountItemQuantities($sellerId, array_keys($expectedByItemId));
        return $this->doesInventorySatisfyExpectedItemQuantities($sellerInventory, $expectedByItemId);
    }

    private function doesBuyerOwnExpectedProductLoot(vRecordId $buyerId, array $expectedProductQuantities) : bool
    {
        $expectedByItemId = $this->resolveExpectedItemQuantitiesForProducts($expectedProductQuantities);
        if (empty($expectedByItemId))
        {
            return false;
        }

        $buyerInventory = $this->getAccountItemQuantities($buyerId, array_keys($expectedByItemId));
        return $this->doesInventorySatisfyExpectedItemQuantities($buyerInventory, $expectedByItemId);
    }

    private function doPriceLootTradesExist(vCart $cart, array $lootEntryQuantities) : bool
    {
        $expectedByLootId = $this->buildExpectedPriceLootByLootId($lootEntryQuantities);
        if (empty($expectedByLootId))
        {
            return true;
        }

        $lootIds = array_keys($expectedByLootId);
        $placeholders = implode(',', array_fill(0, count($lootIds), '?'));
        $sql = "SELECT loot_id, SUM(quantity) AS traded_quantity
            FROM trade
            WHERE from_account_id = ? AND to_account_id = ? AND loot_id IN ($placeholders)
            GROUP BY loot_id";

        $params = array_merge([$cart->account->crand, $cart->store->owner->crand], $lootIds);

        $conn = $this->pdo->getConnection();
        $stmt = $conn->prepare($sql);
        if ($stmt === false)
        {
            throw new Exception("Failed to prepare price trade sanity query");
        }

        $result = $stmt->execute($params);
        if ($result === false)
        {
            throw new Exception("Failed to execute price trade sanity query");
        }

        $tradeByLootId = [];
        while($row = $stmt->fetch())
        {
            $tradeByLootId[(int)$row["loot_id"]] = (int)$row["traded_quantity"];
        }

        foreach($expectedByLootId as $lootId => $expectedQuantity)
        {
            $tradedQuantity = $tradeByLootId[$lootId] ?? 0;
            if ($tradedQuantity < $expectedQuantity)
            {
                return false;
            }
        }

        return true;
    }

    private function doProductLootTradesExist(vCart $cart, array $expectedProductQuantities) : bool
    {
        if (empty($expectedProductQuantities))
        {
            return false;
        }

        $productWhereClause = $this->buildProductWhereClauseFromExpectedQuantities($expectedProductQuantities, 'ppl.ref_product_ctime', 'ppl.ref_product_crand');
        $params = array_merge([$cart->store->owner->crand, $cart->account->crand], $this->buildProductWhereParamsFromExpectedQuantities($expectedProductQuantities));

        $sql = "SELECT 
            ppl.ref_product_ctime AS product_ctime,
            ppl.ref_product_crand AS product_crand,
            SUM(t.quantity) AS traded_quantity
            FROM trade t
            JOIN product_loot_link ppl ON ppl.ref_loot_crand = t.loot_id
            WHERE t.from_account_id = ? AND t.to_account_id = ? AND ($productWhereClause)
            GROUP BY ppl.ref_product_ctime, ppl.ref_product_crand";

        $conn = $this->pdo->getConnection();
        $stmt = $conn->prepare($sql);
        if ($stmt === false)
        {
            throw new Exception("Failed to prepare product trade sanity query");
        }

        $result = $stmt->execute($params);
        if ($result === false)
        {
            throw new Exception("Failed to execute product trade sanity query");
        }

        $tradeByProductKey = [];
        while($row = $stmt->fetch())
        {
            $key = $row["product_ctime"] . '|' . $row["product_crand"];
            $tradeByProductKey[$key] = (int)$row["traded_quantity"];
        }

        foreach($expectedProductQuantities as $key => $entry)
        {
            $expectedQuantity = (int)($entry["quantity"] ?? 0);
            $tradedQuantity = $tradeByProductKey[$key] ?? 0;
            if ($tradedQuantity < $expectedQuantity)
            {
                return false;
            }
        }

        return true;
    }

    private function buildExpectedPriceLootByLootId(array $lootEntryQuantities) : array
    {
        $expectedByLootId = [];

        foreach ($lootEntryQuantities as $entry)
        {
            if (!is_array($entry))
            {
                continue;
            }

            $lootId = $entry["lootId"] ?? null;
            $quantity = isset($entry["quantity"]) ? (int)$entry["quantity"] : 0;
            if (!($lootId instanceof vRecordId) || $quantity <= 0)
            {
                continue;
            }

            $expectedByLootId[$lootId->crand] = ($expectedByLootId[$lootId->crand] ?? 0) + $quantity;
        }

        return $expectedByLootId;
    }

    private function buildExpectedProductQuantitiesFromReservations(array $productReservations) : array
    {
        $expectedByProduct = [];

        foreach ($productReservations as $reservation)
        {
            if (!is_object($reservation) || !property_exists($reservation, "productId"))
            {
                continue;
            }

            $productId = $reservation->productId;
            if (!($productId instanceof vRecordId))
            {
                continue;
            }

            $quantity = property_exists($reservation, "quantity") ? (int)$reservation->quantity : 0;
            if ($quantity <= 0)
            {
                continue;
            }

            $key = $productId->ctime . '|' . $productId->crand;
            if (!isset($expectedByProduct[$key]))
            {
                $expectedByProduct[$key] = [
                    "productId" => $productId,
                    "quantity" => 0
                ];
            }

            $expectedByProduct[$key]["quantity"] += $quantity;
        }

        return $expectedByProduct;
    }

    private function buildProductWhereClauseFromExpectedQuantities(array $expectedProductQuantities, string $ctimeColumn, string $crandColumn) : string
    {
        $clauses = [];
        foreach($expectedProductQuantities as $entry)
        {
            $clauses[] = "($ctimeColumn = ? AND $crandColumn = ?)";
        }

        return implode(" OR ", $clauses);
    }

    private function buildProductWhereParamsFromExpectedQuantities(array $expectedProductQuantities) : array
    {
        $params = [];
        foreach($expectedProductQuantities as $entry)
        {
            $productId = $entry["productId"] ?? null;
            if (!($productId instanceof vRecordId))
            {
                continue;
            }

            $params[] = $productId->ctime;
            $params[] = $productId->crand;
        }

        return $params;
    }

    private function resolveExpectedItemQuantitiesForProducts(array $expectedProductQuantities) : array
    {
        if (empty($expectedProductQuantities))
        {
            return [];
        }

        $whereClause = $this->buildProductWhereClauseFromExpectedQuantities($expectedProductQuantities, 'ppl.ref_product_ctime', 'ppl.ref_product_crand');
        $params = $this->buildProductWhereParamsFromExpectedQuantities($expectedProductQuantities);

        $sql = "SELECT 
            ppl.ref_product_ctime AS product_ctime,
            ppl.ref_product_crand AS product_crand,
            l.item_id,
            SUM(ppl.quantity) AS linked_quantity
            FROM product_loot_link ppl
            JOIN loot l ON l.Id = ppl.ref_loot_crand
            WHERE ($whereClause)
            GROUP BY ppl.ref_product_ctime, ppl.ref_product_crand, l.item_id";

        $conn = $this->pdo->getConnection();
        $stmt = $conn->prepare($sql);
        if ($stmt === false)
        {
            throw new Exception("Failed to prepare product-to-item sanity query");
        }

        $result = $stmt->execute($params);
        if ($result === false)
        {
            throw new Exception("Failed to execute product-to-item sanity query");
        }

        $expectedItemQuantities = [];
        $resolvedProductKeys = [];
        while($row = $stmt->fetch())
        {
            $productKey = $row["product_ctime"] . '|' . $row["product_crand"];
            if (!isset($expectedProductQuantities[$productKey]))
            {
                continue;
            }

            $reservationQuantity = (int)$expectedProductQuantities[$productKey]["quantity"];
            $linkedQuantity = (int)$row["linked_quantity"];
            $itemId = (int)$row["item_id"];

            if ($reservationQuantity <= 0 || $linkedQuantity <= 0 || $itemId <= 0)
            {
                continue;
            }

            $resolvedProductKeys[$productKey] = true;
            $expectedItemQuantities[$itemId] = ($expectedItemQuantities[$itemId] ?? 0) + ($reservationQuantity * $linkedQuantity);
        }

        if (count($resolvedProductKeys) !== count($expectedProductQuantities))
        {
            return [];
        }

        return $expectedItemQuantities;
    }

    private function getAccountItemQuantities(vRecordId $accountId, array $itemIds) : array
    {
        if (empty($itemIds))
        {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($itemIds), '?'));
        $sql = "SELECT item_id, SUM(quantity) AS quantity
            FROM v_loot_item
            WHERE account_id = ? AND item_id IN ($placeholders)
            GROUP BY item_id";

        $params = array_merge([$accountId->crand], $itemIds);

        $conn = $this->pdo->getConnection();
        $stmt = $conn->prepare($sql);
        if ($stmt === false)
        {
            throw new Exception("Failed to prepare account item quantity sanity query");
        }

        $result = $stmt->execute($params);
        if ($result === false)
        {
            throw new Exception("Failed to execute account item quantity sanity query");
        }

        $quantities = [];
        while($row = $stmt->fetch())
        {
            $quantities[(int)$row["item_id"]] = (int)$row["quantity"];
        }

        return $quantities;
    }

    private function doesInventorySatisfyExpectedItemQuantities(array $actualByItemId, array $expectedByItemId) : bool
    {
        foreach($expectedByItemId as $itemId => $expectedQuantity)
        {
            $actualQuantity = $actualByItemId[$itemId] ?? 0;
            if ($actualQuantity < $expectedQuantity)
            {
                return false;
            }
        }

        return true;
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

    private function markCartProductRemoved(vCartItem $cartProduct) : bool
    {
        $conn = $this->pdo->getConnection();
        $sql = "UPDATE cart_product_link SET removed = 1 WHERE ctime = ? AND crand = ?;";
        $stmt = $conn->prepare($sql);
        if ($stmt === false)
        {
            return false;
        }

        $result = $stmt->execute([$cartProduct->ctime, $cartProduct->crand]);
        return $result !== false;
    }

    private function removeCartProductPriceComponents(vCartItem $cartProduct) : bool
    {
        $conn = $this->pdo->getConnection();
        $sql = "UPDATE cart_product_price_component_link SET removed = 1 WHERE ref_cart_product_link_ctime = ? AND ref_cart_product_link_crand = ?;";
        $stmt = $conn->prepare($sql);
        if ($stmt === false)
        {
            return false;
        }

        $result = $stmt->execute([$cartProduct->ctime, $cartProduct->crand]);
        return $result !== false;
    }

    private function getProductQuantityInCart(vRecordId $cartId, vRecordId $productId) : ?int
    {
        $conn = $this->pdo->getConnection();

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

    private function insertCartProductLink(vRecordId $cartId, vRecordId $productId) : ?CartProductLink
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

        $params = [
            $cartProductLink->ctime,
            $cartProductLink->crand,
            0,
            0,
            $cartProductLink->cartId->ctime,
            $cartProductLink->cartId->crand,
            $cartProductLink->productId->ctime,
            $cartProductLink->productId->crand
        ];

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

        return $cartProductLink;
    }

    private function insertCartProductPriceComponents( CartProductLink $cartProductLink, array $priceComponentIds) : bool
    {
        $conn = $this->pdo->getConnection();
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

        $cart->stripeSessionId = $row["stripe_session_id"] ?? null;

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
            $priceComponent->currencyCode = CurrencyCode::from($row["price_component_currency_code"]);
        }

        $priceComponent->ctime = $row["price_component_ctime"];
        $priceComponent->crand = $row["price_component_crand"];
        $priceComponent->amount = $row["price_component_amount"];

        return $priceComponent;
        
    }

    public function setStripeSessionId(vRecordId $cartId, string $sessionId) : bool
    {
        $sql = "UPDATE cart SET stripe_session_id = ? WHERE ctime = ? AND crand = ?";
        $params = [$sessionId, $cartId->ctime, $cartId->crand];

        try
        {
            $conn = $this->pdo->getConnection();
            $stmt = $conn->prepare($sql);
            if ($stmt === false)
            {
                return false;
            }

            $result = $stmt->execute($params);
            return $result !== false;
        }
        catch (PDOException $e)
        {
            throw new Exception("PDO exception caught while setting stripe session id : " . $e->getMessage(), 0, $e);
        }
    }

    public function createStripeTransaction(vRecordId $cartId, string $stripeTransactionId) : bool
    {
        $sql = "INSERT INTO stripe_transaction (ref_cart_ctime, ref_cart_crand, stripe_transaction_id)
                VALUES (?, ?, ?)";
        $params = [$cartId->ctime, $cartId->crand, $stripeTransactionId];

        try
        {
            $conn = $this->pdo->getConnection();
            $stmt = $conn->prepare($sql);
            if ($stmt === false) { return false; }
            $result = $stmt->execute($params);
            return $result !== false;
        }
        catch (PDOException $e)
        {
            throw new Exception("PDO exception caught while creating stripe transaction: " . $e->getMessage(), 0, $e);
        }
    }

    public function getCartByStripeSessionId(string $sessionId) : ?vCart
    {
        $sql = "SELECT ".static::$columnsInCartView." FROM v_cart WHERE stripe_session_id = ? LIMIT 1;";
        $params = [$sessionId];

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

            $row = $stmt->fetch();
            if ($row === false)
            {
                return null;
            }

            $cart = static::cartToView($row);

            $cartItems = $this->getCartProductsViews($cart);
            $cart->cartProducts = $cartItems;

            return $cart;
        }
        catch (PDOException $e)
        {
            throw new Exception("PDO exception caught while getting cart by stripe session id : " . $e->getMessage(), 0, $e);
        }
        catch (Exception $e)
        {
            throw new Exception("Exception caught while getting cart by stripe session id : " . $e->getMessage(), 0, $e);
        }
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
