<?php

declare(strict_types = 1);

namespace Kickback\BackendV2\DAO;

use Exception;
use Kickback\Backend\Views\vAccount;
use Kickback\Backend\Views\vStore;
use Kickback\BackendV2\Persistance\Database;
use PDO;
use PDOException;

class PDOStoreDAO implements StoreDAO
{
    private Database $pdo; 

    private ProductDAO $productDAO;

    private string $columnsInStoreTable = "ctime, crand, name, locator, description, ref_owner_ctime, ref_owner_crand";
    private string $columnsInStoreView = "ctime, crand, name, locator, description, ref_owner_ctime, ref_owner_crand";


    public function __construct(?Database $pdo = null, ?ProductDAO $productDAO = null)
    {
        $this->pdo = $pdo ?? new Database;
        $this->productDAO = $productDAO ?? new ProductDAO();
    }

    public function getStoreByLocator(string $locator) : ?vStore
    {
        try
        {
            $sql = "SELECT ".static::$columnsInStoreView." FROM v_store WHERE locator = ? LIMIT 1;";

            $params = [$locator];

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

            if($stmt->rowCount() === 0)
            {
                return null;
            }

            $store = static::rowToVStore($stmt->fetch(PDO::FETCH_ASSOC));
            $products = $this->productDAO->getProductsForStoreByStoreId($store);

            if(is_null($products)) throw new Exception("Getting products for store by Id returned null");

            $store->products = $products;

            return $store;
        }
        catch (PDOException $e)
        {
            throw new Exception("PDO exception caught while getting store by locator : " . $e->getMessage(), 0, $e);
        }
        catch (Exception $e)
        {
            throw new Exception("Exception caught while getting store by locator : " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Converts a row from the view v_store in the DB into a vStore object
     * 
     * @param array $row the assosiative array which represents the row from the database
     * @return vStore $store the store object returned from the retreived row
     */
    public static function rowToVStore(array $row) : vStore
    {
        $store = new vStore($row["ctime"], $row["crand"]);

        $store->name = $row["name"];
        $store->locator = $row["locator"];
        $store->description = $row["description"];
        $store->ownerUsername = $row["owner_username"];
        $store->owner = new vAccount($row["owner_ctime"], $row["owner_crand"]);

        return $store;
    }
}

?>