<?php

declare(strict_types=1);

namespace Kickback\Tests\Stubs\Product\DAO;

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

    public function closeProductReservations(array $reservations) : void
    {
        throw new Exception("Stub Exception : Failed to close product reservations");
    }

    public function getActiveProductReservationsForCart(vRecordId $cartId) : array
    {
        throw new Exception("Stub Exception : Failed to get active product reservations");
    }

    public function getProductAmountAvailable(vRecordId $productId) : ?int
    {
        throw new Exception("Stub Exception : Failed to get product amount available");
    }

    public function getBasePriceComponentIdsForProduct(vRecordId $productId) : ?array
    {
        throw new Exception("Stub Exception : Failed to get base price component ids");
    }

    public function reserveStockForProducts(array $productEntryQuantities, vRecordId $cartId) : ?bool
    {
        throw new Exception("Stub Exception : Failed to reserve stock for products");
    }

    public function areProductsAvailableInStore(vRecordId $storeId, array $productIds) : ?array
    {
        throw new Exception("Stub Exception : Failed to get product availability");
    }
}

?>
