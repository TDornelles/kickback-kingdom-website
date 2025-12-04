<?php

declare(strict_types=1);

namespace Kickback\Backend\Controllers;

use Kickback\Backend\Controllers\LootController;
use Kickback\Backend\Controllers\ItemController;
use Kickback\AtlasOdyssey\Emberwood\EmberwoodTradingCargoship;
use Kickback\AtlasOdyssey\Emberwood\ShipStatus;
use Kickback\Backend\Models\Response;
use Kickback\Backend\Models\ShipmentManifestItem;
use Kickback\Backend\Models\RecordId;
use Kickback\Backend\Views\vRecordId;
use Kickback\Services\Database;
use Exception;

class ShipmentController
{
    public static function getShipmentItemPoolOptions() : Response {

        $conn = Database::getConnection();
        
        $sql = "SELECT i.* FROM kickbackdb.v_item_info i 
                left join loot l on i.Id = l.item_id
                where (`type` in (3,5)
                or (`type` = 4 and l.Id is null)) and (i.Id not in (15, 16, 17, 18))
                group by i.Id";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result();

        $items = [];
        while ($row = $result->fetch_assoc()) {
            $itemId = new vRecordId('', (int) $row['Id']);
            $items[] = ItemController::row_to_vItem($row, $itemId);
        }

        $stmt->close();

        return new Response(true, "Shipment manifest retrieved successfully", $items);
    }

    public static function getShipmentPool(): Response
    {
        $conn = Database::getConnection();

        $sql = "SELECT sop.item_id, sop.probability, sop.max_count, i.* FROM shipment_order_pool sop JOIN kickbackdb.v_item_info i ON sop.item_id = i.Id ORDER BY i.name";
        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            return new Response(false, "Failed to load shipment pool: " . $conn->error);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        $items = [];
        while ($row = $result->fetch_assoc()) {
            $itemId = new vRecordId('', (int) $row['item_id']);
            $items[] = [
                'item' => ItemController::row_to_vItem($row, $itemId),
                'probability' => (float) $row['probability'],
                'max_count' => (int) $row['max_count'],
            ];
        }

        $stmt->close();

        return new Response(true, "Shipment pool retrieved successfully", $items);
    }

    public static function upsertShipmentPoolItem(int $itemId, float $probability, int $maxCount): Response
    {
        if ($itemId <= 0) {
            return new Response(false, "Item id must be greater than zero.");
        }

        if ($probability < 0 || $probability > 1) {
            return new Response(false, "Probability must be between 0 and 1.");
        }

        if ($maxCount < 1) {
            return new Response(false, "Max count must be at least 1.");
        }

        $conn = Database::getConnection();

        $stmt = $conn->prepare("INSERT INTO shipment_order_pool (ctime, crand, item_id, probability, max_count) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE probability = VALUES(probability), max_count = VALUES(max_count)");

        if (!$stmt) {
            return new Response(false, "Failed to save shipment pool item: " . $conn->error);
        }

        $ctime = RecordId::getCTime();

        while (true) {
            $crand = RecordId::generateCRand();
            $stmt->bind_param('siidi', $ctime, $crand, $itemId, $probability, $maxCount);

            if ($stmt->execute()) {
                break;
            }

            if ($stmt->errno === 1062) {
                // Duplicate crand; retry with a new one
                continue;
            }

            $error = $stmt->error;
            $stmt->close();
            return new Response(false, "Failed to save shipment pool item: " . $error);
        }

        $affectedRows = $stmt->affected_rows;
        $stmt->close();

        return new Response(true, "Shipment pool item saved.", ['affectedRows' => $affectedRows]);
    }

    public static function deleteShipmentPoolItem(int $itemId): Response
    {
        if ($itemId <= 0) {
            return new Response(false, "Item id must be greater than zero.");
        }

        $conn = Database::getConnection();
        $stmt = $conn->prepare("DELETE FROM shipment_order_pool WHERE item_id = ?");

        if (!$stmt) {
            return new Response(false, "Failed to delete shipment pool item: " . $conn->error);
        }

        $stmt->bind_param('i', $itemId);

        if (!$stmt->execute()) {
            $error = $stmt->error;
            $stmt->close();
            return new Response(false, "Failed to delete shipment pool item: " . $error);
        }

        $affectedRows = $stmt->affected_rows;
        $stmt->close();

        return new Response(true, "Shipment pool item removed.", ['affectedRows' => $affectedRows]);
    }
    
