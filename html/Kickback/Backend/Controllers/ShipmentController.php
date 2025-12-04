<?php

declare(strict_types=1);

namespace Kickback\Backend\Controllers;

use Kickback\Backend\Controllers\ItemController;
use Kickback\AtlasOdyssey\Emberwood\EmberwoodTradingCargoship;
use Kickback\AtlasOdyssey\Emberwood\ShipStatus;
use Kickback\Backend\Models\Response;
use Kickback\Backend\Models\ShipmentManifestItem;
use Kickback\Backend\Models\RecordId;
use Kickback\Backend\Views\vRecordId;
use Kickback\Backend\Views\vProduct;
use Kickback\Backend\Views\vMedia;
use Kickback\Backend\Views\vStore;
use Kickback\Backend\Views\vAccount;
use Kickback\Services\Database;
use Exception;

class ShipmentController
{
    public static function getShipmentProductPoolOptions() : Response {

        $conn = Database::getConnection();

        $sql = "SELECT * FROM v_product WHERE removed = 0 ORDER BY name";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result();

        $products = [];
        while ($row = $result->fetch_assoc()) {
            $product = self::rowToVProduct($row);
            if ($product) {
                $products[] = $product;
            }
        }

        $stmt->close();

        return new Response(true, "Shipment products retrieved successfully", $products);
    }

    public static function getShipmentPool(): Response
    {
        $conn = Database::getConnection();

        $sql = "SELECT item_id, product_ctime, product_crand, probability, max_count FROM shipment_order_pool";
        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            return new Response(false, "Failed to load shipment pool: " . $conn->error);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        $items = [];
        while ($row = $result->fetch_assoc()) {
            $product = null;
            if (!empty($row['product_ctime']) && !empty($row['product_crand'])) {
                $product = self::getProductById($row['product_ctime'], (int)$row['product_crand']);
            }

            $legacyItem = null;
            if (!$product && !empty($row['item_id'])) {
                $itemId = new vRecordId('', (int) $row['item_id']);
                $itemResp = ItemController::getItemById($itemId);
                if ($itemResp->success) {
                    $legacyItem = $itemResp->data;
                }
            }

            $items[] = [
                'product' => $product,
                'item' => $legacyItem,
                'probability' => (float) $row['probability'],
                'max_count' => (int) $row['max_count'],
                'product_id' => $product ? new vRecordId($product->ctime, $product->crand) : null,
                'item_id' => $legacyItem?->crand,
            ];
        }

        $stmt->close();

        return new Response(true, "Shipment pool retrieved successfully", $items);
    }

