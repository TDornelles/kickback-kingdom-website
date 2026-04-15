<?php
/**
 * Setup Minimal Database Schema for Stripe Integration
 * Run this once to create all required tables and views
 */

declare(strict_types=1);

require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__) . "/Kickback/init.php");

use Kickback\Services\Database;

header('Content-Type: text/plain');

echo "Setting up minimal schema for Stripe integration...\n\n";

try {
    $conn = Database::getConnection();

    $schemaFile = __DIR__ . '/../meta/schema/stripe-minimal-schema.sql';
    if (!file_exists($schemaFile)) {
        throw new Exception("Schema file not found: $schemaFile");
    }

    $sql = file_get_contents($schemaFile);

    echo "Schema file size: " . strlen($sql) . " bytes\n\n";

    // Remove comments
    $sql = preg_replace('/^--.*$/m', '', $sql);

    echo "After removing comments: " . strlen($sql) . " bytes\n\n";

    // Split by semicolons but be smart about it
    $statements = [];
    $current = '';
    $lines = explode("\n", $sql);

    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) continue;

        $current .= ' ' . $line;

        // If line ends with semicolon, it's the end of a statement
        if (substr($line, -1) === ';') {
            $statements[] = trim(substr($current, 0, -1)); // Remove semicolon
            $current = '';
        }
    }

    echo "Found " . count($statements) . " SQL statements to execute\n\n";

    $successCount = 0;
    $errorCount = 0;

    foreach ($statements as $i => $statement) {
        echo "Executing statement " . ($i + 1) . "...\n";
        if (empty(trim($statement))) {
            continue;
        }

        try {
            $conn->query($statement);
            $successCount++;

            // Extract table/view name for progress
            if (preg_match('/CREATE\s+(?:TABLE|VIEW)\s+(?:IF\s+NOT\s+EXISTS\s+)?`?(\w+)`?/i', $statement, $matches)) {
                echo "✓ Created/updated: {$matches[1]}\n";
            } elseif (preg_match('/ALTER\s+TABLE\s+`?(\w+)`?/i', $statement, $matches)) {
                echo "✓ Altered table: {$matches[1]}\n";
            } else {
                echo "✓ Executed statement\n";
            }
        } catch (Exception $e) {
            $errorCount++;
            echo "✗ Error: " . $e->getMessage() . "\n";
            echo "   Statement: " . substr($statement, 0, 100) . "...\n\n";
        }
    }

    echo "\n========================================\n";
    echo "Schema setup complete!\n";
    echo "Successful: $successCount\n";
    echo "Errors: $errorCount\n";
    echo "========================================\n";

    if ($errorCount === 0) {
        echo "\n✅ All tables and views created successfully!\n";
        echo "You can now proceed with Stripe testing.\n";
    } else {
        echo "\n⚠️  Some errors occurred. Check the output above.\n";
    }

} catch (Exception $e) {
    echo "FATAL ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
?>
