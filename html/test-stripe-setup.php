<?php
/**
 * Test Data Setup Script for Stripe Integration
 *
 * This script creates test data for testing the Stripe checkout integration:
 * - Creates a test store
 * - Creates test products with USD prices
 * - Creates a cart with products
 * - Provides testing instructions
 */

declare(strict_types=1);

require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__) . "/Kickback/init.php");

use Kickback\Backend\Controllers\StoreController;
use Kickback\Backend\Controllers\AccountController;
use Kickback\Backend\Models\Store;
use Kickback\Backend\Models\Product;
use Kickback\Backend\Models\PriceComponent;
use Kickback\Backend\Models\Enums\CurrencyCode;
use Kickback\Backend\Views\vRecordId;
use Kickback\Backend\Views\vStore;
use Kickback\Backend\Views\vMedia;
use Kickback\Services\Database;

header('Content-Type: text/html; charset=utf-8');

?>
<!DOCTYPE html>
<html>
<head>
    <title>Stripe Integration Test Setup</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .section {
            background: white;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 { color: #333; }
        h2 { color: #666; border-bottom: 2px solid #4CAF50; padding-bottom: 10px; }
        .success { color: #4CAF50; font-weight: bold; }
        .error { color: #f44336; font-weight: bold; }
        .info { background: #e3f2fd; padding: 15px; border-left: 4px solid #2196F3; margin: 10px 0; }
        .code { background: #f4f4f4; padding: 10px; border-radius: 4px; font-family: monospace; }
        .button {
            display: inline-block;
            background: #4CAF50;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 4px;
            margin: 10px 5px;
        }
        .button:hover { background: #45a049; }
        pre { background: #272822; color: #f8f8f2; padding: 15px; border-radius: 4px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>🧪 Stripe Integration Test Setup</h1>

<?php

try {
    echo "<div class='section'>";
    echo "<h2>Step 1: Get Test Account</h2>";

    // Get any existing account from the database
    $sql = "SELECT Id, Username, Email, FirstName, LastName FROM account WHERE Banned = 0 LIMIT 1";
    $result = Database::execute($sql, []);

    if ($result->num_rows === 0) {
        throw new Exception("No accounts found in database. Please create an account first.");
    }

    $accountRow = $result->fetch_assoc();
    echo "<p class='success'>✓ Found account: {$accountRow['Username']} ({$accountRow['Email']})</p>";

    // Create RecordId for account - using a simple ctime/crand structure
    // Note: The account table uses integer IDs, so we'll create a pseudo RecordId
    $accountId = new vRecordId(date('YmdHis'), $accountRow['Id']);
    echo "<p class='info'>Account ID: {$accountId->ctime}_{$accountId->crand}</p>";

    echo "</div>";

    // Create Store
    echo "<div class='section'>";
    echo "<h2>Step 2: Create Test Store</h2>";

    // Check if test store already exists
    $testStoreLocator = "test-stripe-store";
    $existingStoreResp = StoreController::getStoreByLocator($testStoreLocator);

    if ($existingStoreResp->success && $existingStoreResp->data !== false) {
        $testStore = $existingStoreResp->data;
        echo "<p class='success'>✓ Test store already exists: {$testStore->name}</p>";
    } else {
        // Create new store
        $store = new Store(
            "Test Stripe Store",
            $testStoreLocator,
            "A test store for validating Stripe checkout integration",
            $accountId
        );

        // Insert store into database
        $sql = "INSERT INTO store (ctime, crand, name, locator, description, ref_account_ctime, ref_account_crand)
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $params = [
            $store->ctime,
            $store->crand,
            $store->name,
            $store->locator,
            $store->description,
            $accountId->ctime,
            $accountId->crand
        ];

        Database::executeSqlQuery($sql, $params);

        // Fetch the store we just created
        $testStoreResp = StoreController::getStoreByLocator($testStoreLocator);
        if (!$testStoreResp->success) {
            throw new Exception("Failed to create store: " . $testStoreResp->message);
        }
        $testStore = $testStoreResp->data;

        echo "<p class='success'>✓ Created test store: {$testStore->name}</p>";
    }

    echo "<p class='info'>Store ID: {$testStore->ctime}_{$testStore->crand}</p>";
    echo "<p class='info'>Store Locator: {$testStore->locator}</p>";
    echo "</div>";

    // Create Products
    echo "<div class='section'>";
    echo "<h2>Step 3: Create Test Products</h2>";

    $products = [
        ['name' => 'Test Widget', 'price' => 999, 'locator' => 'test-widget'],
        ['name' => 'Test Gadget', 'price' => 1999, 'locator' => 'test-gadget'],
        ['name' => 'Premium Test Item', 'price' => 4999, 'locator' => 'test-premium']
    ];

    $createdProducts = [];

    foreach ($products as $productData) {
        // Check if product exists
        $existingProdResp = StoreController::getProductByLocator($productData['locator']);

        if ($existingProdResp->success && $existingProdResp->data !== null) {
            $createdProducts[] = $existingProdResp->data;
            echo "<p class='success'>✓ Product already exists: {$productData['name']} - $" . number_format($productData['price'] / 100, 2) . "</p>";
        } else {
            // Create price component with USD
            $priceComponent = new PriceComponent(
                $productData['price'], // amount in cents
                CurrencyCode::USD,      // currency code
                null                     // no item, just USD
            );

            // Create product
            $product = new Product(
                $productData['name'],
                "Test product for Stripe integration testing",
                false, // not removed
                $productData['locator'],
                "test", // tag
                [], // categories
                $testStore,
                [$priceComponent], // price array
                null, // no media
                null,
                null
            );

            // Insert product
            $upsertResp = StoreController::upsertProduct($product);
            if (!$upsertResp->success) {
                throw new Exception("Failed to create product {$productData['name']}: " . $upsertResp->message);
            }

            $createdProducts[] = $upsertResp->data;
            echo "<p class='success'>✓ Created product: {$productData['name']} - $" . number_format($productData['price'] / 100, 2) . "</p>";
        }
    }

    echo "</div>";

    // Create Cart
    echo "<div class='section'>";
    echo "<h2>Step 4: Create Test Cart</h2>";

    $storeId = new vRecordId($testStore->ctime, $testStore->crand);
    $cartResp = StoreController::getCartForAccount($accountId, $storeId);

    if (!$cartResp->success) {
        throw new Exception("Failed to create cart: " . $cartResp->message);
    }

    $cart = $cartResp->data;
    echo "<p class='success'>✓ Cart created/retrieved</p>";
    echo "<p class='info'>Cart ID: {$cart->ctime}_{$cart->crand}</p>";

    // Add products to cart
    echo "<h3>Adding Products to Cart:</h3>";
    foreach ($createdProducts as $product) {
        $productId = new vRecordId($product->ctime, $product->crand);
        $cartId = new vRecordId($cart->ctime, $cart->crand);

        $addResp = StoreController::addProductToCart($productId, $cart);

        if ($addResp->success) {
            echo "<p class='success'>✓ Added {$product->name} to cart</p>";
        } else {
            echo "<p class='error'>✗ Failed to add {$product->name}: {$addResp->message}</p>";
        }
    }

    echo "</div>";

    // Testing Instructions
    echo "<div class='section'>";
    echo "<h2>Step 5: Test Stripe Checkout</h2>";

    echo "<div class='info'>";
    echo "<p><strong>Your test cart is ready!</strong></p>";
    echo "<p>Cart ID: <code>{$cart->ctime}_{$cart->crand}</code></p>";
    echo "</div>";

    echo "<h3>Option 1: Automatic Test (Recommended)</h3>";
    echo "<a href='/checkout.php' class='button' onclick='setupCart(); return true;'>Go to Checkout Page</a>";

    echo "<script>
    function setupCart() {
        sessionStorage.setItem('cartCtime', '{$cart->ctime}');
        sessionStorage.setItem('cartCrand', '{$cart->crand}');
        console.log('Cart ID stored in sessionStorage');
    }
    </script>";

    echo "<h3>Option 2: Manual Test</h3>";
    echo "<p>1. Open browser console (F12)</p>";
    echo "<p>2. Run these commands:</p>";
    echo "<pre>sessionStorage.setItem('cartCtime', '{$cart->ctime}');
sessionStorage.setItem('cartCrand', '{$cart->crand}');</pre>";
    echo "<p>3. Navigate to <a href='/checkout.php'>/checkout.php</a></p>";

    echo "<h3>Testing Steps:</h3>";
    echo "<ol>";
    echo "<li>Cart should load and display your test products</li>";
    echo "<li>Click 'Proceed to Payment' button</li>";
    echo "<li>You'll be redirected to Stripe Checkout</li>";
    echo "<li>Use test card: <code>4242 4242 4242 4242</code></li>";
    echo "<li>Any future expiry date, any CVC, any ZIP</li>";
    echo "<li>Complete payment</li>";
    echo "<li>Should redirect to success page</li>";
    echo "</ol>";

    echo "<div class='info'>";
    echo "<strong>Stripe Test Credentials (already configured):</strong><br>";
    echo "Publishable Key: <code>pk_test_51Rqwz...</code><br>";
    echo "Secret Key: <code>sk_test_51Rqwz...</code><br>";
    echo "</div>";

    echo "</div>";

    // Summary
    echo "<div class='section'>";
    echo "<h2>✅ Setup Complete!</h2>";
    echo "<p>Test data has been created successfully. You can now test the Stripe integration.</p>";
    echo "<p><strong>Quick Links:</strong></p>";
    echo "<a href='/checkout.php' class='button' onclick='setupCart(); return true;'>Test Checkout Now</a>";
    echo "<a href='/market.php' class='button'>View Market</a>";
    echo "</div>";

} catch (Exception $e) {
    echo "<div class='section'>";
    echo "<h2 class='error'>❌ Error</h2>";
    echo "<p class='error'>An error occurred: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</div>";
}

?>

</body>
</html>
