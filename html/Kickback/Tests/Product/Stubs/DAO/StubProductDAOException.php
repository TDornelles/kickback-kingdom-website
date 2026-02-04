<?php

declare(strict_types=1);

namespace Kickback\Tests\Product\Stubs\DAO;

use Exception;
use Kickback\BackendV2\DAO\Product\ProductDAO;
use Kickback\Backend\Views\vProduct;
use Kickback\Backend\Views\vRecordId;

final class StubProductDAOException implements ProductDAO
{
    public function getProductsForStoreByStoreId(vRecordId $storeId) : ?array
    {
        throw new Exception("Stub Exception : Failed to get products for store");
    }

    public function getProductByLocator(string $productLocator) : ?vProduct
    {
        throw new Exception("Stub Exception : Failed to get product by locator");
    }
}

?>
