<?php

declare(strict_types = 1);

namespace Kickback\BackendV2\DAO\Product;

use Exception;
use Kickback\Backend\Models\ProductReservation;
use Kickback\Backend\Models\Enums\CurrencyCode;
use Kickback\Backend\Views\vAccount;
use Kickback\Backend\Views\vItem;
use Kickback\Backend\Views\vMedia;
use Kickback\Backend\Views\vPriceComponent;
use Kickback\Backend\Views\vProduct;
use Kickback\Backend\Views\vProductReservation;
use Kickback\Backend\Views\vRecordId;
use Kickback\Backend\Views\vStore;
use Kickback\BackendV2\Persistance\Database;
use DateTime;
use PDO;
use PDOException;

class PDOProductDAO implements ProductDAO
{
    private Database $pdo; 

    private static int $productReservationTimeInSeconds = 10;

    private static string $columnsInProductTable = "ctime, crand, `name`, `description`, locator, tag, categories, ref_store_ctime, ref_store_crand, ref_media_id_large, ref_media_id_small, ref_media_id_back";
    private static string $columnsInProductView = "ctime, crand, `name`, `description`, locator, tag, categories, stock, amount_available, removed, store_name, store_locator, store_description, store_owner_username, store_owner_ctime, store_owner_crand, store_ctime, store_crand, large_media_media_path, small_media_media_path, back_media_media_path";

    public function __construct(?Database $pdo = null)
    {
        $this->pdo = $pdo ?? new Database();
    }