    public static function createShipmentManifest(string $trackingNumber): Response
    {
        try {
            $conn = Database::getConnection();
            $conn->begin_transaction();

            $ctime = RecordId::getCTime();

            // Get items from ShipmentOrderPool with their probabilities and max count
            $sql = "SELECT item_id, probability, max_count FROM shipment_order_pool";
            $result = $conn->query($sql);

            $manifestItems = [];
            while ($row = $result->fetch_assoc()) {
                // Determine the number of each item to add to the manifest
                $probability = (float) $row['probability'];
                $maxCount = (int) $row['max_count'];
                $count = self::calculateItemCount($probability, $maxCount);
                if ($count > 0) {
                    $manifestItems[] = ['item_id' => $row['item_id'], 'count' => $count];
                }
            }

            $shipmentManifestItem = new ShipmentManifestItem($trackingNumber);

            // Insert items into the shipmentmanifest table
            $insertedRows = 0;
            foreach ($manifestItems as $item) {
                $stmt = $conn->prepare(
                    "INSERT INTO shipment_manifest (ctime, crand, tracking_number, item_id, count) VALUES (?, ?, ?, ?, ?)"
                );

                if (!$stmt) {
                    throw new Exception("Failed to prepare shipment manifest insert: " . $conn->error);
                }

                while (true) {
                    $crand = RecordId::generateCRand();

                    $stmt->bind_param("sissi", $ctime, $crand, $trackingNumber, $item['item_id'], $item['count']);
                    $stmt->execute();

                    if ($stmt->errno === 1062) {
                        // Duplicate crand; try again with a new one
                        continue;
                    }

                    if ($stmt->errno) {
                        $errorMessage = $stmt->error;
                        $stmt->close();
                        throw new Exception("Failed to insert shipment manifest item: " . $errorMessage);
                    }

                    break;
                }

                $insertedRows += $stmt->affected_rows;
                $stmt->close();
            }

            $conn->commit();
            return new Response(true, "Shipment manifest created successfully", [
                'tracking_number' => $trackingNumber,
                'items' => $manifestItems,
                'insertedRows' => $insertedRows
            ]);

        } catch (Exception $e) {
            $conn->rollback();
            return new Response(false, "Failed to create shipment manifest: " . $e->getMessage());
        }
    }
    
    public static function getShipmentManifest(string $trackingNumber): Response
    {
        $conn = Database::getConnection();

        $sql = "SELECT item_id, count FROM shipment_manifest WHERE tracking_number = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $trackingNumber);
        $stmt->execute();
        $result = $stmt->get_result();

        $items = [];
        while ($row = $result->fetch_assoc()) {
            $items[] = LootController::row_to_vItemStack($row);
        }

        $stmt->close();

        if (empty($items)) {
            return new Response(false, "No items found for tracking number $trackingNumber.");
        }

        return new Response(true, "Shipment manifest retrieved successfully", $items);
    }

    public static function shipmentManifestExists(string $trackingNumber): Response
    {
        $conn = Database::getConnection();

        $stmt = $conn->prepare("SELECT COUNT(*) as manifest_count FROM shipment_manifest WHERE tracking_number = ?");

        if (!$stmt) {
            return new Response(false, "Failed to check shipment manifest existence: " . $conn->error);
        }

        $stmt->bind_param("s", $trackingNumber);

        if (!$stmt->execute()) {
            $error = $stmt->error;
            $stmt->close();
            return new Response(false, "Failed to check shipment manifest existence: " . $error);
        }

        $result = $stmt->get_result();
        $count = (int) ($result->fetch_assoc()['manifest_count'] ?? 0);

        $stmt->close();

        return new Response(true, $count > 0 ? 'Shipment manifest already exists' : 'Shipment manifest not found', [
            'exists' => $count > 0,
            'count' => $count,
        ]);
    }

