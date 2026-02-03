<?php

declare(strict_types = 1);

namespace Kickback\BackendV2\DAO;

use Exception;
use Kickback\Backend\Views\vAccount;
use Kickback\Backend\Views\vMedia;
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
}

?>