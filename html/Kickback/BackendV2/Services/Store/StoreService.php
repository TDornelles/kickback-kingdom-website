<?php

declare(strict_types = 1);

namespace Kickback\BackendV2\Services;

use Kickback\Backend\Models\Response;

interface StoreService
{
    /**
     * Returns the store which matches the locator given
     * 
     * @param string $locator the locator to match for the returned store within the response
     * 
     * @return ?Response the returned response object whose data returns a vStore object of the matched store
     */
    public function getStoreByLocator(string $locator) : Response;
}

?>