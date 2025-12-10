<?php
$pageTitle = "Item Manager";
$pageImage = "https://kickback-kingdom.com/assets/media/context/market-hall.png";
$pageDesc = "Admin panel to browse, create, update, and remove items from the database.";

require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__) . "/Kickback/init.php");

$session = require(\Kickback\SCRIPT_ROOT . "/api/v1/engine/session/verifySession.php");
require("php-components/base-page-pull-active-account-info.php");

use Kickback\Backend\Controllers\ItemController;
use Kickback\Backend\Models\ForeignRecordId;
use Kickback\Backend\Models\Item;
use Kickback\Backend\Models\ItemCategory;
use Kickback\Backend\Models\ItemEquipmentSlot;
use Kickback\Backend\Models\ItemRarity;
use Kickback\Backend\Models\ItemType;
use Kickback\Backend\Views\vRecordId;
use Kickback\Common\Version;
use Kickback\Services\Session;

if (!Session::isAdmin()) {
    header('Location: index.php');
    exit();
}

$alertMessage = '';
$alertVariant = '';

function buildItemFromPost(): Item
{
    $item = new Item();

    $item->name = trim($_POST['name'] ?? '');
    $item->desc = trim($_POST['description'] ?? '');

    $item->type = ItemType::from((int)($_POST['type'] ?? ItemType::Standard->value));
    $item->rarity = ItemRarity::from((int)($_POST['rarity'] ?? ItemRarity::Common->value));

    $item->mediaLarge = new ForeignRecordId('', max(0, (int)($_POST['media_id_large'] ?? -1)));
    $item->mediaSmall = new ForeignRecordId('', max(0, (int)($_POST['media_id_small'] ?? -1)));
    $mediaBackId = (int)($_POST['media_id_back'] ?? -1);
    $item->mediaBack = $mediaBackId > 0 ? new ForeignRecordId('', $mediaBackId) : null;

    $item->nominatedBy = isset($_POST['nominated_by_id']) && $_POST['nominated_by_id'] !== ''
        ? new ForeignRecordId('', (int)$_POST['nominated_by_id'])
        : null;
    $item->collection = isset($_POST['collection_id']) && $_POST['collection_id'] !== ''
        ? new ForeignRecordId('', (int)$_POST['collection_id'])
        : null;

    $item->equipable = isset($_POST['equipable']) ? (bool)$_POST['equipable'] : false;
    $item->equipmentSlot = isset($_POST['equipment_slot']) && $_POST['equipment_slot'] !== ''
        ? ItemEquipmentSlot::from($_POST['equipment_slot'])
        : null;

    $item->redeemable = isset($_POST['redeemable']) ? (bool)$_POST['redeemable'] : false;
    $item->useable = isset($_POST['useable']) ? (bool)$_POST['useable'] : false;

    $item->isContainer = isset($_POST['is_container']) ? (bool)$_POST['is_container'] : false;
    $item->containerSize = (int)($_POST['container_size'] ?? -1);

    $item->containerItemCategory = isset($_POST['container_item_category']) && $_POST['container_item_category'] !== ''
        ? ItemCategory::from((int)$_POST['container_item_category'])
        : null;
    $item->itemCategory = isset($_POST['item_category']) && $_POST['item_category'] !== ''
        ? ItemCategory::from((int)$_POST['item_category'])
        : null;

    $item->fungible = isset($_POST['is_fungible']) ? (bool)$_POST['is_fungible'] : false;

    return $item;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'update') {
        $item = buildItemFromPost();

        if ($action === 'update') {
            $item->crand = (int)($_POST['item_id'] ?? -1);
        }

        $response = $action === 'create'
            ? ItemController::insertItem($item)
            : ItemController::updateItem($item);

        $alertMessage = $response->message;
        $alertVariant = $response->success ? 'success' : 'danger';
    } elseif ($action === 'delete') {
        $itemId = new vRecordId('', (int)($_POST['item_id'] ?? -1));
        $response = ItemController::deleteItem($itemId);

        $alertMessage = $response->message;
        $alertVariant = $response->success ? 'success' : 'danger';
    }
}

$itemTableResp = ItemController::getItemTable();
$itemRows = $itemTableResp->success ? $itemTableResp->data : [];

$itemTypes = ItemType::cases();
$itemRarities = ItemRarity::cases();
$equipmentSlots = ItemEquipmentSlot::cases();
$itemCategories = ItemCategory::cases();

