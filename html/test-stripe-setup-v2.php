<?php
/**
 * Test Data Setup V2 - Using Minimal Controllers
 *
 * This creates test data for Stripe integration using the clean minimal controllers.
 */

declare(strict_types=1);

// Disable output buffering for progressive display
error_reporting(E_ALL);
ini_set('display_errors', '1');
if (ob_get_level()) ob_end_flush();

require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__) . "/Kickback/init.php");

use Kickback\Backend\Controllers\MinimalStoreController;
use Kickback\Backend\Controllers\MinimalProductController;
use Kickback\Backend\Controllers\CartController;
use Kickback\Backend\Models\Store;
use Kickback\Backend\Models\Product;
use Kickback\Backend\Models\PriceComponent;
use Kickback\Backend\Models\Enums\CurrencyCode;
use Kickback\Backend\Views\vRecordId;
use Kickback\Services\Database;

header('Content-Type: text/html; charset=utf-8');

?>
<!DOCTYPE html>
<html>
<head>
    <title>Stripe Test Setup V2</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 900px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
        .section { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #333; }
        h2 { color: #666; border-bottom: 2px solid #4CAF50; padding-bottom: 10px; }
        .success { color: #4CAF50; font-weight: bold; }
        .error { color: #f44336; font-weight: bold; }
        .info { background: #e3f2fd; padding: 15px; border-left: 4px solid #2196F3; margin: 10px 0; }
        .button { display: inline-block; background: #4CAF50; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px; margin: 10px 5px; }
        .button:hover { background: #45a049; }
    </style>
</head>
<body>
    <h1>🧪 Stripe Test Setup V2 (Clean Implementation)</h1>

<?php

try {
    // Step 1: Get Account
    echo "<div class='section'>";
    echo "<h2>Step 1: Get Test Account</h2>";
    flush();

    $sql = "SELECT Id, Username, Email FROM account WHERE Banned = 0 LIMIT 1";
    $result = Database::executeSqlQuery($sql, []);

    if ($result->num_rows === 0) {
        throw new Exception("No accounts found. Please create an account first.");
    }

    $accountRow = $result->fetch_assoc();
    echo "<p class='success'>✓ Using account: {$accountRow['Username']}</p>";

    $accountId = new vRecordId(
        (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s.u'),
        $accountRow['Id']
    );
    echo "</div>";
    flush();

    // Step 2: Create or Get Store
    echo "<div class='section'>";
    echo "<h2>Step 2: Create Test Store</h2>";
    flush();

    $storeLocator = "test-stripe-store-v2";

    if (MinimalStoreController::storeExists($storeLocator)) {
        $storeResp = MinimalStoreController::getStoreByLocator($storeLocator);
        $testStore = $storeResp->data;
        echo "<p class='success'>✓ Store already exists: {$testStore->name}</p>";
    } else {
        $store = new Store(
            "Test Stripe Store V2",
            $storeLocator,
            "Clean minimal implementation for Stripe testing",
            $accountId
        );

        $createResp = MinimalStoreController::createStore($store);

        if (!$createResp->success) {
            throw new Exception("Failed to create store: " . $createResp->message);
        }

        $testStore = $createResp->data;
        echo "<p class='success'>✓ Created store: {$testStore->name}</p>";
    }

    $storeId = new vRecordId($testStore->ctime, $testStore->crand);
    echo "<p class='info'>Store ID: {$testStore->ctime}_{$testStore->crand}</p>";
    echo "</div>";
    flush();

    // Step 3: Create Products
    echo "<div class='section'>";
    echo "<h2>Step 3: Create Test Products</h2>";
    flush();

    $products = [
        ['name' => 'Test Widget', 'price' => 999, 'locator' => 'test-widget-v2'],
        ['name' => 'Test Gadget', 'price' => 1999, 'locator' => 'test-gadget-v2'],
        ['name' => 'Premium Test Item', 'price' => 4999, 'locator' => 'test-premium-v2']
    ];

    $createdProducts = [];

    foreach ($products as $productData) {
        // Check if exists
        $existsResp = MinimalProductController::getProductByLocator($productData['locator']);

        if ($existsResp->success) {
            $createdProducts[] = $existsResp->data;
            echo "<p class='success'>✓ Product exists: {$productData['name']} - $" . number_format($productData['price'] / 100, 2) . "</p>";
        } else {
            // Create product (without price first)
            $product = new Product(
                $productData['name'],
                "Test product for Stripe integration",
                false, // not removed
                $productData['locator'],
                "test",
                [],
                $testStore,
                [], // empty price array - will add separately
                null, null, null
            );

            // Now add the price component separately
            $priceComponent = new PriceComponent(
                $productData['price'],
                CurrencyCode::USD,
                null // no item-based pricing
            );
            $product->price = [$priceComponent];

            $createResp = MinimalProductController::createProduct($product);

            if (!$createResp->success) {
                throw new Exception("Failed to create product {$productData['name']}: " . $createResp->message);
            }

            $createdProducts[] = $createResp->data;
            echo "<p class='success'>✓ Created product: {$productData['name']} - $" . number_format($productData['price'] / 100, 2) . "</p>";
        }
        flush();
    }

    echo "</div>";
    flush();

    // Step 4: Create Cart and Add Products
    echo "<div class='section'>";
    echo "<h2>Step 4: Create Cart & Add Products</h2>";
    flush();

    $cartResp = CartController::getOrCreateCart($accountId, $storeId);

    if (!$cartResp->success) {
        throw new Exception("Failed to get/create cart: " . $cartResp->message);
    }

    $cart = $cartResp->data;
    echo "<p class='success'>✓ Cart ready</p>";
    echo "<p class='info'>Cart ID: {$cart->ctime}_{$cart->crand}</p>";

    // Add products to cart
    foreach ($createdProducts as $product) {
        $productId = new vRecordId($product->ctime, $product->crand);
        $cartId = new vRecordId($cart->ctime, $cart->crand);

        $addResp = CartController::addProductToCart($productId, $cartId);

        if ($addResp->success) {
            echo "<p class='success'>✓ Added {$product->name} to cart</p>";
        } else {
            echo "<p class='error'>✗ Failed to add {$product->name}: {$addResp->message}</p>";
        }
        flush();
    }

    echo "</div>";
    flush();

    // Step 5: Testing Instructions
    echo "<div class='section'>";
    echo "<h2>Step 5: Test Stripe Checkout</h2>";

    echo "<div class='info'>";
    echo "<p><strong>Your test cart is ready!</strong></p>";
    echo "<p>Cart ID: <code>{$cart->ctime}_{$cart->crand}</code></p>";
    echo "</div>";

    echo "<a href='/checkout.php' class='button' onclick='setupCart(); return true;'>Go to Checkout Page</a>";

    echo "<script>
    function setupCart() {
        sessionStorage.setItem('cartCtime', '{$cart->ctime}');
        sessionStorage.setItem('cartCrand', '{$cart->crand}');
        console.log('Cart ID stored in sessionStorage');
    }
    </script>";

    echo "<h3>Testing Steps:</h3>";
    echo "<ol>";
    echo "<li>Click the button above to go to checkout</li>";
    echo "<li>Cart should load with 3 test products (~$80 total)</li>";
    echo "<li>Click 'Proceed to Payment'</li>";
    echo "<li>Use Stripe test card: <code>4242 4242 4242 4242</code></li>";
    echo "<li>Any future date, any CVC, any ZIP</li>";
    echo "<li>Complete payment</li>";
    echo "<li>Should redirect to success page</li>";
    echo "</ol>";

    echo "</div>";

    // Success Summary
    echo "<div class='section'>";
    echo "<h2>✅ Setup Complete!</h2>";
    echo "<p>All test data created successfully using clean minimal controllers.</p>";
    echo "<p><strong>Ready to test Stripe integration!</strong></p>";
    echo "</div>";

} catch (Exception $e) {
    echo "<div class='section'>";
    echo "<h2 class='error'>❌ Error</h2>";
    echo "<p class='error'>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</div>";
}

?>

</body>
</html>
