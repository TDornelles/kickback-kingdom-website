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
$defaultMediaId = 221;

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
    $collectionId = $row->collection?->crand ?? 0;
    if ($collectionId > 0) {
        if (!isset($collectionOptions[$collectionId])) {
            $collectionOptions[$collectionId] = [
                'id' => $collectionId,
                'items' => [],
            ];
        }

        if (!empty($row->name)) {
            $collectionOptions[$collectionId]['items'][] = $row->name;
        }
    }
}

$itemTypes = ItemType::cases();
$restrictedItemTypes = [
    ItemType::RaffleTicket,
    ItemType::PrestigeToken,
    ItemType::WritOfPassage,
];
$itemTypeOptions = array_values(array_filter(
    $itemTypes,
    fn(ItemType $type) => !in_array($type, $restrictedItemTypes, true)
));
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
                                    <th scope="col" class="sortable" data-sort-key="id">ID <i class="fa-solid ms-1 sort-icon d-none"></i></th>
                                    <th scope="col" class="sortable" data-sort-key="name">Name <i class="fa-solid ms-1 sort-icon d-none"></i></th>
                                    <th scope="col" class="sortable" data-sort-key="type">Type <i class="fa-solid ms-1 sort-icon d-none"></i></th>
                                    <th scope="col" class="sortable" data-sort-key="rarity">Rarity <i class="fa-solid ms-1 sort-icon d-none"></i></th>
                                    <th scope="col" class="sortable" data-sort-key="category">Category <i class="fa-solid ms-1 sort-icon d-none"></i></th>
                                    <th scope="col" class="sortable" data-sort-key="equipable">Equip <i class="fa-solid ms-1 sort-icon d-none"></i></th>
                                    <th scope="col" class="sortable" data-sort-key="redeemable">Redeem <i class="fa-solid ms-1 sort-icon d-none"></i></th>
                                    <th scope="col" class="sortable" data-sort-key="useable">Use <i class="fa-solid ms-1 sort-icon d-none"></i></th>
                                    <th scope="col" class="sortable" data-sort-key="is_container">Container <i class="fa-solid ms-1 sort-icon d-none"></i></th>
                                    <th scope="col" class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($itemRows as $row) {
                                    $type = $row->type;
                                    $rarity = $row->rarity;
                                    $category = $row->itemCategory;
                                    $mediaSmallId = $row->iconSmall->crand;
                                    $mediaLargeId = $row->iconBig->crand;
                                    $mediaBackId = $row->iconBack->crand;
                                    $mediaSmallPath = $row->iconSmall->isValid()
                                        ? trim(str_replace('/assets/media/', '', $row->iconSmall->getFullPath()), '/')
                                        : '';
                                    $smallMediaSrc = $row->iconSmall->isValid()
                                        ? $row->iconSmall->getFullPath()
                                        : "/assets/media/items/{$defaultMediaId}.png";
                                    $mediaLargePath = $row->iconBig->isValid()
                                        ? trim(str_replace('/assets/media/', '', $row->iconBig->getFullPath()), '/')
                                        : '';
                                    $mediaBackPath = $row->iconBack->isValid()
                                        ? trim(str_replace('/assets/media/', '', $row->iconBack->getFullPath()), '/')
                                        : '';

                                    $itemData = [
                                        'Id' => $row->crand,
                                        'name' => $row->name,
                                        'desc' => $row->description,
                                        'type' => $type->value,
                                        'rarity' => $rarity->value,
                                        'item_category' => $category?->value,
                                        'media_id_large' => $mediaLargeId,
                                        'media_id_small' => $mediaSmallId,
                                        'media_id_back' => $mediaBackId,
                                        'media_path_large' => $mediaLargePath,
                                        'media_path_small' => $mediaSmallPath,
                                        'media_path_back' => $mediaBackPath,
                                        'nominated_by_id' => $row->nominatedBy?->crand,
                                        'collection_id' => $row->collection?->crand,
                                        'equipment_slot' => $row->equipmentSlot?->value,
                                        'container_item_category' => $row->containerItemCategory?->value,
                                        'container_size' => $row->containerSize,
                                        'equipable' => $row->equipable ? 1 : 0,
                                        'redeemable' => $row->redeemable ? 1 : 0,
                                        'useable' => $row->useable ? 1 : 0,
                                        'is_container' => $row->isContainer ? 1 : 0,
                                        'is_fungible' => $row->fungible ? 1 : 0,
                                    ];
                                ?>
                                    <tr
                                        data-item-name="<?= htmlspecialchars($row->name); ?>"
                                        data-item-id="<?= (int)$row->crand; ?>"
                                        data-item-type="<?= $type->value; ?>"
                                        data-item-rarity="<?= $rarity->value; ?>"
                                        data-item-category="<?= $category?->value ?? ''; ?>"
                                        data-item-equipable="<?= $row->equipable ? 1 : 0; ?>"
                                        data-item-redeemable="<?= $row->redeemable ? 1 : 0; ?>"
                                        data-item-useable="<?= $row->useable ? 1 : 0; ?>"
                                        data-item-container="<?= $row->isContainer ? 1 : 0; ?>"
                                        data-media-small-path="<?= htmlspecialchars($mediaSmallPath); ?>"
                                        data-media-large-path="<?= htmlspecialchars($mediaLargePath); ?>"
                                        data-media-back-path="<?= htmlspecialchars($mediaBackPath); ?>"
                                    >
                                        <td class="fw-semibold">#<?= (int)$row->crand; ?></td>
                                        <td>
                                            <div class="d-flex align-items-center gap-3">
                                                <div class="ratio ratio-1x1" style="width: 48px;">
                                                    <img src="<?= htmlspecialchars($smallMediaSrc); ?>" alt="<?= htmlspecialchars($row->name); ?> icon" class="w-100 h-100 object-fit-contain rounded border bg-body-secondary bg-opacity-25">
                                                </div>
                                                <div>
                                                    <div class="fw-semibold mb-0"><?= htmlspecialchars($row->name); ?></div>
                                                    <small class="text-body-secondary">Media: L<?= (int)$mediaLargeId; ?> / S<?= (int)$mediaSmallId; ?> / B<?= (int)$mediaBackId; ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td><span class="badge text-bg-secondary"><?= enumLabel($type->name); ?></span></td>
                                        <td><span class="badge text-bg-primary"><?= enumLabel($rarity->name); ?></span></td>
                                        <td><?= $category ? enumLabel($category->name) : '—'; ?></td>
                                        <td><?= $row->equipable ? 'Yes' : 'No'; ?></td>
                                        <td><?= $row->redeemable ? 'Yes' : 'No'; ?></td>
                                        <td><?= $row->useable ? 'Yes' : 'No'; ?></td>
                                        <td><?= $row->isContainer ? 'Yes' : 'No'; ?></td>
                                        <td class="text-end">
                                            <div class="btn-group" role="group">
                                                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#itemModal" data-mode="edit"
                                                    data-item='<?= htmlspecialchars(json_encode($itemData), ENT_QUOTES); ?>'>
                                                    <i class="fa-solid fa-pen"></i> Edit
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal" data-item-id="<?= (int)$row->crand; ?>" data-item-name="<?= htmlspecialchars($row->name); ?>">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2 mt-3">
                        <div class="d-flex align-items-center gap-2">
                            <label for="item-page-size" class="form-label mb-0 small text-body-secondary">Rows per page</label>
                            <select class="form-select form-select-sm" id="item-page-size" style="width: auto; min-width: 90px;">
                                <option value="10" selected>10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                            </select>
                        </div>
                        <nav aria-label="Item pagination">
                            <ul class="pagination pagination-sm mb-0" id="item-pagination"></ul>
                        </nav>
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
                                            <?php foreach ($itemTypeOptions as $type) { ?>
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
                                        <input type="hidden" name="media_id_large" id="media-large" value="<?= $defaultMediaId; ?>" required>
                                        <div class="card shadow-sm border" role="button" style="cursor: pointer;" onclick="openMediaPicker('media-large', 'media-large-preview', 'media-large-label')">
                                            <div class="ratio ratio-1x1 bg-body-secondary bg-opacity-25">
                                                <img src="/assets/media/items/<?= $defaultMediaId; ?>.png" alt="Large preview" id="media-large-preview" class="object-fit-contain w-100 h-100">
                                            </div>
                                        </div>
                                        <div class="form-text" id="media-large-label">Default preview shown. Click to select.</div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Media (Small)</label>
                                        <input type="hidden" name="media_id_small" id="media-small" value="<?= $defaultMediaId; ?>" required>
                                        <div class="card shadow-sm border" role="button" style="cursor: pointer;" onclick="openMediaPicker('media-small', 'media-small-preview', 'media-small-label')">
                                            <div class="ratio ratio-1x1 bg-body-secondary bg-opacity-25">
                                                <img src="/assets/media/items/<?= $defaultMediaId; ?>.png" alt="Small preview" id="media-small-preview" class="object-fit-contain w-100 h-100">
                                            </div>
                                        </div>
                                        <div class="form-text" id="media-small-label">Default preview shown. Click to select.</div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Media (Back)</label>
                                        <input type="hidden" name="media_id_back" id="media-back" value="<?= $defaultMediaId; ?>" required>
                                        <div class="card shadow-sm border" role="button" style="cursor: pointer;" onclick="openMediaPicker('media-back', 'media-back-preview', 'media-back-label')">
                                            <div class="ratio ratio-1x1 bg-body-secondary bg-opacity-25">
                                                <img src="/assets/media/items/<?= $defaultMediaId; ?>.png" alt="Back preview" id="media-back-preview" class="object-fit-contain w-100 h-100">
                                            </div>
                                        </div>
                                        <div class="form-text" id="media-back-label">Default preview shown. Click to select.</div>
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
                                    <div class="col-md-4" id="equipment-slot-group">
                                        <label class="form-label">Equipment Slot</label>
                                        <select class="form-select" name="equipment_slot" id="equipment-slot">
                                            <option value="">None</option>
                                            <?php foreach ($equipmentSlots as $slot) { ?>
                                                <option value="<?= $slot->value; ?>"><?= enumLabel($slot->value); ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4" id="container-category-group">
                                        <label class="form-label">Container Category</label>
                                        <select class="form-select" name="container_item_category" id="container-item-category">
                                            <option value="">None</option>
                                            <?php foreach ($itemCategories as $category) { ?>
                                                <option value="<?= $category->value; ?>"><?= enumLabel($category->name); ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4" id="container-size-group">
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
        const DEFAULT_MEDIA_ID = '<?= $defaultMediaId; ?>';
        const DEFAULT_MEDIA_SRC = `/assets/media/items/${DEFAULT_MEDIA_ID}.png`;
        const ITEM_TYPE_UNIQUE = '<?= ItemType::Unique->value; ?>';
        const ITEM_TYPE_STANDARD = '<?= ItemType::Standard->value; ?>';
        const ITEM_RARITY_UNIQUE = '<?= ItemRarity::Unique->value; ?>';
        const ITEM_RARITY_LEGENDARY = '<?= ItemRarity::Legendary->value; ?>';

        function toggleEquipmentFields(isChecked) {
            const equipmentGroup = document.getElementById('equipment-slot-group');
            if (equipmentGroup) {
                equipmentGroup.classList.toggle('d-none', !isChecked);
            }

            if (!isChecked) {
                const equipmentSlot = document.getElementById('equipment-slot');
                if (equipmentSlot) {
                    equipmentSlot.value = '';
                }
            }
        }

        function toggleContainerFields(isChecked) {
            const containerGroups = [
                document.getElementById('container-category-group'),
                document.getElementById('container-size-group'),
            ];

            containerGroups.forEach((group) => group?.classList.toggle('d-none', !isChecked));

            if (!isChecked) {
                const containerCategory = document.getElementById('container-item-category');
                const containerSize = document.getElementById('container-size');
                if (containerCategory) {
                    containerCategory.value = '';
                }
                if (containerSize) {
                    containerSize.value = -1;
                }
            }
        }

        function enforceUniquePairing() {
            const typeSelect = document.getElementById('item-type');
            const raritySelect = document.getElementById('item-rarity');
            if (!typeSelect || !raritySelect) return;

            const shouldBeUnique = typeSelect.value === ITEM_TYPE_UNIQUE || raritySelect.value === ITEM_RARITY_UNIQUE;

            if (shouldBeUnique) {
                if (typeSelect.value !== ITEM_TYPE_UNIQUE) {
                    typeSelect.value = ITEM_TYPE_UNIQUE;
                }
                if (raritySelect.value !== ITEM_RARITY_UNIQUE) {
                    raritySelect.value = ITEM_RARITY_UNIQUE;
                }
            }
        }

        function handleRarityChange() {
            const typeSelect = document.getElementById('item-type');
            const raritySelect = document.getElementById('item-rarity');
            if (!typeSelect || !raritySelect) return;

            if (raritySelect.value !== ITEM_RARITY_UNIQUE && typeSelect.value === ITEM_TYPE_UNIQUE) {
                typeSelect.value = ITEM_TYPE_STANDARD;
            }

            enforceUniquePairing();
        }

        function handleTypeChange() {
            const typeSelect = document.getElementById('item-type');
            const raritySelect = document.getElementById('item-rarity');
            if (!typeSelect || !raritySelect) return;

            if (typeSelect.value !== ITEM_TYPE_UNIQUE && raritySelect.value === ITEM_RARITY_UNIQUE) {
                raritySelect.value = ITEM_RARITY_LEGENDARY;
            }

            enforceUniquePairing();
        }

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

                setMediaSelection('media-large', 'media-large-preview', 'media-large-label', itemData.media_id_large, itemData.media_path_large);
                setMediaSelection('media-small', 'media-small-preview', 'media-small-label', itemData.media_id_small, itemData.media_path_small);
                setMediaSelection('media-back', 'media-back-preview', 'media-back-label', itemData.media_id_back, itemData.media_path_back);

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

                toggleEquipmentFields(itemData.equipable == 1);
                toggleContainerFields(itemData.is_container == 1);
                enforceUniquePairing();
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

                toggleEquipmentFields(false);
                toggleContainerFields(false);
                enforceUniquePairing();
            }
        });

        const equipableCheckbox = document.getElementById('equipable');
        equipableCheckbox?.addEventListener('change', (event) => toggleEquipmentFields(event.target.checked));

        const containerCheckbox = document.getElementById('is-container');
        containerCheckbox?.addEventListener('change', (event) => toggleContainerFields(event.target.checked));

        const itemTypeSelect = document.getElementById('item-type');
        const itemRaritySelect = document.getElementById('item-rarity');
        itemTypeSelect?.addEventListener('change', handleTypeChange);
        itemRaritySelect?.addEventListener('change', handleRarityChange);

        toggleEquipmentFields(equipableCheckbox?.checked ?? false);
        toggleContainerFields(containerCheckbox?.checked ?? false);

        function openMediaPicker(inputId, previewId, labelId) {
            OpenSelectMediaModal('itemModal', previewId, inputId, (mediaId, mediaPath) => {
                updateMediaPreview(previewId, inputId, labelId, mediaId, mediaPath);
            });
        }

        function getMediaSrc(mediaId, mediaPath = '') {
            const normalizedPath = (mediaPath ?? '').toString().trim();
            if (normalizedPath) {
                return normalizedPath.startsWith('/') || normalizedPath.startsWith('http')
                    ? normalizedPath
                    : `/assets/media/${normalizedPath}`;
            }

            const trimmedId = (mediaId ?? '').toString().trim();
            return trimmedId ? `/assets/media/items/${trimmedId}.png` : DEFAULT_MEDIA_SRC;
        }

        function updateMediaPreview(previewId, inputId, labelId, displayMediaId = null, mediaPath = '') {
            const preview = document.getElementById(previewId);
            const input = document.getElementById(inputId);
            const label = document.getElementById(labelId);
            if (!preview || !input) return;

            const mediaId = displayMediaId ?? input.value?.trim();
            const hasSelection = !!mediaId;
            const previewSrc = getMediaSrc(input.value?.trim(), mediaPath || preview.dataset.mediaPath || '');
            preview.dataset.mediaPath = mediaPath || '';
            preview.dataset.selectedSrc = previewSrc;
            preview.src = previewSrc;

            if (label) {
                label.textContent = hasSelection
                    ? `Selected media ID: #${mediaId}`
                    : 'Default preview shown. Click to select.';
            }
        }

        function setMediaSelection(inputId, previewId, labelId, mediaId, mediaPath = '') {
            const input = document.getElementById(inputId);
            if (!input) return;

            const selectionId = mediaId && mediaId !== '0' ? mediaId.toString() : '';
            input.value = selectionId || DEFAULT_MEDIA_ID;
            updateMediaPreview(previewId, inputId, labelId, selectionId, mediaPath);
        }

        function clearMediaPreviews() {
            ['media-large', 'media-small', 'media-back'].forEach((inputId) => {
                setMediaSelection(inputId, `${inputId}-preview`, `${inputId}-label`, '');
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
        const tableHeaders = document.querySelectorAll('#item-table thead th[data-sort-key]');
        const paginationContainer = document.getElementById('item-pagination');
        const pageSizeSelect = document.getElementById('item-page-size');
        const allRows = tableBody ? Array.from(tableBody.querySelectorAll('tr')) : [];

        const DATASET_KEYS = {
            id: 'itemId',
            name: 'itemName',
            type: 'itemType',
            rarity: 'itemRarity',
            category: 'itemCategory',
            equipable: 'itemEquipable',
            redeemable: 'itemRedeemable',
            useable: 'itemUseable',
            is_container: 'itemContainer',
        };

        let currentSort = { key: 'id', direction: 'asc' };
        let currentPage = 1;
        let pageSize = parseInt(pageSizeSelect?.value ?? '10', 10) || 10;

        function isNumericKey(key) {
            return ['id', 'type', 'rarity', 'category', 'equipable', 'redeemable', 'useable', 'is_container'].includes(key);
        }

        function getSortableValue(row, key) {
            const datasetKey = DATASET_KEYS[key] ?? key;
            const value = row.dataset[datasetKey] ?? '';
            if (isNumericKey(key)) {
                return parseInt(value, 10) || 0;
            }

            return value.toString().toLowerCase();
        }

        function sortRows(rowsToSort) {
            return [...rowsToSort].sort((a, b) => {
                const valA = getSortableValue(a, currentSort.key);
                const valB = getSortableValue(b, currentSort.key);

                if (valA < valB) return currentSort.direction === 'asc' ? -1 : 1;
                if (valA > valB) return currentSort.direction === 'asc' ? 1 : -1;
                return 0;
            });
        }

        function renderPagination(totalPages) {
            if (!paginationContainer) return;
            paginationContainer.innerHTML = '';

            const createPageItem = (page, label, disabled = false, active = false) => {
                const li = document.createElement('li');
                li.className = `page-item${disabled ? ' disabled' : ''}${active ? ' active' : ''}`;
                const link = document.createElement(disabled ? 'span' : 'button');
                link.className = 'page-link';
                if (!disabled) {
                    link.type = 'button';
                    link.textContent = label;
                    link.addEventListener('click', () => {
                        if (page === currentPage) return;
                        currentPage = page;
                        renderTable();
                    });
                } else {
                    link.textContent = label;
                }
                li.appendChild(link);
                return li;
            };

            const prevDisabled = currentPage === 1;
            paginationContainer.appendChild(createPageItem(currentPage - 1, 'Prev', prevDisabled));

            let pagesToShow = [];
            if (totalPages <= 7) {
                pagesToShow = Array.from({ length: totalPages }, (_, idx) => idx + 1);
            } else {
                const startPage = Math.max(2, currentPage - 1);
                const endPage = Math.min(totalPages - 1, currentPage + 1);

                pagesToShow.push(1);
                if (startPage > 2) {
                    pagesToShow.push('ellipsis');
                }

                for (let page = startPage; page <= endPage; page += 1) {
                    pagesToShow.push(page);
                }

                if (endPage < totalPages - 1) {
                    pagesToShow.push('ellipsis');
                }

                pagesToShow.push(totalPages);
            }

            pagesToShow.forEach((page) => {
                if (page === 'ellipsis') {
                    paginationContainer.appendChild(createPageItem(currentPage, '...', true));
                } else {
                    paginationContainer.appendChild(createPageItem(page, page, false, page === currentPage));
                }
            });

            const nextDisabled = currentPage === totalPages;
            paginationContainer.appendChild(createPageItem(currentPage + 1, 'Next', nextDisabled));
        }

        function updateSortIcons() {
            tableHeaders.forEach((header) => {
                const icon = header.querySelector('i');
                const sortKey = header.getAttribute('data-sort-key');
                header.classList.toggle('text-ranked', sortKey === currentSort.key);
                if (!icon) return;

                icon.classList.add('d-none');
                icon.classList.remove('fa-sort', 'fa-sort-up', 'fa-sort-down');
                if (sortKey === currentSort.key) {
                    icon.classList.remove('d-none');
                    icon.classList.add(currentSort.direction === 'asc' ? 'fa-sort-up' : 'fa-sort-down');
                }
            });
        }

        function renderTable() {
            if (!tableBody) return;

            const query = (searchInput?.value || '').toLowerCase();
            const filteredRows = allRows.filter((row) => {
                const id = (row.dataset.itemId || '').toLowerCase();
                const name = (row.dataset.itemName || '').toLowerCase();
                return id.includes(query) || name.includes(query);
            });

            const sortedRows = sortRows(filteredRows);
            const totalPages = Math.max(1, Math.ceil(sortedRows.length / pageSize));
            currentPage = Math.min(currentPage, totalPages);

            tableBody.innerHTML = '';
            const startIndex = (currentPage - 1) * pageSize;
            const paginatedRows = sortedRows.slice(startIndex, startIndex + pageSize);
            paginatedRows.forEach((row) => tableBody.appendChild(row));

            renderPagination(totalPages);
            updateSortIcons();
        }

        tableHeaders.forEach((header) => {
            header.style.cursor = 'pointer';
            header.addEventListener('click', () => {
                const sortKey = header.getAttribute('data-sort-key');
                if (!sortKey) return;

                if (currentSort.key === sortKey) {
                    currentSort.direction = currentSort.direction === 'asc' ? 'desc' : 'asc';
                } else {
                    currentSort = { key: sortKey, direction: 'asc' };
                }
                currentPage = 1;
                renderTable();
            });
        });

        if (searchInput) {
            searchInput.addEventListener('input', () => {
                currentPage = 1;
                renderTable();
            });
        }

        if (pageSizeSelect) {
            pageSizeSelect.addEventListener('change', (event) => {
                pageSize = parseInt(event.target.value, 10) || 10;
                currentPage = 1;
                renderTable();
            });
        }

        renderTable();
    </script>

</body>

</html>
