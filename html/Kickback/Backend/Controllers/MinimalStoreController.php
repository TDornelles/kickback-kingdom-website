<?php

declare(strict_types=1);

namespace Kickback\Backend\Controllers;

use Kickback\Backend\Models\Response;
use Kickback\Backend\Models\Store;
use Kickback\Backend\Views\vRecordId;
use Kickback\Backend\Views\vStore;
use Kickback\Services\Database;
use Exception;

/**
 * MinimalStoreController - Clean implementation for Stripe integration
 *
 * This controller provides ONLY the essential store operations needed
 * for Stripe checkout testing. No bloat, no complex dependencies.
 */
class MinimalStoreController
{
    /**
     * Create a new store
     */
    public static function createStore(Store $store): Response
    {
        $resp = new Response(false, "Failed to create store");

        try {
            $sql = "INSERT INTO store (ctime, crand, name, locator, description, ref_account_ctime, ref_account_crand)
                    VALUES (?, ?, ?, ?, ?, ?, ?)";

            $params = [
                $store->ctime,
                $store->crand,
                $store->name,
                $store->locator,
                $store->description,
                $store->accountId->ctime,
                $store->accountId->crand
            ];

            $result = Database::executeSqlQuery($sql, $params);

            if ($result) {
                $resp->success = true;
                $resp->message = "Store created successfully";
                $resp->data = self::getStoreByLocator($store->locator)->data;
            }

        } catch (Exception $e) {
            $resp->message = "Error creating store: " . $e->getMessage();
        }

        return $resp;
    }

    /**
     * Get store by locator (URL-friendly identifier)
     */
    public static function getStoreByLocator(string $locator): Response
    {
        $resp = new Response(false, "Store not found");

        try {
            $sql = "SELECT ctime, crand, name, locator, description, owner_username, owner_ctime, owner_crand
                    FROM v_store
                    WHERE locator = ?
                    LIMIT 1";

            $result = Database::executeSqlQuery($sql, [$locator]);

            if ($result && $result->num_rows > 0) {
                $row = $result->fetch_assoc();

                $store = new vStore($row['ctime'], (int)$row['crand']);
                $store->name = $row['name'];
                $store->locator = $row['locator'];
                $store->description = $row['description'] ?? '';
                $store->ownerUsername = $row['owner_username'] ?? '';
                $store->products = [];

                $resp->success = true;
                $resp->message = "Store found";
                $resp->data = $store;
            }

        } catch (Exception $e) {
            $resp->message = "Error getting store: " . $e->getMessage();
        }

        return $resp;
    }

    /**
     * Check if store exists by locator
     */
    public static function storeExists(string $locator): bool
    {
        $result = self::getStoreByLocator($locator);
        return $result->success;
    }
}

?>
