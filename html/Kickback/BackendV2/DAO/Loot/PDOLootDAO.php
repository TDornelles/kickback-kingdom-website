<?php

declare(strict_types = 1);

namespace Kickback\BackendV2\DAO\Loot;

use Exception;
use Kickback\Backend\Models\LootReservation;
use Kickback\Backend\Views\vLootReservation;
use Kickback\Backend\Views\vRecordId;
use Kickback\BackendV2\Persistance\Database;
use DateTime;
use LogicException;
use PDO;
use RuntimeException;

class PDOLootDAO implements LootDAO
{
    private Database $pdo;

    public function __construct(?Database $pdo = null)
    {
        $this->pdo = $pdo ?? new Database();
    }

    /**
     * Reserves loot by inserting rows into the loot_reservation table.
     *
     * @param array $lootEntryQuantities array of ["lootId" => vRecordId, "quantity" => int]
     * @param int $reservationSeconds seconds until reservation expiry
     */
    public function reserveLoots(array $lootEntryQuantities, int $reservationSeconds) : ?bool
    {
        if (empty($lootEntryQuantities))
        {
            return true;
        }

        $params = [];
        $valueClause = $this->buildLootReservationInsert($lootEntryQuantities, $params, $reservationSeconds);
        if (empty($valueClause))
        {
            return true;
        }

        $sql = "INSERT INTO loot_reservation (
            ctime,
            crand,
            ref_loot_ctime,
            ref_loot_crand,
            quantity,
            expiry_time,
            close_time)
            VALUES $valueClause";

        $conn = $this->pdo->getConnection();
        $stmt = $conn->prepare($sql);
        if ($stmt === false)
        {
            return null;
        }

        $result = $stmt->execute($params);
        if ($result === false)
        {
            return null;
        }

