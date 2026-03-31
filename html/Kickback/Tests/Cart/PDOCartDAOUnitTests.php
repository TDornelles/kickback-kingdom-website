<?php

declare(strict_types=1);

namespace Kickback\Tests\Cart;

use Exception;
use Kickback\Backend\Views\vAccount;
use Kickback\Backend\Views\vCart;
use Kickback\Backend\Views\vCartItem;
use Kickback\Backend\Views\vItem;
use Kickback\Backend\Views\vPriceComponent;
use Kickback\Backend\Views\vProduct;
use Kickback\Backend\Views\vRecordId;
use Kickback\Backend\Views\vStore;
use Kickback\BackendV2\DAO\Cart\PDOCartDAO;
use Kickback\Tests\Tests;
use ReflectionClass;

final class PDOCartDAOUnitTests implements Tests
{
    private PDOCartDAO $dao;
    private ReflectionClass $daoReflection;

    public function __construct()
    {
        $this->daoReflection = new ReflectionClass(PDOCartDAO::class);
        $this->dao = $this->daoReflection->newInstanceWithoutConstructor();
    }

    public function runTests() : void
    {
        $this->unittest_validateCartForCheckout_sameOwner_returnsFalse();
        $this->unittest_validateCartForCheckout_emptyCart_returnsFalse();
        $this->unittest_validateCartForCheckout_validCart_returnsTrue();
        $this->unittest_countProductsInCart_duplicateProducts_countsByRecordId();
        $this->unittest_areProductCountsWithinAvailability_notEnoughStock_returnsFalse();
        $this->unittest_areProductCountsWithinAvailability_enoughStock_returnsTrue();
        $this->unittest_buildProductReservationEntries_aggregatesByProductId();
        $this->unittest_getItemTotals_filtersOutCurrencyAndZeroAmount();
        $this->unittest_getItemIdsFromTotals_returnsAllItemIds();
        $this->unittest_extractLootIdsFromEntries_returnsOnlyLootRecordIds();
        $this->unittest_buildExpectedPriceLootByLootId_twoPriceComponents_returnsAllRequiredLoot();
        $this->unittest_buildPriceLootTransferEntries_threePriceComponents_aggregatesDuplicateLootIds();
        $this->unittest_buildCartProductIdWhereClause_createsExpectedPredicate();
        $this->unittest_buildCartProductIdParams_flattensCartProductIds();
    }

    private function unittest_validateCartForCheckout_sameOwner_returnsFalse() : void
    {
        $cart = $this->makeCart('acct', 1, 'acct', 1, [static::makeCartItem('p1', 100)]);

        $result = $this->invokePrivate('validateCartForCheckout', [$cart]);

        if($result !== false) throw new Exception("Expected false when store owner equals account");
    }

    private function unittest_validateCartForCheckout_emptyCart_returnsFalse() : void
    {
        $cart = $this->makeCart('acct', 1, 'owner', 2, []);

        $result = $this->invokePrivate('validateCartForCheckout', [$cart]);

        if($result !== false) throw new Exception("Expected false when cart has no products");
    }

    private function unittest_validateCartForCheckout_validCart_returnsTrue() : void
    {
        $cart = $this->makeCart('acct', 1, 'owner', 2, [static::makeCartItem('p1', 100)]);

        $result = $this->invokePrivate('validateCartForCheckout', [$cart]);

        if($result !== true) throw new Exception("Expected true for a valid cart");
    }

    private function unittest_countProductsInCart_duplicateProducts_countsByRecordId() : void
    {
        $cartItems = [
            static::makeCartItem('p1', 100),
            static::makeCartItem('p1', 100),
            static::makeCartItem('p2', 200)
        ];

        $counts = $this->invokePrivate('countProductsInCart', [$cartItems]);

        if(($counts['p1|100'] ?? null) !== 2) throw new Exception("Expected p1|100 quantity to be 2");
        if(($counts['p2|200'] ?? null) !== 1) throw new Exception("Expected p2|200 quantity to be 1");
    }

