<?php

declare(strict_types=1);

namespace Kickback\Tests\Store\Stubs\DAO;

use Exception;
use Kickback\BackendV2\DAO\Store\StoreDAO;
use Kickback\Backend\Views\vRecordId;
use Kickback\Backend\Views\vStore;

final class StubStoreDAOException implements StoreDAO
{
    public function getStoreByLocator(string $locator): ?vStore
    {
        throw new Exception("Stub Exception : Failed to get store by locator");
    }

    public function doesStoreExistById(vRecordId $storeId): ?bool
    {
        throw new Exception("Stub Exception : Failed to check store exists by id");
    }

    public function doesStoreExistByLocator(string $storeLocator): ?bool
    {
        throw new Exception("Stub Exception : Failed to check store exists by locator");
    }
}

