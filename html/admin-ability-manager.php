<?php
$pageTitle = "Ability Manager";
$pageImage = "https://kickback-kingdom.com/assets/media/context/market-hall.png";
$pageDesc = "Admin panel to browse, create, update, and remove abilities from the database.";

require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__) . "/Kickback/init.php");

$session = require(\Kickback\SCRIPT_ROOT . "/api/v1/engine/session/verifySession.php");
require("php-components/base-page-pull-active-account-info.php");

use Kickback\Backend\Controllers\AbilityController;
use Kickback\Backend\Models\Ability;
use Kickback\Backend\Views\vRecordId;
use Kickback\Backend\Views\vAbility;
use Kickback\Common\Version;
use Kickback\Services\Session;

if (!Session::isAdmin()) {
    header('Location: index.php');
    exit();
}

$alertMessage = '';
$alertVariant = '';
$defaultIconClass = 'fa-wand-sparkles';
$iconOptions = [
    'fa-wand-sparkles',
    'fa-fire',
    'fa-sparkles',
    'fa-hat-wizard',
    'fa-dragon',
    'fa-bolt',
    'fa-shield-halved',
    'fa-heart',
    'fa-hand-fist',
    'fa-feather-pointed',
    'fa-diamond',
    'fa-skull',
    'fa-sun',
    'fa-moon',
    'fa-star',
    'fa-person-running',
];

function sanitizeIconClass(string $icon, string $defaultIcon): string
{
    $normalized = preg_replace('/[^a-z0-9\-\s]/i', '', trim($icon));
    return $normalized === '' ? $defaultIcon : $normalized;
}

function buildAbilityFromPost(string $defaultIcon): Ability
{
    $ability = new Ability();
    $ability->name = trim($_POST['name'] ?? '');
    $ability->desc = trim($_POST['description'] ?? '');
    $ability->icon = sanitizeIconClass($_POST['icon'] ?? '', $defaultIcon);
    $ability->prestigeGain = (int)($_POST['prestige_gain'] ?? 0);
    $ability->prestigeMultiplier = (float)($_POST['prestige_multiplier'] ?? 0);
    $ability->expGain = (int)($_POST['exp_gain'] ?? 0);
    $ability->expMultiplier = (float)($_POST['exp_multiplier'] ?? 0);
    $ability->levelGain = (int)($_POST['level_gain'] ?? 0);
    $ability->levelMultiplier = (float)($_POST['level_multiplier'] ?? 0);
    $ability->titleChange = trim($_POST['title_change'] ?? '');

    return $ability;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'update') {
        $ability = buildAbilityFromPost($defaultIconClass);

        if ($action === 'update') {
            $ability->crand = (int)($_POST['ability_id'] ?? -1);
        }

        $response = $action === 'create'
            ? AbilityController::insertAbility($ability)
            : AbilityController::updateAbility($ability);

        $alertMessage = $response->message;
        $alertVariant = $response->success ? 'success' : 'danger';
    } elseif ($action === 'delete') {
        $abilityId = new vRecordId('', (int)($_POST['ability_id'] ?? -1));
        $response = AbilityController::deleteAbility($abilityId);

        $alertMessage = $response->message;
        $alertVariant = $response->success ? 'success' : 'danger';
    }
}

