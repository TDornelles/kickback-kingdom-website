<?php

declare(strict_types = 1);

namespace Kickback\BackendV2\DAO\Product;

use Kickback\Backend\Views\vRecordId;
use Kickback\Backend\Views\vProduct;

interface ProductDAO
{
    /**
     * Returns all the products for a store with the matching Id
     * 
     * @param vRecordId $storeId the id of the store for which to get all products
     * 
     * @return ?array the array of vProduct objects for the matching
     */
    public function getProductsForStoreByStoreId(vRecordId $storeId) : ?array;

    /**
     * Returns a product by locator with its base price populated
     *
     * @param string $productLocator the locator to lookup
     *
     * @return ?vProduct the product view or null on failure/not found
     */
    public function getProductByLocator(string $productLocator) : ?vProduct;
}

?>
