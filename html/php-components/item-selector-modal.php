<?php
$selectorId = $selectorId ?? 'itemSelectorModal';
$itemOptions = $itemOptions ?? [];
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
                <div class="mb-3">
                    <label for="<?= htmlspecialchars($selectorId) ?>Search" class="form-label">Search</label>
                    <input type="search" class="form-control" id="<?= htmlspecialchars($selectorId) ?>Search" placeholder="Search by name or #ID" data-item-selector-search>
                    <div class="form-text">Results update as you type.</div>
                </div>
                <div class="row g-3" data-item-selector-list>
                    <?php foreach ($itemOptions as $option): ?>
                        <?php
                            $iconUrl = ($option->iconSmall && $option->iconSmall->isValid()) ? $option->iconSmall->getFullPath() : '';
                            $rarityName = $option->rarity->name ?? 'Unknown';
                        ?>
                        <div class="col-12 col-md-6 col-lg-4" data-item-selector-item data-item-id="<?= $option->crand ?>" data-item-name="<?= htmlspecialchars($option->name) ?>" data-item-rarity="<?= htmlspecialchars($rarityName) ?>" data-item-icon="<?= htmlspecialchars($iconUrl) ?>">
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
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
