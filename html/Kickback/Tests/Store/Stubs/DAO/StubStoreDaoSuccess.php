<?php

declare(strict_types=1);

namespace Kickback\Tests\Store\Stubs\DAO;

use Kickback\BackendV2\DAO\Store\StoreDAO;
use Kickback\Backend\Views\vRecordId;
use Kickback\Backend\Views\vStore;

final class StubStoreDAOSuccess implements StoreDAO
{
    public function getStoreByLocator(string $locator): ?vStore
    {
        return new vStore('testCtime', -1);
    }

    public function doesStoreExistById(vRecordId $storeId): ?bool
    {
        return true;
    }

    public function doesStoreExistByLocator(string $storeLocator): ?bool
    {
        return true;
    }
}