function enumLabel(string $value): string
{
    return ucwords(strtolower(str_replace(['_', '-'], ' ', $value)));
}

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


                $activePageName = "Item Manager";
                require("php-components/base-page-breadcrumbs.php");


                ?>

            </div>

            <?php require("php-components/base-page-discord.php"); ?>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm mb-3">
                    <div class="card-body d-flex flex-column flex-md-row gap-2 align-items-md-center justify-content-between">
                        <div>
                            <h5 class="mb-1">Item Table</h5>
                            <p class="text-body-secondary mb-0">View, create, edit, or delete items directly from the item table.</p>
                        </div>
                        <div class="d-flex gap-2 align-items-center">
                            <input type="search" class="form-control" id="item-search" placeholder="Search by name or ID">
                            <button class="btn btn-ranked" data-bs-toggle="modal" data-bs-target="#itemModal" data-mode="create">
                                <i class="bi bi-plus-lg"></i> New Item
                            </button>
                        </div>
                    </div>
                </div>

                <?php if ($alertMessage !== '') { ?>
                    <div class="alert alert-<?= $alertVariant; ?> alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($alertMessage); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php } ?>

                <?php if (!$itemTableResp->success) { ?>
                    <div class="alert alert-danger" role="alert">
                        <?= htmlspecialchars($itemTableResp->message); ?>
                    </div>
                <?php } else { ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle" id="item-table">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col">ID</th>
                                    <th scope="col">Name</th>
                                    <th scope="col">Type</th>
                                    <th scope="col">Rarity</th>
                                    <th scope="col">Category</th>
                                    <th scope="col">Equip</th>
                                    <th scope="col">Redeem</th>
                                    <th scope="col">Use</th>
                                    <th scope="col">Container</th>
                                    <th scope="col" class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($itemRows as $row) {
                                    $type = ItemType::from((int)$row['type']);
                                    $rarity = ItemRarity::from((int)$row['rarity']);
                                    $category = isset($row['item_category']) && $row['item_category'] !== null
                                        ? ItemCategory::tryFrom((int)$row['item_category'])
                                        : null;
                                ?>
                                    <tr data-item-name="<?= htmlspecialchars($row['name']); ?>" data-item-id="<?= (int)$row['Id']; ?>">
                                        <td class="fw-semibold">#<?= (int)$row['Id']; ?></td>
                                        <td>
                                            <div class="fw-semibold mb-0"><?= htmlspecialchars($row['name']); ?></div>
                                            <small class="text-body-secondary">Media: L<?= (int)$row['media_id_large']; ?> / S<?= (int)$row['media_id_small']; ?> / B<?= (int)$row['media_id_back']; ?></small>
                                        </td>
                                        <td><span class="badge text-bg-secondary"><?= enumLabel($type->name); ?></span></td>
                                        <td><span class="badge text-bg-primary"><?= enumLabel($rarity->name); ?></span></td>
                                        <td><?= $category ? enumLabel($category->name) : '—'; ?></td>
                                        <td><?= ((int)$row['equipable']) === 1 ? 'Yes' : 'No'; ?></td>
                                        <td><?= ((int)$row['redeemable']) === 1 ? 'Yes' : 'No'; ?></td>
                                        <td><?= ((int)$row['useable']) === 1 ? 'Yes' : 'No'; ?></td>
                                        <td><?= ((int)$row['is_container']) === 1 ? 'Yes' : 'No'; ?></td>
                                        <td class="text-end">
                                            <div class="btn-group" role="group">
                                                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#itemModal" data-mode="edit"
                                                    data-item='<?= htmlspecialchars(json_encode($row), ENT_QUOTES); ?>'>
                                                    <i class="bi bi-pencil"></i> Edit
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal" data-item-id="<?= (int)$row['Id']; ?>" data-item-name="<?= htmlspecialchars($row['name']); ?>">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                <?php } ?>
            </div>
        </div>

        <?php require("php-components/base-page-footer.php"); ?>
    </main>

    <!-- Create / Edit Modal -->
    <div class="modal fade" id="itemModal" tabindex="-1" aria-labelledby="itemModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="itemModalLabel">Create Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create" id="item-form-action">
                        <input type="hidden" name="item_id" value="" id="item-id">

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Name</label>
                                <input type="text" class="form-control" name="name" id="item-name" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Description</label>
                                <input type="text" class="form-control" name="description" id="item-description" required>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Type</label>
                                <select class="form-select" name="type" id="item-type">
                                    <?php foreach ($itemTypes as $type) { ?>
                                        <option value="<?= $type->value; ?>"><?= enumLabel($type->name); ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Rarity</label>
                                <select class="form-select" name="rarity" id="item-rarity">
                                    <?php foreach ($itemRarities as $rarity) { ?>
                                        <option value="<?= $rarity->value; ?>"><?= enumLabel($rarity->name); ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Item Category</label>
                                <select class="form-select" name="item_category" id="item-category">
                                    <option value="">None</option>
                                    <?php foreach ($itemCategories as $category) { ?>
                                        <option value="<?= $category->value; ?>"><?= enumLabel($category->name); ?></option>
                                    <?php } ?>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Media ID (Large)</label>
                                <input type="number" class="form-control" name="media_id_large" id="media-large" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Media ID (Small)</label>
                                <input type="number" class="form-control" name="media_id_small" id="media-small" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Media ID (Back)</label>
                                <input type="number" class="form-control" name="media_id_back" id="media-back" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Nominated By (Account ID)</label>
                                <input type="number" class="form-control" name="nominated_by_id" id="nominated-by" placeholder="Optional">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Collection ID</label>
                                <input type="number" class="form-control" name="collection_id" id="collection-id" placeholder="Optional">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Equipment Slot</label>
                                <select class="form-select" name="equipment_slot" id="equipment-slot">
                                    <option value="">None</option>
                                    <?php foreach ($equipmentSlots as $slot) { ?>
                                        <option value="<?= $slot->value; ?>"><?= enumLabel($slot->value); ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Container Category</label>
                                <select class="form-select" name="container_item_category" id="container-item-category">
                                    <option value="">None</option>
                                    <?php foreach ($itemCategories as $category) { ?>
                                        <option value="<?= $category->value; ?>"><?= enumLabel($category->name); ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Container Size</label>
                                <input type="number" class="form-control" name="container_size" id="container-size" value="-1">
                            </div>

                            <div class="col-md-3 form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="equipable" name="equipable" value="1">
                                <label class="form-check-label" for="equipable">Equipable</label>
                            </div>
                            <div class="col-md-3 form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="redeemable" name="redeemable" value="1">
                                <label class="form-check-label" for="redeemable">Redeemable</label>
                            </div>
                            <div class="col-md-3 form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="useable" name="useable" value="1">
                                <label class="form-check-label" for="useable">Useable</label>
                            </div>
                            <div class="col-md-3 form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="is-container" name="is_container" value="1">
                                <label class="form-check-label" for="is-container">Is Container</label>
                            </div>
                            <div class="col-md-3 form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="is-fungible" name="is_fungible" value="1">
                                <label class="form-check-label" for="is-fungible">Fungible</label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-ranked" id="item-submit">Create Item</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Delete Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="item_id" id="delete-item-id">
                    <div class="modal-body">
                        <p class="mb-0">Are you sure you want to delete <strong id="delete-item-name"></strong>?</p>
                        <p class="text-danger small mb-0">This action cannot be undone.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete Item</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php require("php-components/base-page-javascript.php"); ?>

    <script>
        const itemModal = document.getElementById('itemModal');
        itemModal.addEventListener('show.bs.modal', (event) => {
            const trigger = event.relatedTarget;
            const mode = trigger?.getAttribute('data-mode') || 'create';
            const modalTitle = document.getElementById('itemModalLabel');
            const submitBtn = document.getElementById('item-submit');
            const actionInput = document.getElementById('item-form-action');

            if (mode === 'edit') {
                const itemData = JSON.parse(trigger.getAttribute('data-item'));
                modalTitle.textContent = `Edit Item #${itemData.Id}`;
                submitBtn.textContent = 'Update Item';
                actionInput.value = 'update';

                document.getElementById('item-id').value = itemData.Id;
                document.getElementById('item-name').value = itemData.name || '';
                document.getElementById('item-description').value = itemData.desc || '';
                document.getElementById('item-type').value = itemData.type ?? '';
                document.getElementById('item-rarity').value = itemData.rarity ?? '';
                document.getElementById('item-category').value = itemData.item_category ?? '';

                document.getElementById('media-large').value = itemData.media_id_large ?? '';
                document.getElementById('media-small').value = itemData.media_id_small ?? '';
                document.getElementById('media-back').value = itemData.media_id_back ?? '';

                document.getElementById('nominated-by').value = itemData.nominated_by_id ?? '';
                document.getElementById('collection-id').value = itemData.collection_id ?? '';

                document.getElementById('equipment-slot').value = itemData.equipment_slot ?? '';
                document.getElementById('container-item-category').value = itemData.container_item_category ?? '';
                document.getElementById('container-size').value = itemData.container_size ?? -1;

                document.getElementById('equipable').checked = itemData.equipable == 1;
                document.getElementById('redeemable').checked = itemData.redeemable == 1;
                document.getElementById('useable').checked = itemData.useable == 1;
                document.getElementById('is-container').checked = itemData.is_container == 1;
                document.getElementById('is-fungible').checked = itemData.is_fungible == 1;
            } else {
                modalTitle.textContent = 'Create Item';
                submitBtn.textContent = 'Create Item';
                actionInput.value = 'create';

                document.querySelector('#itemModal form').reset();
                document.getElementById('item-id').value = '';
                document.getElementById('container-size').value = -1;
            }
        });

        const deleteModal = document.getElementById('deleteModal');
        deleteModal.addEventListener('show.bs.modal', (event) => {
            const trigger = event.relatedTarget;
            const itemId = trigger.getAttribute('data-item-id');
            const itemName = trigger.getAttribute('data-item-name');

            document.getElementById('delete-item-id').value = itemId;
            document.getElementById('delete-item-name').textContent = itemName;
        });

        const searchInput = document.getElementById('item-search');
        const tableBody = document.querySelector('#item-table tbody');

        if (searchInput && tableBody) {
            searchInput.addEventListener('input', (event) => {
                const query = event.target.value.toLowerCase();
                const rows = tableBody.querySelectorAll('tr');

                rows.forEach((row) => {
                    const id = row.getAttribute('data-item-id');
                    const name = (row.getAttribute('data-item-name') || '').toLowerCase();
                    const matches = id?.includes(query) || name.includes(query);
                    row.classList.toggle('d-none', !matches);
                });
            });
        }
    </script>

</body>

</html>
