<?php
$pageTitle = "Shipment Pool Manager";
$pageImage = "https://kickback-kingdom.com/assets/media/context/emberwood-ship.png";
$pageDesc = "Admin tools for configuring the Emberwood shipment manifest pool.";

require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__) . "/Kickback/init.php");

$session = require(\Kickback\SCRIPT_ROOT . "/api/v1/engine/session/verifySession.php");
require("php-components/base-page-pull-active-account-info.php");

use Kickback\Backend\Controllers\ShipmentController;
use Kickback\Common\Version;
use Kickback\Services\Session;

if (!Session::isAdmin()) {
    header('Location: index.php');
    exit();
}

$alertMessage = '';
$alertVariant = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $itemId = isset($_POST['item_id']) ? (int) $_POST['item_id'] : 0;

    if ($action === 'save') {
        $probability = isset($_POST['probability']) ? (float) $_POST['probability'] : -1;
        $maxCount = isset($_POST['max_count']) ? (int) $_POST['max_count'] : 0;

        $saveResp = ShipmentController::upsertShipmentPoolItem($itemId, $probability, $maxCount);
        $alertMessage = $saveResp->message;
        $alertVariant = $saveResp->success ? 'success' : 'danger';
    } elseif ($action === 'delete') {
        $deleteResp = ShipmentController::deleteShipmentPoolItem($itemId);
        $alertMessage = $deleteResp->message;
        $alertVariant = $deleteResp->success ? 'success' : 'danger';
    }
}

$poolResp = ShipmentController::getShipmentPool();
$poolItems = $poolResp->success ? $poolResp->data : [];

$itemOptionsResp = ShipmentController::getShipmentItemPoolOptions();
$itemOptions = $itemOptionsResp->success ? $itemOptionsResp->data : [];

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

                <?php if (!$itemOptionsResp->success): ?>
                    <div class="alert alert-danger" role="alert">
                        <?= htmlspecialchars($itemOptionsResp->message) ?>
                    </div>
                <?php endif; ?>

                <div class="card mb-4">
                    <div class="card-body d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                        <div>
                            <h2 class="h5 mb-1">Add Items to the Pool</h2>
                            <p class="text-muted mb-0">Use the reusable item picker to search by name or ID, then set the probability and count.</p>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn bg-ranked-1 text-white" data-open-pool-modal>
                                <i class="bi bi-plus-lg me-1"></i> Add Item to Pool
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
                            <p class="text-muted mb-0">No items configured yet.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table align-middle">
                                    <thead>
                                        <tr>
                                            <th scope="col">Item</th>
                                            <th scope="col">Probability</th>
                                            <th scope="col">Max Count</th>
                                            <th scope="col" class="text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($poolItems as $entry): ?>
                                            <?php $item = $entry['item']; ?>
                                            <tr>
                                                <td class="align-middle">
                                                    <div class="d-flex align-items-center gap-2">
                                                        <?php if ($item->iconSmall && $item->iconSmall->isValid()): ?>
                                                            <img src="<?= htmlspecialchars($item->iconSmall->getFullPath()) ?>" alt="<?= htmlspecialchars($item->name) ?>" width="40" height="40" class="rounded">
                                                        <?php endif; ?>
                                                        <div>
                                                            <div class="fw-semibold">#<?= $item->crand ?> — <?= htmlspecialchars($item->name) ?></div>
                                                            <div class="text-muted small">Rarity: <?= $item->rarity->name ?? 'Unknown' ?></div>
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
                                                        <form method="POST" class="d-inline" id="update-<?= $item->crand ?>">
                                                            <input type="hidden" name="action" value="save" />
                                                            <input type="hidden" name="item_id" value="<?= $item->crand ?>" />
                                                            <button type="submit" class="btn btn-outline-primary btn-sm">
                                                                <i class="bi bi-save me-1"></i>Update
                                                            </button>
                                                        </form>
                                                        <form method="POST" class="d-inline">
                                                            <input type="hidden" name="action" value="delete" />
                                                            <input type="hidden" name="item_id" value="<?= $item->crand ?>" />
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
        $selectorId = 'adminItemSelector';
        require("php-components/item-selector-modal.php");
        ?>

        <div class="modal fade" id="poolItemModal" tabindex="-1" aria-labelledby="poolItemModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <form method="POST" class="modal-content" id="poolItemForm">
                    <input type="hidden" name="action" value="save" />
                    <input type="hidden" name="item_id" id="pool_item_id" required />
                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title" id="poolItemModalLabel">Add Item to Shipment Pool</h5>
                            <p class="text-muted small mb-0">Select an item with the search modal, then configure its probability and maximum count.</p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Item</label>
                            <div class="d-flex flex-column gap-2">
                                <div class="border rounded p-3 d-flex align-items-center gap-3" data-selected-item-preview>
                                    <div class="bg-body-secondary rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                        <i class="bi bi-box-seam text-muted"></i>
                                    </div>
                                    <div>
                                        <div class="fw-semibold" data-selected-item-title>No item selected</div>
                                        <div class="text-muted small" data-selected-item-meta>Select an item to continue.</div>
                                    </div>
                                </div>
                                <div>
                                    <button type="button" class="btn btn-outline-primary" data-open-item-selector>
                                        <i class="bi bi-search me-1"></i> Open Item Search
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
    <script src="<?= Version::urlBetaPrefix(); ?>/assets/js/item-selector.js"></script>
    <script>
        (function () {
            const selectorId = 'adminItemSelector';
            const selectorModal = ItemSelector.init(selectorId);
            const poolModalEl = document.getElementById('poolItemModal');
            const poolModal = poolModalEl ? bootstrap.Modal.getOrCreateInstance(poolModalEl) : null;
            const openPoolButton = document.querySelector('[data-open-pool-modal]');
            const openSearchButton = document.querySelector('[data-open-item-selector]');
            const selectedTitle = document.querySelector('[data-selected-item-title]');
            const selectedMeta = document.querySelector('[data-selected-item-meta]');
            const selectedPreview = document.querySelector('[data-selected-item-preview]');
            const itemIdInput = document.getElementById('pool_item_id');
            const poolForm = document.getElementById('poolItemForm');
            const probabilityInput = document.getElementById('probability');
            const maxCountInput = document.getElementById('max_count');

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

                selectedTitle.textContent = item ? `#${item.crand} — ${item.name}` : 'No item selected';
                selectedMeta.textContent = item ? `Rarity: ${item.rarity || 'Unknown'}` : 'Select an item to continue.';
                selectedMeta.classList.toggle('text-danger', !item);
            }

            function resetForm() {
                if (itemIdInput) {
                    itemIdInput.value = '';
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
                    const modalInstance = bootstrap.Modal.getOrCreateInstance(selectorModal);
                    modalInstance.show();
                });
            }

            document.addEventListener('item-selector:selected', (event) => {
                if (event.detail.selectorId !== selectorId) {
                    return;
                }

                const item = event.detail;
                if (itemIdInput) {
                    itemIdInput.value = item.crand;
                }
                updatePreview(item);
            });

            if (poolForm) {
                poolForm.addEventListener('submit', (event) => {
                    if (!itemIdInput || itemIdInput.value === '') {
                        event.preventDefault();
                        if (selectedMeta) {
                            selectedMeta.textContent = 'Please select an item before saving to the pool.';
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
