<?php

declare(strict_types = 1);

namespace Kickback\BackendV2\DAO\Product;

use Exception;
use Kickback\Backend\Models\Enums\CurrencyCode;
use Kickback\Backend\Views\vAccount;
use Kickback\Backend\Views\vItem;
use Kickback\Backend\Views\vMedia;
use Kickback\Backend\Views\vPriceComponent;
use Kickback\Backend\Views\vProduct;
use Kickback\Backend\Views\vRecordId;
use Kickback\Backend\Views\vStore;
use Kickback\BackendV2\Persistance\Database;
use PDO;
use PDOException;

class PDOProductDAO implements ProductDAO
{
    private Database $pdo; 

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
                $products[] = static::rowToVProduct($row);
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

    private static function priceComponentToView(array $row) : vPriceComponent
    {
        $iconSmall = new vMedia();
        $iconSmall->setMediaPath($row["media_path_small"]);

        $iconLarge = new vMedia();
        $iconLarge->setMediaPath($row["media_path_large"]);

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
