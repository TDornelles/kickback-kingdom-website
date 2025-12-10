<?php
require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__ . '/..') . "/Kickback/init.php");

$session = require(\Kickback\SCRIPT_ROOT . "/api/v1/engine/session/verifySession.php");
require("../php-components/base-page-pull-active-account-info.php");

use Kickback\Common\Version;
use Kickback\Services\Session;

if (!Session::isLoggedIn()) {
    header('Location: ' . Version::urlBetaPrefix() . '/login.php?redirect=tickets/dashboard.php');
    exit();
}

$currentAccount = Session::getCurrentAccount();
$canManageTickets = Session::isAdmin() || Session::isMagisterOfTheAdventurersGuild() || Session::isServantOfTheLich();
$isMyTicketsShortcut = isset($_GET['my']) && $_GET['my'] === '1';

$prefillFilters = [
    'status' => $_GET['status'] ?? '',
    'priority' => $_GET['priority'] ?? '',
    'assignee' => $_GET['assignee'] ?? ($isMyTicketsShortcut && isset($currentAccount) ? $currentAccount->username : ''),
    'guild' => $_GET['guild'] ?? '',
    'dateFrom' => $_GET['from'] ?? '',
    'dateTo' => $_GET['to'] ?? '',
];

$notificationEmail = isset($currentAccount->email) ? $currentAccount->email : '';
?>
<!DOCTYPE html>
<html lang="en">

<?php require("../php-components/base-page-head.php"); ?>

