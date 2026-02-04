<?php

declare(strict_types=1);

namespace Kickback\Tests\Product\Stubs\DAO;

use Kickback\BackendV2\DAO\Product\ProductDAO;
use Kickback\Backend\Views\vProduct;
use Kickback\Backend\Views\vRecordId;

final class StubProductDAOSuccess implements ProductDAO
{
    public function getProductsForStoreByStoreId(vRecordId $storeId) : ?array
    {
        return [];
    }

    public function getProductByLocator(string $productLocator) : ?vProduct
    {
        $product = new vProduct('testCtime', -1);
        $product->locator = $productLocator;
        $product->price = [];

        return $product;
    }
}

?>
