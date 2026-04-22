<?php

declare(strict_types=1);

namespace Kickback\BackendV2\DAO\Loot;

use Kickback\Backend\Views\vRecordId;

interface LootDAO
{
    /**
     * Reserves loots for a number of seconds, marking them as unavaiable for a period of time
     * 
     * @param array $lootEntryQuantities array of ["lootId" => vRecordId, "quantity" => int]
     * @param int $reservationSeconds seconds until reservation expiry
     * @return ?bool true on success, null on failure
     */
    public function reserveLoots(array $lootEntryQuantities, int $reservationSeconds) : ?bool;

    /**
     * Returns active loot reservations for the provided loot ids
     * 
     * @param array $loots array of vRecordId loot ids
     * 
     * @return vLootReservation[] array of vLootReservation objects representing active reservations for the provided loot ids
     */
    public function getActiveLootReservationsForLoots(array $loots) : array;

    /**
     * Gets loot entry ids for the provided items and quantities
     *
     * @param vRecordId $accountId
     * @param array $totals array of totals ["itemId" => vRecordId, "quantity" => int]
     * @return array array of ["lootId" => vRecordId, "quantity" => int]
     */
    public function getLootFromAccountForItems(vRecordId $accountId, array $totals) : array;

    /**
     * Closes loot reservations
     * 
     * @param vLootReservation[] $reservations array of vLootReservation objects to close
     * 
     * @return void
     */
    public function closeLootReservations(array $reservations) : void;
}

?>
