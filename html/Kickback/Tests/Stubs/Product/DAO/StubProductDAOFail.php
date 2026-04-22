<?php

declare(strict_types=1);

namespace Kickback\Tests\Stubs\Product\DAO;

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

    public function closeProductReservations(array $reservations) : void
    {
    }

    public function getActiveProductReservationsForCart(vRecordId $cartId) : array
    {
        return [];
    }

    public function getProductAmountAvailable(vRecordId $productId) : ?int
    {
        return null;
    }

    public function getBasePriceComponentIdsForProduct(vRecordId $productId) : ?array
    {
        return null;
    }

    public function reserveStockForProducts(array $productEntryQuantities, vRecordId $cartId) : ?bool
    {
        return false;
    }

    public function areProductsAvailableInStore(vRecordId $storeId, array $productIds) : ?array
    {
        return null;
    }
}

?>
