<?php

declare(strict_types=1);

namespace Kickback\Tests\Stubs\Product\DAO;

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

    public function closeProductReservations(array $reservations) : void
    {
    }

    public function getActiveProductReservationsForCart(vRecordId $cartId) : array
    {
        return [];
    }

    public function getProductAmountAvailable(vRecordId $productId) : ?int
    {
        return 10;
    }

    public function getBasePriceComponentIdsForProduct(vRecordId $productId) : ?array
    {
        return [new vRecordId('priceComponent', 1)];
    }

    public function reserveStockForProducts(array $productEntryQuantities, vRecordId $cartId) : ?bool
    {
        return true;
    }

    public function areProductsAvailableInStore(vRecordId $storeId, array $productIds) : ?array
    {
        $available = [];

        foreach($productIds as $productId)
        {
            if(!($productId instanceof vRecordId))
            {
                continue;
            }

            $available[] = [
                'productId' => $productId,
                'quantityAvailable' => 10
            ];
        }

        return $available;
    }
}

?>
