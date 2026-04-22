<?php

declare(strict_types=1);

namespace Kickback\Tests\Store;

use Exception;
use Kickback\BackendV2\DAO\Store\PDOStoreDAO;
use Kickback\Tests\Tests;

final class PDOStoreDAOUnitTests implements Tests
{
    public function runTests() : void
    {
        $this->unittest_rowToVStore_mapsRowIntoStoreView();
    }

    private function unittest_rowToVStore_mapsRowIntoStoreView() : void
    {
        $row = [
            'ctime' => 'storeCtime',
            'crand' => 10,
            'name' => 'Test Store',
            'locator' => 'TEST_STORE',
            'description' => 'Test description',
            'owner_username' => 'ownerUser',
            'owner_ctime' => 'ownerCtime',
            'owner_crand' => 77
        ];

        $store = PDOStoreDAO::rowToVStore($row);

        if($store->name !== 'Test Store') throw new Exception("Expected store name to be mapped");
        if($store->locator !== 'TEST_STORE') throw new Exception("Expected store locator to be mapped");
        if($store->ownerUsername !== 'ownerUser') throw new Exception("Expected owner username to be mapped");
        if($store->owner->crand !== 77) throw new Exception("Expected owner crand to be mapped");
    }
}

