<?php
$pageTitle = "Loot Grant Tool";
$pageImage = "https://kickback-kingdom.com/assets/media/context/treasure-hunt.png";
$pageDesc = "Spawn loot entries for one or more accounts in bulk.";

require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__) . "/Kickback/init.php");

$session = require(\Kickback\SCRIPT_ROOT . "/api/v1/engine/session/verifySession.php");
require("php-components/base-page-pull-active-account-info.php");

use Kickback\Backend\Controllers\ItemController;
use Kickback\Backend\Controllers\LootController;
use Kickback\Backend\Controllers\AccountController;
use Kickback\Backend\Views\vRecordId;
use Kickback\Common\Version;
use Kickback\Services\Session;

if (!Session::isAdmin()) {
    header('Location: index.php');
    exit();
}

$alertMessage = '';
$alertVariant = '';
$grantResults = [];

$itemTableResp = ItemController::getItemTable();
$itemOptions = $itemTableResp->success ? $itemTableResp->data : [];

$quantityValue = max(1, (int)($_POST['quantity'] ?? 1));
$selectedItemId = (int)($_POST['item_id'] ?? 0);
$recipientMode = $_POST['recipient_mode'] ?? 'single';
$isAllRecipients = $recipientMode === 'all';
$singleAccountId = (int)($_POST['single_account_id'] ?? 0);
$accountIdListRaw = $_POST['account_ids'] ?? '';
$selectedItemTitle = $selectedItemId > 0 ? "#{$selectedItemId} ready" : 'No item selected';
$selectedItemMeta = $selectedItemId > 0
    ? "Using item ID #{$selectedItemId}. Reopen the selector to change."
    : 'Choose an item from the selector.';

