<?php

declare(strict_types = 1);

namespace Kickback\BackendV2\DAO;

use Kickback\Backend\Views\vRecordId;

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
}

?>