    private function unittest_areProductCountsWithinAvailability_notEnoughStock_returnsFalse() : void
    {
        $productCounts = ['p1|100' => 2];
        $availability = [
            ['productId' => new vRecordId('p1', 100), 'quantityAvailable' => 1]
        ];

        $result = $this->invokePrivate('areProductCountsWithinAvailability', [$productCounts, $availability]);

        if($result !== false) throw new Exception("Expected false when required quantity exceeds availability");
    }

    private function unittest_areProductCountsWithinAvailability_enoughStock_returnsTrue() : void
    {
        $productCounts = ['p1|100' => 2, 'p2|200' => 1];
        $availability = [
            ['productId' => new vRecordId('p1', 100), 'quantityAvailable' => 3],
            ['productId' => new vRecordId('p2', 200), 'quantityAvailable' => 1],
            ['productId' => null, 'quantityAvailable' => 50]
        ];

        $result = $this->invokePrivate('areProductCountsWithinAvailability', [$productCounts, $availability]);

        if($result !== true) throw new Exception("Expected true when all product counts are available");
    }

    private function unittest_buildProductReservationEntries_aggregatesByProductId() : void
    {
        $cart = $this->makeCart(
            'acct',
            1,
            'owner',
            2,
            [
                static::makeCartItem('p1', 100),
                static::makeCartItem('p1', 100),
                static::makeCartItem('p2', 200)
            ]
        );

        $entries = $this->invokePrivate('buildProductReservationEntries', [$cart]);

        if(count($entries) !== 2) throw new Exception("Expected exactly two reservation entries");

        $byKey = [];
        foreach($entries as $entry)
        {
            $key = $entry['productId']->ctime . '|' . $entry['productId']->crand;
            $byKey[$key] = $entry['quantity'];
        }

        if(($byKey['p1|100'] ?? null) !== 2) throw new Exception("Expected p1|100 reservation quantity to be 2");
        if(($byKey['p2|200'] ?? null) !== 1) throw new Exception("Expected p2|200 reservation quantity to be 1");
    }

    private function unittest_getItemTotals_filtersOutCurrencyAndZeroAmount() : void
    {
        $item = new vItem('item1', 11);
        $totals = [
            new vPriceComponent('pc1', 1, 5, $item),
            new vPriceComponent('pc2', 2, 7, null),
            new vPriceComponent('pc3', 3, 0, new vItem('item2', 22))
        ];

        $result = $this->invokePrivate('getItemTotals', [$totals]);

        if(count($result) !== 1) throw new Exception("Expected only one item total after filtering");
        if($result[0]->item->crand !== 11) throw new Exception("Expected item total for item crand=11");
        if($result[0]->amount !== 5) throw new Exception("Expected filtered item total amount to remain 5");
    }

    private function unittest_getItemIdsFromTotals_returnsAllItemIds() : void
    {
        $totals = [
            new vPriceComponent('pc1', 1, 5, new vItem('item1', 11)),
            new vPriceComponent('pc2', 2, 3, null),
            new vPriceComponent('pc3', 3, 1, new vItem('item2', 22))
        ];

        $itemIds = $this->invokePrivate('getItemIdsFromTotals', [$totals]);

        if(count($itemIds) !== 2) throw new Exception("Expected two item ids");
        if($itemIds[0] !== 11 || $itemIds[1] !== 22) throw new Exception("Expected item ids [11, 22]");
    }

    private function unittest_extractLootIdsFromEntries_returnsOnlyLootRecordIds() : void
    {
        $entries = [
            ['lootId' => new vRecordId('loot1', 101), 'quantity' => 1],
            ['lootId' => null, 'quantity' => 2],
            ['notLootId' => new vRecordId('loot2', 202)],
            'invalid entry'
        ];

        $lootIds = $this->invokePrivate('extractLootIdsFromEntries', [$entries]);

        if(count($lootIds) !== 1) throw new Exception("Expected one valid loot id entry");
        if($lootIds[0]->crand !== 101) throw new Exception("Expected loot id crand=101");
    }

