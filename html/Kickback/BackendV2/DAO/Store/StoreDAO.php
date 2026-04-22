<?php

declare(strict_types = 1);

namespace Kickback\BackendV2\DAO\Store;

use Kickback\Backend\Views\vRecordId;
use Kickback\Backend\Views\vStore;

interface StoreDAO
{
    /**
     * Returns the store which matches the locator given
     * 
     * @param string $locator the locator to match for the returned store within the response
     * 
     * @return ?vStore the matching store; null if no matching store found
     */
    public function getStoreByLocator(string $locator) : ?vStore;

    /**
     * Returns whether the given store exist based on its id
     * 
     * @param vRecordId $storeId the storeId to check if a store with this id exists
     * 
     * @return bool the returned bool which represents if the given store exists
     */
    public function doesStoreExistById(vRecordId $storeId) : ?bool;

    /**
     * Returns whether the given store exist based on its locator
     * 
     * @param string $storeLocator the locator to check if a store with this locator exists
     * 
     * @return bool the returned bool which represents if the given store exists
     */
    public function doesStoreExistByLocator(string $storeLocator) : ?bool;
}

?>