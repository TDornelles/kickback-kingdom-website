<?php

declare(strict_types=1);

namespace Kickback\Tests\Stubs\Store\DAO;

use Kickback\Backend\Views\vRecordId;
use Kickback\Backend\Views\vStore;
use Kickback\BackendV2\DAO\Store\StoreDAO;

final class StubStoreDAOExistsFalse implements StoreDAO
{
    public function getStoreByLocator(string $locator): ?vStore
    {
        return new vStore('testCtime', -1);
    }

    public function doesStoreExistById(vRecordId $storeId): ?bool
    {
        return false;
    }

    public function doesStoreExistByLocator(string $storeLocator): ?bool
    {
        return false;
    }
}