    private function unittest_buildExpectedPriceLootByLootId_twoPriceComponents_returnsAllRequiredLoot() : void
    {
        $lootEntryQuantities = [
            static::makeLootEntry('loot1', 101, 2),
            static::makeLootEntry('loot2', 202, 5)
        ];

        $expectedByLootId = $this->invokePrivate('buildExpectedPriceLootByLootId', [$lootEntryQuantities]);

        $expected = [
            101 => 2,
            202 => 5
        ];

        if($expectedByLootId !== $expected) throw new Exception("Expected checkout price loot requirements to preserve both price components");
    }

    private function unittest_buildPriceLootTransferEntries_threePriceComponents_aggregatesDuplicateLootIds() : void
    {
        $lootEntryQuantities = [
            static::makeLootEntry('loot1', 101, 2),
            static::makeLootEntry('loot2', 202, 3),
            static::makeLootEntry('loot1-duplicate', 101, 4)
        ];

        $transferEntries = $this->invokePrivate('buildPriceLootTransferEntries', [$lootEntryQuantities]);

        if(count($transferEntries) !== 2) throw new Exception("Expected duplicate price-component loot ids to be aggregated into two transfer entries");

        $byLootId = [];
        foreach($transferEntries as $entry)
        {
            $byLootId[$entry['lootId']] = $entry['quantity'];
        }

        if(($byLootId[101] ?? null) !== 6) throw new Exception("Expected loot id 101 quantity to aggregate to 6 across two price components");
        if(($byLootId[202] ?? null) !== 3) throw new Exception("Expected loot id 202 quantity to remain 3 for the third price component");
    }

    private function unittest_buildCartProductIdWhereClause_createsExpectedPredicate() : void
    {
        $cartProducts = [
            new vCartItem('cp1', 10),
            new vCartItem('cp2', 20)
        ];

        $where = $this->invokePrivate('buildCartProductIdWhereClause', [$cartProducts]);

        $expected = "(ref_cart_product_link_ctime = ? AND ref_cart_product_link_crand = ?) OR (ref_cart_product_link_ctime = ? AND ref_cart_product_link_crand = ?)";
        if($where !== $expected) throw new Exception("Unexpected where-clause output for cart product id predicates");
    }

    private function unittest_buildCartProductIdParams_flattensCartProductIds() : void
    {
        $cartProducts = [
            new vCartItem('cp1', 10),
            new vCartItem('cp2', 20)
        ];

        $params = $this->invokePrivate('buildCartProductIdParams', [$cartProducts]);

        $expected = ['cp1', 10, 'cp2', 20];
        if($params !== $expected) throw new Exception("Unexpected parameter list for cart product id predicates");
    }

    private function makeCart(string $accountCtime, int $accountCrand, string $ownerCtime, int $ownerCrand, array $cartProducts) : vCart
    {
        $cart = new vCart('cartCtime', 999);
        $cart->account = new vAccount($accountCtime, $accountCrand);
        $cart->store = new vStore('storeCtime', 500);
        $cart->store->owner = new vAccount($ownerCtime, $ownerCrand);
        $cart->cartProducts = $cartProducts;
        $cart->totals = [];

        return $cart;
    }

    private static function makeCartItem(string $productCtime, int $productCrand) : vCartItem
    {
        $cartItem = new vCartItem('cartProductCtime', -1);
        $cartItem->product = new vProduct($productCtime, $productCrand);

        return $cartItem;
    }

    private static function makeLootEntry(string $lootCtime, int $lootCrand, int $quantity) : array
    {
        return [
            'lootId' => new vRecordId($lootCtime, $lootCrand),
            'quantity' => $quantity
        ];
    }

    private function invokePrivate(string $methodName, array $args = [])
    {
        $method = $this->daoReflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($this->dao, $args);
    }
}

