<?php
$pageTitle = "Shipment Pool Manager";
$pageImage = "https://kickback-kingdom.com/assets/media/context/emberwood-ship.png";
$pageDesc = "Admin tools for configuring the Emberwood shipment manifest pool.";

require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__) . "/Kickback/init.php");

$session = require(\Kickback\SCRIPT_ROOT . "/api/v1/engine/session/verifySession.php");
require("php-components/base-page-pull-active-account-info.php");

use Kickback\Backend\Controllers\ShipmentController;
use Kickback\Backend\Controllers\StoreController;
use Kickback\Backend\Controllers\ItemController;
use Kickback\AtlasOdyssey\Emberwood\EmberwoodTradingCargoship;
use Kickback\Common\Version;
use Kickback\Services\Session;

if (!Session::isAdmin()) {
    header('Location: index.php');
    exit();
}

$alertMessage = '';
$alertVariant = '';
$emberwoodShip = new EmberwoodTradingCargoship();
$activeTrackingNumber = $emberwoodShip->getTrackingNumber();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $productCtime = $_POST['product_ctime'] ?? '';
    $productCrand = isset($_POST['product_crand']) ? (int) $_POST['product_crand'] : 0;
    $legacyItemId = isset($_POST['legacy_item_id']) ? (int) $_POST['legacy_item_id'] : null;

    if ($action === 'save') {
        $probability = isset($_POST['probability']) ? (float) $_POST['probability'] : -1;
        $maxCount = isset($_POST['max_count']) ? (int) $_POST['max_count'] : 0;

        $saveResp = ShipmentController::upsertShipmentPoolItem($productCtime, $productCrand, $probability, $maxCount, $legacyItemId);
        $alertMessage = $saveResp->message;
        $alertVariant = $saveResp->success ? 'success' : 'danger';
    } elseif ($action === 'delete') {
        $deleteResp = ShipmentController::deleteShipmentPoolItem($productCtime, $productCrand, $legacyItemId);
        $alertMessage = $deleteResp->message;
        $alertVariant = $deleteResp->success ? 'success' : 'danger';
    } elseif ($action === 'create_manifest') {
        $manifestExistsResp = ShipmentController::shipmentManifestExists($activeTrackingNumber);
        $manifestExists = $manifestExistsResp->success && ($manifestExistsResp->data['exists'] ?? false);

        if ($manifestExists) {
            $alertMessage = "A shipment manifest already exists for tracking #{$activeTrackingNumber}.";
            $alertVariant = 'warning';
        } else {
            $manifestResp = ShipmentController::createShipmentManifest($activeTrackingNumber);
            $alertMessage = $manifestResp->message;
            $alertVariant = $manifestResp->success ? 'success' : 'danger';
        }
    } elseif ($action === 'create_product') {
        $storeLocator = trim($_POST['store_locator'] ?? '');
        $baseItemId = isset($_POST['base_item_id']) ? (int) $_POST['base_item_id'] : 0;
        $productName = trim($_POST['product_name'] ?? '');
        $productDesc = trim($_POST['product_description'] ?? '');
        $productLocator = trim($_POST['product_locator'] ?? '');

        $priceAmounts = $_POST['price_amount'] ?? [];
        $priceCurrencies = $_POST['price_currency'] ?? [];
        $priceItemIds = $_POST['price_item_id'] ?? [];

        $lootIdsRaw = array_filter(array_map('trim', explode(',', $_POST['stock_loot_ids'] ?? '')));
        $lootQuantity = max(1, (int) ($_POST['stock_quantity'] ?? 1));

        if ($storeLocator === '' || $baseItemId <= 0) {
            $alertMessage = 'Store locator and base item are required to create a product.';
            $alertVariant = 'danger';
        } else {
            $storeResp = StoreController::getStoreByLocator($storeLocator);
            $itemResp = ItemController::getItemById(new \Kickback\Backend\Views\vRecordId('', $baseItemId));

            $priceComponents = [];
            foreach ($priceAmounts as $idx => $amountRaw) {
                $amount = (int) $amountRaw;
                $currency = $priceCurrencies[$idx] ?? '';
                if ($amount <= 0 || $currency === '') {
                    continue;
                }

                $component = new \Kickback\Backend\Views\vPriceComponent('', 0, $amount);
                $component->currencyCode = \Kickback\Backend\Models\Enums\CurrencyCode::from($currency);

                $priceItemId = isset($priceItemIds[$idx]) ? (int) $priceItemIds[$idx] : 0;
                if ($priceItemId > 0) {
                    $component->item = new \Kickback\Backend\Views\vItem('', $priceItemId);
                }

                $priceComponents[] = $component;
            }

            $lootLinks = [];
            foreach ($lootIdsRaw as $lootId) {
                $lootCrand = (int) $lootId;
                if ($lootCrand <= 0) {
                    continue;
                }
                $loot = new \Kickback\Backend\Views\vLoot('', $lootCrand);
                $loot->quantity = $lootQuantity;
                $lootLinks[] = $loot;
            }

            if (!$storeResp->success) {
                $alertMessage = "Unable to load store: {$storeResp->message}";
                $alertVariant = 'danger';
            } elseif (!$itemResp->success) {
                $alertMessage = "Unable to load base item: {$itemResp->message}";
                $alertVariant = 'danger';
            } elseif (count($priceComponents) === 0) {
                $alertMessage = 'At least one valid price component is required.';
                $alertVariant = 'danger';
            } else {
                $productResp = StoreController::createProductFromItemAndPrice(
                    $itemResp->data,
                    $storeResp->data,
                    $priceComponents,
                    $lootLinks,
                    $productName,
                    $productDesc,
                    $productLocator === '' ? null : $productLocator
                );

                $alertMessage = $productResp->message;
                if ($productResp->success && isset($productResp->data['productId'])) {
                    $created = $productResp->data['productId'];
                    $alertMessage .= " — New Product ID: {$created->ctime}|{$created->crand}";
                }
                $alertVariant = $productResp->success ? 'success' : 'danger';
            }
        }
    }
}

