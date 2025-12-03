<?php
$selectorId = $selectorId ?? 'itemSelectorModal';
$itemOptions = $itemOptions ?? [];

$humanizeLabel = static function (string $value): string {
    $spaced = preg_replace('/(?<!^)([A-Z])/', ' $1', str_replace('_', ' ', $value));
    return ucwords(strtolower(trim($spaced ?? $value)));
};

$filterTypes = [];
$filterCategories = [];
$filterEquipmentSlots = [];

foreach ($itemOptions as $option) {
    $typeName = $option->type->name ?? '';
    if ($typeName !== '') {
        $filterTypes[strtolower($typeName)] = $humanizeLabel($typeName);
    }

    $categoryName = $option->itemCategory?->name ?? '';
    $categoryKey = $categoryName !== '' ? strtolower($categoryName) : 'uncategorized';
    $filterCategories[$categoryKey] = $categoryName !== '' ? $humanizeLabel($categoryName) : 'Uncategorized';

    $equipmentName = $option->equipmentSlot?->name ?? '';
    $equipmentKey = $equipmentName !== '' ? strtolower($equipmentName) : 'none';
    $filterEquipmentSlots[$equipmentKey] = $equipmentName !== '' ? $humanizeLabel($equipmentName) : 'None';
}

ksort($filterTypes);
ksort($filterCategories);
ksort($filterEquipmentSlots);
?>

<div class="modal fade" id="<?= htmlspecialchars($selectorId) ?>" tabindex="-1" aria-labelledby="<?= htmlspecialchars($selectorId) ?>Label" aria-hidden="true" data-item-selector-modal>
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title mb-1" id="<?= htmlspecialchars($selectorId) ?>Label">Select an Item</h5>
                    <p class="text-muted small mb-0">Search and choose an item to reuse across different admin workflows.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3 align-items-end mb-3">
                    <div class="col-12 col-lg-4">
                        <label for="<?= htmlspecialchars($selectorId) ?>Search" class="form-label">Search</label>
                        <input type="search" class="form-control" id="<?= htmlspecialchars($selectorId) ?>Search" placeholder="Search by name or #ID" data-item-selector-search>
                        <div class="form-text">Results update as you type.</div>
                    </div>
                    <div class="col-6 col-lg-2">
                        <label for="<?= htmlspecialchars($selectorId) ?>Type" class="form-label">Item Type</label>
                        <select class="form-select" id="<?= htmlspecialchars($selectorId) ?>Type" data-item-selector-filter="type">
                            <option value="">All</option>
                            <?php foreach ($filterTypes as $key => $label): ?>
                                <option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6 col-lg-2">
                        <label for="<?= htmlspecialchars($selectorId) ?>Category" class="form-label">Item Category</label>
                        <select class="form-select" id="<?= htmlspecialchars($selectorId) ?>Category" data-item-selector-filter="category">
                            <option value="">All</option>
                            <?php foreach ($filterCategories as $key => $label): ?>
                                <option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6 col-lg-2">
                        <label for="<?= htmlspecialchars($selectorId) ?>Equipment" class="form-label">Equipment Slot</label>
                        <select class="form-select" id="<?= htmlspecialchars($selectorId) ?>Equipment" data-item-selector-filter="equipment">
                            <option value="">All</option>
                            <?php foreach ($filterEquipmentSlots as $key => $label): ?>
                                <option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6 col-lg-2">
                        <label for="<?= htmlspecialchars($selectorId) ?>PageSize" class="form-label">Results per page</label>
                        <select class="form-select" id="<?= htmlspecialchars($selectorId) ?>PageSize" data-item-selector-page-size>
                            <option value="6">6</option>
                            <option value="12" selected>12</option>
                            <option value="24">24</option>
                        </select>
                    </div>
                </div>
                <div class="row g-3" data-item-selector-list>
                    <?php foreach ($itemOptions as $option): ?>
                        <?php
                            $iconUrl = ($option->iconSmall && $option->iconSmall->isValid()) ? $option->iconSmall->getFullPath() : '';
                            $rarityName = $option->rarity->name ?? 'Unknown';
                            $typeName = $option->type->name ?? '';
                            $categoryName = $option->itemCategory?->name ?? '';
                            $equipmentName = $option->equipmentSlot?->name ?? '';
                            $typeKey = $typeName !== '' ? strtolower($typeName) : '';
                            $categoryKey = $categoryName !== '' ? strtolower($categoryName) : 'uncategorized';
                            $equipmentKey = $equipmentName !== '' ? strtolower($equipmentName) : 'none';
                            $typeLabel = $typeName !== '' ? $humanizeLabel($typeName) : 'Unknown';
                            $categoryLabel = $categoryName !== '' ? $humanizeLabel($categoryName) : 'Uncategorized';
                            $equipmentLabel = $equipmentName !== '' ? $humanizeLabel($equipmentName) : 'None';
                        ?>
                        <div class="col-12 col-md-6 col-lg-4" data-item-selector-item data-item-id="<?= $option->crand ?>" data-item-name="<?= htmlspecialchars($option->name) ?>" data-item-rarity="<?= htmlspecialchars($rarityName) ?>" data-item-icon="<?= htmlspecialchars($iconUrl) ?>" data-item-type="<?= htmlspecialchars($typeKey) ?>" data-item-type-label="<?= htmlspecialchars($typeLabel) ?>" data-item-category="<?= htmlspecialchars($categoryKey) ?>" data-item-category-label="<?= htmlspecialchars($categoryLabel) ?>" data-item-equipment="<?= htmlspecialchars($equipmentKey) ?>" data-item-equipment-label="<?= htmlspecialchars($equipmentLabel) ?>">
                            <div class="card h-100 shadow-sm">
                                <div class="card-body d-flex flex-column gap-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <?php if ($iconUrl): ?>
                                            <img src="<?= htmlspecialchars($iconUrl) ?>" alt="<?= htmlspecialchars($option->name) ?>" width="48" height="48" class="rounded">
                                        <?php else: ?>
                                            <div class="bg-body-secondary rounded d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                                <i class="bi bi-box-seam text-muted"></i>
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <div class="fw-semibold">#<?= $option->crand ?> — <?= htmlspecialchars($option->name) ?></div>
                                            <div class="text-muted small">Rarity: <?= htmlspecialchars($rarityName) ?></div>
                                            <div class="text-muted small">Type: <?= htmlspecialchars($typeLabel) ?> • Category: <?= htmlspecialchars($categoryLabel) ?></div>
                                            <?php if ($equipmentLabel !== 'None'): ?>
                                                <span class="badge bg-secondary-subtle text-secondary-emphasis mt-1">Equipment: <?= htmlspecialchars($equipmentLabel) ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-primary w-100 mt-auto" data-item-selector-choose>
                                        <i class="bi bi-check2-circle me-1"></i>Select Item
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="text-center text-muted py-4 d-none" data-item-selector-empty>
                    <i class="bi bi-search"></i>
                    <p class="mb-0">No items match your search. Try a different name or ID.</p>
                </div>
            </div>
            <div class="modal-footer">
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between w-100 gap-3">
                    <div class="text-muted small" data-item-selector-pagination-summary>Showing 0-0 of 0 items</div>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-item-selector-prev>
                            <i class="bi bi-chevron-left"></i>
                        </button>
                        <div class="small" data-item-selector-pagination-label>Page 1</div>
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-item-selector-next>
                            <i class="bi bi-chevron-right"></i>
                        </button>
                    </div>
                </div>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
