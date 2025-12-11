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

$collectionOptions = [];
foreach ($itemRows as $row) {
    $collectionId = isset($row['collection_id']) ? (int)$row['collection_id'] : 0;
    if ($collectionId > 0) {
        if (!isset($collectionOptions[$collectionId])) {
            $collectionOptions[$collectionId] = [
                'id' => $collectionId,
                'items' => [],
            ];
        }

        if (isset($row['name']) && $row['name'] !== '') {
            $collectionOptions[$collectionId]['items'][] = $row['name'];
        }
    }
}

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
            <div class="col-12">


                <?php


                $activePageName = "Item Manager";
                require("php-components/base-page-breadcrumbs.php");


                ?>

                <div class="card shadow-sm mb-3">
                    <div class="card-body d-flex flex-column flex-md-row gap-2 align-items-md-center justify-content-between">
                        <div>
                            <h5 class="mb-1">Item Table</h5>
                            <p class="text-body-secondary mb-0">View, create, edit, or delete items directly from the item table.</p>
                        </div>
                        <div class="d-flex gap-2 align-items-center">
                            <input type="search" class="form-control" id="item-search" placeholder="Search by name or ID">
                            <button class="btn btn-ranked" data-bs-toggle="modal" data-bs-target="#itemModal" data-mode="create">
                                <i class="fa-solid fa-plus"></i> New Item
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
                                                    <i class="fa-solid fa-pen"></i> Edit
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal" data-item-id="<?= (int)$row['Id']; ?>" data-item-name="<?= htmlspecialchars($row['name']); ?>">
                                                    <i class="fa-solid fa-trash"></i>
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
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="itemModalLabel">Create Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create" id="item-form-action">
                        <input type="hidden" name="item_id" value="" id="item-id">

                        <div class="row gy-4">
                            <div class="col-12">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <span class="text-uppercase small fw-semibold text-body-secondary">Item Details</span>
                                    <div class="border-top flex-grow-1 opacity-25"></div>
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Name</label>
                                        <input type="text" class="form-control" name="name" id="item-name" required>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Description</label>
                                        <textarea class="form-control" name="description" id="item-description" rows="2" required></textarea>
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
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <span class="text-uppercase small fw-semibold text-body-secondary">Media & Visuals</span>
                                    <div class="border-top flex-grow-1 opacity-25"></div>
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Media (Large)</label>
                                        <input type="hidden" name="media_id_large" id="media-large" value="221" required>
                                        <div class="card shadow-sm border" role="button" style="cursor: pointer;" onclick="openMediaPicker('media-large', 'media-large-preview', 'media-large-label')">
                                            <div class="ratio ratio-1x1 bg-body-secondary bg-opacity-25">
                                                <img src="/assets/media/items/221.png" alt="Large preview" id="media-large-preview" class="object-fit-contain w-100 h-100">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Media (Small)</label>
                                        <input type="hidden" name="media_id_small" id="media-small" value="221" required>
                                        <div class="card shadow-sm border" role="button" style="cursor: pointer;" onclick="openMediaPicker('media-small', 'media-small-preview', 'media-small-label')">
                                            <div class="ratio ratio-1x1 bg-body-secondary bg-opacity-25">
                                                <img src="/assets/media/items/221.png" alt="Small preview" id="media-small-preview" class="object-fit-contain w-100 h-100">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Media (Back)</label>
                                        <input type="hidden" name="media_id_back" id="media-back" value="221" required>
                                        <div class="card shadow-sm border" role="button" style="cursor: pointer;" onclick="openMediaPicker('media-back', 'media-back-preview', 'media-back-label')">
                                            <div class="ratio ratio-1x1 bg-body-secondary bg-opacity-25">
                                                <img src="/assets/media/items/221.png" alt="Back preview" id="media-back-preview" class="object-fit-contain w-100 h-100">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <span class="text-uppercase small fw-semibold text-body-secondary">Associations</span>
                                    <div class="border-top flex-grow-1 opacity-25"></div>
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Nominated By</label>
                                        <input type="hidden" name="nominated_by_id" id="nominated-by">
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="nominated-by-display" placeholder="No account selected" readonly>
                                            <button class="btn btn-outline-secondary" type="button" onclick="openAccountPicker()">
                                                <i class="fa-solid fa-magnifying-glass"></i>
                                            </button>
                                            <button class="btn btn-outline-danger" type="button" onclick="clearNominatedBy()" title="Clear selection">
                                                <i class="fa-solid fa-xmark"></i>
                                            </button>
                                        </div>
                                        <div class="form-text" id="nominated-by-label">No account selected.</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Collection</label>
                                        <input type="hidden" name="collection_id" id="collection-id">
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="collection-display" placeholder="No collection selected" readonly>
                                            <button class="btn btn-outline-secondary" type="button" onclick="openCollectionModal()">
                                                <i class="fa-solid fa-layer-group"></i>
                                            </button>
                                            <button class="btn btn-outline-danger" type="button" onclick="clearCollection()" title="Clear collection">
                                                <i class="fa-solid fa-xmark"></i>
                                            </button>
                                        </div>
                                        <div class="form-text" id="collection-label">No collection selected.</div>
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
                                        <div class="form-text">Use -1 to leave unset.</div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <span class="text-uppercase small fw-semibold text-body-secondary">Flags</span>
                                    <div class="border-top flex-grow-1 opacity-25"></div>
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-6 col-lg-4">
                                        <div class="d-flex align-items-start justify-content-between p-3 border rounded bg-body-tertiary h-100">
                                            <div class="me-3">
                                                <div class="fw-semibold">Equipable</div>
                                                <div class="text-body-secondary small">Allows this item to be worn or held when an equipment slot is provided.</div>
                                            </div>
                                            <div class="form-check form-switch m-0">
                                                <input class="form-check-input" type="checkbox" role="switch" id="equipable" name="equipable" value="1">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-lg-4">
                                        <div class="d-flex align-items-start justify-content-between p-3 border rounded bg-body-tertiary h-100">
                                            <div class="me-3">
                                                <div class="fw-semibold">Redeemable</div>
                                                <div class="text-body-secondary small">Flag items that can be exchanged or turned in through redemption flows.</div>
                                            </div>
                                            <div class="form-check form-switch m-0">
                                                <input class="form-check-input" type="checkbox" role="switch" id="redeemable" name="redeemable" value="1">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-lg-4">
                                        <div class="d-flex align-items-start justify-content-between p-3 border rounded bg-body-tertiary h-100">
                                            <div class="me-3">
                                                <div class="fw-semibold">Useable</div>
                                                <div class="text-body-secondary small">Marks items that can be actively consumed or triggered by players.</div>
                                            </div>
                                            <div class="form-check form-switch m-0">
                                                <input class="form-check-input" type="checkbox" role="switch" id="useable" name="useable" value="1">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-lg-4">
                                        <div class="d-flex align-items-start justify-content-between p-3 border rounded bg-body-tertiary h-100">
                                            <div class="me-3">
                                                <div class="fw-semibold">Is Container</div>
                                                <div class="text-body-secondary small">Enables storage behavior so container size and category limits apply.</div>
                                            </div>
                                            <div class="form-check form-switch m-0">
                                                <input class="form-check-input" type="checkbox" role="switch" id="is-container" name="is_container" value="1">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-lg-4">
                                        <div class="d-flex align-items-start justify-content-between p-3 border rounded bg-body-tertiary h-100">
                                            <div class="me-3">
                                                <div class="fw-semibold">Fungible</div>
                                                <div class="text-body-secondary small">Makes the item stackable and interchangeable with identical copies.</div>
                                            </div>
                                            <div class="form-check form-switch m-0">
                                                <input class="form-check-input" type="checkbox" role="switch" id="is-fungible" name="is_fungible" value="1">
                                            </div>
                                        </div>
                                    </div>
                                </div>
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

    <!-- Collection Search Modal -->
    <div class="modal fade" id="collectionSearchModal" tabindex="-1" aria-labelledby="collectionSearchModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title mb-1" id="collectionSearchModalLabel">Select a Collection</h5>
                        <p class="text-muted small mb-0">Search available item collections by ID or item names.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label" for="collection-search-input">Search</label>
                        <input type="search" class="form-control" id="collection-search-input" placeholder="Search by ID or item name">
                        <div class="form-text">Results update as you type.</div>
                    </div>
                    <div class="row g-3" id="collection-search-results"></div>
                    <div class="text-center text-muted py-3 d-none" id="collection-search-empty">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <p class="mb-0">No collections match your search.</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                </div>
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
        const collectionOptions = <?= json_encode(array_values($collectionOptions)); ?>;
        const itemModal = document.getElementById('itemModal');
        const DEFAULT_MEDIA_SRC = '/assets/media/items/221.png';
        const DEFAULT_MEDIA_ID = '221';

        itemModal.addEventListener('show.bs.modal', (event) => {
            const trigger = event.relatedTarget;

            // When returning from a nested modal (e.g., media picker) we do not want to reset the form.
            if (!trigger) {
                return;
            }

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
                updateMediaPreview('media-large-preview', 'media-large', 'media-large-label');
                updateMediaPreview('media-small-preview', 'media-small', 'media-small-label');
                updateMediaPreview('media-back-preview', 'media-back', 'media-back-label');

                document.getElementById('nominated-by').value = itemData.nominated_by_id ?? '';
                updateNominatedByLabel();
                document.getElementById('collection-id').value = itemData.collection_id ?? '';
                updateCollectionLabel();

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
                clearNominatedBy();
                clearCollection();
                clearMediaPreviews();
            }
        });

        function openMediaPicker(inputId, previewId, labelId) {
            OpenSelectMediaModal('itemModal', previewId, inputId, () => updateMediaPreview(previewId, inputId, labelId));
        }

        function updateMediaPreview(previewId, inputId, labelId) {
            const preview = document.getElementById(previewId);
            const input = document.getElementById(inputId);
            const label = document.getElementById(labelId);
            if (!preview || !input) return;

            const mediaId = input.value?.trim();
            const hasSelection = !!mediaId;
            preview.dataset.selectedSrc = preview.src;
            preview.src = hasSelection && preview.dataset.selectedSrc ? preview.dataset.selectedSrc : DEFAULT_MEDIA_SRC;

            if (label) {
                label.textContent = hasSelection
                    ? `Selected media ID: #${mediaId}`
                    : 'Default preview shown. Click to select.';
            }
        }

        function clearMediaPreviews() {
            ['media-large', 'media-small', 'media-back'].forEach((inputId) => {
                const input = document.getElementById(inputId);
                const preview = document.getElementById(`${inputId}-preview`);
                const label = document.getElementById(`${inputId}-label`);

                if (input) {
                    input.value = DEFAULT_MEDIA_ID;
                }
                if (preview) {
                    preview.dataset.selectedSrc = '';
                    preview.src = DEFAULT_MEDIA_SRC;
                }
                if (label) {
                    label.textContent = 'Default preview shown. Click to select.';
                }
            });
        }

        function openAccountPicker() {
            OpenSelectAccountModal('itemModal', 'selectNominatedAccount');
        }

        function selectNominatedAccount(accountId) {
            const nominatedInput = document.getElementById('nominated-by');
            if (!nominatedInput) return;

            nominatedInput.value = accountId ?? '';
            updateNominatedByLabel();

            const selectModalElement = document.getElementById('selectAccountModal');
            const selectModalInstance = selectModalElement ? bootstrap.Modal.getOrCreateInstance(selectModalElement) : null;
            if (selectModalInstance) {
                selectModalInstance.hide();
            }

            if (selectAccountModalCallerId && selectAccountModalCallerId !== -1) {
                const previousModalElement = document.getElementById(selectAccountModalCallerId);
                const previousInstance = previousModalElement ? bootstrap.Modal.getOrCreateInstance(previousModalElement) : null;
                previousInstance?.show();
            }

            selectAccountModalCallerId = -1;
        }

        function updateNominatedByLabel() {
            const nominatedInput = document.getElementById('nominated-by');
            const display = document.getElementById('nominated-by-display');
            const label = document.getElementById('nominated-by-label');
            if (!nominatedInput || !label) return;

            const id = nominatedInput.value?.trim();
            if (!id) {
                label.textContent = 'No account selected.';
                if (display) display.value = '';
                return;
            }

            let username = '';
            if (typeof selectAccountResultsById !== 'undefined' && selectAccountResultsById[id]) {
                username = selectAccountResultsById[id]?.username ?? '';
            }

            label.textContent = username ? `Selected: ${username} (#${id})` : `Selected Account ID: #${id}`;
            if (display) {
                display.value = username || `Account #${id}`;
            }
        }

        function clearNominatedBy() {
            const nominatedInput = document.getElementById('nominated-by');
            const display = document.getElementById('nominated-by-display');
            if (nominatedInput) {
                nominatedInput.value = '';
            }
            if (display) {
                display.value = '';
            }
            updateNominatedByLabel();
        }

        let previousModalInstance = null;
        function openCollectionModal() {
            const itemModalInstance = bootstrap.Modal.getOrCreateInstance(document.getElementById('itemModal'));
            itemModalInstance.hide();
            previousModalInstance = itemModalInstance;

            renderCollectionResults();

            const modalElement = document.getElementById('collectionSearchModal');
            const collectionModal = bootstrap.Modal.getOrCreateInstance(modalElement);
            collectionModal.show();
        }

        function renderCollectionResults() {
            const resultsContainer = document.getElementById('collection-search-results');
            const emptyState = document.getElementById('collection-search-empty');
            const searchTerm = (document.getElementById('collection-search-input')?.value || '').toLowerCase();

            if (!resultsContainer) return;

            resultsContainer.innerHTML = '';
            let hasResults = false;

            collectionOptions.forEach((collection) => {
                const idMatch = collection.id.toString().includes(searchTerm);
                const nameMatch = collection.items.some((name) => name.toLowerCase().includes(searchTerm));

                if (searchTerm && !idMatch && !nameMatch) {
                    return;
                }

                hasResults = true;
                const sampleItems = collection.items.slice(0, 3).join(', ');
                const card = document.createElement('div');
                card.className = 'col-12 col-md-6';
                card.innerHTML = `
                    <div class="card h-100 shadow-sm">
                        <div class="card-body d-flex flex-column gap-2">
                            <div class="fw-semibold">Collection #${collection.id}</div>
                            <div class="text-muted small">${sampleItems || 'No item names recorded'}</div>
                            <button type="button" class="btn btn-sm btn-outline-primary mt-auto" data-collection-id="${collection.id}">
                                <i class="fa-solid fa-circle-check me-1"></i>Select Collection
                            </button>
                        </div>
                    </div>`;

                card.querySelector('button')?.addEventListener('click', () => selectCollection(collection.id));
                resultsContainer.appendChild(card);
            });

            if (emptyState) {
                emptyState.classList.toggle('d-none', hasResults);
            }
        }

        const collectionSearchInput = document.getElementById('collection-search-input');
        collectionSearchInput?.addEventListener('input', renderCollectionResults);

        function selectCollection(collectionId) {
            const collectionInput = document.getElementById('collection-id');
            if (collectionInput) {
                collectionInput.value = collectionId;
            }
            updateCollectionLabel();

            const modalElement = document.getElementById('collectionSearchModal');
            const collectionModal = modalElement ? bootstrap.Modal.getOrCreateInstance(modalElement) : null;
            collectionModal?.hide();

            if (previousModalInstance) {
                previousModalInstance.show();
            }
        }

        function updateCollectionLabel() {
            const collectionInput = document.getElementById('collection-id');
            const display = document.getElementById('collection-display');
            const label = document.getElementById('collection-label');
            if (!collectionInput || !label) return;

            const id = collectionInput.value?.trim();
            if (!id) {
                label.textContent = 'No collection selected.';
                if (display) display.value = '';
                return;
            }

            const collection = collectionOptions.find((c) => c.id.toString() === id);
            const sampleItems = collection?.items?.slice(0, 3).join(', ') || '';
            label.textContent = sampleItems ? `Selected Collection #${id} (${sampleItems})` : `Selected Collection ID: #${id}`;

            if (display) {
                display.value = sampleItems ? `Collection #${id} • ${sampleItems}` : `Collection #${id}`;
            }
        }

        function clearCollection() {
            const collectionInput = document.getElementById('collection-id');
            const display = document.getElementById('collection-display');
            if (collectionInput) {
                collectionInput.value = '';
            }
            if (display) {
                display.value = '';
            }
            updateCollectionLabel();
        }

        const collectionModalElement = document.getElementById('collectionSearchModal');
        if (collectionModalElement) {
            collectionModalElement.addEventListener('hidden.bs.modal', () => {
                if (previousModalInstance) {
                    previousModalInstance.show();
                }
            });
        }

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