<body class="bg-body-secondary container p-0">
    <?php
    require("../php-components/base-page-components.php");
    require("../php-components/ad-carousel.php");
    ?>

    <main class="container pt-3 bg-body" style="margin-bottom: 56px;">
        <div class="row">
            <div class="col-12">
                <?php
                    $activePageName = "Ticket Dashboard";
                    require("../php-components/base-page-breadcrumbs.php");
                ?>

                <div class="row g-3 mb-3">
                    <div class="col-6 col-lg-3">
                        <div class="card shadow-sm h-100">
                            <div class="card-body">
                                <div class="text-muted small">Total Tickets</div>
                                <div class="h4 mb-0" id="statTotal">0</div>
                                <div class="small text-secondary" id="statLastUpdated">Updated --</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="card shadow-sm h-100">
                            <div class="card-body">
                                <div class="text-muted small">Open</div>
                                <div class="h4 mb-0" id="statOpen">0</div>
                                <div class="small text-secondary">Waiting for action</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="card shadow-sm h-100">
                            <div class="card-body">
                                <div class="text-muted small">In Progress</div>
                                <div class="h4 mb-0" id="statInProgress">0</div>
                                <div class="small text-secondary">Owned by teammates</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="card shadow-sm h-100">
                            <div class="card-body">
                                <div class="text-muted small">Urgent & High</div>
                                <div class="h4 mb-0" id="statHigh">0</div>
                                <div class="small text-secondary">Needs fast response</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm mb-3">
                    <div class="card-body d-flex align-items-center">
                        <div class="p-3 bg-primary text-white rounded-3 me-3">
                            <i class="fa-solid fa-ticket fa-2x"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center mb-1">
                                <h2 class="h4 mb-0">Support Ticket Dashboard</h2>
                                <span id="ticketNotificationBadge" class="badge bg-danger ms-2">3</span>
                            </div>
                            <p class="mb-0 text-muted">Filter, triage, and respond to support tickets from one place.</p>
                        </div>
                        <?php if (!$canManageTickets) { ?>
                            <span class="badge text-bg-warning">Limited to your tickets</span>
                        <?php } else { ?>
                            <span class="badge text-bg-success">Staff Access</span>
                        <?php } ?>
                    </div>
                </div>

                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <div class="row g-3 align-items-end">
                            <div class="col-sm-6 col-lg-3">
                                <label for="filterStatus" class="form-label">Status</label>
                                <select class="form-select" id="filterStatus">
                                    <option value="">Any</option>
                                    <option value="open">Open</option>
                                    <option value="in_progress">In Progress</option>
                                    <option value="resolved">Resolved</option>
                                    <option value="closed">Closed</option>
                                </select>
                            </div>
                            <div class="col-sm-6 col-lg-3">
                                <label for="filterPriority" class="form-label">Priority</label>
                                <select class="form-select" id="filterPriority">
                                    <option value="">Any</option>
                                    <option value="urgent">Urgent</option>
                                    <option value="high">High</option>
                                    <option value="medium">Medium</option>
                                    <option value="low">Low</option>
                                </select>
                            </div>
                            <div class="col-sm-6 col-lg-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <label for="filterCategory" class="form-label mb-0">Category</label>
                                    <?php if ($canManageTickets) { ?>
                                        <button type="button" class="btn btn-link btn-sm text-decoration-none" data-bs-toggle="modal" data-bs-target="#categoryManagerModal">
                                            <i class="fa-solid fa-gear me-1"></i>Manage
                                        </button>
                                    <?php } ?>
                                </div>
                                <select class="form-select" id="filterCategory">
                                    <option value="">Any</option>
                                </select>
                            </div>
                            <div class="col-sm-6 col-lg-3">
                                <label for="filterAssignee" class="form-label">Assignee</label>
                                <select class="form-select" id="filterAssignee">
                                    <option value="">Any</option>
                                </select>
                            </div>
                            <div class="col-sm-6 col-lg-3">
                                <label for="filterGuild" class="form-label">Guild</label>
                                <select class="form-select" id="filterGuild">
                                    <option value="">All guilds</option>
                                    <option value="Adventurers">Adventurers Guild</option>
                                    <option value="Merchants">Merchants Guild</option>
                                    <option value="Craftsmen">Craftsmen's Guild</option>
                                    <option value="Stewards">Stewards Guild</option>
                                    <option value="Lich">Lich & Card Games</option>
                                </select>
                            </div>
                            <div class="col-sm-6 col-lg-3">
                                <label for="filterFrom" class="form-label">From</label>
                                <input type="date" class="form-control" id="filterFrom">
                            </div>
                            <div class="col-sm-6 col-lg-3">
                                <label for="filterTo" class="form-label">To</label>
                                <input type="date" class="form-control" id="filterTo">
                            </div>
                            <div class="col-sm-12 col-lg-6">
                                <label for="filterSearch" class="form-label">Search</label>
                                <input type="text" class="form-control" id="filterSearch" placeholder="Subject, requester, tags">
                            </div>
                        </div>
                        <div class="d-flex justify-content-end gap-2 mt-3">
                            <button class="btn btn-outline-secondary" id="resetFilters"><i class="fa-solid fa-rotate-left me-1"></i>Reset</button>
                            <button class="btn btn-primary" id="applyFilters"><i class="fa-solid fa-filter me-1"></i>Apply Filters</button>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                            <div class="d-flex align-items-center gap-2">
                                <input class="form-check-input" type="checkbox" id="selectAllTickets">
                                <label for="selectAllTickets" class="form-check-label">Select all</label>
                                <div class="dropdown ms-2">
                                    <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        Bulk Actions
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" data-bulk="assign" href="#">Assign to...</a></li>
                                        <li><a class="dropdown-item" data-bulk="status" href="#">Change status</a></li>
                                        <li><a class="dropdown-item" data-bulk="priority" href="#">Change priority</a></li>
                                        <li><a class="dropdown-item" data-bulk="note" href="#">Add internal note</a></li>
                                    </ul>
                                </div>
                            </div>
                            <div class="badge bg-light text-dark" id="listSummary">0 tickets</div>
                        </div>
                        <div class="table-responsive">
                            <table class="table align-middle" id="ticketTable">
                                <thead>
                                    <tr>
                                        <th scope="col"></th>
                                        <th scope="col" class="sortable" data-sort="ticket">Ticket <span class="sort-indicator"></span></th>
                                        <th scope="col" class="sortable" data-sort="status">Status <span class="sort-indicator"></span></th>
                                        <th scope="col" class="sortable" data-sort="priority">Priority <span class="sort-indicator"></span></th>
                                        <th scope="col" class="sortable" data-sort="category">Category <span class="sort-indicator"></span></th>
                                        <th scope="col" class="sortable" data-sort="assignee">Assignee <span class="sort-indicator"></span></th>
                                        <th scope="col" class="sortable" data-sort="guild">Guild <span class="sort-indicator"></span></th>
                                        <th scope="col" class="sortable" data-sort="updated">Updated <span class="sort-indicator"></span></th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                            <div class="d-flex flex-wrap justify-content-between align-items-center mt-3 gap-2">
                                <div class="d-flex align-items-center gap-2">
                                    <label for="pageSizeSelect" class="form-label mb-0 small">Rows per page</label>
                                    <select id="pageSizeSelect" class="form-select form-select-sm" style="width: auto; min-width: 90px;">
                                        <option value="5">5</option>
                                        <option value="10" selected>10</option>
                                        <option value="20">20</option>
                                        <option value="50">50</option>
                                    </select>
                                </div>
                                <nav>
                                    <ul class="pagination pagination-sm mb-0" id="ticketPagination"></ul>
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
        <?php require("../php-components/base-page-footer.php"); ?>
    </main>

    <div class="modal fade" id="categoryManagerModal" tabindex="-1" aria-labelledby="categoryManagerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="categoryManagerModalLabel">Manage Ticket Categories</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-3">Add, rename, or remove categories used for ticket submission and filtering.</p>
                    <?php if ($canManageTickets) { ?>
                        <form class="row g-2 align-items-end mb-3" id="categoryCreateForm">
                            <div class="col-sm-8">
                                <label for="categoryNameInput" class="form-label">Add a category</label>
                                <input type="text" class="form-control" id="categoryNameInput" placeholder="New category name">
                            </div>
                            <div class="col-sm-4 text-sm-end">
                                <button class="btn btn-primary w-100" type="submit"><i class="fa-solid fa-plus me-1"></i>Add Category</button>
                            </div>
                        </form>
                    <?php } ?>
                    <div id="categoryErrorAlert" class="alert alert-danger d-none" role="alert"></div>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th scope="col">Name</th>
                                    <th scope="col" class="d-none d-sm-table-cell">Slug</th>
                                    <th scope="col" class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="categoryManagerTable"></tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <?php require("../php-components/base-page-javascript.php"); ?>
    <script>
        const canManageTickets = <?= $canManageTickets ? 'true' : 'false'; ?>;
        const activeUser = <?= json_encode(isset($currentAccount->username) ? $currentAccount->username : ''); ?>;
        const prefillFilters = <?= json_encode($prefillFilters); ?>;
        const notificationEmail = <?= json_encode($notificationEmail); ?>;

        let tickets = [];
        let filteredTickets = [];
        let notificationCount = 0;
        let currentPage = 1;
        let pageSize = 10;
        let sortConfig = { column: 'priority', direction: 'desc' };
        let isLoading = false;
        let loadError = '';
        let categories = [];
        let isLoadingCategories = false;
        let categoryError = '';
        let editingCategoryId = '';
        let editingCategoryName = '';
        let assigneeOptions = [];
        let isLoadingAssignees = false;
        let assigneeLoadError = '';
        let pendingAssigneeValue = prefillFilters.assignee || '';

        const normalizeValue = (value) => (value || '').toString().trim().toLowerCase();
        const normalizeStatus = (value) => normalizeValue(value).replace('-', '_');
        const normalizeDateInput = (value, endOfDay = false) => {
            if (!value) return null;
            const date = new Date(value);
            if (Number.isNaN(date.getTime())) return null;
            if (endOfDay) date.setHours(23, 59, 59, 999);
            return date;
        };

        const buildDateTimeElement = (value, rawValue = '') => {
            const date = value instanceof Date ? value : normalizeDateInput(value);
            if (!date) return '<span class="text-muted">--</span>';
            const basic = date.toLocaleString(undefined, { month: 'short', day: 'numeric', year: 'numeric' });
            const detailed = date.toUTCString();
            const isoValue = date.toISOString();
            const dbValue = rawValue || isoValue;

            return `<span class="date" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="${detailed} UTC" data-datetime-utc="${isoValue}" data-db-value="${dbValue}">${basic}</span>`;
        };

        function getFilterValues() {
            return {
                status: document.getElementById('filterStatus').value,
                priority: document.getElementById('filterPriority').value,
                category: document.getElementById('filterCategory').value,
                assignee: normalizeValue(document.getElementById('filterAssignee').value),
                guild: document.getElementById('filterGuild').value,
                from: document.getElementById('filterFrom').value,
                to: document.getElementById('filterTo').value,
                search: document.getElementById('filterSearch').value,
            };
        }

        async function fetchCategories() {
            isLoadingCategories = true;
            categoryError = '';
            renderCategoryOptions();

            try {
                const response = await fetch('<?= Version::urlBetaPrefix(); ?>/api/v1/tickets/categories/list.php', {
                    method: 'POST',
                    credentials: 'same-origin'
                });

                if (!response.ok) {
                    throw new Error(`Request failed with status ${response.status}`);
                }

                const result = await response.json();
                if (result.success && Array.isArray(result.data)) {
                    categories = result.data;
                } else {
                    categories = [];
                    categoryError = result.message || 'Unable to load categories.';
                }
            } catch (error) {
                console.error('Failed to load categories', error);
                categories = [];
                categoryError = 'Failed to load categories. Please try again.';
            }

            isLoadingCategories = false;
            renderCategoryOptions();
            renderCategoryManagement();
        }

        async function fetchAssignees() {
            isLoadingAssignees = true;
            assigneeLoadError = '';
            renderAssigneeOptions();

            try {
                const response = await fetch('<?= Version::urlBetaPrefix(); ?>/api/v1/tickets/assignees.php', {
                    method: 'POST',
                    credentials: 'same-origin'
                });

                if (!response.ok) {
                    throw new Error(`Request failed with status ${response.status}`);
                }

                const result = await response.json();
                if (result.success && Array.isArray(result.data)) {
                    assigneeOptions = result.data;
                } else {
                    assigneeOptions = [];
                    assigneeLoadError = result.message || 'Unable to load assignees.';
                }
            } catch (error) {
                console.error('Failed to load assignees', error);
                assigneeOptions = [];
                assigneeLoadError = 'Failed to load assignees. Please try again.';
            }

            isLoadingAssignees = false;
            renderAssigneeOptions();
        }

        function renderCategoryOptions() {
            const select = document.getElementById('filterCategory');
            if (!select) return;

            const currentValue = select.value;
            select.innerHTML = '';

            const anyOption = document.createElement('option');
            anyOption.value = '';
            anyOption.textContent = 'Any';
            select.appendChild(anyOption);

            if (isLoadingCategories) {
                select.disabled = true;
                const loadingOption = document.createElement('option');
                loadingOption.disabled = true;
                loadingOption.textContent = 'Loading categories...';
                select.appendChild(loadingOption);
                return;
            }

            select.disabled = false;

            if (categoryError) {
                const errorOption = document.createElement('option');
                errorOption.disabled = true;
                errorOption.textContent = categoryError;
                select.appendChild(errorOption);
                select.value = '';
                return;
            }

            if (categories.length === 0) {
                const emptyOption = document.createElement('option');
                emptyOption.disabled = true;
                emptyOption.textContent = 'No categories found';
                select.appendChild(emptyOption);
                select.value = '';
                return;
            }

            categories.forEach(category => {
                const opt = document.createElement('option');
                opt.value = category.slug;
                opt.textContent = category.name;
                select.appendChild(opt);
            });

            const desiredValue = currentValue;
            const hasDesiredOption = Array.from(select.options).some(opt => opt.value === desiredValue);
            select.value = hasDesiredOption ? desiredValue : '';
        }

        function renderCategoryManagement() {
            const tableBody = document.getElementById('categoryManagerTable');
            const errorAlert = document.getElementById('categoryErrorAlert');
            if (!tableBody) return;

            if (errorAlert) {
                if (categoryError) {
                    errorAlert.textContent = categoryError;
                    errorAlert.classList.remove('d-none');
                } else {
                    errorAlert.classList.add('d-none');
                    errorAlert.textContent = '';
                }
            }

            tableBody.innerHTML = '';

            if (editingCategoryId) {
                const editingCategory = categories.find(cat => `${cat.ctime || ''}-${cat.crand || ''}` === editingCategoryId);
                if (!editingCategory) {
                    editingCategoryId = '';
                    editingCategoryName = '';
                } else if (editingCategoryName === '') {
                    editingCategoryName = editingCategory.name || '';
                }
            }

            if (isLoadingCategories) {
                tableBody.innerHTML = '<tr><td colspan="3" class="py-3 text-center text-muted">Loading categories...</td></tr>';
                return;
            }

            if (categoryError) {
                tableBody.innerHTML = '<tr><td colspan="3" class="py-3 text-danger">There was an issue loading categories.</td></tr>';
                return;
            }

            if (categories.length === 0) {
                tableBody.innerHTML = '<tr><td colspan="3" class="py-3 text-center text-muted">No categories yet. Add your first one to get started.</td></tr>';
                return;
            }

            categories.forEach(category => {
                const row = document.createElement('tr');
                const categoryId = `${category.ctime || ''}-${category.crand || ''}`;
                const isEditing = editingCategoryId === categoryId;

                const nameCell = document.createElement('td');
                if (isEditing) {
                    const input = document.createElement('input');
                    input.type = 'text';
                    input.className = 'form-control form-control-sm';
                    input.value = editingCategoryName;
                    input.setAttribute('data-edit-id', categoryId);
                    nameCell.appendChild(input);

                    const helper = document.createElement('div');
                    helper.className = 'form-text';
                    helper.textContent = `Slug: ${category.slug}`;
                    nameCell.appendChild(helper);
                } else {
                    nameCell.innerHTML = `<div class="fw-semibold">${category.name}</div><div class="text-muted small">Slug: ${category.slug}</div>`;
                }

                const slugCell = document.createElement('td');
                slugCell.className = 'd-none d-sm-table-cell';
                slugCell.textContent = category.slug;

                const actionsCell = document.createElement('td');
                actionsCell.className = 'text-end';

                if (canManageTickets) {
                    if (isEditing) {
                        const saveBtn = document.createElement('button');
                        saveBtn.type = 'button';
                        saveBtn.className = 'btn btn-primary btn-sm me-2';
                        saveBtn.setAttribute('data-action', 'save');
                        saveBtn.setAttribute('data-category-ctime', category.ctime || '');
                        saveBtn.setAttribute('data-category-crand', category.crand || '');
                        saveBtn.innerHTML = '<i class="fa-solid fa-floppy-disk me-1"></i>Save';
                        actionsCell.appendChild(saveBtn);

                        const cancelBtn = document.createElement('button');
                        cancelBtn.type = 'button';
                        cancelBtn.className = 'btn btn-outline-secondary btn-sm me-2';
                        cancelBtn.setAttribute('data-action', 'cancel');
                        cancelBtn.innerHTML = 'Cancel';
                        actionsCell.appendChild(cancelBtn);
                    } else {
                        const editBtn = document.createElement('button');
                        editBtn.type = 'button';
                        editBtn.className = 'btn btn-outline-primary btn-sm me-2';
                        editBtn.setAttribute('data-action', 'edit');
                        editBtn.setAttribute('data-category-ctime', category.ctime || '');
                        editBtn.setAttribute('data-category-crand', category.crand || '');
                        editBtn.setAttribute('data-category-name', category.name || '');
                        editBtn.innerHTML = '<i class="fa-solid fa-pen me-1"></i>Rename';
                        actionsCell.appendChild(editBtn);
                    }

                    const deleteBtn = document.createElement('button');
                    deleteBtn.type = 'button';
                    deleteBtn.className = 'btn btn-outline-danger btn-sm';
                    deleteBtn.setAttribute('data-action', 'delete');
                    deleteBtn.setAttribute('data-category-ctime', category.ctime || '');
                    deleteBtn.setAttribute('data-category-crand', category.crand || '');
                    deleteBtn.setAttribute('data-category-name', category.name || '');
                    deleteBtn.innerHTML = '<i class="fa-solid fa-trash"></i>';
                    actionsCell.appendChild(deleteBtn);
                }

                row.appendChild(nameCell);
                row.appendChild(slugCell);
                row.appendChild(actionsCell);
                tableBody.appendChild(row);
            });
        }

        async function createCategory(name) {
            if (!canManageTickets || name.trim() === '') return;
            categoryError = '';
            renderCategoryManagement();

            try {
                const payload = new FormData();
                payload.append('name', name.trim());

                const response = await fetch('<?= Version::urlBetaPrefix(); ?>/api/v1/tickets/categories/create.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: payload
                });

                const result = await response.json();
                if (!result.success) {
                    categoryError = result.message || 'Unable to create category.';
                }
            } catch (error) {
                console.error('Failed to create category', error);
                categoryError = 'Failed to create category. Please try again.';
            }

            const input = document.getElementById('categoryNameInput');
            if (input) {
                input.value = '';
            }

            await fetchCategories();
        }

        async function renameCategory(ctime, crand, name) {
            if (!canManageTickets || !ctime || !crand || name.trim() === '') return;
            categoryError = '';
            renderCategoryManagement();

            try {
                const payload = new FormData();
                payload.append('ctime', ctime);
                payload.append('crand', String(crand));
                payload.append('name', name.trim());

                const response = await fetch('<?= Version::urlBetaPrefix(); ?>/api/v1/tickets/categories/update.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: payload
                });

                const result = await response.json();
                if (!result.success) {
                    categoryError = result.message || 'Unable to update category.';
                }
            } catch (error) {
                console.error('Failed to update category', error);
                categoryError = 'Failed to update category. Please try again.';
            }

            editingCategoryId = '';
            editingCategoryName = '';
            await fetchCategories();
        }

        async function deleteCategory(ctime, crand) {
            if (!canManageTickets || !ctime || !crand) return;
            categoryError = '';
            if (editingCategoryId === `${ctime}-${crand}`) {
                editingCategoryId = '';
                editingCategoryName = '';
            }
            renderCategoryManagement();

            try {
                const payload = new FormData();
                payload.append('ctime', ctime);
                payload.append('crand', String(crand));

                const response = await fetch('<?= Version::urlBetaPrefix(); ?>/api/v1/tickets/categories/delete.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: payload
                });

                const result = await response.json();
                if (!result.success) {
                    categoryError = result.message || 'Unable to delete category.';
                }
            } catch (error) {
                console.error('Failed to delete category', error);
                categoryError = 'Failed to delete category. Please try again.';
            }

            await fetchCategories();
        }

        function renderAssigneeOptions() {
            const select = document.getElementById('filterAssignee');
            const currentValue = select.value;
            select.innerHTML = '';

            const anyOption = document.createElement('option');
            anyOption.value = '';
            anyOption.textContent = 'Any';
            select.appendChild(anyOption);

            if (isLoadingAssignees) {
                select.disabled = true;
                const loadingOption = document.createElement('option');
                loadingOption.disabled = true;
                loadingOption.textContent = 'Loading assignees...';
                select.appendChild(loadingOption);
                return;
            }

            select.disabled = false;

            if (assigneeLoadError) {
                const errorOption = document.createElement('option');
                errorOption.disabled = true;
                errorOption.textContent = assigneeLoadError;
                select.appendChild(errorOption);
                select.value = '';
                return;
            }

            if (assigneeOptions.length === 0) {
                const emptyOption = document.createElement('option');
                emptyOption.disabled = true;
                emptyOption.textContent = 'No assignees found';
                select.appendChild(emptyOption);
                select.value = '';
                return;
            }

            assigneeOptions.forEach(option => {
                const opt = document.createElement('option');
                const username = option.username || 'Unknown user';
                opt.value = username.toLowerCase();
                opt.textContent = username;
                select.appendChild(opt);
            });

            const desiredValue = (pendingAssigneeValue || currentValue).toLowerCase();
            const hasDesiredOption = Array.from(select.options).some(opt => opt.value === desiredValue);
            select.value = hasDesiredOption ? desiredValue : '';
            pendingAssigneeValue = hasDesiredOption ? '' : pendingAssigneeValue;
        }

        async function fetchTickets(filters = {}) {
            isLoading = true;
            loadError = '';
            renderTickets();

            const payload = new FormData();
            const normalizedStatus = (filters.status || '').replace('-', '_');
            if (normalizedStatus) payload.append('status', normalizedStatus);
            if (filters.priority) payload.append('priority', filters.priority);
            if (filters.category) payload.append('category', filters.category);
            if (filters.assignee) payload.append('assignee', filters.assignee);
            if (filters.guild) payload.append('guild', filters.guild);
            if (filters.from) payload.append('from', filters.from);
            if (filters.to) payload.append('to', filters.to);
            if (filters.search) payload.append('search', filters.search);

            try {
                const response = await fetch('<?= Version::urlBetaPrefix(); ?>/api/v1/tickets/list.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: payload
                });

                if (!response.ok) {
                    throw new Error(`Request failed with status ${response.status}`);
                }

                        const result = await response.json();
                        if (result.success && Array.isArray(result.data)) {
                            tickets = result.data.map((ticket) => {
                                const assignees = Array.isArray(ticket.assignees)
                                    ? ticket.assignees.filter(name => !!name)
                                    : [];
                                const primaryAssignee = (ticket.assignee || assignees[0] || '').trim();
                                const priorityLabel = (ticket.priority || '').toString();

                                return {
                                    ...ticket,
                                    assignees,
                                    assignee: primaryAssignee,
                                    id: `${ticket.ctime}-${ticket.crand}`,
                                    requester: ticket.createdByUsername || 'Unknown user',
                                    updated: ticket.updatedAt || '',
                                    category: ticket.category || '',
                                    guild: ticket.guild || '',
                                    status: ticket.status || '',
                                    priority: priorityLabel,
                                    normalizedStatus: normalizeStatus(ticket.status),
                                    normalizedPriority: normalizeValue(priorityLabel),
                                    normalizedAssignee: normalizeValue(primaryAssignee),
                                    normalizedAssignees: assignees.map(normalizeValue).filter(Boolean),
                                    normalizedCategory: normalizeValue(ticket.category),
                                    normalizedGuild: normalizeValue(ticket.guild),
                                    updatedDate: normalizeDateInput(ticket.updatedAt || ticket.updated),
                                    comments: Array.isArray(ticket.comments) ? ticket.comments : [],
                                };
                            });
                } else {
                    tickets = [];
                    loadError = result.message || 'Unable to load tickets.';
                }
            } catch (error) {
                console.error('Failed to load tickets', error);
                tickets = [];
                loadError = 'Failed to load tickets. Please try again.';
            }

            isLoading = false;
            applyFiltersAndRender();
            syncNotificationCount();
        }

        function renderStats() {
            const total = filteredTickets.length;
            const openCount = filteredTickets.filter(t => t.status === 'open').length;
            const inProgressCount = filteredTickets.filter(t => t.status === 'in_progress').length;
            const highCount = filteredTickets.filter(t => t.priority === 'urgent' || t.priority === 'high').length;
            const lastUpdated = filteredTickets.reduce((latest, ticket) => {
                const ticketTime = ticket.updatedDate ? ticket.updatedDate.getTime() : 0;
                if (ticketTime > latest.time) {
                    return { time: ticketTime, raw: ticket.updated || '' };
                }
                return latest;
            }, { time: 0, raw: '' });

            document.getElementById('statTotal').textContent = total;
            document.getElementById('statOpen').textContent = openCount;
            document.getElementById('statInProgress').textContent = inProgressCount;
            document.getElementById('statHigh').textContent = highCount;
            const statLastUpdated = document.getElementById('statLastUpdated');
            if (lastUpdated.raw) {
                statLastUpdated.innerHTML = `Updated ${buildDateTimeElement(lastUpdated.time, lastUpdated.raw)}`;
            } else {
                statLastUpdated.textContent = 'Updated --';
            }
        }

        function applyPrefill() {
            document.getElementById('filterStatus').value = (prefillFilters.status || '').replace('-', '_');
            document.getElementById('filterPriority').value = prefillFilters.priority || '';
            document.getElementById('filterCategory').value = prefillFilters.category || '';
            pendingAssigneeValue = prefillFilters.assignee || '';
            document.getElementById('filterGuild').value = prefillFilters.guild || '';
            document.getElementById('filterFrom').value = prefillFilters.dateFrom || '';
            document.getElementById('filterTo').value = prefillFilters.dateTo || '';
        }

        function ticketMatchesFilters(ticket) {
            const { status, priority, assignee, category, guild, search, from, to } = getFilterValues();
            const normalizedStatus = normalizeStatus(status);
            const normalizedPriority = normalizeValue(priority);
            const normalizedCategory = normalizeValue(category);
            const normalizedGuild = normalizeValue(guild);
            const searchTerm = normalizeValue(search);
            const fromDate = normalizeDateInput(from);
            const toDate = normalizeDateInput(to, true);
            const ticketDate = ticket.updatedDate;

            if (normalizedStatus && ticket.normalizedStatus !== normalizedStatus) return false;
            if (normalizedPriority && ticket.normalizedPriority !== normalizedPriority) return false;
            if (normalizedCategory && ticket.normalizedCategory !== normalizedCategory) return false;
            if (normalizedGuild && ticket.normalizedGuild !== normalizedGuild) return false;
            if (assignee) {
                const matchesAssignee = (ticket.normalizedAssignees || []).includes(assignee) || ticket.normalizedAssignee === assignee;
                if (!matchesAssignee) return false;
            }

            if (fromDate && (!ticketDate || ticketDate < fromDate)) return false;
            if (toDate && (!ticketDate || ticketDate > toDate)) return false;

            if (searchTerm) {
                const haystack = `${ticket.subject || ''} ${ticket.description || ''} ${ticket.requester || ''} ${ticket.id || ''}`.toLowerCase();
                if (!haystack.includes(searchTerm)) return false;
            }
            if (!canManageTickets && activeUser && ticket.requester !== activeUser && ticket.assignee !== activeUser) return false;
            return true;
        }

        function sortTickets(ticketsToSort) {
            const sortedTickets = [...ticketsToSort];
            const { column, direction } = sortConfig;
            const directionMultiplier = direction === 'asc' ? 1 : -1;

            const getSortValue = (ticket) => {
                switch (column) {
                    case 'ticket':
                        return normalizeValue(ticket.subject);
                    case 'status':
                        return ticket.normalizedStatus;
                    case 'priority':
                        return ticket.normalizedPriority;
                    case 'category':
                        return ticket.normalizedCategory;
                    case 'assignee':
                        return ticket.normalizedAssignee;
                    case 'guild':
                        return ticket.normalizedGuild;
                    case 'updated':
                        return ticket.updatedDate ? ticket.updatedDate.getTime() : 0;
                    default:
                        return '';
                }
            };

            sortedTickets.sort((a, b) => {
                const valueA = getSortValue(a);
                const valueB = getSortValue(b);

                if (valueA === valueB) return 0;
                if (valueA === undefined || valueA === null) return 1 * directionMultiplier;
                if (valueB === undefined || valueB === null) return -1 * directionMultiplier;

                if (typeof valueA === 'number' && typeof valueB === 'number') {
                    return (valueA - valueB) * directionMultiplier;
                }

                return valueA > valueB ? directionMultiplier : -directionMultiplier;
            });

            return sortedTickets;
        }

        function renderPagination(totalPages) {
            const pagination = document.getElementById('ticketPagination');
            pagination.innerHTML = '';

            if (totalPages <= 1) {
                pagination.innerHTML = '<li class="page-item disabled"><span class="page-link">1</span></li>';
                return;
            }

            const addPageItem = (page, label, disabled = false, active = false) => {
                const li = document.createElement('li');
                li.className = `page-item ${disabled ? 'disabled' : ''} ${active ? 'active' : ''}`;
                const link = document.createElement('a');
                link.className = 'page-link';
                link.href = '#';
                link.textContent = label;
                link.addEventListener('click', (event) => {
                    event.preventDefault();
                    if (disabled || page === currentPage) return;
                    currentPage = page;
                    renderTickets();
                });
                li.appendChild(link);
                pagination.appendChild(li);
            };

            addPageItem(Math.max(1, currentPage - 1), '«', currentPage === 1);

            for (let page = 1; page <= totalPages; page++) {
                addPageItem(page, page, false, currentPage === page);
            }

            addPageItem(Math.min(totalPages, currentPage + 1), '»', currentPage === totalPages);
        }

        function updateSortIndicators() {
            document.querySelectorAll('#ticketTable th[data-sort]').forEach((th) => {
                const indicator = th.querySelector('.sort-indicator');
                if (!indicator) return;
                if (th.dataset.sort === sortConfig.column) {
                    indicator.textContent = sortConfig.direction === 'asc' ? '▲' : '▼';
                } else {
                    indicator.textContent = '';
                }
            });
        }

        function renderTickets() {
            const tbody = document.querySelector('#ticketTable tbody');
            tbody.innerHTML = '';

            if (isLoading) {
                tbody.innerHTML = '<tr><td colspan="8" class="text-center py-4">Loading tickets...</td></tr>';
                document.getElementById('listSummary').textContent = 'Loading...';
                renderStats();
                updateSortIndicators();
                return;
            }

            if (loadError) {
                tbody.innerHTML = `<tr><td colspan="8" class="text-center text-danger py-4">${loadError}</td></tr>`;
                document.getElementById('listSummary').textContent = '0 tickets';
                renderStats();
                updateSortIndicators();
                return;
            }

            if (filteredTickets.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" class="text-center py-4">No tickets match your filters yet.</td></tr>';
                document.getElementById('listSummary').textContent = '0 tickets';
                renderStats();
                updateSortIndicators();
                return;
            }

            const sortedTickets = sortTickets(filteredTickets);
            const totalPages = Math.max(1, Math.ceil(sortedTickets.length / pageSize));
            currentPage = Math.min(currentPage, totalPages);
            const startIndex = (currentPage - 1) * pageSize;
            const endIndex = startIndex + pageSize;
            const pageTickets = sortedTickets.slice(startIndex, endIndex);

            pageTickets.forEach(ticket => {
                const row = document.createElement('tr');
                const detailLink = `<?= Version::urlBetaPrefix(); ?>/tickets/view.php?ctime=${encodeURIComponent(ticket.ctime)}&crand=${encodeURIComponent(ticket.crand)}`;
                const assigneeDisplay = ticket.assignees && ticket.assignees.length > 0
                    ? ticket.assignees.join(', ')
                    : 'Unassigned';
                row.innerHTML = `
                    <td><input type="checkbox" class="form-check-input ticket-checkbox" data-id="${ticket.id}"></td>
                    <td class="ticket-subject position-relative" data-id="${ticket.id}">
                        <a class="stretched-link text-decoration-none text-dark" href="${detailLink}">
                            <div class="fw-semibold">${ticket.subject}</div>
                            <div class="small text-muted">${ticket.id} • ${ticket.requester}</div>
                        </a>
                    </td>
                    <td><span class="badge status-pill status-${ticket.status}">${ticket.status.replace(/[_-]/g, ' ')}</span></td>
                    <td><span class="badge priority-pill priority-${ticket.priority}">${ticket.priority}</span></td>
                    <td>${ticket.category || 'Uncategorized'}</td>
                    <td>${assigneeDisplay}</td>
                    <td>${ticket.guild}</td>
                    <td>${buildDateTimeElement(ticket.updatedDate || ticket.updated, ticket.updated)}</td>
                `;
                tbody.appendChild(row);
            });

            const showingStart = startIndex + 1;
            const showingEnd = Math.min(filteredTickets.length, endIndex);
            const summaryText = `${showingStart}-${showingEnd} of ${filteredTickets.length} ticket${filteredTickets.length === 1 ? '' : 's'}`;
            document.getElementById('listSummary').textContent = summaryText;
            renderPagination(totalPages);
            bindTicketSelection();
            renderStats();
            updateSortIndicators();
        }

        function bindTicketSelection() {
            document.querySelectorAll('.ticket-subject').forEach(cell => {
                cell.addEventListener('click', (event) => {
                    const target = event.target.closest('a');
                    if (target) {
                        window.location.href = target.href;
                    }
                });
            });
        }

        function applyFiltersAndRender(refetch = false, resetPage = false) {
            if (refetch) {
                currentPage = 1;
                fetchTickets(getFilterValues());
                return;
            }
            filteredTickets = tickets.filter(ticketMatchesFilters);
            if (resetPage) {
                currentPage = 1;
            }
            renderTickets();
        }

        function updateBadge() {
            document.getElementById('ticketNotificationBadge').textContent = notificationCount;
        }

        function syncNotificationCount() {
            notificationCount = tickets.filter(t => t.status === 'open' || t.status === 'in_progress').length;
            updateBadge();
        }

        function queueNotification(ticket, message) {
            notificationCount += 1;
            updateBadge();
            sendEmailNotification(ticket, message);
        }

        function sendEmailNotification(ticket, message) {
            const payload = new FormData();
            payload.append('ticketId', ticket.id);
            payload.append('email', notificationEmail);
            payload.append('message', message);
            fetch('<?= Version::urlBetaPrefix(); ?>/api/v1/support/sendTicketNotification.php', {
                method: 'POST',
                body: payload
            }).catch(() => console.warn('Notification endpoint unreachable'));    
        }

        document.getElementById('applyFilters').addEventListener('click', () => applyFiltersAndRender(true));
        document.getElementById('resetFilters').addEventListener('click', () => {
            ['filterStatus','filterPriority','filterCategory','filterAssignee','filterGuild','filterFrom','filterTo','filterSearch'].forEach(id => {
                document.getElementById(id).value = '';
            });
            applyFiltersAndRender(true);
        });

        document.querySelectorAll('#ticketTable th[data-sort]').forEach((th) => {
            th.addEventListener('click', () => {
                const column = th.dataset.sort;
                if (sortConfig.column === column) {
                    sortConfig.direction = sortConfig.direction === 'asc' ? 'desc' : 'asc';
                } else {
                    sortConfig = { column, direction: column === 'updated' ? 'desc' : 'asc' };
                }
                renderTickets();
            });
        });

        document.getElementById('pageSizeSelect').addEventListener('change', (event) => {
            const newSize = parseInt(event.target.value, 10);
            pageSize = Number.isNaN(newSize) ? 10 : newSize;
            currentPage = 1;
            renderTickets();
        });

        const categoryForm = document.getElementById('categoryCreateForm');
        if (categoryForm) {
            categoryForm.addEventListener('submit', (event) => {
                event.preventDefault();
                const nameInput = document.getElementById('categoryNameInput');
                const name = nameInput ? nameInput.value : '';
                if (name.trim() !== '') {
                    createCategory(name);
                }
            });
        }

        const categoryTable = document.getElementById('categoryManagerTable');
        if (categoryTable) {
            categoryTable.addEventListener('click', (event) => {
                const button = event.target.closest('button[data-action]');
                if (!button) return;

                const action = button.getAttribute('data-action');
                const ctime = button.getAttribute('data-category-ctime') || '';
                const crand = parseInt(button.getAttribute('data-category-crand') || '0', 10);

                if (action === 'delete') {
                    const name = button.getAttribute('data-category-name') || 'this category';
                    if (!Number.isNaN(crand) && crand > 0 && ctime && confirm(`Delete ${name}? This cannot be undone.`)) {
                        deleteCategory(ctime, crand);
                    }
                    return;
                }

                if (action === 'edit') {
                    editingCategoryId = `${ctime}-${crand}`;
                    editingCategoryName = button.getAttribute('data-category-name') || '';
                    renderCategoryManagement();
                    return;
                }

                if (action === 'cancel') {
                    editingCategoryId = '';
                    editingCategoryName = '';
                    renderCategoryManagement();
                    return;
                }

                if (action === 'save' && !Number.isNaN(crand) && crand > 0 && ctime) {
                    renameCategory(ctime, crand, editingCategoryName || '');
                }
            });

            categoryTable.addEventListener('input', (event) => {
                const input = event.target.closest('input[data-edit-id]');
                if (!input) return;
                editingCategoryName = input.value;
            });
        }

        const categoryManagerModal = document.getElementById('categoryManagerModal');
        if (categoryManagerModal) {
            categoryManagerModal.addEventListener('show.bs.modal', () => {
                renderCategoryManagement();
            });

            categoryManagerModal.addEventListener('hidden.bs.modal', () => {
                editingCategoryId = '';
                editingCategoryName = '';
                renderCategoryManagement();
            });
        }

        document.getElementById('selectAllTickets').addEventListener('change', (e) => {
            document.querySelectorAll('.ticket-checkbox').forEach(cb => cb.checked = e.target.checked);
        });

        document.querySelectorAll('[data-bulk]').forEach(action => {
            action.addEventListener('click', (event) => {
                event.preventDefault();
                const selected = Array.from(document.querySelectorAll('.ticket-checkbox:checked')).map(cb => cb.dataset.id);
                if (selected.length === 0) return;
                const type = action.getAttribute('data-bulk');
                const value = prompt(`Set ${type} for ${selected.length} ticket(s):`);
                if (!value) return;
                selected.forEach(id => {
                    const ticket = tickets.find(t => t.id === id);
                    if (!ticket) return;
                    if (type === 'assign') ticket.assignee = value;
                    if (type === 'status') ticket.status = value;
                    if (type === 'priority') ticket.priority = value;
                    if (type === 'note') {
                        ticket.comments.push({ author: activeUser || 'System', type: 'internal', message: value, time: new Date().toLocaleTimeString() });
                    }
                });
                applyFiltersAndRender();
            });
        });


        applyPrefill();
        fetchCategories();
        fetchAssignees();
        fetchTickets(getFilterValues());
    </script>

    <style>
        .status-open { background: #e8f5ff; color: #0b6cbf; }
        .status-in_progress { background: #fff4e5; color: #c46a00; }
        .status-waiting { background: #f3e8ff; color: #6f42c1; }
        .status-resolved { background: #e9f9ee; color: #0f9d58; }
        .status-closed { background: #f1f3f5; color: #495057; }
        .priority-urgent { background: #ffebee; color: #c62828; }
        .priority-high { background: #fff3cd; color: #a66f00; }
        .priority-medium { background: #e3f2fd; color: #1565c0; }
        .priority-low { background: #eef2ff; color: #4650dd; }
        .comment-thread .comment-internal { border-left: 4px solid #6c757d; background: #f8f9fa; }
        .comment-thread .comment { border: 1px solid #e9ecef; }
        #ticketPagination .page-link { min-width: 2.25rem; text-align: center; }
        th.sortable { cursor: pointer; user-select: none; }
        th .sort-indicator { font-size: 0.75rem; }
    </style>
</body>

</html>
