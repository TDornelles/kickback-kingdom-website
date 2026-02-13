<?php

declare(strict_types=1);

namespace Kickback\Tests\Loot;

use Exception;
use Kickback\Backend\Views\vItem;
use Kickback\Backend\Views\vPriceComponent;
use Kickback\Backend\Views\vRecordId;
use Kickback\Backend\Views\vLootReservation;
use Kickback\BackendV2\DAO\Loot\PDOLootDAO;
use Kickback\Tests\Tests;
use ReflectionClass;
use RuntimeException;

final class PDOLootDAOUnitTests implements Tests
{
    private PDOLootDAO $dao;
    private ReflectionClass $daoReflection;

    public function __construct()
    {
        $this->daoReflection = new ReflectionClass(PDOLootDAO::class);
        $this->dao = $this->daoReflection->newInstanceWithoutConstructor();
    }

    public function runTests() : void
    {
        $this->unittest_coalesceLootArray_filtersCurrencyAndZeroAmountTotals();
        $this->unittest_getItemIdsFromTotals_returnsItemCrands();
        $this->unittest_getLootCrandsFromLoots_deduplicatesAndIgnoresInvalidValues();
        $this->unittest_buildReservationWhereClauseAndParams_buildExpectedOutput();
        $this->unittest_buildLootEntryQuantities_keepsOnlyPositiveQuantities();
        $this->unittest_consolidateLootForTotals_allocatesRequiredQuantities();
        $this->unittest_consolidateLootForTotals_throwsWhenLootIsInsufficient();
    }

    private function unittest_coalesceLootArray_filtersCurrencyAndZeroAmountTotals() : void
    {
        $totals = [
            new vPriceComponent('pc1', 1, 3, new vItem('item1', 11)),
            new vPriceComponent('pc2', 2, 5, null),
            new vPriceComponent('pc3', 3, 0, new vItem('item2', 22))
        ];

        $coalesced = $this->invokePrivate('coalesceLootArray', [$totals]);

        if(count($coalesced) !== 1) throw new Exception("Expected one coalesced loot total");
        if($coalesced[0]->item->crand !== 11) throw new Exception("Expected item 11 to remain after coalesce");
    }

    private function unittest_getItemIdsFromTotals_returnsItemCrands() : void
    {
        $totals = [
            new vPriceComponent('pc1', 1, 3, new vItem('item1', 11)),
            new vPriceComponent('pc2', 2, 3, null),
            new vPriceComponent('pc3', 3, 1, new vItem('item2', 22))
        ];

        $itemIds = $this->invokePrivate('getItemIdsFromTotals', [$totals]);

        if($itemIds !== [11, 22]) throw new Exception("Expected item ids [11, 22]");
    }

    private function unittest_getLootCrandsFromLoots_deduplicatesAndIgnoresInvalidValues() : void
    {
        $lootObject = new \stdClass();
        $lootObject->crand = 5;

        $lootCrands = $this->invokePrivate(
            'getLootCrandsFromLoots',
            [[new vRecordId('l1', 5), new vRecordId('l2', 6), $lootObject, new \stdClass(), new vRecordId('l1', 5)]]
        );

        if($lootCrands !== [5, 6]) throw new Exception("Expected deduplicated loot crands [5, 6]");
    }

    private function unittest_buildReservationWhereClauseAndParams_buildExpectedOutput() : void
    {
        $reservations = [
            new vLootReservation('r1', 10),
            new vLootReservation('r2', 20)
        ];

        $whereClause = $this->invokePrivate('buildReservationWhereClause', [$reservations]);
        $params = $this->invokePrivate('buildReservationParams', [$reservations]);

        $expectedWhere = "(ctime = ? AND crand = ?) OR (ctime = ? AND crand = ?)";
        if($whereClause !== $expectedWhere) throw new Exception("Unexpected loot reservation where clause");
        if($params !== ['r1', 10, 'r2', 20]) throw new Exception("Unexpected loot reservation params");
    }

    private function unittest_buildLootEntryQuantities_keepsOnlyPositiveQuantities() : void
    {
        $loot1 = new \stdClass();
        $loot1->ctime = 'l1';
        $loot1->crand = 1;
        $loot1->quantity = 2;

        $loot2 = new \stdClass();
        $loot2->ctime = 'l2';
        $loot2->crand = 2;
        $loot2->quantity = 0;

        $entries = $this->invokePrivate('buildLootEntryQuantities', [[$loot1, $loot2]]);

        if(count($entries) !== 1) throw new Exception("Expected one loot entry with positive quantity");
        if($entries[0]['quantity'] !== 2) throw new Exception("Expected quantity=2 on remaining loot entry");
        if($entries[0]['lootId']->crand !== 1) throw new Exception("Expected lootId crand=1 on remaining loot entry");
    }

    private function unittest_consolidateLootForTotals_allocatesRequiredQuantities() : void
    {
        $totals = [
            new vPriceComponent('pc1', 1, 3, new vItem('item1', 11))
        ];

        $loot1 = new \stdClass();
        $loot1->ctime = 'l1';
        $loot1->crand = 1;
        $loot1->quantity = 2;
        $loot1->item = new vItem('item1', 11);

        $loot2 = new \stdClass();
        $loot2->ctime = 'l2';
        $loot2->crand = 2;
        $loot2->quantity = 5;
        $loot2->item = new vItem('item1', 11);

        $consolidated = $this->invokePrivate('consolidateLootForTotals', [$totals, [$loot1, $loot2]]);

        if(count($consolidated) !== 2) throw new Exception("Expected two consolidated loot entries");
        if($consolidated[0]->quantity !== 2) throw new Exception("Expected first consolidated quantity to be 2");
        if($consolidated[1]->quantity !== 1) throw new Exception("Expected second consolidated quantity to be 1");
    }

    private function unittest_consolidateLootForTotals_throwsWhenLootIsInsufficient() : void
    {
        $totals = [
            new vPriceComponent('pc1', 1, 3, new vItem('item1', 11))
        ];

        $loot = new \stdClass();
        $loot->ctime = 'l1';
        $loot->crand = 1;
        $loot->quantity = 1;
        $loot->item = new vItem('item1', 11);

        try
        {
            $this->invokePrivate('consolidateLootForTotals', [$totals, [$loot]]);
        }
        catch(RuntimeException $e)
        {
            return;
        }

        throw new Exception("Expected RuntimeException when loot is insufficient for totals");
    }

    private function invokePrivate(string $methodName, array $args = [])
    {
        $method = $this->daoReflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($this->dao, $args);
    }
}

