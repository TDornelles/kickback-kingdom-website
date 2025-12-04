<?php
$pageTitle = "Shipment Pool Manager";
$pageImage = "https://kickback-kingdom.com/assets/media/context/emberwood-ship.png";
$pageDesc = "Admin tools for configuring the Emberwood shipment manifest pool.";

require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__) . "/Kickback/init.php");

$session = require(\Kickback\SCRIPT_ROOT . "/api/v1/engine/session/verifySession.php");
require("php-components/base-page-pull-active-account-info.php");

use Kickback\Backend\Controllers\ShipmentController;
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
    }
}

$poolResp = ShipmentController::getShipmentPool();
$poolItems = $poolResp->success ? $poolResp->data : [];

$productOptionsResp = ShipmentController::getShipmentProductPoolOptions();
$productOptions = $productOptionsResp->success ? $productOptionsResp->data : [];

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
                                    <button type="button" class="btn btn-outline-primary" data-open-item-selector>
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
        <?php require("php-components/base-page-footer.php"); ?>
    </main>


    <?php require("php-components/base-page-javascript.php"); ?>
    <script src="<?= Version::urlBetaPrefix(); ?>/assets/js/product-selector.js"></script>
    <script>
        (function () {
            const selectorId = 'adminProductSelector';
            const selectorModal = ProductSelector.init(selectorId);
            const poolModalEl = document.getElementById('poolItemModal');
            const poolModal = poolModalEl ? bootstrap.Modal.getOrCreateInstance(poolModalEl) : null;
            const openPoolButton = document.querySelector('[data-open-pool-modal]');
            const openSearchButton = document.querySelector('[data-open-item-selector]');
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