$poolResp = ShipmentController::getShipmentPool();
$poolItems = $poolResp->success ? $poolResp->data : [];

$productOptionsResp = ShipmentController::getShipmentProductPoolOptions();
$productOptions = $productOptionsResp->success ? $productOptionsResp->data : [];

$itemOptionsResp = ItemController::getAllItems(true);
$itemOptions = $itemOptionsResp->success ? $itemOptionsResp->data : [];

$storeOptionsResp = StoreController::getAllStores();
$storeOptions = $storeOptionsResp->success ? $storeOptionsResp->data : [];

$manifestExistsResp = ShipmentController::shipmentManifestExists($activeTrackingNumber);
$manifestExists = $manifestExistsResp->success && ($manifestExistsResp->data['exists'] ?? false);

?>

<!DOCTYPE html>
<html lang="en">


<?php require("php-components/base-page-head.php"); ?>

<body class="bg-body-secondary container p-0">

    <?php

    require("php-components/base-page-components.php");

    require("php-components/ad-carousel.php");

    ?>



    <!--MAIN CONTENT-->
    <main class="container pt-3 bg-body" style="margin-bottom: 56px;">
        <div class="row">
            <div class="col-12 col-xl-9">


                <?php


                $activePageName = "Shipment Pool";
                require("php-components/base-page-breadcrumbs.php");


                ?>

                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-3">
                    <div>
                        <h1 class="h3 mb-1">Shipment Pool Manager</h1>
                        <p class="text-muted mb-0">Control which items populate Emberwood shipment manifests.</p>
                    </div>
                    <span class="badge text-bg-dark">Admin Only</span>
                </div>

                <?php if ($alertMessage !== ''): ?>
                    <div class="alert alert-<?= $alertVariant ?> alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($alertMessage) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if (!$poolResp->success): ?>
                    <div class="alert alert-danger" role="alert">
                        <?= htmlspecialchars($poolResp->message) ?>
                    </div>
                <?php endif; ?>

                <?php if (!$productOptionsResp->success): ?>
                    <div class="alert alert-danger" role="alert">
                        <?= htmlspecialchars($productOptionsResp->message) ?>
                    </div>
                <?php endif; ?>

                <?php if (!$itemOptionsResp->success): ?>
                    <div class="alert alert-danger" role="alert">
                        <?= htmlspecialchars($itemOptionsResp->message) ?>
                    </div>
                <?php endif; ?>

                <?php if (!$storeOptionsResp->success): ?>
                    <div class="alert alert-danger" role="alert">
                        <?= htmlspecialchars($storeOptionsResp->message) ?>
                    </div>
                <?php endif; ?>

                <div class="card mb-4">
                    <div class="card-body d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                        <div>
                            <h2 class="h5 mb-1">Active Emberwood Shipment</h2>
                            <p class="text-muted mb-2">Tracking #<?= htmlspecialchars($activeTrackingNumber); ?></p>
                            <?php if ($manifestExists): ?>
                                <span class="badge text-bg-success">Manifest ready</span>
                            <?php else: ?>
                                <span class="badge text-bg-warning text-dark">No manifest created yet</span>
                            <?php endif; ?>
                            <?php if (!$manifestExistsResp->success): ?>
                                <div class="text-danger small mt-1">Unable to verify manifest status: <?= htmlspecialchars($manifestExistsResp->message); ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="d-flex gap-2 align-items-center">
                            <?php if (!$manifestExists): ?>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="create_manifest" />
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-clipboard-plus me-1"></i> Create Manifest from Pool
                                    </button>
                                </form>
                            <?php else: ?>
                                <span class="text-muted small">Manifest generated for current shipment.</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-body d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                        <div>
                        <h2 class="h5 mb-1">Add Products to the Pool</h2>
                        <p class="text-muted mb-0">Use the product picker to search by name or locator, then set the probability and count.</p>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-secondary" data-open-create-product>
                                <i class="bi bi-tools me-1"></i> Create Product
                            </button>
                            <button type="button" class="btn bg-ranked-1 text-white" data-open-pool-modal>
                                <i class="bi bi-plus-lg me-1"></i> Add Product to Pool
                            </button>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header bg-light">
                        <strong>Current Shipment Pool</strong>
                    </div>
                    <div class="card-body">
                        <?php if (empty($poolItems)): ?>
                            <p class="text-muted mb-0">No products configured yet.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table align-middle">
                                    <thead>
                                        <tr>
                                            <th scope="col">Product</th>
                                            <th scope="col">Probability</th>
                                            <th scope="col">Max Count</th>
                                            <th scope="col" class="text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($poolItems as $entry): ?>
                                            <?php $product = $entry['product']; $legacyItem = $entry['item']; ?>
                                            <tr>
                                                <td class="align-middle">
                                                    <div class="d-flex align-items-center gap-2">
                                                        <?php if ($product?->mediaSmall && $product->mediaSmall->isValid()): ?>
                                                            <img src="<?= htmlspecialchars($product->mediaSmall->getFullPath()) ?>" alt="<?= htmlspecialchars($product->name) ?>" width="40" height="40" class="rounded">
                                                        <?php elseif ($legacyItem?->iconSmall && $legacyItem->iconSmall->isValid()): ?>
                                                            <img src="<?= htmlspecialchars($legacyItem->iconSmall->getFullPath()) ?>" alt="<?= htmlspecialchars($legacyItem->name) ?>" width="40" height="40" class="rounded">
                                                        <?php endif; ?>
                                                        <div>
                                                            <div class="fw-semibold">
                                                                <?php if ($product): ?>
                                                                    #<?= $product->crand ?> — <?= htmlspecialchars($product->name) ?>
                                                                <?php elseif ($legacyItem): ?>
                                                                    #<?= $legacyItem->crand ?> — <?= htmlspecialchars($legacyItem->name) ?>
                                                                <?php else: ?>
                                                                    Unknown entry
                                                                <?php endif; ?>
                                                            </div>
                                                            <div class="text-muted small">
                                                                <?php if ($product): ?>
                                                                    Store: <?= htmlspecialchars($product->store->name ?? 'Unknown') ?>
                                                                <?php elseif ($legacyItem): ?>
                                                                    Legacy Item
                                                                <?php else: ?>
                                                                    Missing reference
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="align-middle" style="max-width: 140px;">
                                                    <input type="number" min="0" max="1" step="0.01" class="form-control form-control-sm" name="probability" value="<?= htmlspecialchars((string) $entry['probability']) ?>" form="update-<?= $item->crand ?>" required>
                                                </td>
                                                <td class="align-middle" style="max-width: 120px;">
                                                    <input type="number" min="1" class="form-control form-control-sm" name="max_count" value="<?= htmlspecialchars((string) $entry['max_count']) ?>" form="update-<?= $item->crand ?>" required>
                                                </td>
                                                <td class="align-middle text-end">
                                                    <div class="btn-group" role="group">
                                                        <form method="POST" class="d-inline" id="update-<?= $product?->crand ?? $legacyItem?->crand ?>">
                                                            <input type="hidden" name="action" value="save" />
                                                            <input type="hidden" name="product_ctime" value="<?= htmlspecialchars($product?->ctime ?? '') ?>" />
                                                            <input type="hidden" name="product_crand" value="<?= htmlspecialchars((string) ($product?->crand ?? 0)) ?>" />
                                                            <input type="hidden" name="legacy_item_id" value="<?= htmlspecialchars((string) ($legacyItem->crand ?? 0)) ?>" />
                                                            <button type="submit" class="btn btn-outline-primary btn-sm">
                                                                <i class="bi bi-save me-1"></i>Update
                                                            </button>
                                                        </form>
                                                        <form method="POST" class="d-inline">
                                                            <input type="hidden" name="action" value="delete" />
                                                            <input type="hidden" name="product_ctime" value="<?= htmlspecialchars($product?->ctime ?? '') ?>" />
                                                            <input type="hidden" name="product_crand" value="<?= htmlspecialchars((string) ($product?->crand ?? 0)) ?>" />
                                                            <input type="hidden" name="legacy_item_id" value="<?= htmlspecialchars((string) ($legacyItem->crand ?? 0)) ?>" />
                                                            <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('Remove this item from the shipment pool?');">
                                                                <i class="bi bi-trash3 me-1"></i>Remove
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>

            <?php require("php-components/base-page-discord.php"); ?>
        </div>
        
        <?php
        $selectorId = 'adminProductSelector';
        require("php-components/product-selector-modal.php");

        $itemSelectorId = 'adminItemSelector';
        $selectorId = $itemSelectorId;
        require("php-components/item-selector-modal.php");
        ?>

        <div class="modal fade" id="poolItemModal" tabindex="-1" aria-labelledby="poolItemModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <form method="POST" class="modal-content" id="poolItemForm">
                    <input type="hidden" name="action" value="save" />
                    <input type="hidden" name="product_ctime" id="pool_product_ctime" required />
                    <input type="hidden" name="product_crand" id="pool_product_crand" required />
                    <input type="hidden" name="legacy_item_id" id="pool_legacy_item_id" />
                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title" id="poolItemModalLabel">Add Product to Shipment Pool</h5>
                            <p class="text-muted small mb-0">Select a product with the search modal, then configure its probability and maximum count.</p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Product</label>
                            <div class="d-flex flex-column gap-2">
                                <div class="border rounded p-3 d-flex align-items-center gap-3" data-selected-item-preview>
                                    <div class="bg-body-secondary rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                        <i class="bi bi-box-seam text-muted"></i>
                                    </div>
                                    <div>
                                        <div class="fw-semibold" data-selected-item-title>No product selected</div>
                                        <div class="text-muted small" data-selected-item-meta>Select a product to continue.</div>
                                    </div>
                                </div>
                                <div>
                                <button type="button" class="btn btn-outline-primary" data-open-product-selector>
                                    <i class="bi bi-search me-1"></i> Open Product Search
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label for="probability" class="form-label">Probability (0-1)</label>
                                <input type="number" step="0.01" min="0" max="1" class="form-control" id="probability" name="probability" value="0.25" required>
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="max_count" class="form-label">Max Count</label>
                                <input type="number" min="1" class="form-control" id="max_count" name="max_count" value="1" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn bg-ranked-1 text-white">
                            <i class="bi bi-plus-lg me-1"></i> Save to Pool
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="modal fade" id="createProductModal" tabindex="-1" aria-labelledby="createProductModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <form method="POST" class="modal-content" id="createProductForm">
                    <input type="hidden" name="action" value="create_product" />
                    <input type="hidden" name="base_item_id" id="create_base_item_id" required />
                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title" id="createProductModalLabel">Create Shipment Product</h5>
                            <p class="text-muted small mb-0">Select a base item, define price components, and optionally link stock loot.</p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Base Item</label>
                                <div class="d-flex flex-column gap-2">
                                    <div class="border rounded p-3 d-flex align-items-center gap-3" data-selected-base-item-preview>
                                        <div class="bg-body-secondary rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                            <i class="bi bi-gem text-muted"></i>
                                        </div>
                                        <div>
                                            <div class="fw-semibold" data-selected-base-item-title>No item selected</div>
                                            <div class="text-muted small" data-selected-base-item-meta>Select a base item to continue.</div>
                                        </div>
                                    </div>
                                    <div>
                                        <button type="button" class="btn btn-outline-primary" data-open-base-item-selector>
                                            <i class="bi bi-search me-1"></i> Open Item Search
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="store_locator">Store Locator</label>
                                <select class="form-select" id="store_locator" name="store_locator" required>
                                    <option value="">Select a store</option>
                                    <?php foreach ($storeOptions as $store): ?>
                                        <option value="<?= htmlspecialchars($store->locator ?? '') ?>">
                                            <?= htmlspecialchars($store->name ?? 'Unknown Store') ?> (<?= htmlspecialchars($store->locator ?? 'n/a') ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">Choose the store that will own this product.</div>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="product_locator">Product Locator (optional)</label>
                                <input type="text" class="form-control" id="product_locator" name="product_locator" placeholder="auto-generated if left blank">
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="product_name">Product Name (optional)</label>
                                <input type="text" class="form-control" id="product_name" name="product_name" placeholder="Defaults to base item name">
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="product_description">Product Description (optional)</label>
                                <input type="text" class="form-control" id="product_description" name="product_description" placeholder="Defaults to base item description">
                            </div>
                        </div>

                        <hr>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="mb-0">Price Components</h6>
                            <button type="button" class="btn btn-sm btn-outline-primary" data-add-price-row>
                                <i class="bi bi-plus-lg me-1"></i>Add Component
                            </button>
                        </div>
                        <div class="table-responsive mb-3">
                            <table class="table align-middle mb-0" id="priceComponentTable">
                                <thead>
                                    <tr>
                                        <th style="width: 25%;">Amount</th>
                                        <th style="width: 25%;">Currency</th>
                                        <th style="width: 35%;">Item (optional)</th>
                                        <th style="width: 15%;"></th>
                                    </tr>
                                </thead>
                                <tbody data-price-rows>
                                </tbody>
                            </table>
                        </div>
                        <div class="alert alert-info small" role="alert">
                            Each price component can be ADA/USD or item-based, and you can use the selector to pick the item instead of entering an ID.
                        </div>

                        <hr>
                        <div class="row g-3">
                            <div class="col-12 col-md-8">
                                <label class="form-label" for="stock_loot_ids">Stock Loot IDs (optional)</label>
                                <input type="text" class="form-control" id="stock_loot_ids" name="stock_loot_ids" placeholder="Comma separated loot IDs">
                                <div class="form-text">Linked as product stock for shipments.</div>
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label" for="stock_quantity">Quantity per Loot</label>
                                <input type="number" min="1" class="form-control" id="stock_quantity" name="stock_quantity" value="1">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn bg-ranked-1 text-white">
                            <i class="bi bi-save me-1"></i> Create Product
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <?php require("php-components/base-page-footer.php"); ?>
    </main>


    <?php require("php-components/base-page-javascript.php"); ?>
    <script src="<?= Version::urlBetaPrefix(); ?>/assets/js/product-selector.js"></script>
    <script src="<?= Version::urlBetaPrefix(); ?>/assets/js/item-selector.js"></script>
    <script>
        (function () {
            const selectorId = 'adminProductSelector';
            const selectorModal = ProductSelector.init(selectorId);
            const poolModalEl = document.getElementById('poolItemModal');
            const poolModal = poolModalEl ? bootstrap.Modal.getOrCreateInstance(poolModalEl) : null;
            const openPoolButton = document.querySelector('[data-open-pool-modal]');
            const openSearchButton = document.querySelector('[data-open-product-selector]');
            const selectedTitle = document.querySelector('[data-selected-item-title]');
            const selectedMeta = document.querySelector('[data-selected-item-meta]');
            const selectedPreview = document.querySelector('[data-selected-item-preview]');
            const productCtimeInput = document.getElementById('pool_product_ctime');
            const productCrandInput = document.getElementById('pool_product_crand');
            const legacyItemIdInput = document.getElementById('pool_legacy_item_id');
            const poolForm = document.getElementById('poolItemForm');
            const probabilityInput = document.getElementById('probability');
            const maxCountInput = document.getElementById('max_count');
            let shouldReopenPoolAfterSelect = false;

            const itemSelectorId = 'adminItemSelector';
            const itemSelectorModal = ItemSelector.init(itemSelectorId);
            const createProductModalEl = document.getElementById('createProductModal');
            const createProductModal = createProductModalEl ? bootstrap.Modal.getOrCreateInstance(createProductModalEl) : null;
            const openCreateProductButton = document.querySelector('[data-open-create-product]');
            const createProductForm = document.getElementById('createProductForm');
            const baseItemIdInput = document.getElementById('create_base_item_id');
            const baseItemPreview = document.querySelector('[data-selected-base-item-preview]');
            const baseItemTitle = document.querySelector('[data-selected-base-item-title]');
            const baseItemMeta = document.querySelector('[data-selected-base-item-meta]');
            const priceRows = document.querySelector('[data-price-rows]');
            let activePriceRowItem = null;

            let reopenCreateProductAfterSelector = false;

            if (itemSelectorModal) {
                itemSelectorModal.addEventListener('hidden.bs.modal', () => {
                    activePriceRowItem = null;

                    if (reopenCreateProductAfterSelector) {
                        createProductModal?.show();
                        reopenCreateProductAfterSelector = false;
                    }
                });
            }

            function setPriceItemSelection(row, item) {
                if (!row) return;
                const itemInput = row.querySelector('[data-price-item-input]');
                const preview = row.querySelector('[data-price-item-preview]');
                const fallback = row.querySelector('[data-price-item-fallback]');
                const itemTitle = row.querySelector('[data-price-item-title]');
                const itemMeta = row.querySelector('[data-price-item-meta]');

                if (itemInput) itemInput.value = item?.crand || '';

                if (preview) {
                    preview.querySelector('img')?.remove();
                    if (item?.icon) {
                        const img = document.createElement('img');
                        img.src = item.icon;
                        img.alt = item.name || 'Selected item';
                        img.width = 40;
                        img.height = 40;
                        img.className = 'rounded';
                        preview.prepend(img);
                    }
                }

                if (fallback) {
                    fallback.classList.toggle('d-none', !!item?.icon);
                }

                if (itemTitle) {
                    itemTitle.textContent = item ? `#${item.crand} — ${item.name}` : 'No item selected';
                    itemTitle.classList.toggle('text-muted', !item);
                }

                if (itemMeta) {
                    itemMeta.textContent = item ? (item.type || 'Item selected') : 'Select an item to use as currency.';
                    itemMeta.classList.toggle('text-muted', !item);
                    itemMeta.classList.remove('text-danger');
                }
            }

            function clearPriceItemSelection(row) {
                setPriceItemSelection(row, null);
            }

            function togglePriceItemSection(row) {
                const currencySelect = row?.querySelector('[data-price-currency]');
                const itemSection = row?.querySelector('[data-price-item-section]');
                const itemInput = row?.querySelector('[data-price-item-input]');
                const selectButton = row?.querySelector('[data-select-price-item]');
                const isItemCurrency = currencySelect?.value === 'ITEM';
                if (itemSection) itemSection.classList.toggle('opacity-50', !isItemCurrency);
                if (selectButton) selectButton.disabled = false;
                if (itemInput) {
                    itemInput.required = isItemCurrency;
                    if (!isItemCurrency) {
                        clearPriceItemSelection(row);
                    }
                }
            }

            function addPriceRow(amount = '', currency = 'ADA', itemId = '') {
                if (!priceRows) return;
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td><input type="number" min="1" class="form-control" name="price_amount[]" value="${amount}" required></td>
                    <td>
                        <select class="form-select" name="price_currency[]" required data-price-currency>
                            <option value="ADA" ${currency === 'ADA' ? 'selected' : ''}>ADA</option>
                            <option value="USD" ${currency === 'USD' ? 'selected' : ''}>USD</option>
                            <option value="ITEM" ${currency === 'ITEM' ? 'selected' : ''}>Item</option>
                        </select>
                    </td>
                    <td>
                        <input type="hidden" name="price_item_id[]" value="${itemId}" data-price-item-input>
                        <div class="d-flex flex-column gap-2" data-price-item-section>
                            <div class="d-flex align-items-center gap-3" data-price-item-preview>
                                <div class="bg-body-secondary rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;" data-price-item-fallback>
                                    <i class="bi bi-gem text-muted"></i>
                                </div>
                                <div>
                                    <div class="fw-semibold text-muted" data-price-item-title>${itemId ? `Item #${itemId}` : 'No item selected'}</div>
                                    <div class="text-muted small" data-price-item-meta>Select an item to use as currency.</div>
                                </div>
                            </div>
                            <div class="d-flex flex-wrap gap-2">
                                <button type="button" class="btn btn-outline-primary btn-sm" data-select-price-item>
                                    <i class="bi bi-search me-1"></i>Select Item
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" data-clear-price-item>
                                    <i class="bi bi-x-lg me-1"></i>Clear
                                </button>
                            </div>
                            <div class="form-text">Choose the item to use as currency.</div>
                        </div>
                    </td>
                    <td class="text-end"><button type="button" class="btn btn-outline-danger btn-sm d-inline-flex align-items-center" data-remove-price-row><i class="bi bi-trash me-1"></i>Delete</button></td>
                `;
                setPriceItemSelection(row, itemId ? { crand: itemId, name: `Item #${itemId}` } : null);
                togglePriceItemSection(row);
                priceRows.appendChild(row);
            }

            function resetPriceRows() {
                if (!priceRows) return;
                priceRows.innerHTML = '';
                addPriceRow(1, 'ADA', '');
            }

            function resetBaseItemPreview() {
                if (baseItemTitle) baseItemTitle.textContent = 'No item selected';
                if (baseItemMeta) {
                    baseItemMeta.textContent = 'Select a base item to continue.';
                    baseItemMeta.classList.remove('text-danger');
                }
                if (baseItemPreview) {
                    baseItemPreview.querySelector('img')?.remove();
                    const fallback = baseItemPreview.querySelector('.bg-body-secondary');
                    if (fallback) fallback.classList.remove('d-none');
                }
                if (baseItemIdInput) baseItemIdInput.value = '';
            }

            if (openCreateProductButton && createProductModal) {
                openCreateProductButton.addEventListener('click', () => {
                    resetBaseItemPreview();
                    resetPriceRows();
                    createProductForm?.reset();
                    createProductModal.show();
                });
            }

            document.addEventListener('item-selector:selected', (event) => {
                if (event.detail.selectorId !== itemSelectorId) return;
                const item = event.detail;

                if (activePriceRowItem) {
                    const currencySelect = activePriceRowItem.querySelector('[data-price-currency]');
                    if (currencySelect && currencySelect.value !== 'ITEM') {
                        currencySelect.value = 'ITEM';
                    }
                    setPriceItemSelection(activePriceRowItem, item);
                    togglePriceItemSection(activePriceRowItem);
                    activePriceRowItem = null;
                } else {
                    if (baseItemIdInput) baseItemIdInput.value = item.crand;
                    if (baseItemTitle) baseItemTitle.textContent = `#${item.crand} — ${item.name}`;
                    if (baseItemMeta) baseItemMeta.textContent = item.type ? `${item.type}` : 'Base item selected';
                    if (baseItemPreview) {
                        baseItemPreview.querySelector('img')?.remove();
                        const fallback = baseItemPreview.querySelector('.bg-body-secondary');
                        if (fallback) fallback.classList.toggle('d-none', !!item.icon);
                        if (item.icon) {
                            const img = document.createElement('img');
                            img.src = item.icon;
                            img.alt = item.name || 'Selected item';
                            img.width = 48;
                            img.height = 48;
                            img.className = 'rounded';
                            baseItemPreview.prepend(img);
                        }
                    }
                }

                if (itemSelectorModal) {
                    const selectorInstance = bootstrap.Modal.getOrCreateInstance(itemSelectorModal);
                    selectorInstance.hide();
                }
            });

            function openItemSelectorFromCreateProduct() {
                if (!itemSelectorModal) {
                    return;
                }

                if (createProductModalEl?.classList.contains('show')) {
                    reopenCreateProductAfterSelector = true;
                    createProductModal?.hide();
                }

                const modalInstance = bootstrap.Modal.getOrCreateInstance(itemSelectorModal);
                modalInstance.show();
            }

            document.addEventListener('click', (event) => {
                const trigger = event.target.closest('[data-remove-price-row]');
                if (trigger) {
                    const row = trigger.closest('tr');
                    row?.remove();
                    return;
                }

                const itemSelectTrigger = event.target.closest('[data-select-price-item]');
                if (itemSelectTrigger && itemSelectorModal) {
                    activePriceRowItem = itemSelectTrigger.closest('tr');
                    openItemSelectorFromCreateProduct();
                    return;
                }

                const clearItemTrigger = event.target.closest('[data-clear-price-item]');
                if (clearItemTrigger) {
                    const row = clearItemTrigger.closest('tr');
                    clearPriceItemSelection(row);
                    togglePriceItemSection(row);
                }
            });

            document.addEventListener('change', (event) => {
                const currencySelect = event.target.closest('[data-price-currency]');
                if (currencySelect) {
                    const row = currencySelect.closest('tr');
                    togglePriceItemSection(row);
                }
            });

            const addPriceButton = document.querySelector('[data-add-price-row]');
            if (addPriceButton) {
                addPriceButton.addEventListener('click', () => addPriceRow());
            }

            if (createProductForm) {
                createProductForm.addEventListener('submit', (event) => {
                    if (!baseItemIdInput || baseItemIdInput.value === '') {
                        event.preventDefault();
                        baseItemMeta?.classList.add('text-danger');
                        baseItemMeta?.classList.remove('text-muted');
                        baseItemMeta?.classList.add('fw-semibold');
                        if (baseItemMeta) baseItemMeta.textContent = 'Please select a base item.';
                        return;
                    }

                    if (!priceRows || priceRows.children.length === 0) {
                        event.preventDefault();
                        addPriceRow();
                    }
                });
            }

            if (itemSelectorModal) {
                const trigger = document.querySelector('[data-open-base-item-selector]');
                trigger?.addEventListener('click', openItemSelectorFromCreateProduct);
            }

            function updatePreview(item) {
                if (!selectedPreview || !selectedTitle || !selectedMeta) {
                    return;
                }

                selectedPreview.querySelector('img')?.remove();
                const fallback = selectedPreview.querySelector('.bg-body-secondary');
                if (fallback) {
                    fallback.classList.toggle('d-none', !!item?.icon);
                }

                if (item?.icon) {
                    const img = document.createElement('img');
                    img.src = item.icon;
                    img.alt = item.name || 'Selected item';
                    img.width = 48;
                    img.height = 48;
                    img.className = 'rounded';
                    selectedPreview.prepend(img);
                }

                selectedTitle.textContent = item ? `#${item.crand} — ${item.name}` : 'No product selected';
                selectedMeta.textContent = item ? (item.store ? `Store: ${item.store}` : 'Available product') : 'Select a product to continue.';
                selectedMeta.classList.toggle('text-danger', !item);
            }

            function resetForm() {
                if (productCtimeInput) {
                    productCtimeInput.value = '';
                }
                if (productCrandInput) {
                    productCrandInput.value = '';
                }
                if (legacyItemIdInput) {
                    legacyItemIdInput.value = '';
                }
                if (probabilityInput) {
                    probabilityInput.value = '0.25';
                }
                if (maxCountInput) {
                    maxCountInput.value = '1';
                }
                updatePreview(null);
            }

            if (openPoolButton && poolModal) {
                openPoolButton.addEventListener('click', () => {
                    resetForm();
                    poolModal.show();
                });
            }

            if (openSearchButton) {
                openSearchButton.addEventListener('click', () => {
                    if (!selectorModal) return;
                    shouldReopenPoolAfterSelect = poolModalEl?.classList.contains('show') ?? false;
                    if (shouldReopenPoolAfterSelect) {
                        poolModal?.hide();
                    }
                    const modalInstance = bootstrap.Modal.getOrCreateInstance(selectorModal);
                    modalInstance.show();
                });
            }

            document.addEventListener('product-selector:selected', (event) => {
                if (event.detail.selectorId !== selectorId) {
                    return;
                }

                const item = event.detail;
                if (productCtimeInput) {
                    productCtimeInput.value = item.ctime;
                }
                if (productCrandInput) {
                    productCrandInput.value = item.crand;
                }
                if (legacyItemIdInput) {
                    legacyItemIdInput.value = item.legacyItemId || '';
                }
                updatePreview(item);

                const modalInstance = bootstrap.Modal.getOrCreateInstance(selectorModal);
                modalInstance.hide();
                if (shouldReopenPoolAfterSelect && poolModal) {
                    poolModal.show();
                }
                shouldReopenPoolAfterSelect = false;
            });

            if (poolForm) {
                poolForm.addEventListener('submit', (event) => {
                    if (!productCtimeInput || productCtimeInput.value === '' || !productCrandInput || productCrandInput.value === '') {
                        event.preventDefault();
                        if (selectedMeta) {
                            selectedMeta.textContent = 'Please select a product before saving to the pool.';
                            selectedMeta.classList.add('text-danger');
                        }
                        if (openSearchButton) {
                            openSearchButton.focus();
                        }
                    }
                });
            }
        })();
    </script>

</body>

</html>
