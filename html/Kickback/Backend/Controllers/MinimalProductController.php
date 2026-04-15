<?php

declare(strict_types=1);

namespace Kickback\Backend\Controllers;

use Kickback\Backend\Models\Response;
use Kickback\Backend\Models\Product;
use Kickback\Backend\Models\PriceComponent;
use Kickback\Backend\Views\vRecordId;
use Kickback\Backend\Views\vProduct;
use Kickback\Backend\Views\vPriceComponent;
use Kickback\Services\Database;
use Exception;

/**
 * MinimalProductController - Clean implementation for Stripe integration
 */
class MinimalProductController
{
    /**
     * Create a new product
     */
    public static function createProduct(Product $product): Response
    {
        $resp = new Response(false, "Failed to create product");

        try {
            $sql = "INSERT INTO product (ctime, crand, name, description, locator, removed, ref_store_ctime, ref_store_crand)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

            $params = [
                $product->ctime,
                $product->crand,
                $product->name,
                $product->description,
                $product->locator,
                $product->removed ? 1 : 0,
                $product->store->ctime,
                $product->store->crand
            ];

            $result = Database::executeSqlQuery($sql, $params);

            if ($result) {
                // Now add the price components
                foreach ($product->price as $priceComponent) {
                    $priceResp = self::addPriceToProduct(
                        new vRecordId($product->ctime, $product->crand),
                        $priceComponent
                    );

                    if (!$priceResp->success) {
                        throw new Exception("Failed to add price: " . $priceResp->message);
                    }
                }

                $resp->success = true;
                $resp->message = "Product created successfully";
                $resp->data = self::getProductByLocator($product->locator)->data;
            }

        } catch (Exception $e) {
            $resp->message = "Error creating product: " . $e->getMessage();
        }

        return $resp;
    }

    /**
     * Add a price component to a product
     */
    public static function addPriceToProduct(vRecordId $productId, PriceComponent $price): Response
    {
        $resp = new Response(false, "Failed to add price");

        try {
            // First, create the price component if it doesn't exist
            $sql = "INSERT INTO price_component (ctime, crand, amount, currency_code, ref_item_ctime, ref_item_crand)
                    VALUES (?, ?, ?, ?, ?, ?)";

            $params = [
                $price->ctime,
                $price->crand,
                $price->amount,
                $price->currencyCode ? $price->currencyCode->value : null,
                $price->itemId?->ctime,
                $price->itemId?->crand
            ];

            Database::executeSqlQuery($sql, $params);

            // Now link it to the product
            $linkSql = "INSERT INTO product_price_component_link
                        (ref_product_ctime, ref_product_crand, ref_price_component_ctime, ref_price_component_crand)
                        VALUES (?, ?, ?, ?)";

            $linkParams = [
                $productId->ctime,
                $productId->crand,
                $price->ctime,
                $price->crand
            ];

            $linkResult = Database::executeSqlQuery($linkSql, $linkParams);

            if ($linkResult) {
                $resp->success = true;
                $resp->message = "Price added successfully";
            }

        } catch (Exception $e) {
            $resp->message = "Error adding price: " . $e->getMessage();
        }

        return $resp;
    }

    /**
     * Get product by locator with its prices
     */
    public static function getProductByLocator(string $locator): Response
    {
        $resp = new Response(false, "Product not found");

        try {
            // Get product
            $sql = "SELECT ctime, crand, name, description, locator, removed,
                           store_ctime, store_crand, store_name, store_locator
                    FROM v_product
                    WHERE locator = ? AND removed = 0
                    LIMIT 1";

            $result = Database::executeSqlQuery($sql, [$locator]);

            if ($result && $result->num_rows > 0) {
                $row = $result->fetch_assoc();

                $product = new vProduct(
                    $row['ctime'],
                    (int)$row['crand'],
                    $row['name'],
                    $row['description'] ?? '',
                    $row['locator'],
                    '', // tag
                    [], // categories
                    -1, // stock
                    -1, // amountAvailable
                    (bool)$row['removed']
                );

                // Get prices for this product
                $product->price = self::getPricesForProduct($product->ctime, $product->crand);

                $resp->success = true;
                $resp->message = "Product found";
                $resp->data = $product;
            }

        } catch (Exception $e) {
            $resp->message = "Error getting product: " . $e->getMessage();
        }

        return $resp;
    }

    /**
     * Get all price components for a product
     */
    private static function getPricesForProduct(string $productCtime, int $productCrand): array
    {
        $prices = [];

        try {
            $sql = "SELECT pc.ctime, pc.crand, pc.amount, pc.currency_code, pc.ref_item_ctime, pc.ref_item_crand
                    FROM price_component pc
                    JOIN product_price_component_link ppl
                        ON pc.ctime = ppl.ref_price_component_ctime
                        AND pc.crand = ppl.ref_price_component_crand
                    WHERE ppl.ref_product_ctime = ? AND ppl.ref_product_crand = ?";

            $result = Database::executeSqlQuery($sql, [$productCtime, $productCrand]);

            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $price = new vPriceComponent(
                        $row['ctime'],
                        (int)$row['crand'],
                        (int)$row['amount'],
                        null, // item
                        $row['currency_code'] ? \Kickback\Backend\Models\Enums\CurrencyCode::from($row['currency_code']) : null
                    );

                    $prices[] = $price;
                }
            }

        } catch (Exception $e) {
            // Return empty array on error
        }

        return $prices;
    }
}

?>
