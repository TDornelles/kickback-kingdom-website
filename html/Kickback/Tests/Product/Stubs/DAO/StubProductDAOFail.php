<?php

declare(strict_types=1);

namespace Kickback\Tests\Product\Stubs\DAO;

use Kickback\BackendV2\DAO\Product\ProductDAO;
use Kickback\Backend\Views\vProduct;
use Kickback\Backend\Views\vRecordId;

final class StubProductDAOFail implements ProductDAO
{
    public function getProductsForStoreByStoreId(vRecordId $storeId) : ?array
    {
        return null;
    }

    public function getProductByLocator(string $productLocator) : ?vProduct
    {
        return null;
    }
}

?>
