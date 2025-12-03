<?php
$pageTitle = "Shipment Pool Manager";
$pageImage = "https://kickback-kingdom.com/assets/media/context/emberwood-ship.png";
$pageDesc = "Admin tools for configuring the Emberwood shipment manifest pool.";

require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__) . "/Kickback/init.php");

$session = require(\Kickback\SCRIPT_ROOT . "/api/v1/engine/session/verifySession.php");
require("php-components/base-page-pull-active-account-info.php");

use Kickback\Backend\Controllers\ShipmentController;
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
                    <div class="card-header bg-light">
                        <strong>Add or Update Item</strong>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="row g-3 align-items-end">
                            <input type="hidden" name="action" value="save" />
                            <div class="col-12 col-md-6 col-lg-5">
                                <label for="item_id" class="form-label">Item</label>
                                <select class="form-select" id="item_id" name="item_id" required>
                                    <option value="" disabled selected>Select an item</option>
                                    <?php foreach ($itemOptions as $option): ?>
                                        <option value="<?= $option->crand ?>">#<?= $option->crand ?> — <?= htmlspecialchars($option->name) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-6 col-md-3 col-lg-2">
                                <label for="probability" class="form-label">Probability (0-1)</label>
                                <input type="number" step="0.01" min="0" max="1" class="form-control" id="probability" name="probability" value="0.25" required>
                            </div>
                            <div class="col-6 col-md-3 col-lg-2">
                                <label for="max_count" class="form-label">Max Count</label>
                                <input type="number" min="1" class="form-control" id="max_count" name="max_count" value="1" required>
                            </div>
                            <div class="col-12 col-md-12 col-lg-3 d-grid">
                                <button type="submit" class="btn bg-ranked-1 text-white">
                                    <i class="bi bi-plus-lg me-1"></i> Save to Pool
                                </button>
                            </div>
                        </form>
                        <p class="text-muted small mt-3 mb-0">Existing entries will be updated if the same item is selected.</p>
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
        <?php require("php-components/base-page-footer.php"); ?>
    </main>


    <?php require("php-components/base-page-javascript.php"); ?>

</body>

</html>