    public static function validateTrackingNumber(string $trackingNumber): Response
    {
        try {
            $parsed = EmberwoodTradingCargoship::parseTrackingNumber($trackingNumber);
            $isValid = EmberwoodTradingCargoship::isValidTrackingNumber($trackingNumber);

            if ($isValid) {
                return new Response(true, "Tracking number is valid", $parsed);
            } else {
                return new Response(false, "Tracking number is invalid");
            }
        } catch (Exception $e) {
            return new Response(false, "Error validating tracking number: " . $e->getMessage());
        }
    }

    public static function getEmberwoodTrackerData(int $shipId = 2, string $username = 'Alibaba'): array
    {
        $emberwoodShip = new EmberwoodTradingCargoship($shipId);

        $progressPercentage = $emberwoodShip->getJourneyPercentage();
        $roundedProgress = round($progressPercentage, 1);
        $adjustedProgress = max(0, min($roundedProgress, 100));
        $leftPosition = "{$adjustedProgress}%";

        $timeUntilNextDelivery = $emberwoodShip->getTimeUntilNextDeliveryInATC();
        $currentATCDate = $emberwoodShip->getCurrentATCDateTime();
        $shipLocation = $emberwoodShip->getLocation();
        $shipStatus = $emberwoodShip->getShipStatusWithDetails();
        $trackingNumber = $emberwoodShip->getTrackingNumber();

        $shipmentManifestResp = self::getShipmentManifest($trackingNumber);
        $shipmentManifest = $shipmentManifestResp->success ? $shipmentManifestResp->data : [];

        $journeyWaypoints = $emberwoodShip->getJourneyWaypoints();
        $waypointCount = max(count($journeyWaypoints) - 1, 1);
        $segmentPercentage = 100 / $waypointCount;
        $currentWaypointIndex = min((int) floor($adjustedProgress / $segmentPercentage), count($journeyWaypoints) - 1);

        [$fleetStatusHeadline, $fleetStatusBadge] = self::buildFleetStatusPair(
            $shipStatus,
            $shipLocation,
            $trackingNumber
        );

        return [
            'progressPercentage' => $progressPercentage,
            'roundedProgress' => $roundedProgress,
            'adjustedProgress' => $adjustedProgress,
            'leftPosition' => $leftPosition,
            'timeUntilNextDelivery' => $timeUntilNextDelivery,
            'currentATCDate' => $currentATCDate,
            'shipLocation' => $shipLocation,
            'shipStatus' => $shipStatus,
            'trackingNumber' => $trackingNumber,
            'shipmentManifest' => $shipmentManifest,
            'journeyWaypoints' => $journeyWaypoints,
            'segmentPercentage' => $segmentPercentage,
            'currentWaypointIndex' => $currentWaypointIndex,
            'fleetStatusHeadline' => $fleetStatusHeadline,
            'fleetStatusBadge' => $fleetStatusBadge,
            'username' => $username,
        ];
    }

    private static function calculateItemCount(float $probability, int $maxCount): int
    {
        $count = 0;
        for ($i = 0; $i < $maxCount; $i++) {
            if (mt_rand(0, 100) / 100 <= $probability) {
                $count++;
            }
        }
        return $count;
    }

    private static function buildFleetStatusPair(ShipStatus $shipStatus, string $shipLocation, string $seed): array
    {
        $templates = [
            'left' => [
                'Currently %statusLower near %location',
                'Live update: %status (%location)',
                'Bridge reports "%status" in-sector',
                'Fleet log shows %statusLower over %location',
                'Situation: %statusLower in this corridor',
            ],
            'right' => [
                '%response',
                'Response: %response',
                'Ops note: %response',
                'Action: %response',
                'Command focus: %response',
            ],
        ];

        $context = self::buildFleetStatusContext($shipStatus, $shipLocation, $seed);

        $leftTemplates = $templates['left'];
        $rightTemplates = $templates['right'];

        $seedValue = crc32($context['status'] . $context['location'] . $context['seed']);
        $leftIndex = $seedValue % count($leftTemplates);
        $rightIndex = ($seedValue >> 3) % count($rightTemplates);

        $headline = self::renderFleetStatusTemplate($leftTemplates[$leftIndex], $context);
        $badge = self::renderFleetStatusTemplate($rightTemplates[$rightIndex], $context);

        if ($badge === $headline) {
            $badge = self::renderFleetStatusTemplate($rightTemplates[($rightIndex + 1) % count($rightTemplates)], $context);
        }

        return [$headline, $badge];
    }