        return true;
    }

    /**
     * Closes loot reservations by setting close_time.
     *
     * @param array $reservations array of reservation views with ctime/crand
     */
    public function closeLootReservations(array $reservations) : void
    {
        if (empty($reservations))
        {
            return;
        }

        $whereClause = $this->buildReservationWhereClause($reservations);
        $sql = "UPDATE loot_reservation SET close_time = NOW() WHERE $whereClause";
        $params = $this->buildReservationParams($reservations);

        $conn = $this->pdo->getConnection();
        $stmt = $conn->prepare($sql);
        if ($stmt === false)
        {
            throw new Exception("Failed to prepare close loot reservations");
        }

        $result = $stmt->execute($params);
        if ($result === false)
        {
            throw new Exception("Failed to close loot reservations");
        }
    }

    /**
     * Returns active loot reservations for the provided loots.
     *
     * @param array $loots array of vRecordId or vLoot
     * @return vLootReservation[] active reservations
     */
    public function getActiveLootReservationsForLoots(array $loots) : array
    {
        $lootCrands = $this->getLootCrandsFromLoots($loots);
        if (empty($lootCrands))
        {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($lootCrands), '?'));
        $sql = "SELECT ctime, crand, loot_ctime, loot_crand, quantity, expiry_time, close_time
            FROM v_loot_reservation
            WHERE close_time IS NULL AND expiry_time > NOW() AND loot_crand IN ($placeholders)";

        $conn = $this->pdo->getConnection();
        $stmt = $conn->prepare($sql);
        if ($stmt === false)
        {
            return [];
        }

        $result = $stmt->execute($lootCrands);
        if ($result === false)
        {
            return [];
        }

        $reservations = [];
        while($row = $stmt->fetch(PDO::FETCH_ASSOC))
        {
            $lootId = new vRecordId($row["loot_ctime"], (int)$row["loot_crand"]);
            $expiryTime = DateTime::createFromFormat('Y-m-d H:i:s.u', $row["expiry_time"]);
            if ($expiryTime === false && !empty($row["expiry_time"]))
            {
                $expiryTime = new DateTime($row["expiry_time"]);
            }

            $closeTime = null;
            if (!empty($row["close_time"]))
            {
                $closeTime = DateTime::createFromFormat('Y-m-d H:i:s.u', $row["close_time"]);
                if ($closeTime === false)
                {
                    $closeTime = new DateTime($row["close_time"]);
                }
            }

            $reservation = new vLootReservation(
                $row["ctime"],
                (int)$row["crand"],
                $lootId,
                (int)$row["quantity"],
                $expiryTime,
                $closeTime
            );

            $reservations[] = $reservation;
        }

        return $reservations;
    }

    public function getLootFromAccountForItems(vRecordId $accountId, array $itemQuantities) : array
    {
        $itemTotals = $this->coalesceLootArray($itemQuantities);
        if (empty($itemTotals))
        {
            return [];
        }

        $lootForTotals = $this->getLootForTotals($accountId, $itemTotals);
        $consolidatedLoot = $this->consolidateLootForTotals($itemTotals, $lootForTotals);

        return $this->buildLootEntryQuantities($consolidatedLoot);
    }

    private function buildLootReservationInsert(array $lootEntryQuantities, array &$params, int $reservationSeconds) : string
    {
        $valueClause = "";

        foreach ($lootEntryQuantities as $entry)
        {
            if (!is_array($entry))
            {
                continue;
            }

            $lootId = $entry["lootId"] ?? null;
            $quantity = isset($entry["quantity"]) ? (int)$entry["quantity"] : 0;

            if ($quantity <= 0)
            {
                continue;
            }

            if (!($lootId instanceof vRecordId))
            {
                throw new Exception("Loot entry must contain a vRecordId in 'lootId'");
            }

            $reservation = new LootReservation($lootId, $quantity, null, null);
            $expiryTime = new DateTime($reservation->ctime);
            $expiryTime->modify("+" . $reservationSeconds . " seconds");
            $formattedExpiryTime = $expiryTime->format("Y-m-d H:i:s.u");

            if(!empty($valueClause)) $valueClause .= ", ";
            $valueClause .= "(?,?,?,?,?,?,?)";

            $params[] = $reservation->ctime;
            $params[] = $reservation->crand;
            $params[] = $reservation->lootId->ctime;
            $params[] = $reservation->lootId->crand;
            $params[] = $reservation->quantity;
            $params[] = $formattedExpiryTime;
            $params[] = null;
        }

        return $valueClause;
    }

    private function buildLootEntryQuantities(array $lootEntries) : array
    {
        $lootEntryQuantities = [];

        foreach ($lootEntries as $loot)
        {
            $quantity = (int)($loot->quantity ?? 0);
            if ($quantity <= 0)
            {
                continue;
            }

            $lootId = new vRecordId($loot->ctime ?? '', $loot->crand);
            $lootEntryQuantities[] = [
                "lootId" => $lootId,
                "quantity" => $quantity
            ];
        }

        return $lootEntryQuantities;
    }

    private function getLootForTotals(vRecordId $accountId, array $totals) : array
    {
        $itemIds = $this->getItemIdsFromTotals($totals);
        if (empty($itemIds))
        {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($itemIds), '?'));
        $sql = "
            SELECT 
                vli.Id,
                vli.opened,
                vli.account_id,
                vli.item_id,
                vli.quest_id,
                vli.media_id_small,
                vli.media_id_large,
                vli.media_id_back,
                vli.loot_type,
                vli.`desc`,
                vli.rarity,
                vli.dateObtained,
                vli.container_loot_id,
                vli.quantity,
                vi.Id as item_id,
                vi.name,
                vi.is_fungible
            FROM v_loot_item vli
            JOIN v_item_info vi ON vli.item_id = vi.id
            WHERE account_id = ? AND vli.quantity_available > 0 AND item_id IN ($placeholders);";

        $params = array_merge([$accountId->crand], $itemIds);

        $conn = $this->pdo->getConnection();
        $stmt = $conn->prepare($sql);
        if ($stmt === false)
        {
            throw new Exception("Failed to prepare loot query");
        }

        $result = $stmt->execute($params);
        if ($result === false)
        {
            throw new Exception("Failed to execute loot query");
        }

        $loot = [];
        while($row = $stmt->fetch(PDO::FETCH_ASSOC))
        {
            $lootRow = \Kickback\Backend\Controllers\LootController::row_to_vLoot($row);
            $loot[] = $lootRow;
        }

        return $loot;
    }

    private function coalesceLootArray(array $totals) : array
    {
        $itemTotals = [];

        foreach($totals as $total)
        {
            if(is_null($total->item) || $total->amount === 0) continue;
            $itemTotals[] = $total;
        }

        return $itemTotals;
    }

    private function getItemIdsFromTotals(array $totals) : array
    {
        $itemIds = [];

        foreach($totals as $total)
        {
            if(is_null($total->item)) continue;
            $itemIds[] = $total->item->crand;
        }

        return $itemIds;
    }

    private function consolidateLootForTotals(array $cartTotals, array $lootForTotals) : array
    {
        $consolidatedLoot = [];

        foreach($cartTotals as $total)
        {
            if(is_null($total->item) || $total->amount === 0) continue;

            $totalAmount = $total->amount;

            for($i = 0; $i < count($lootForTotals); $i++)
            {
                $loot = $lootForTotals[$i];

                if($loot->item->crand !== $total->item->crand)
                {
                    if($i === count($lootForTotals)-1 && $totalAmount !== 0) throw new RuntimeException("Account did not have enough loot to satisfy totals");
                    continue;
                }

                $lootAmountToAdd = $totalAmount < $loot->quantity ? $totalAmount : $loot->quantity;
                $loot->quantity = $lootAmountToAdd;

                $consolidatedLoot[] = $loot;
                $totalAmount -= $lootAmountToAdd;

                if($totalAmount === 0) break;
                if($totalAmount < 0) throw new LogicException("More loot than needed was added to satisfy totals");
                if($i === count($lootForTotals)-1 && $totalAmount !== 0) throw new RuntimeException("Account did not have enough loot to satisfy totals");
            }
        }

        return $consolidatedLoot;
    }

    private function buildReservationWhereClause(array $reservations) : string
    {
        $clauses = [];
        foreach($reservations as $reservation)
        {
            $clauses[] = "(ctime = ? AND crand = ?)";
        }

        return implode(" OR ", $clauses);
    }

    private function buildReservationParams(array $reservations) : array
    {
        $params = [];
        foreach($reservations as $reservation)
        {
            $params[] = $reservation->ctime;
            $params[] = $reservation->crand;
        }

        return $params;
    }

    

    private function getLootCrandsFromLoots(array $loots) : array
    {
        $lootCrands = [];

        foreach ($loots as $loot)
        {
            if ($loot instanceof vRecordId)
            {
                if ($loot->crand > 0)
                {
                    $lootCrands[] = $loot->crand;
                }

                continue;
            }

            if (is_object($loot) && property_exists($loot, "crand") && (int)$loot->crand > 0)
            {
                $lootCrands[] = (int)$loot->crand;
            }
        }

        return array_values(array_unique($lootCrands));
    }
}

?>