$accountCountResp = AccountController::getAccountCount();
$accountCount = $accountCountResp->success ? (int)$accountCountResp->data : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $recipientIds = [];

    if ($recipientMode === 'list') {
        $rawIds = preg_split('/[\s,]+/', $accountIdListRaw);
        foreach ($rawIds as $rawId) {
            $value = (int)trim($rawId);
            if ($value > 0) {
                $recipientIds[] = $value;
            }
        }
    } else {
        if ($singleAccountId > 0) {
            $recipientIds[] = $singleAccountId;
        } else {
            $alertMessage = 'Please choose a valid account for single-recipient grants.';
            $alertVariant = 'warning';
        }
    }

    $recipientIds = array_values(array_unique(array_filter($recipientIds, fn(int $id) => $id > 0)));

    if ($selectedItemId <= 0) {
        $alertMessage = 'Select an item before granting loot.';
        $alertVariant = 'danger';
    } elseif ($alertMessage === '' && !$isAllRecipients && empty($recipientIds)) {
        $alertMessage = 'No recipients were provided.';
        $alertVariant = 'warning';
    }

    if ($alertMessage === '') {
        $itemRecord = new vRecordId('', $selectedItemId);
        $recipientRecords = array_map(fn(int $id) => new vRecordId('', $id), $recipientIds);

        $itemsToGrant = [];
        for ($i = 0; $i < $quantityValue; $i++) {
            $itemsToGrant[] = [
                'Id' => $itemRecord,
                'DateObtained' => null,
            ];
        }

        if ($isAllRecipients) {
            $resp = LootController::giveLootArrayToAllAccounts($itemsToGrant);

            $grantResults[] = [
                'accountId' => 'All accounts',
                'success' => $resp->success,
                'message' => $resp->message,
            ];

            if ($resp->success) {
                $alertVariant = 'success';
                $alertMessage = "Granted item #{$selectedItemId} to all accounts.";
            } else {
                $alertVariant = 'danger';
                $alertMessage = "Failed to grant item #{$selectedItemId} to all accounts: " . $resp->message;
            }
        } else {
            $resp = LootController::giveLootArrayToAccounts($recipientRecords, $itemsToGrant);
            foreach ($recipientRecords as $record) {
                $grantResults[] = [
                    'accountId' => $record->crand,
                    'success' => $resp->success,
                    'message' => $resp->message,
                ];
            }

            $successCount = $resp->success ? count($recipientRecords) : 0;
            $failureCount = count($recipientRecords) - $successCount;

            if ($failureCount === 0) {
                $alertVariant = 'success';
                $alertMessage = "Granted item #{$selectedItemId} to {$successCount} account(s).";
            } else {
                $alertVariant = 'warning';
                $alertMessage = "Granted item #{$selectedItemId} to {$successCount} account(s) with {$failureCount} failure(s).";
            }
        }
    }
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

    <main class="container pt-3 bg-body" style="margin-bottom: 56px;">
        <div class="row">
            <div class="col-12">
                <?php
                $activePageName = "Loot Grant Tool";
                require("php-components/base-page-breadcrumbs.php");
                ?>

                <div class="card shadow-sm mb-3">
                    <div class="card-body d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2">
                        <div>
                            <h5 class="mb-1">Spawn Loot Records</h5>
                            <p class="text-body-secondary mb-0">Quickly add loot entries for one account, a curated list, or the entire realm.</p>
                        </div>
                        <div class="d-flex align-items-center gap-2 text-body-secondary small">
                            <i class="fa-solid fa-shield-halved"></i>
                            <span>Admin-only utility</span>
                        </div>
                    </div>
                </div>

                <?php if ($alertMessage !== '') { ?>
                    <div class="alert alert-<?= htmlspecialchars($alertVariant ?: 'info'); ?>" role="alert">
                        <?= htmlspecialchars($alertMessage); ?>
                    </div>
                <?php } ?>

                <form method="POST" class="card shadow-sm">
                    <div class="card-body">
                        <div class="row g-4">
                            <div class="col-12 col-lg-5">
                                <div class="d-flex align-items-center gap-2 mb-3">
                                    <span class="text-uppercase small fw-semibold text-body-secondary">Item</span>
                                    <div class="border-top flex-grow-1 opacity-25"></div>
                                </div>
                                <input type="hidden" name="item_id" id="item-id" value="<?= $selectedItemId > 0 ? $selectedItemId : ''; ?>">
                                <div class="p-3 border rounded bg-body-tertiary h-100">
                                    <div class="d-flex align-items-start gap-3">
                                        <div class="rounded bg-body-secondary d-flex align-items-center justify-content-center" style="width:72px;height:72px;">
                                            <i class="fa-solid fa-gift text-muted fs-3" id="item-preview-icon"></i>
                                            <img src="" alt="Selected item" id="item-preview-image" class="img-fluid rounded d-none" style="width:72px;height:72px;object-fit:contain;">
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="fw-semibold mb-1" id="item-title"><?= htmlspecialchars($selectedItemTitle); ?></div>
                                            <div class="text-body-secondary small" id="item-meta"><?= htmlspecialchars($selectedItemMeta); ?></div>
                                            <div class="mt-3 d-flex flex-wrap gap-2">
                                                <button type="button" class="btn btn-outline-primary btn-sm" data-open-item-selector>
                                                    <i class="fa-solid fa-magnifying-glass me-1"></i> Browse Items
                                                </button>
                                                <a class="btn btn-outline-secondary btn-sm" href="admin-item-manager.php" target="_blank">
                                                    <i class="fa-solid fa-box-open me-1"></i> Manage Items
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <label for="quantity" class="form-label">Copies to grant per recipient</label>
                                        <input type="number" min="1" class="form-control" id="quantity" name="quantity" value="<?= $quantityValue; ?>">
                                        <div class="form-text">Creates one loot row per copy for each target account.</div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 col-lg-7">
                                <div class="d-flex align-items-center gap-2 mb-3">
                                    <span class="text-uppercase small fw-semibold text-body-secondary">Recipients</span>
                                    <div class="border-top flex-grow-1 opacity-25"></div>
                                </div>
                                <div class="p-3 border rounded bg-body-tertiary h-100">
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="radio" name="recipient_mode" id="recipient-single" value="single" <?= $recipientMode === 'single' ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="recipient-single">
                                            Single account
                                        </label>
                                    </div>
                                    <div class="ps-4 mb-3" id="single-recipient-fields">
                                        <input type="hidden" name="single_account_id" id="single-account-id" value="<?= $singleAccountId > 0 ? $singleAccountId : ''; ?>">
                                        <div class="input-group mb-2">
                                            <input type="text" class="form-control" id="single-account-display" placeholder="No account selected" value="<?= $singleAccountId > 0 ? "Account #{$singleAccountId}" : ''; ?>" readonly>
                                            <button class="btn btn-outline-secondary" type="button" data-open-account-single>
                                                <i class="fa-solid fa-magnifying-glass"></i>
                                            </button>
                                            <button class="btn btn-outline-danger" type="button" id="clear-single-account" title="Clear selection">
                                                <i class="fa-solid fa-xmark"></i>
                                            </button>
                                        </div>
                                        <div class="form-text" id="single-account-label">Pick an account to grant loot.</div>
                                    </div>

                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="radio" name="recipient_mode" id="recipient-list" value="list" <?= $recipientMode === 'list' ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="recipient-list">
                                            Specific list
                                        </label>
                                    </div>
                                    <div class="ps-4 mb-3" id="list-recipient-fields">
                                        <label for="account-ids" class="form-label">Account IDs (comma, space, or newline separated)</label>
                                        <textarea class="form-control" id="account-ids" name="account_ids" rows="4" placeholder="123, 456, 789"><?= htmlspecialchars($accountIdListRaw); ?></textarea>
                                        <div class="d-flex flex-wrap gap-2 mt-2">
                                            <button type="button" class="btn btn-outline-secondary btn-sm" data-open-account-list>
                                                <i class="fa-solid fa-user-plus me-1"></i> Add from search
                                            </button>
                                            <button type="button" class="btn btn-outline-danger btn-sm" id="clear-list-accounts">
                                                <i class="fa-solid fa-eraser me-1"></i> Clear list
                                            </button>
                                        </div>
                                        <div class="form-text">Use the account search modal to append IDs without leaving this page.</div>
                                    </div>

                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="radio" name="recipient_mode" id="recipient-all" value="all" <?= $recipientMode === 'all' ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="recipient-all">
                                            Everyone (<?= $accountCount; ?> account<?= $accountCount === 1 ? '' : 's'; ?>)
                                        </label>
                                    </div>
                                    <div class="ps-4" id="all-recipient-fields">
                                        <div class="alert alert-warning d-flex align-items-start gap-2 mb-0" role="alert">
                                            <i class="fa-solid fa-triangle-exclamation"></i>
                                            <div>
                                                Grants will be created for every account currently in the database. Double-check the item and quantity before submitting.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 flex-wrap">
                        <div class="text-body-secondary small flex-grow-1 text-wrap">
                            Grants are created immediately using LootController helpers based on the recipient selection.
                        </div>
                        <button type="submit" class="btn bg-ranked-1 text-white">
                            <i class="fa-solid fa-paper-plane me-1"></i> Grant Loot
                        </button>
                    </div>
                </form>

                <?php if (!empty($grantResults)) { ?>
                    <div class="card shadow-sm mt-3">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <div>
                                <span class="badge bg-secondary me-2"><?= count($grantResults); ?> processed</span>
                                Grant results
                            </div>
                            <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#grant-results" aria-expanded="true" aria-controls="grant-results">
                                Toggle Details
                            </button>
                        </div>
                        <div class="collapse show" id="grant-results">
                            <div class="table-responsive">
                                <table class="table table-sm align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th style="width: 120px;">Account ID</th>
                                            <th style="width: 120px;">Status</th>
                                            <th>Message</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($grantResults as $row) { ?>
                                            <tr>
                                                <td>#<?= htmlspecialchars((string)$row['accountId']); ?></td>
                                                <td>
                                                    <?php if ($row['success']) { ?>
                                                        <span class="badge bg-success">Success</span>
                                                    <?php } else { ?>
                                                        <span class="badge bg-danger">Failed</span>
                                                    <?php } ?>
                                                </td>
                                                <td><?= htmlspecialchars($row['message'] ?? ''); ?></td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php } ?>

            </div>
        </div>
        <?php require("php-components/base-page-footer.php"); ?>
    </main>

    <?php require("php-components/base-page-javascript.php"); ?>
    <?php require("php-components/base-page-javascript-account-search.php"); ?>
    <script src="<?= Version::urlBetaPrefix(); ?>/assets/js/item-selector.js"></script>
    <?php $selectorId = 'lootGrantItemSelector'; require("php-components/item-selector-modal.php"); ?>
    <script>
        (function() {
            const selectorId = 'lootGrantItemSelector';
            const itemSelectorModal = ItemSelector.init(selectorId);
            const itemIdInput = document.getElementById('item-id');
            const itemTitle = document.getElementById('item-title');
            const itemMeta = document.getElementById('item-meta');
            const itemIcon = document.getElementById('item-preview-icon');
            const itemImage = document.getElementById('item-preview-image');
            const quantityInput = document.getElementById('quantity');

            const singleRadio = document.getElementById('recipient-single');
            const listRadio = document.getElementById('recipient-list');
            const allRadio = document.getElementById('recipient-all');
            const singleFields = document.getElementById('single-recipient-fields');
            const listFields = document.getElementById('list-recipient-fields');
            const allFields = document.getElementById('all-recipient-fields');

            const singleAccountIdInput = document.getElementById('single-account-id');
            const singleAccountDisplay = document.getElementById('single-account-display');
            const singleAccountLabel = document.getElementById('single-account-label');
            const accountIdsTextarea = document.getElementById('account-ids');

            function updateItemPreview(item) {
                if (!item) {
                    itemIcon?.classList.remove('d-none');
                    itemImage?.classList.add('d-none');
                    itemImage?.setAttribute('src', '');
                    if (itemTitle) itemTitle.textContent = 'No item selected';
                    if (itemMeta) itemMeta.textContent = 'Choose an item from the selector.';
                    return;
                }

                if (itemIdInput) itemIdInput.value = item.crand || '';
                if (itemTitle) itemTitle.textContent = `#${item.crand} — ${item.name || 'Unnamed item'}`;
                if (itemMeta) itemMeta.textContent = `${item.rarity || 'Unknown rarity'} • ${item.type || 'Item'}`;

                if (item.icon) {
                    itemIcon?.classList.add('d-none');
                    itemImage?.classList.remove('d-none');
                    itemImage?.setAttribute('src', item.icon);
                } else {
                    itemIcon?.classList.remove('d-none');
                    itemImage?.classList.add('d-none');
                    itemImage?.setAttribute('src', '');
                }
            }

            function setRecipientVisibility() {
                if (singleFields) singleFields.classList.toggle('d-none', !singleRadio.checked);
                if (listFields) listFields.classList.toggle('d-none', !listRadio.checked);
                if (allFields) allFields.classList.toggle('d-none', !allRadio.checked);
            }

            function formatAccountLabel(accountId) {
                if (!accountId) return 'Pick an account to grant loot.';

                const accountInfo = typeof selectAccountResultsById !== 'undefined'
                    ? selectAccountResultsById[accountId]
                    : null;

                const username = accountInfo?.username ? `@${accountInfo.username}` : null;
                return username ? `${username} (#${accountId})` : `Selected account #${accountId}`;
            }

            function updateSingleAccountLabel() {
                const id = singleAccountIdInput?.value || '';
                if (singleAccountLabel) {
                    singleAccountLabel.textContent = formatAccountLabel(id);
                }
                if (singleAccountDisplay) {
                    singleAccountDisplay.value = id ? `Account #${id}` : '';
                }
            }

            function openAccountModal(callbackName) {
                if (typeof OpenSelectAccountModal === 'function') {
                    OpenSelectAccountModal(null, callbackName);
                }
            }

            function appendAccountId(id) {
                if (!id || !accountIdsTextarea) return;
                const existing = accountIdsTextarea.value.trim();
                const separator = existing === '' ? '' : '\n';
                accountIdsTextarea.value = `${existing}${separator}${id}`;
            }

            document.querySelector('[data-open-item-selector]')?.addEventListener('click', () => {
                itemSelectorModal?.show();
            });

            document.querySelector('[data-open-account-single]')?.addEventListener('click', () => {
                singleRadio.checked = true;
                setRecipientVisibility();
                openAccountModal('selectSingleAccount');
            });

            document.querySelector('[data-open-account-list]')?.addEventListener('click', () => {
                listRadio.checked = true;
                setRecipientVisibility();
                openAccountModal('addAccountToList');
            });

            document.getElementById('clear-single-account')?.addEventListener('click', () => {
                if (singleAccountIdInput) singleAccountIdInput.value = '';
                updateSingleAccountLabel();
            });

            document.getElementById('clear-list-accounts')?.addEventListener('click', () => {
                if (accountIdsTextarea) accountIdsTextarea.value = '';
            });

            [singleRadio, listRadio, allRadio].forEach((radio) => {
                radio?.addEventListener('change', setRecipientVisibility);
            });

            document.addEventListener('item-selector:selected', (event) => {
                const item = event.detail?.item;
                updateItemPreview(item);
                itemSelectorModal?.hide();
            });

            window.selectSingleAccount = function(accountId) {
                if (singleAccountIdInput) singleAccountIdInput.value = accountId ?? '';
                updateSingleAccountLabel();
                const modalEl = document.getElementById('selectAccountModal');
                const modal = modalEl ? bootstrap.Modal.getOrCreateInstance(modalEl) : null;
                modal?.hide();
            };

            window.addAccountToList = function(accountId) {
                appendAccountId(accountId);
                const modalEl = document.getElementById('selectAccountModal');
                const modal = modalEl ? bootstrap.Modal.getOrCreateInstance(modalEl) : null;
                modal?.hide();
            };

            // Initialize state on load
            setRecipientVisibility();
            updateSingleAccountLabel();

            // Ensure quantity stays positive
            quantityInput?.addEventListener('change', () => {
                if (!quantityInput.value || Number(quantityInput.value) < 1) {
                    quantityInput.value = '1';
                }
            });
        })();
    </script>
</body>

</html>
