<?php

declare(strict_types=1);

namespace Kickback\Tests\Product;

use Exception;
use Kickback\Backend\Views\vRecordId;
use Kickback\BackendV2\DAO\Product\PDOProductDAO;
use Kickback\Tests\Tests;
use ReflectionClass;

final class PDOProductDAOUnitTests implements Tests
{
    private PDOProductDAO $dao;
    private ReflectionClass $daoReflection;

    public function __construct()
    {
        $this->daoReflection = new ReflectionClass(PDOProductDAO::class);
        $this->dao = $this->daoReflection->newInstanceWithoutConstructor();
    }

    public function runTests() : void
    {
        $this->unittest_buildProductsWhereClauseAndParams_createsExpectedPredicateAndParams();
        $this->unittest_areProductCountsAvailable_returnsTrueWhenCountsFitAvailability();
        $this->unittest_areProductCountsAvailable_returnsFalseWhenProductMissingOrInsufficient();
        $this->unittest_buildProductReservationsFromEntries_filtersInvalidEntries();
        $this->unittest_buildProductReservationInsert_createsValueClauseAndParams();
        $this->unittest_rowToVProduct_mapsRowIntoView();
    }

    private function unittest_buildProductsWhereClauseAndParams_createsExpectedPredicateAndParams() : void
    {
        $productCounts = [
            'p1|100' => 2,
            'p2|200' => 1
        ];

        $whereClause = $this->invokePrivate('buildProductsWhereClause', [$productCounts]);
        $params = $this->invokePrivate('buildProductsWhereParams', [$productCounts]);

        $expectedWhere = "(ctime = ? AND crand = ?) OR (ctime = ? AND crand = ?)";
        if($whereClause !== $expectedWhere) throw new Exception("Unexpected where clause for product counts");
        if($params !== ['p1', '100', 'p2', '200']) throw new Exception("Unexpected params for product counts where clause");
    }

    private function unittest_areProductCountsAvailable_returnsTrueWhenCountsFitAvailability() : void
    {
        $productCounts = ['p1|100' => 2];
        $productsInStore = [
            ['ctime' => 'p1', 'crand' => 100, 'removed' => 0, 'amount_available' => 2]
        ];

        $result = $this->invokePrivate('areProductCountsAvailable', [$productCounts, $productsInStore]);

        if($result !== true) throw new Exception("Expected availability check to return true");
    }

    private function unittest_areProductCountsAvailable_returnsFalseWhenProductMissingOrInsufficient() : void
    {
        $resultMissing = $this->invokePrivate(
            'areProductCountsAvailable',
            [
                ['p1|100' => 1],
                []
            ]
        );
        if($resultMissing !== false) throw new Exception("Expected false when product is missing");

        $resultInsufficient = $this->invokePrivate(
            'areProductCountsAvailable',
            [
                ['p1|100' => 2],
                [['ctime' => 'p1', 'crand' => 100, 'removed' => 0, 'amount_available' => 1]]
            ]
        );
        if($resultInsufficient !== false) throw new Exception("Expected false when stock is insufficient");
    }

    private function unittest_buildProductReservationsFromEntries_filtersInvalidEntries() : void
    {
        $entries = [
            ['productId' => new vRecordId('p1', 100), 'quantity' => 2],
            ['productId' => new vRecordId('p2', 200), 'quantity' => 0],
            ['productId' => null, 'quantity' => 0],
            'invalid'
        ];
        $cartId = new vRecordId('cart', 1);

        $reservations = $this->invokePrivate('buildProductReservationsFromEntries', [$entries, $cartId]);

        if(count($reservations) !== 1) throw new Exception("Expected exactly one reservation after filtering");
        if($reservations[0]->quantity !== 2) throw new Exception("Expected reservation quantity to be 2");
    }

    private function unittest_buildProductReservationInsert_createsValueClauseAndParams() : void
    {
        $entries = [
            ['productId' => new vRecordId('p1', 100), 'quantity' => 2]
        ];
        $cartId = new vRecordId('cart', 1);
        $reservations = $this->invokePrivate('buildProductReservationsFromEntries', [$entries, $cartId]);

        $params = [];
        $valueClause = $this->invokePrivate('buildProductReservationInsert', [$reservations, &$params]);

        if($valueClause !== "(?,?,?,?,?,?,?,?,?)") throw new Exception("Unexpected reservation insert value clause");
        if(count($params) !== 9) throw new Exception("Expected nine parameters for one reservation insert clause");
    }

    private function unittest_rowToVProduct_mapsRowIntoView() : void
    {
        $row = [
            'ctime' => 'productCtime',
            'crand' => 123,
            'locator' => 'TEST_PRODUCT',
            'tag' => 'tag',
            'categories' => '["cat1","cat2"]',
            'name' => 'Test Product',
            'description' => 'Desc',
            'stock' => 10,
            'amount_available' => 8,
            'removed' => 0,
            'store_owner_ctime' => 'ownerCtime',
            'store_owner_crand' => 77,
            'store_owner_username' => 'owner',
            'small_media_media_path' => null,
            'large_media_media_path' => null,
            'back_media_media_path' => null,
            'store_ctime' => 'storeCtime',
            'store_crand' => 55,
            'store_name' => 'Store',
            'store_description' => 'StoreDesc',
            'store_locator' => 'TEST_STORE'
        ];

        $product = $this->invokePrivateStatic('rowToVProduct', [$row]);

        if($product->locator !== 'TEST_PRODUCT') throw new Exception("Expected locator to be mapped");
        if($product->stock !== 10) throw new Exception("Expected stock to be mapped");
        if($product->store->locator !== 'TEST_STORE') throw new Exception("Expected store locator to be mapped");
        if($product->owner->username !== 'owner') throw new Exception("Expected owner username to be mapped");
    }

    private function invokePrivate(string $methodName, array $args = [])
    {
        $method = $this->daoReflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($this->dao, $args);
    }

    private function invokePrivateStatic(string $methodName, array $args = [])
    {
        $method = $this->daoReflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs(null, $args);
    }
}
