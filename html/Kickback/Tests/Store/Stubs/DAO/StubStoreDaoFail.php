<?php

declare(strict_types=1);

namespace Kickback\Tests\Store\Stubs\DAO;

use Kickback\BackendV2\DAO\Store\StoreDAO;
use Kickback\Backend\Views\vRecordId;
use Kickback\Backend\Views\vStore;

final class StubStoreDAOFail implements StoreDAO
{
    public function getStoreByLocator(string $locator): ?vStore
    {
        return null;
    }

    public function doesStoreExistById(vRecordId $storeId): ?bool
    {
        return null;
    }

    public function doesStoreExistByLocator(string $storeLocator): ?bool
    {
        return null;
    }
}

