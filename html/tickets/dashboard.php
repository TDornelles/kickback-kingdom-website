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
                                <label for="filterCategory" class="form-label">Category</label>
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
                        <div class="mt-4 border-top pt-3" id="categoryManagement">
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                                <div>
                                    <div class="fw-semibold">Ticket Categories</div>
                                    <div class="text-muted small">Manage reusable categories for filtering and ticket creation.</div>
                                </div>
                                <?php if ($canManageTickets) { ?>
                                    <form class="d-flex gap-2" id="categoryCreateForm">
                                        <input type="text" class="form-control" id="categoryNameInput" placeholder="New category name">
                                        <button class="btn btn-outline-primary" type="submit"><i class="fa-solid fa-plus me-1"></i>Add</button>
                                    </form>
                                <?php } ?>
                            </div>
                            <div class="text-danger small mt-2" id="categoryError"></div>
                            <div id="categoryList" class="d-flex flex-wrap gap-2 mt-2"></div>
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
                                        <th scope="col">Ticket</th>
                                        <th scope="col">Status</th>
                                        <th scope="col">Priority</th>
                                        <th scope="col">Category</th>
                                        <th scope="col">Assignee</th>
                                        <th scope="col">Guild</th>
                                        <th scope="col">Updated</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </div>
        <?php require("../php-components/base-page-footer.php"); ?>
    </main>

    <?php require("../php-components/base-page-javascript.php"); ?>
    <script>
        const canManageTickets = <?= $canManageTickets ? 'true' : 'false'; ?>;
        const activeUser = <?= json_encode(isset($currentAccount->username) ? $currentAccount->username : ''); ?>;
        const prefillFilters = <?= json_encode($prefillFilters); ?>;
        const notificationEmail = <?= json_encode($notificationEmail); ?>;

        let tickets = [];
        let filteredTickets = [];
        let notificationCount = 0;
        let isLoading = false;
        let loadError = '';
        let categories = [];
        let isLoadingCategories = false;
        let categoryError = '';
        let assigneeOptions = [];
        let isLoadingAssignees = false;
        let assigneeLoadError = '';
        let pendingAssigneeValue = prefillFilters.assignee || '';

        function getFilterValues() {
            return {
                status: document.getElementById('filterStatus').value,
                priority: document.getElementById('filterPriority').value,
                category: document.getElementById('filterCategory').value,
                assignee: document.getElementById('filterAssignee').value.toLowerCase(),
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
            const list = document.getElementById('categoryList');
            const errorEl = document.getElementById('categoryError');
            if (!list || !errorEl) return;

            list.innerHTML = '';
            errorEl.textContent = categoryError;

            if (isLoadingCategories) {
                list.innerHTML = '<span class="text-muted">Loading categories...</span>';
                return;
            }

            if (!categoryError && categories.length === 0) {
                list.innerHTML = '<span class="text-muted">No categories yet.</span>';
                return;
            }

            categories.forEach(category => {
                const badge = document.createElement('div');
                badge.className = 'badge text-bg-light text-dark d-inline-flex align-items-center gap-2 py-2 px-3';
                badge.innerHTML = `<span>${category.name}</span>`;

                if (canManageTickets) {
                    const deleteBtn = document.createElement('button');
                    deleteBtn.type = 'button';
                    deleteBtn.className = 'btn btn-sm btn-outline-danger';
                    deleteBtn.setAttribute('data-category-ctime', category.ctime || '');
                    deleteBtn.setAttribute('data-category-crand', category.crand || '');
                    deleteBtn.innerHTML = '<i class="fa-solid fa-trash"></i>';
                    badge.appendChild(deleteBtn);
                }

                list.appendChild(badge);
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

        async function deleteCategory(ctime, crand) {
            if (!canManageTickets || !ctime || !crand) return;
            categoryError = '';
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
                opt.value = (option.username || '').toLowerCase();
                opt.textContent = option.username || `Account #${option.id}`;
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
                            tickets = result.data.map((ticket) => ({
                                ...ticket,
                                id: `${ticket.ctime}-${ticket.crand}`,
                                requester: ticket.createdByCrand ? `Account #${ticket.createdByCrand}` : 'Unknown',
                                updated: ticket.updatedAt || '',
                                assignee: ticket.assignee || '',
                                category: ticket.category || '',
                                guild: ticket.guild || '',
                                status: ticket.status || '',
                                priority: ticket.priority || '',
                                comments: Array.isArray(ticket.comments) ? ticket.comments : [],
                            }));
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
            const lastUpdated = filteredTickets.reduce((latest, ticket) => ticket.updated > latest ? ticket.updated : latest, '');

            document.getElementById('statTotal').textContent = total;
            document.getElementById('statOpen').textContent = openCount;
            document.getElementById('statInProgress').textContent = inProgressCount;
            document.getElementById('statHigh').textContent = highCount;
            document.getElementById('statLastUpdated').textContent = lastUpdated ? `Updated ${lastUpdated}` : 'Updated --';
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
            const normalizedStatus = status.replace('-', '_');
            const searchTerm = (search || '').toLowerCase();

            if (normalizedStatus && ticket.status !== normalizedStatus) return false;
            if (priority && ticket.priority !== priority) return false;
            if (category && (ticket.category || '') !== category) return false;
            if (guild && (ticket.guild || '') !== guild) return false;
            if (assignee && (ticket.assignee || '').toLowerCase() !== assignee) return false;
            if (from && ticket.updated < from) return false;
            if (to && ticket.updated > to) return false;
            if (searchTerm) {
                const haystack = `${ticket.subject || ''} ${ticket.description || ''} ${ticket.requester || ''} ${ticket.id || ''}`.toLowerCase();
                if (!haystack.includes(searchTerm)) return false;
            }
            if (!canManageTickets && activeUser && ticket.requester !== activeUser && ticket.assignee !== activeUser) return false;
            return true;
        }

        function renderTickets() {
            const tbody = document.querySelector('#ticketTable tbody');
            tbody.innerHTML = '';

            if (isLoading) {
                tbody.innerHTML = '<tr><td colspan="8" class="text-center py-4">Loading tickets...</td></tr>';
                document.getElementById('listSummary').textContent = 'Loading...';
                renderStats();
                return;
            }

            if (loadError) {
                tbody.innerHTML = `<tr><td colspan="8" class="text-center text-danger py-4">${loadError}</td></tr>`;
                document.getElementById('listSummary').textContent = '0 tickets';
                renderStats();
                return;
            }

            if (filteredTickets.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" class="text-center py-4">No tickets match your filters yet.</td></tr>';
                document.getElementById('listSummary').textContent = '0 tickets';
                renderStats();
                return;
            }
            filteredTickets.forEach(ticket => {
                const row = document.createElement('tr');
                const detailLink = `<?= Version::urlBetaPrefix(); ?>/tickets/view.php?ctime=${encodeURIComponent(ticket.ctime)}&crand=${encodeURIComponent(ticket.crand)}`;
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
                    <td>${ticket.assignee || 'Unassigned'}</td>
                    <td>${ticket.guild}</td>
                    <td>${ticket.updated}</td>
                `;
                tbody.appendChild(row);
            });
            document.getElementById('listSummary').textContent = `${filteredTickets.length} ticket${filteredTickets.length === 1 ? '' : 's'}`;
            bindTicketSelection();
            renderStats();
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

        function applyFiltersAndRender(refetch = false) {
            if (refetch) {
                fetchTickets(getFilterValues());
                return;
            }
            filteredTickets = tickets.filter(ticketMatchesFilters);
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

        const categoryListEl = document.getElementById('categoryList');
        if (categoryListEl) {
            categoryListEl.addEventListener('click', (event) => {
                const button = event.target.closest('button[data-category-ctime][data-category-crand]');
                if (!button) return;

                event.preventDefault();
                const ctime = button.getAttribute('data-category-ctime') || '';
                const crand = parseInt(button.getAttribute('data-category-crand') || '0', 10);
                if (!Number.isNaN(crand) && crand > 0 && ctime) {
                    deleteCategory(ctime, crand);
                }
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

        document.getElementById('notificationTest').addEventListener('click', () => {
            const ticket = filteredTickets[0] || tickets[0];
            if (ticket) {
                queueNotification(ticket, 'Test notification from dashboard');
            }
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
    </style>
</body>

</html>
