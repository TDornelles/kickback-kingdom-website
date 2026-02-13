<?php

declare(strict_types = 1);

namespace Kickback\BackendV2\Services\Store;

use Kickback\Backend\Models\Response;
use Kickback\Backend\Views\vRecordId;

interface StoreService
{
    /**
     * Returns the store which matches the locator given
     * 
     * @param string $locator the locator to match for the returned store within the response
     * 
     * @return Response the returned response object whose data returns a vStore object of the matched store
     */
    public function getStoreByLocator(string $locator) : Response;

    /**
     * Returns whether the given store exist based on its id
     * 
     * @param vRecordId $storeId the storeId to check if a store with this id exists
     * 
     * @return Response the returend response object whose data returns the state of if the given store exists
     */
    public function doesStoreExistById(vRecordId $storeId) : Response;

    /**
     * Returns whether the given store exist based on its locator
     * 
     * @param string $storeLocator the locator to check if a store with this locator exists
     * 
     * @return Response the returend response object whose data returns the state of if the given store exists
     */
    public function doesStoreExistByLocator(string $storeLocator) : Response;
}

?>
