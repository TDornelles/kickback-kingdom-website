<?php
use Kickback\Backend\Views\vProduct;

$selectorId = $selectorId ?? 'productSelectorModal';
$productOptions = $productOptions ?? [];

$humanizeLabel = static function (string $value): string {
    $normalized = str_replace('_', ' ', $value);
    $spaced = preg_replace('/(?<!^)([A-Z])/', ' $1', $normalized);
    return ucwords(strtolower(trim($spaced ?? $normalized)));
};

$stores = [];
foreach ($productOptions as $product) {
    $storeName = $product->store->name ?? '';
    $storeKey = strtolower($storeName);
    if ($storeKey !== '') {
        $stores[$storeKey] = $storeName;
    }
}
ksort($stores);
?>

<div class="modal fade" id="<?= htmlspecialchars($selectorId) ?>" tabindex="-1" aria-labelledby="<?= htmlspecialchars($selectorId) ?>Label" aria-hidden="true" data-product-selector-modal>
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title mb-1" id="<?= htmlspecialchars($selectorId) ?>Label">Select a Product</h5>
                    <p class="text-muted small mb-0">Search by name, ID, or store to add products to the Emberwood shipment pool.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3 align-items-end mb-3">
                    <div class="col-12 col-lg-4">
                        <div class="d-flex flex-column h-100 gap-1">
                            <label for="<?= htmlspecialchars($selectorId) ?>Search" class="form-label">Search</label>
                            <input type="search" class="form-control" id="<?= htmlspecialchars($selectorId) ?>Search" placeholder="Search by name or #ID" data-product-selector-search>
                            <div class="form-text mb-0">Results update as you type.</div>
                        </div>
                    </div>
                    <div class="col-12 col-lg-4">
                        <div class="d-flex flex-column h-100 gap-1">
                            <label for="<?= htmlspecialchars($selectorId) ?>Store" class="form-label">Store</label>
                            <select class="form-select" id="<?= htmlspecialchars($selectorId) ?>Store" data-product-selector-filter="store">
                                <option value="">All stores</option>
                                <?php foreach ($stores as $key => $label): ?>
                                    <option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-12 col-lg-2">
                        <div class="d-flex flex-column h-100 gap-1">
                            <label for="<?= htmlspecialchars($selectorId) ?>PageSize" class="form-label">Results per page</label>
                            <select class="form-select" id="<?= htmlspecialchars($selectorId) ?>PageSize" data-product-selector-page-size>
                                <option value="6">6</option>
                                <option value="12" selected>12</option>
                                <option value="24">24</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row g-3" data-product-selector-list>
                    <?php foreach ($productOptions as $option): ?>
                        <?php
                            /** @var vProduct $option */
                            $iconUrl = ($option->mediaSmall && $option->mediaSmall->isValid()) ? $option->mediaSmall->getFullPath() : '';
                            $storeName = $option->store->name ?? '';
                            $storeKey = strtolower($storeName);
                        ?>
                        <div class="col-12 col-md-6 col-lg-4" data-product-selector-item data-product-id="<?= htmlspecialchars($option->crand) ?>" data-product-ctime="<?= htmlspecialchars($option->ctime) ?>" data-product-name="<?= htmlspecialchars($option->name) ?>" data-product-store="<?= htmlspecialchars($storeKey) ?>" data-product-store-label="<?= htmlspecialchars($storeName) ?>">
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
                                            <div class="text-muted small">Store: <?= htmlspecialchars($storeName !== '' ? $storeName : 'Unknown') ?></div>
                                            <div class="text-muted small">Locator: <?= htmlspecialchars($option->locator ?? 'N/A') ?></div>
                                        </div>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-primary w-100 mt-auto" data-product-selector-choose>
                                        <i class="bi bi-check2-circle me-1"></i>Select Product
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="text-center text-muted py-4 d-none" data-product-selector-empty>
                    <i class="bi bi-search"></i>
                    <p class="mb-0">No products match your search. Try a different name or locator.</p>
                </div>
            </div>
            <div class="modal-footer">
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between w-100 gap-3">
                    <div class="text-muted small" data-product-selector-pagination-summary>Showing 0-0 of 0 products</div>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1" data-product-selector-prev>
                            <i class="bi bi-chevron-left"></i>
                            <span>Previous</span>
                        </button>
                        <div class="small" data-product-selector-pagination-label>Page 1</div>
                        <button type="button" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1" data-product-selector-next>
                            <span>Next</span>
                            <i class="bi bi-chevron-right"></i>
                        </button>
                    </div>
                </div>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