    private static function buildFleetStatusContext(ShipStatus $shipStatus, string $shipLocation, string $seed): array
    {
        $responseOptions = self::getFleetResponseLines($shipStatus);
        $responseIndex = crc32($seed . $shipStatus->text) % count($responseOptions);

        return [
            'status' => $shipStatus->text,
            'location' => $shipLocation,
            'response' => $responseOptions[$responseIndex],
            'seed' => $seed,
        ];
    }

    private static function getFleetResponseLines(ShipStatus $shipStatus): array
    {
        $statusKey = strtolower($shipStatus->text);

        $typeResponses = [
            'combat' => [
                'Weapons teams are rotating shield facings and tracking hostiles',
                'Tactical is coordinating countermeasures and drone screens',
                'Security has teams at key bulkheads and evacuation routes',
            ],
            'science' => [
                'Science bay is crunching scans and uplinking findings',
                'Sensor arrays are running deep-spectrum passes',
                'Research teams are cataloging samples and anomalies',
            ],
            'normal' => [
                'Ops is maintaining thrust and nav corrections',
                'Bridge is running continuous course checks',
                'Crew logging drive temps and hull telemetry',
            ],
        ];

        $statusResponses = [
            'delayed' => [
                'Command dispatched override to regain schedule',
                'Engineering is rebalancing thrust to claw back time',
                'Ops is resequencing cargo drops to avoid impact',
            ],
            'holding' => [
                'Traffic control has us staged and comms are open',
                'Bridge is waiting on new vector clearance',
                'Ops is using the pause to run quick diagnostics',
            ],
            'docked' => [
                'Port crew is guiding the cargo unload',
                'Ops is confirming manifests with station logistics',
                'Systems are idling while the bay teams work',
            ],
            'loading' => [
                'Cargo teams are locking down the bay for departure',
                'Ops is sequencing pallets for fastest exit',
                'Quartermasters are double-checking manifests',
            ],
            'unloading' => [
                'Cargo teams are clearing holds in priority order',
                'Ops is reconciling delivered containers',
                'Port liaison is verifying custody transfers',
            ],
            'maintenance' => [
                'Engineering has diagnostics running shipwide',
                'Ops queued hot-swap spares from onboard stores',
                'Bridge is holding until systems sign off',
            ],
            'en route' => [
                'Helm is trimming the sails for smoother current riding',
                'Navigation is plotting micro-corrections on the fly',
                'Ops is balancing power between sails and comms',
            ],
        ];

        $matchedStatusResponses = [];
        foreach ($statusResponses as $key => $responses) {
            if (str_contains($statusKey, $key)) {
                $matchedStatusResponses = array_merge($matchedStatusResponses, $responses);
            }
        }

        $typeBucket = $typeResponses[$shipStatus->type] ?? $typeResponses['normal'];
        $fallback = [
            'Ops is monitoring systems and ready to pivot',
            'Bridge is coordinating with nearby relays',
            'Crew is standing by for the next directive',
        ];

        $responsePool = array_unique(array_merge($matchedStatusResponses, $typeBucket, $fallback));

        return $responsePool ?: $fallback;
    }

    private static function renderFleetStatusTemplate(string $template, array $context): string
    {
        return strtr($template, [
            '%status' => $context['status'],
            '%statusLower' => strtolower($context['status']),
            '%location' => $context['location'],
            '%response' => $context['response'],
        ]);
    }
}