$abilityTableResp = AbilityController::getAbilityTable();
$abilities = $abilityTableResp->success ? $abilityTableResp->data : [];
foreach ($abilities as $ability) {
    /** @var vAbility $ability */
    $ability->icon = sanitizeIconClass($ability->icon ?? '', $defaultIconClass);
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
                $activePageName = "Ability Manager";
                require("php-components/base-page-breadcrumbs.php");
                ?>

                <div class="card shadow-sm mb-3">
                    <div class="card-body d-flex flex-column flex-md-row gap-2 align-items-md-center justify-content-between">
                        <div>
                            <h5 class="mb-1">Ability Table</h5>
                            <p class="text-body-secondary mb-0">Search, sort, and manage abilities directly from the table.</p>
                        </div>
                        <div class="d-flex flex-column flex-sm-row gap-2 align-items-stretch align-items-sm-center">
                            <input type="search" class="form-control" id="ability-search" placeholder="Search by name or ID">
                            <button class="btn bg-ranked-1 flex-shrink-0 text-nowrap" data-bs-toggle="modal" data-bs-target="#abilityModal" data-mode="create">
                                <i class="fa-solid fa-plus"></i> New Ability
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

                <?php if (!$abilityTableResp->success) { ?>
                    <div class="alert alert-danger" role="alert">
                        <?= htmlspecialchars($abilityTableResp->message); ?>
                    </div>
                <?php } else { ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle" id="ability-table">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col" class="sortable" data-sort-key="id">ID <i class="fa-solid ms-1 sort-icon d-none"></i></th>
                                    <th scope="col">Icon</th>
                                    <th scope="col" class="sortable" data-sort-key="name">Name <i class="fa-solid ms-1 sort-icon d-none"></i></th>
                                    <th scope="col" class="sortable" data-sort-key="prestige_gain">Prestige Gain <i class="fa-solid ms-1 sort-icon d-none"></i></th>
                                    <th scope="col" class="sortable" data-sort-key="exp_gain">EXP Gain <i class="fa-solid ms-1 sort-icon d-none"></i></th>
                                    <th scope="col" class="sortable" data-sort-key="level_gain">Level Gain <i class="fa-solid ms-1 sort-icon d-none"></i></th>
                                    <th scope="col">Title Change</th>
                                    <th scope="col">Description</th>
                                    <th scope="col" class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($abilities as $ability) {
                                    /** @var vAbility $ability */
                                    $iconClass = htmlspecialchars($ability->icon ?: $defaultIconClass);
                                    $abilityData = [
                                        'id' => $ability->crand,
                                        'name' => $ability->name,
                                        'description' => $ability->description,
                                        'icon' => $ability->icon ?: $defaultIconClass,
                                        'prestige_gain' => $ability->prestigeGain,
                                        'prestige_multiplier' => $ability->prestigeMultiplier,
                                        'exp_gain' => $ability->expGain,
                                        'exp_multiplier' => $ability->expMultiplier,
                                        'level_gain' => $ability->levelGain,
                                        'level_multiplier' => $ability->levelMultiplier,
                                        'title_change' => $ability->titleChange,
                                    ];
                                ?>
                                    <tr
                                        data-ability-id="<?= (int)$ability->crand; ?>"
                                        data-ability-name="<?= htmlspecialchars(strtolower($ability->name)); ?>"
                                        data-ability-prestige-gain="<?= (int)$ability->prestigeGain; ?>"
                                        data-ability-exp-gain="<?= (int)$ability->expGain; ?>"
                                        data-ability-level-gain="<?= (int)$ability->levelGain; ?>"
                                    >
                                        <td class="fw-semibold">#<?= (int)$ability->crand; ?></td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-ranked-1 bg-opacity-10 text-ranked" style="width: 40px; height: 40px;">
                                                    <i class="fa-solid <?= $iconClass; ?>"></i>
                                                </span>
                                                <div class="small text-body-secondary text-truncate" style="max-width: 140px;">
                                                    <?= $iconClass; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="fw-semibold"><?= htmlspecialchars($ability->name); ?></td>
                                        <td><?= (int)$ability->prestigeGain; ?></td>
                                        <td><?= (int)$ability->expGain; ?></td>
                                        <td><?= (int)$ability->levelGain; ?></td>
                                        <td><?= htmlspecialchars($ability->titleChange ?: '—'); ?></td>
                                        <td class="text-break" style="max-width: 320px;">
                                            <div class="small mb-0">Multiplier P/E/L: <?= $ability->prestigeMultiplier; ?> / <?= $ability->expMultiplier; ?> / <?= $ability->levelMultiplier; ?></div>
                                            <div><?= htmlspecialchars($ability->description); ?></div>
                                        </td>
                                        <td class="text-end">
                                            <div class="btn-group" role="group">
                                                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#abilityModal" data-mode="edit" data-ability='<?= htmlspecialchars(json_encode($abilityData), ENT_QUOTES); ?>'>
                                                    <i class="fa-solid fa-pen"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteAbilityModal" data-ability-id="<?= (int)$ability->crand; ?>" data-ability-name="<?= htmlspecialchars($ability->name); ?>">
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
                            <label for="ability-page-size" class="form-label mb-0 small text-body-secondary">Rows per page</label>
                            <select class="form-select form-select-sm" id="ability-page-size" style="width: auto; min-width: 90px;">
                                <option value="10" selected>10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                            </select>
                        </div>
                        <nav aria-label="Ability pagination">
                            <ul class="pagination pagination-sm mb-0" id="ability-pagination"></ul>
                        </nav>
                    </div>
                <?php } ?>
            </div>
        </div>

        <?php require("php-components/base-page-footer.php"); ?>
    </main>

    <div class="modal fade" id="abilityModal" tabindex="-1" aria-labelledby="abilityModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title mb-1" id="abilityModalLabel">Create Ability</h5>
                        <p class="text-muted small mb-0">Fill out the fields below to create or update an ability.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create" id="ability-form-action">
                        <input type="hidden" name="ability_id" value="" id="ability-id">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="ability-name" class="form-label">Name</label>
                                <input type="text" class="form-control" id="ability-name" name="name" required maxlength="45">
                            </div>
                            <div class="col-md-6">
                                <label for="ability-title-change" class="form-label">Title Change</label>
                                <input type="text" class="form-control" id="ability-title-change" name="title_change" maxlength="45">
                            </div>
                            <div class="col-12">
                                <label for="ability-description" class="form-label">Description</label>
                                <textarea class="form-control" id="ability-description" name="description" rows="2" maxlength="255" required></textarea>
                            </div>
                            <div class="col-md-6">
                                <label for="ability-icon" class="form-label">Font Awesome Icon</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-body-secondary" id="icon-preview"><i class="fa-solid <?= htmlspecialchars($defaultIconClass); ?>"></i></span>
                                    <input type="text" class="form-control" id="ability-icon" name="icon" list="icon-options" placeholder="e.g., fa-wand-sparkles" required>
                                </div>
                                <div class="form-text">Use any Font Awesome class. Start typing to search common options.</div>
                                <datalist id="icon-options">
                                    <?php foreach ($iconOptions as $icon) { ?>
                                        <option value="<?= htmlspecialchars($icon); ?>"></option>
                                    <?php } ?>
                                </datalist>
                            </div>
                            <div class="col-md-3">
                                <label for="ability-prestige-gain" class="form-label">Prestige Gain</label>
                                <input type="number" class="form-control" id="ability-prestige-gain" name="prestige_gain" value="0" min="0" required>
                            </div>
                            <div class="col-md-3">
                                <label for="ability-prestige-multiplier" class="form-label">Prestige Multiplier</label>
                                <input type="number" step="0.01" class="form-control" id="ability-prestige-multiplier" name="prestige_multiplier" value="0" min="0" required>
                            </div>
                            <div class="col-md-3">
                                <label for="ability-exp-gain" class="form-label">EXP Gain</label>
                                <input type="number" class="form-control" id="ability-exp-gain" name="exp_gain" value="0" min="0" required>
                            </div>
                            <div class="col-md-3">
                                <label for="ability-exp-multiplier" class="form-label">EXP Multiplier</label>
                                <input type="number" step="0.01" class="form-control" id="ability-exp-multiplier" name="exp_multiplier" value="0" min="0" required>
                            </div>
                            <div class="col-md-3">
                                <label for="ability-level-gain" class="form-label">Level Gain</label>
                                <input type="number" class="form-control" id="ability-level-gain" name="level_gain" value="0" min="0" required>
                            </div>
                            <div class="col-md-3">
                                <label for="ability-level-multiplier" class="form-label">Level Multiplier</label>
                                <input type="number" step="0.01" class="form-control" id="ability-level-multiplier" name="level_multiplier" value="0" min="0" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer d-flex justify-content-between">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn bg-ranked-1" id="ability-submit">Create Ability</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="deleteAbilityModal" tabindex="-1" aria-labelledby="deleteAbilityModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteAbilityModalLabel">Delete Ability</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="ability_id" id="delete-ability-id">
                    <div class="modal-body">
                        <p class="mb-0">Are you sure you want to delete <strong id="delete-ability-name"></strong>?</p>
                        <p class="text-danger small mb-0">This action cannot be undone.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete Ability</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php require("php-components/base-page-javascript.php"); ?>

    <script>
        const defaultIconClass = <?= json_encode($defaultIconClass); ?>;
        const abilityModal = document.getElementById('abilityModal');
        const abilityFormAction = document.getElementById('ability-form-action');
        const abilityIdInput = document.getElementById('ability-id');
        const abilityNameInput = document.getElementById('ability-name');
        const abilityDescriptionInput = document.getElementById('ability-description');
        const abilityTitleInput = document.getElementById('ability-title-change');
        const abilityIconInput = document.getElementById('ability-icon');
        const iconPreviewWrapper = document.getElementById('icon-preview');
        const prestigeGainInput = document.getElementById('ability-prestige-gain');
        const prestigeMultiplierInput = document.getElementById('ability-prestige-multiplier');
        const expGainInput = document.getElementById('ability-exp-gain');
        const expMultiplierInput = document.getElementById('ability-exp-multiplier');
        const levelGainInput = document.getElementById('ability-level-gain');
        const levelMultiplierInput = document.getElementById('ability-level-multiplier');
        const abilitySubmitBtn = document.getElementById('ability-submit');

        function setIconPreview(iconClass = '') {
            const targetClass = iconClass?.trim() || defaultIconClass;
            if (iconPreviewWrapper) {
                iconPreviewWrapper.innerHTML = `<i class="fa-solid ${targetClass}"></i>`;
            }
        }

        abilityIconInput?.addEventListener('input', (event) => {
            setIconPreview(event.target.value);
        });

        abilityModal?.addEventListener('show.bs.modal', (event) => {
            const trigger = event.relatedTarget;
            const mode = trigger?.getAttribute('data-mode') || 'create';
            const modalTitle = document.getElementById('abilityModalLabel');

            if (mode === 'edit' && trigger?.getAttribute('data-ability')) {
                const abilityData = JSON.parse(trigger.getAttribute('data-ability'));
                modalTitle.textContent = `Edit Ability #${abilityData.id}`;
                abilitySubmitBtn.textContent = 'Update Ability';
                abilityFormAction.value = 'update';
                abilityIdInput.value = abilityData.id;
                abilityNameInput.value = abilityData.name || '';
                abilityDescriptionInput.value = abilityData.description || '';
                abilityTitleInput.value = abilityData.title_change || '';
                abilityIconInput.value = abilityData.icon || defaultIconClass;
                prestigeGainInput.value = abilityData.prestige_gain ?? 0;
                prestigeMultiplierInput.value = abilityData.prestige_multiplier ?? 0;
                expGainInput.value = abilityData.exp_gain ?? 0;
                expMultiplierInput.value = abilityData.exp_multiplier ?? 0;
                levelGainInput.value = abilityData.level_gain ?? 0;
                levelMultiplierInput.value = abilityData.level_multiplier ?? 0;
            } else {
                modalTitle.textContent = 'Create Ability';
                abilitySubmitBtn.textContent = 'Create Ability';
                abilityFormAction.value = 'create';
                abilityIdInput.value = '';
                document.querySelector('#abilityModal form')?.reset();
                abilityIconInput.value = defaultIconClass;
                prestigeGainInput.value = 0;
                prestigeMultiplierInput.value = 0;
                expGainInput.value = 0;
                expMultiplierInput.value = 0;
                levelGainInput.value = 0;
                levelMultiplierInput.value = 0;
            }

            setIconPreview(abilityIconInput.value);
        });

        const deleteAbilityModal = document.getElementById('deleteAbilityModal');
        deleteAbilityModal?.addEventListener('show.bs.modal', (event) => {
            const trigger = event.relatedTarget;
            const abilityId = trigger?.getAttribute('data-ability-id') ?? '';
            const abilityName = trigger?.getAttribute('data-ability-name') ?? '';
            document.getElementById('delete-ability-id').value = abilityId;
            document.getElementById('delete-ability-name').textContent = abilityName;
        });

        const searchInput = document.getElementById('ability-search');
        const tableBody = document.querySelector('#ability-table tbody');
        const tableHeaders = document.querySelectorAll('#ability-table thead th[data-sort-key]');
        const paginationContainer = document.getElementById('ability-pagination');
        const pageSizeSelect = document.getElementById('ability-page-size');
        const allRows = tableBody ? Array.from(tableBody.querySelectorAll('tr')) : [];

        const DATASET_KEYS = {
            id: 'abilityId',
            name: 'abilityName',
            prestige_gain: 'abilityPrestigeGain',
            exp_gain: 'abilityExpGain',
            level_gain: 'abilityLevelGain',
        };

        let currentSort = { key: 'id', direction: 'asc' };
        let currentPage = 1;
        let pageSize = parseInt(pageSizeSelect?.value ?? '10', 10) || 10;

        function isNumericKey(key) {
            return ['id', 'prestige_gain', 'exp_gain', 'level_gain'].includes(key);
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
                const id = (row.dataset.abilityId || '').toLowerCase();
                const name = (row.dataset.abilityName || '').toLowerCase();
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

        searchInput?.addEventListener('input', () => {
            currentPage = 1;
            renderTable();
        });

        pageSizeSelect?.addEventListener('change', (event) => {
            pageSize = parseInt(event.target.value, 10) || 10;
            currentPage = 1;
            renderTable();
        });

        renderTable();
    </script>

</body>

</html>
