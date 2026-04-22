<?php
/**
 * Fix missing columns in existing tables
 */

declare(strict_types=1);

require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__) . "/Kickback/init.php");

use Kickback\Services\Database;

header('Content-Type: text/plain');

echo "Fixing missing columns in existing tables...\n\n";

try {
    $conn = Database::getConnection();

    $fixes = [
        // Add removed column to product if missing
        "ALTER TABLE product ADD COLUMN IF NOT EXISTS removed tinyint(1) NOT NULL DEFAULT 0",

        // Add void column to cart if missing
        "ALTER TABLE cart ADD COLUMN IF NOT EXISTS void tinyint(1) NOT NULL DEFAULT 0",

        // Now recreate the views that failed
        "CREATE OR REPLACE VIEW `v_product` AS
SELECT
    p.ctime,
    p.crand,
    p.name,
    p.description,
    p.locator,
    p.removed,
    s.ctime AS store_ctime,
    s.crand AS store_crand,
    s.name AS store_name,
    s.locator AS store_locator
FROM product p
LEFT JOIN store s ON p.ref_store_ctime = s.ctime AND p.ref_store_crand = s.crand",

        "CREATE OR REPLACE VIEW `v_price_component` AS
SELECT
    pc.ctime,
    pc.crand,
    pc.amount,
    pc.currency_code,
    pc.ref_item_ctime AS item_ctime,
    pc.ref_item_crand AS item_crand,
    CASE
        WHEN pc.currency_code IS NOT NULL THEN pc.currency_code
        ELSE 'UNKNOWN'
    END AS display_name
FROM price_component pc",

        "CREATE OR REPLACE VIEW `v_cart` AS
SELECT
    c.ctime,
    c.crand,
    c.checked_out,
    c.void,
    a.Username AS account_username,
    c.ref_account_ctime AS account_ctime,
    c.ref_account_crand AS account_crand,
    s.name AS store_name,
    c.ref_store_ctime AS store_ctime,
    c.ref_store_crand AS store_crand
FROM cart c
LEFT JOIN account a ON a.Id = c.ref_account_crand
LEFT JOIN store s ON c.ref_store_ctime = s.ctime AND c.ref_store_crand = s.crand",

        "CREATE INDEX IF NOT EXISTS idx_product_lookup ON product(locator, removed)",
    ];

    $successCount = 0;
    $errorCount = 0;

    foreach ($fixes as $i => $sql) {
        echo "Executing fix " . ($i + 1) . "...\n";

        try {
            $conn->query($sql);
            $successCount++;
            echo "✓ Success\n\n";
        } catch (Exception $e) {
            $errorCount++;
            echo "✗ Error: " . $e->getMessage() . "\n\n";
        }
    }

    echo "========================================\n";
    echo "Column fixes complete!\n";
    echo "Successful: $successCount\n";
    echo "Errors: $errorCount\n";
    echo "========================================\n";

    if ($errorCount === 0) {
        echo "\n✅ All columns and views fixed!\n";
    }

} catch (Exception $e) {
    echo "FATAL ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
?>