    public function getProductsForStoreByStoreId(vRecordId $storeId) : ?array
    {
        try
        {
            $sql = "SELECT ".static::$columnsInProductView." FROM v_product where store_ctime = ? AND store_crand = ?;
            ";

            $params = [$storeId->ctime, $storeId->crand];

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

            if(!$result) throw new Exception("Result returned false when selecting for products in store");

            $products = [];
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) 
            {
                $product = static::rowToVProduct($row);
                $product->price = $this->getBasePriceForProduct($product);
                $products[] = $product;
            }

            return $products;
        }
        catch (PDOException $e)
        {
            throw new Exception("PDO exception caught while getting products for store by store id : " . $e->getMessage(), 0, $e);
        }
        catch (Exception $e)
        {
            throw new Exception("Exception caught while getting products for store by store id : " . $e->getMessage(), 0, $e);
        }
    }

    public function getProductByLocator(string $productLocator) : ?vProduct
    {
        try
        {
            $sql = "SELECT ".static::$columnsInProductView." FROM v_product WHERE locator = ? LIMIT 1;";

            $params = [$productLocator];

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

            if ($stmt->rowCount() === 0)
            {
                return null;
            }

            $product = static::rowToVProduct($stmt->fetch(PDO::FETCH_ASSOC));
            $priceComponents = $this->getBasePriceForProduct($product);

            if ($priceComponents === null)
            {
                return null;
            }

            $product->price = $priceComponents;

            return $product;
        }
        catch (PDOException $e)
        {
            throw new Exception("PDO exception caught while getting product by locator : " . $e->getMessage(), 0, $e);
        }
        catch (Exception $e)
        {
            throw new Exception("Exception caught while getting product by locator : " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Reserves product stock by inserting rows into the product_reservation table.
     *
     * @param array $productEntryQuantities array of ["productId" => vRecordId, "quantity" => int]
     * @param vRecordId $cartId
     * @return ?bool true on success, null on failure
     */
    public function reserveStockForProducts(array $productEntryQuantities, vRecordId $cartId) : ?bool
    {
        if (empty($productEntryQuantities))
        {
            return true;
        }

        if (empty($cartId->ctime) || $cartId->crand <= 0)
        {
            return null;
        }

        $reservations = $this->buildProductReservationsFromEntries($productEntryQuantities, $cartId);
        if (empty($reservations))
        {
            return true;
        }

        $params = [];
        $valueClause = $this->buildProductReservationInsert($reservations, $params);
        if (empty($valueClause))
        {
            return true;
        }
        $sql = "INSERT INTO product_reservation (
            ctime,
            crand,
            ref_cart_ctime,
            ref_cart_crand,
            ref_product_ctime,
            ref_product_crand,
            quantity,
            expiry_time,
            close_time)
            VALUES $valueClause";

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

        return true;
    }

    /**
     * Closes product reservations by setting close_time.
     *
     * @param array $reservations array of reservation views with ctime/crand
     */
    public function closeProductReservations(array $reservations) : void
    {
        if (empty($reservations))
        {
            return;
        }

        $whereClause = $this->buildReservationWhereClause($reservations);
        $sql = "UPDATE product_reservation SET close_time = NOW() WHERE $whereClause";
        $params = $this->buildReservationParams($reservations);

        $conn = $this->pdo->getConnection();
        $stmt = $conn->prepare($sql);
        if ($stmt === false)
        {
            throw new Exception("Failed to prepare close product reservations");
        }

        $result = $stmt->execute($params);
        if ($result === false)
        {
            throw new Exception("Failed to close product reservations");
        }
    }

    /**
     * Returns active product reservations for the provided cart.
     *
     * @param vRecordId $cartId
     * @return vProductReservation[] active reservations
     */
    public function getActiveProductReservationsForCart(vRecordId $cartId) : array
    {
        if (empty($cartId->ctime) || $cartId->crand <= 0)
        {
            return [];
        }

        $sql = "SELECT ctime, crand, ref_cart_ctime, ref_cart_crand, ref_product_ctime, ref_product_crand, quantity, expiry_time, close_time
            FROM v_product_reservation
            WHERE close_time IS NULL AND expiry_time > NOW() AND ref_cart_ctime = ? AND ref_cart_crand = ?";
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

        $reservations = [];
        while($row = $stmt->fetch(PDO::FETCH_ASSOC))
        {
            $reservationCartId = new vRecordId($row["ref_cart_ctime"], (int)$row["ref_cart_crand"]);
            $productId = new vRecordId($row["ref_product_ctime"], (int)$row["ref_product_crand"]);

            $expiryTime = null;
            if (!empty($row["expiry_time"]))
            {
                $expiryTime = DateTime::createFromFormat('Y-m-d H:i:s.u', $row["expiry_time"]);
                if ($expiryTime === false)
                {
                    $expiryTime = new DateTime($row["expiry_time"]);
                }
            }

            $closeTime = null;
            if (!empty($row["close_time"]))
            {
                $closeTime = DateTime::createFromFormat('Y-m-d H:i:s.u', $row["close_time"]);
                if ($closeTime === false)
                {
                    $closeTime = new DateTime($row["close_time"]);
                }
            }

            $reservations[] = new vProductReservation(
                $row["ctime"],
                (int)$row["crand"],
                $reservationCartId,
                $productId,
                (int)$row["quantity"],
                $expiryTime,
                $closeTime
            );
        }

        return $reservations;
    }

    /**
     * Returns available stock for a product.
     */
    public function getProductAmountAvailable(vRecordId $productId) : ?int
    {
        $conn = $this->pdo->getConnection();
        if ($conn === null)
        {
            return null;
        }

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

    /**
     * Returns the base price component ids for a product.
     */
    public function getBasePriceComponentIdsForProduct(vRecordId $productId) : ?array
    {
        $conn = $this->pdo->getConnection();
        if ($conn === null)
        {
            return null;
        }

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

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($rows === false)
        {
            return null;
        }

        $priceComponentIds = [];
        foreach ($rows as $row)
        {
            $priceComponentIds[] = new vRecordId($row["ctime"], (int)$row["crand"]);
        }

        return $priceComponentIds;
    }

    /**
     * Returns available quantities for products in a store.
     *
     * @param vRecordId $storeId
     * @param vRecordId[] $productIds
     *
     * @return ?array array of ["productId" => vRecordId, "quantityAvailable" => int]
     */
    public function areProductsAvailableInStore(vRecordId $storeId, array $productIds) : ?array
    {
        if (empty($productIds))
        {
            return [];
        }

        $productIdMap = [];
        foreach ($productIds as $productId)
        {
            if (!($productId instanceof vRecordId))
            {
                continue;
            }

            $key = $productId->ctime . '|' . $productId->crand;
            $productIdMap[$key] = $productId;
        }

        if (empty($productIdMap))
        {
            return [];
        }

        $whereClause = $this->buildProductsWhereClause($productIdMap);
        $params = array_merge([$storeId->ctime, $storeId->crand], $this->buildProductsWhereParams($productIdMap));

        $sql = "SELECT ctime, crand, amount_available, removed
            FROM v_product
            WHERE store_ctime = ? AND store_crand = ? AND ($whereClause)";

        $conn = $this->pdo->getConnection();
        if ($conn === null)
        {
            return null;
        }

        $stmt = $conn->prepare($sql);
        if ($stmt === false)
        {
            throw new Exception("Failed to prepare product availability query");
        }

        $result = $stmt->execute($params);
        if ($result === false)
        {
            throw new Exception("Failed to execute product availability query");
        }

        $productsInStore = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $productMap = [];
        foreach ($productsInStore as $productRow)
        {
            $productMap[$productRow["ctime"] . '|' . $productRow["crand"]] = $productRow;
        }

        $availability = [];
        foreach ($productIdMap as $key => $productId)
        {
            $matching = $productMap[$key] ?? null;
            $quantityAvailable = 0;
            if ($matching !== null && !boolval($matching["removed"]))
            {
                $quantityAvailable = (int)$matching["amount_available"];
            }

            $availability[] = [
                "productId" => $productId,
                "quantityAvailable" => $quantityAvailable
            ];
        }

        return $availability;
    }

    /**
     * Converts an associative row of the product view and returns a populated vProduct object
     * 
     * @param array $row the associative array of a row from the product view
     * 
     * @return vProduct the returned vProduct object populated from the row
     */
    private static function rowToVProduct(array $row): vProduct
    {
        $owner = new vAccount($row['store_owner_ctime'],(int)$row['store_owner_crand']);
        $owner->username = $row['store_owner_username'];

        $smallIcon = new vMedia();
        if(!is_null($row['small_media_media_path']))$smallIcon->setMediaPath($row['small_media_media_path']);
        $largeIcon = new vMedia();
        if(!is_null($row['large_media_media_path']))$largeIcon->setMediaPath($row['large_media_media_path']);
        $backIcon = new vMedia();
        if(!is_null($row['back_media_media_path']))$backIcon->setMediaPath($row['back_media_media_path']);

        $store = new vStore($row['store_ctime'], (int)$row['store_crand']);
        $store->name = $row['store_name'];
        $store->description = $row['store_description'];
        $store->locator = $row['store_locator'];

        $product= new vProduct($row['ctime'],(int)$row['crand']);
            $product->locator = $row["locator"];
            $product->tag = $row["tag"];
            $product->categories = json_decode($row["categories"]);
            $product->name = $row["name"];
            $product->description = $row["description"];
            $product->stock = $row["stock"];
            $product->amountAvailable = $row["amount_available"];
            $product->removed = boolval($row["removed"]);
            $product->owner = $owner;
            $product->store = $store;
            $product->mediaSmall = $smallIcon;
            $product->mediaLarge = $largeIcon;
            $product->mediaBack = $backIcon;

        return $product;
    }

    /**
     * Returns the base price components for a product
     *
     * @param vRecordId $product the product id
     *
     * @return ?array array of vPriceComponent or null on failure
     */
    private function getBasePriceForProduct(vRecordId $product) : ?array
    {
        $sql = "SELECT 
            vp.ctime, 
            vp.crand, 
            vp.amount, 
            vp.currency_code, 
            vp.item_ctime, 
            vp.item_crand, 
            vp.item_name, 
            vp.item_desc,  
            vp.media_path_small, 
            vp.media_path_large, 
            vp.media_path_back,
            vp.item_is_fungible
            FROM v_price_component vp 
            JOIN product_price_component_link ppl ON ppl.ref_price_component_ctime = vp.ctime AND ppl.ref_price_component_crand = vp.crand
            WHERE ppl.ref_product_ctime = ? AND ppl.ref_product_crand = ?;";

        $params = [$product->ctime, $product->crand];

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

        $priceComponents = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC))
        {
            $priceComponents[] = static::priceComponentToView($row);
        }

        return $priceComponents;
    }

    private function buildProductReservationInsert(array $reservations, array &$params) : string
    {
        $clauses = [];
        foreach($reservations as $reservation)
        {
            $clauses[] = "(?,?,?,?,?,?,?,?,?)";
            $params[] = $reservation->ctime;
            $params[] = $reservation->crand;
            $params[] = $reservation->cartId->ctime;
            $params[] = $reservation->cartId->crand;
            $params[] = $reservation->productId->ctime;
            $params[] = $reservation->productId->crand;
            $params[] = $reservation->quantity;
            $params[] = $reservation->expiryTime?->format("Y-m-d H:i:s.u");
            $params[] = $reservation->closeTime?->format("Y-m-d H:i:s.u");
        }

        return implode(", ", $clauses);
    }

    private function buildProductReservationsFromEntries(array $productEntryQuantities, vRecordId $cartId) : array
    {
        $reservations = [];

        foreach ($productEntryQuantities as $entry)
        {
            if (!is_array($entry))
            {
                continue;
            }

            $productId = $entry["productId"] ?? null;
            $quantity = isset($entry["quantity"]) ? (int)$entry["quantity"] : 0;

            if ($quantity <= 0)
            {
                continue;
            }

            if (!($productId instanceof vRecordId))
            {
                throw new Exception("Product entry must contain a vRecordId in 'productId'");
            }

            $reservation = new ProductReservation($cartId, $productId, $quantity, null, null);
            $expiryTime = new DateTime($reservation->ctime);
            $expiryTime->modify("+" . static::$productReservationTimeInSeconds . " seconds");
            $reservation->expiryTime = $expiryTime;
            $reservations[] = $reservation;
        }

        return $reservations;
    }

    private function buildReservationWhereClause(array $reservations) : string
    {
        $clauses = [];
        foreach($reservations as $reservation)
        {
            $clauses[] = "(ctime = ? AND crand = ?)";
        }

        return implode(" OR ", $clauses);
    }

    private function buildReservationParams(array $reservations) : array
    {
        $params = [];
        foreach($reservations as $reservation)
        {
            $params[] = $reservation->ctime;
            $params[] = $reservation->crand;
        }

        return $params;
    }

    private function buildProductsWhereClause(array $productCounts) : string
    {
        $clauses = [];
        foreach($productCounts as $key => $_qty)
        {
            $clauses[] = "(ctime = ? AND crand = ?)";
        }

        return implode(" OR ", $clauses);
    }

    private function buildProductsWhereParams(array $productCounts) : array
    {
        $params = [];
        foreach($productCounts as $key => $_qty)
        {
            [$ctime, $crand] = explode('|', $key);
            $params[] = $ctime;
            $params[] = $crand;
        }

        return $params;
    }

    private function areProductCountsAvailable(array $productCounts, array $productsInStore) : bool
    {
        $productMap = [];
        foreach($productsInStore as $productRow)
        {
            $productMap[$productRow["ctime"] . '|' . $productRow["crand"]] = $productRow;
        }

        foreach($productCounts as $key => $qty)
        {
            $matching = $productMap[$key] ?? null;
            if (is_null($matching))
            {
                return false;
            }

            if (boolval($matching["removed"]) || ((int)$matching["amount_available"] - $qty) < 0)
            {
                return false;
            }
        }

        return true;
    }


    private static function priceComponentToView(array $row) : vPriceComponent
    {
        $iconSmall = new vMedia();
        if (!empty($row["media_path_small"])) $iconSmall->setMediaPath($row["media_path_small"]);

        $iconLarge = new vMedia();
        if (!empty($row["media_path_large"])) $iconLarge->setMediaPath($row["media_path_large"]);

        $iconBack = new vMedia();
        if (!empty($row["media_path_back"]))$iconBack->setMediaPath($row["media_path_back"]);

        $item = new vItem($row["item_ctime"], $row["item_crand"]);
        $item->name = $row["item_name"];
        $item->description = $row["item_desc"];
        $item->iconSmall = $iconSmall;
        $item->iconBig = $iconLarge;
        $item->iconBack = $iconBack;
        $item->applyMediaFallbacks();
        $item->fungible = boolval($row["item_is_fungible"]);

        $currencyCode = $row["currency_code"] !== null ? CurrencyCode::from($row["currency_code"]) : null;

        return new vPriceComponent(
            $row["ctime"],
            $row["crand"],
            $row["amount"],
            $item,
            $currencyCode
        );
    }
}

?>