    public static function upsertShipmentPoolItem(string $productCtime, int $productCrand, float $probability, int $maxCount, ?int $itemId = null): Response
    {
        $productCtime = $productCtime ?? '';
        if (($productCtime === '' || $productCrand <= 0) && ($itemId === null || $itemId <= 0)) {
            return new Response(false, "A product id or legacy item id must be provided.");
        }

        if ($probability < 0 || $probability > 1) {
            return new Response(false, "Probability must be between 0 and 1.");
        }

        if ($maxCount < 1) {
            return new Response(false, "Max count must be at least 1.");
        }

        $conn = Database::getConnection();

        $stmt = $conn->prepare("INSERT INTO shipment_order_pool (ctime, crand, product_ctime, product_crand, item_id, probability, max_count) VALUES (?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE product_ctime = VALUES(product_ctime), product_crand = VALUES(product_crand), item_id = VALUES(item_id), probability = VALUES(probability), max_count = VALUES(max_count)");

        if (!$stmt) {
            return new Response(false, "Failed to save shipment pool item: " . $conn->error);
        }

        $ctime = RecordId::getCTime();

        $productCtimeParam = $productCtime !== '' ? $productCtime : null;
        $productCrandParam = $productCrand > 0 ? $productCrand : null;
        $itemIdParam = ($itemId !== null && $itemId > 0) ? $itemId : null;

        while (true) {
            $crand = RecordId::generateCRand();
            $stmt->bind_param('sissidi', $ctime, $crand, $productCtimeParam, $productCrandParam, $itemIdParam, $probability, $maxCount);

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

    public static function deleteShipmentPoolItem(?string $productCtime, ?int $productCrand, ?int $itemId = null): Response
    {
        $hasProductId = $productCtime !== null && $productCtime !== '' && $productCrand !== null && $productCrand > 0;
        if (!$hasProductId && ($itemId === null || $itemId <= 0)) {
            return new Response(false, "A valid product or item id must be provided.");
        }

        $conn = Database::getConnection();
        if ($hasProductId) {
            $stmt = $conn->prepare("DELETE FROM shipment_order_pool WHERE product_ctime = ? AND product_crand = ?");
            if (!$stmt) {
                return new Response(false, "Failed to delete shipment pool item: " . $conn->error);
            }
            $stmt->bind_param('si', $productCtime, $productCrand);
        } else {
            $stmt = $conn->prepare("DELETE FROM shipment_order_pool WHERE item_id = ?");
            if (!$stmt) {
                return new Response(false, "Failed to delete shipment pool item: " . $conn->error);
            }
            $stmt->bind_param('i', $itemId);
        }

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
            $sql = "SELECT item_id, product_ctime, product_crand, probability, max_count FROM shipment_order_pool";
            $result = $conn->query($sql);

            $manifestItems = [];
            while ($row = $result->fetch_assoc()) {
                // Determine the number of each item to add to the manifest
                $probability = (float) $row['probability'];
                $maxCount = (int) $row['max_count'];
                $count = self::calculateItemCount($probability, $maxCount);
                if ($count > 0 && ((!empty($row['product_ctime']) && !empty($row['product_crand'])) || !empty($row['item_id']))) {
                    $manifestItems[] = [
                        'product_ctime' => $row['product_ctime'] ?? null,
                        'product_crand' => isset($row['product_crand']) ? (int)$row['product_crand'] : null,
                        'item_id' => isset($row['item_id']) ? (int)$row['item_id'] : null,
                        'count' => $count
                    ];
                }
            }

            $shipmentManifestItem = new ShipmentManifestItem($trackingNumber);

            // Insert items into the shipmentmanifest table
            $insertedRows = 0;
            foreach ($manifestItems as $item) {
                $stmt = $conn->prepare(
                    "INSERT INTO shipment_manifest (ctime, crand, tracking_number, product_ctime, product_crand, item_id, count) VALUES (?, ?, ?, ?, ?, ?, ?)"
                );

                if (!$stmt) {
                    throw new Exception("Failed to prepare shipment manifest insert: " . $conn->error);
                }

                while (true) {
                    $crand = RecordId::generateCRand();

                    $productCtime = $item['product_ctime'] ?? null;
                    $productCrand = $item['product_crand'] ?? null;
                    $itemId = $item['item_id'] ?? null;

                    $stmt->bind_param("sissisi", $ctime, $crand, $trackingNumber, $productCtime, $productCrand, $itemId, $item['count']);
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

        $sql = "SELECT product_ctime, product_crand, item_id, count FROM shipment_manifest WHERE tracking_number = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $trackingNumber);
        $stmt->execute();
        $result = $stmt->get_result();

        $items = [];
        while ($row = $result->fetch_assoc()) {
            $product = null;
            if (!empty($row['product_ctime']) && !empty($row['product_crand'])) {
                $product = self::getProductById($row['product_ctime'], (int)$row['product_crand']);
            }

            $legacyItem = null;
            if (!$product && !empty($row['item_id'])) {
                $legacyResp = ItemController::getItemById(new vRecordId('', (int)$row['item_id']));
                if ($legacyResp->success) {
                    $legacyItem = $legacyResp->data;
                }
            }

            $items[] = [
                'product' => $product,
                'item' => $legacyItem,
                'count' => (int)($row['count'] ?? 0),
            ];
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

    private static function getProductById(string $ctime, int $crand): ?vProduct
    {
        $conn = Database::getConnection();
        $sql = "SELECT * FROM v_product WHERE ctime = ? AND crand = ? LIMIT 1";
        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            return null;
        }

        $stmt->bind_param('si', $ctime, $crand);

        if (!$stmt->execute()) {
            $stmt->close();
            return null;
        }

        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        return $row ? self::rowToVProduct($row) : null;
    }

    private static function rowToVProduct(?array $row): ?vProduct
    {
        if (!$row) {
            return null;
        }

        $owner = new vAccount($row['store_owner_ctime'] ?? '', (int)($row['store_owner_crand'] ?? 0));
        $owner->username = $row['store_owner_username'] ?? '';

        $store = new vStore($row['store_ctime'] ?? '', (int)($row['store_crand'] ?? 0));
        $store->name = $row['store_name'] ?? '';
        $store->description = $row['store_description'] ?? '';
        $store->locator = $row['store_locator'] ?? '';

        $smallIcon = new vMedia();
        if (!empty($row['small_media_media_path'])) {
            $smallIcon->setMediaPath($row['small_media_media_path']);
        }

        $largeIcon = new vMedia();
        if (!empty($row['large_media_media_path'])) {
            $largeIcon->setMediaPath($row['large_media_media_path']);
        }

        $backIcon = new vMedia();
        if (!empty($row['back_media_media_path'])) {
            $backIcon->setMediaPath($row['back_media_media_path']);
        }

        $product = new vProduct($row['ctime'] ?? '', (int)($row['crand'] ?? 0));
        $product->locator = $row["locator"] ?? null;
        $product->tag = (string)($row["tags"] ?? '');
        $decodedCategories = [];
        if (!empty($row['tags'])) {
            $decodedCategories = json_decode($row['tags'], true);
            if (!is_array($decodedCategories)) {
                $decodedCategories = [];
            }
        }
        $product->categories = $decodedCategories;
        $product->name = $row["name"] ?? '';
        $product->description = $row["description"] ?? '';
        $product->stock = (int)($row["stock"] ?? 0);
        $product->amountAvailable = (int)($row["amount_available"] ?? 0);
        $product->removed = (bool)($row["removed"] ?? false);
        $product->owner = $owner;
        $product->store = $store;
        $product->mediaSmall = $smallIcon->isValid() ? $smallIcon : null;
        $product->mediaLarge = $largeIcon->isValid() ? $largeIcon : null;
        $product->mediaBack = $backIcon->isValid() ? $backIcon : null;

        return $product;
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
