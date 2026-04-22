<?php
/**
 * Fix ctime column sizes to accommodate microsecond timestamps
 */

declare(strict_types=1);

require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__) . "/Kickback/init.php");

use Kickback\Services\Database;

header('Content-Type: text/plain');

echo "Fixing ctime column sizes...\n\n";

try {
    $conn = Database::getConnection();

    // Tables that need ctime column fixes
    $tables = ['product', 'price_component'];

    foreach ($tables as $table) {
        echo "Fixing $table.ctime...\n";

        $sql = "ALTER TABLE $table MODIFY COLUMN ctime varchar(50) NOT NULL";

        try {
            $conn->query($sql);
            echo "✓ $table.ctime resized to varchar(50)\n\n";
        } catch (Exception $e) {
            echo "✗ Error: " . $e->getMessage() . "\n\n";
        }
    }

    echo "========================================\n";
    echo "Column resizing complete!\n";
    echo "========================================\n";

} catch (Exception $e) {
    echo "FATAL ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
?>
