<?php

declare(strict_types = 1);

namespace Kickback\BackendV2\DAO;

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
}

?>