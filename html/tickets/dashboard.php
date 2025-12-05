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
            <div class="col-12 col-xl-9">
                <?php
                    $activePageName = "Ticket Dashboard";
                    require("../php-components/base-page-breadcrumbs.php");
                ?>

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

                <div class="alert alert-info mb-3">
                    <div class="d-flex align-items-center">
                        <i class="fa-solid fa-bell me-3"></i>
                        <div class="flex-grow-1">
                            <div class="fw-semibold">Notifications are on</div>
                            <div class="small text-muted">Email updates will be sent to <strong><?= htmlspecialchars($notificationEmail); ?></strong> and in-app badges will update as you work.</div>
                        </div>
                        <button class="btn btn-outline-primary" id="notificationTest">Send test alert</button>
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
                                    <option value="in-progress">In Progress</option>
                                    <option value="resolved">Resolved</option>
                                    <option value="waiting">Waiting on Customer</option>
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
                                <label for="filterAssignee" class="form-label">Assignee</label>
                                <input type="text" class="form-control" id="filterAssignee" placeholder="User or Team">
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
                                        <th scope="col">Ticket</th>
                                        <th scope="col">Status</th>
                                        <th scope="col">Priority</th>
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

                <div class="card shadow-sm mb-4" id="ticketDetail">
                    <div class="card-body">
                        <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-2">
                            <div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge text-bg-secondary" id="detailStatus">Open</span>
                                    <span class="badge text-bg-primary" id="detailPriority">Medium</span>
                                </div>
                                <h3 class="h5 mt-2 mb-1" id="detailSubject">Ticket subject</h3>
                                <div class="text-muted" id="detailMeta">Requester • Date</div>
                            </div>
                            <div class="d-flex flex-wrap gap-2">
                                <button class="btn btn-outline-primary" id="assignSelf"><i class="fa-solid fa-user-plus me-1"></i>Assign to me</button>
                                <button class="btn btn-outline-secondary" id="assignGuild"><i class="fa-solid fa-people-group me-1"></i>Assign to guild</button>
                                <button class="btn btn-outline-danger" id="escalatePriority"><i class="fa-solid fa-arrow-up me-1"></i>Escalate</button>
                            </div>
                        </div>
                        <p class="text-muted" id="detailDescription"></p>

                        <div class="row g-4 mt-1">
                            <div class="col-12 col-lg-7">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h4 class="h6 mb-0">Comments</h4>
                                    <div class="btn-group btn-group-sm" role="group" aria-label="comment visibility">
                                        <input type="radio" class="btn-check" name="commentVisibility" id="visibilityPublic" autocomplete="off" checked>
                                        <label class="btn btn-outline-primary" for="visibilityPublic"><i class="fa-regular fa-message me-1"></i>Public</label>
                                        <input type="radio" class="btn-check" name="commentVisibility" id="visibilityInternal" autocomplete="off">
                                        <label class="btn btn-outline-secondary" for="visibilityInternal"><i class="fa-solid fa-user-shield me-1"></i>Internal</label>
                                    </div>
                                </div>
                                <div id="commentThread" class="comment-thread"></div>
                                <div class="mt-3">
                                    <label for="commentInput" class="form-label">Add a comment</label>
                                    <textarea class="form-control" id="commentInput" rows="3" placeholder="Share an update or internal note"></textarea>
                                    <div class="d-flex justify-content-between align-items-center mt-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="1" id="notifyRequester" checked>
                                            <label class="form-check-label" for="notifyRequester">Notify requester by email</label>
                                        </div>
                                        <button class="btn btn-primary" id="addComment"><i class="fa-solid fa-paper-plane me-1"></i>Post update</button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-lg-5">
                                <div class="card border-0 bg-light mb-3">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <h4 class="h6 mb-0">Status & Assignment</h4>
                                            <span class="badge text-bg-light" id="detailTicketId"></span>
                                        </div>
                                        <div class="mb-2">
                                            <label for="detailStatusSelect" class="form-label">Status</label>
                                            <select id="detailStatusSelect" class="form-select form-select-sm">
                                                <option value="open">Open</option>
                                                <option value="in-progress">In Progress</option>
                                                <option value="waiting">Waiting on Customer</option>
                                                <option value="resolved">Resolved</option>
                                            </select>
                                        </div>
                                        <div class="mb-2">
                                            <label for="detailPrioritySelect" class="form-label">Priority</label>
                                            <select id="detailPrioritySelect" class="form-select form-select-sm">
                                                <option value="urgent">Urgent</option>
                                                <option value="high">High</option>
                                                <option value="medium">Medium</option>
                                                <option value="low">Low</option>
                                            </select>
                                        </div>
                                        <div class="mb-2">
                                            <label for="detailAssignee" class="form-label">Assignee</label>
                                            <input type="text" id="detailAssignee" class="form-control form-control-sm" placeholder="Enter username or guild">
                                        </div>
                                        <button class="btn btn-outline-primary w-100" id="saveTicketMeta"><i class="fa-solid fa-floppy-disk me-1"></i>Save</button>
                                    </div>
                                </div>
                                <div class="card border-0">
                                    <div class="card-body">
                                        <h4 class="h6">History</h4>
                                        <ul class="list-unstyled" id="historyList"></ul>
                                        <div class="mt-2">
                                            <label for="quickNote" class="form-label">Quick internal note</label>
                                            <textarea id="quickNote" class="form-control" rows="2"></textarea>
                                            <button class="btn btn-outline-secondary btn-sm mt-2" id="saveQuickNote"><i class="fa-solid fa-lock me-1"></i>Save internal note</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php require("../php-components/base-page-discord.php"); ?>
        </div>
        <?php require("../php-components/base-page-footer.php"); ?>
    </main>

    <?php require("../php-components/base-page-javascript.php"); ?>
    <script>
        const canManageTickets = <?= $canManageTickets ? 'true' : 'false'; ?>;
        const activeUser = <?= json_encode(isset($currentAccount->username) ? $currentAccount->username : ''); ?>;
        const prefillFilters = <?= json_encode($prefillFilters); ?>;
        const notificationEmail = <?= json_encode($notificationEmail); ?>;

        const tickets = [
            {
                id: 'TCK-1024',
                subject: 'Card deck sync is stuck',
                description: 'Players report that the new deck list is not updating in the lobby.',
                status: 'open',
                priority: 'high',
                assignee: 'Astra',
                guild: 'Lich',
                requester: 'Kara',
                updated: '2025-03-02',
                history: [
                    { time: '2025-03-02 09:15', entry: 'Ticket created by Kara' },
                    { time: '2025-03-02 10:00', entry: 'Assigned to Astra' }
                ],
                comments: [
                    { author: 'Kara', type: 'public', message: 'Deck is frozen after the last patch.', time: '09:20' },
                    { author: 'Astra', type: 'public', message: 'Investigating logs now.', time: '10:05' }
                ]
            },
            {
                id: 'TCK-2048',
                subject: 'Guild treasury export for March',
                description: 'Need CSV export for merchant ledgers before finance meeting.',
                status: 'in-progress',
                priority: 'medium',
                assignee: 'Ledger Team',
                guild: 'Merchants',
                requester: 'Finley',
                updated: '2025-03-01',
                history: [
                    { time: '2025-02-28 14:10', entry: 'Ticket created by Finley' },
                    { time: '2025-02-28 14:45', entry: 'Marked In Progress by Ledger Team' }
                ],
                comments: [
                    { author: 'Finley', type: 'public', message: 'Deadline is Friday.', time: '14:12' },
                    { author: 'Ledger Team', type: 'public', message: 'We can deliver by Thursday.', time: '14:50' }
                ]
            },
            {
                id: 'TCK-3001',
                subject: 'Tournament registration stuck in pending',
                description: 'Three entrants cannot complete payment flow for Emberwood event.',
                status: 'waiting',
                priority: 'urgent',
                assignee: '',
                guild: 'Adventurers',
                requester: 'QuestAdmin',
                updated: '2025-03-03',
                history: [
                    { time: '2025-03-03 08:00', entry: 'Ticket created by QuestAdmin' }
                ],
                comments: [
                    { author: 'QuestAdmin', type: 'public', message: 'Registrations stuck after redirect.', time: '08:02' }
                ]
            }
        ];

        let filteredTickets = [...tickets];
        let selectedTicketId = tickets[0]?.id || null;
        let notificationCount = 3;

        function applyPrefill() {
            document.getElementById('filterStatus').value = prefillFilters.status || '';
            document.getElementById('filterPriority').value = prefillFilters.priority || '';
            document.getElementById('filterAssignee').value = prefillFilters.assignee || '';
            document.getElementById('filterGuild').value = prefillFilters.guild || '';
            document.getElementById('filterFrom').value = prefillFilters.dateFrom || '';
            document.getElementById('filterTo').value = prefillFilters.dateTo || '';
        }

        function ticketMatchesFilters(ticket) {
            const status = document.getElementById('filterStatus').value;
            const priority = document.getElementById('filterPriority').value;
            const assignee = document.getElementById('filterAssignee').value.toLowerCase();
            const guild = document.getElementById('filterGuild').value;
            const search = document.getElementById('filterSearch').value.toLowerCase();
            const from = document.getElementById('filterFrom').value;
            const to = document.getElementById('filterTo').value;

            if (status && ticket.status !== status) return false;
            if (priority && ticket.priority !== priority) return false;
            if (guild && ticket.guild !== guild) return false;
            if (assignee && !(ticket.assignee || '').toLowerCase().includes(assignee)) return false;
            if (from && ticket.updated < from) return false;
            if (to && ticket.updated > to) return false;
            if (search) {
                const haystack = `${ticket.subject} ${ticket.description} ${ticket.requester} ${ticket.id}`.toLowerCase();
                if (!haystack.includes(search)) return false;
            }
            if (!canManageTickets && activeUser && ticket.requester !== activeUser && ticket.assignee !== activeUser) return false;
            return true;
        }

        function renderTickets() {
            const tbody = document.querySelector('#ticketTable tbody');
            tbody.innerHTML = '';
            filteredTickets.forEach(ticket => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td><input type="checkbox" class="form-check-input ticket-checkbox" data-id="${ticket.id}"></td>
                    <td class="ticket-subject" data-id="${ticket.id}">
                        <div class="fw-semibold">${ticket.subject}</div>
                        <div class="small text-muted">${ticket.id} • ${ticket.requester}</div>
                    </td>
                    <td><span class="badge status-pill status-${ticket.status}">${ticket.status.replace('-', ' ')}</span></td>
                    <td><span class="badge priority-pill priority-${ticket.priority}">${ticket.priority}</span></td>
                    <td>${ticket.assignee || 'Unassigned'}</td>
                    <td>${ticket.guild}</td>
                    <td>${ticket.updated}</td>
                `;
                tbody.appendChild(row);
            });
            document.getElementById('listSummary').textContent = `${filteredTickets.length} ticket${filteredTickets.length === 1 ? '' : 's'}`;
            bindTicketSelection();
        }

        function renderDetail() {
            const ticket = tickets.find(t => t.id === selectedTicketId);
            if (!ticket) return;
            document.getElementById('detailStatus').textContent = ticket.status;
            document.getElementById('detailStatus').className = `badge text-bg-secondary status-${ticket.status}`;
            document.getElementById('detailPriority').textContent = ticket.priority;
            document.getElementById('detailPriority').className = `badge text-bg-primary priority-${ticket.priority}`;
            document.getElementById('detailSubject').textContent = ticket.subject;
            document.getElementById('detailMeta').textContent = `${ticket.requester} • Updated ${ticket.updated}`;
            document.getElementById('detailDescription').textContent = ticket.description;
            document.getElementById('detailAssignee').value = ticket.assignee;
            document.getElementById('detailStatusSelect').value = ticket.status;
            document.getElementById('detailPrioritySelect').value = ticket.priority;
            document.getElementById('detailTicketId').textContent = ticket.id;

            const historyList = document.getElementById('historyList');
            historyList.innerHTML = '';
            ticket.history.forEach(item => {
                const li = document.createElement('li');
                li.className = 'mb-1';
                li.innerHTML = `<small class="text-muted">${item.time}</small><div>${item.entry}</div>`;
                historyList.appendChild(li);
            });

            const thread = document.getElementById('commentThread');
            thread.innerHTML = '';
            ticket.comments.forEach(comment => {
                const card = document.createElement('div');
                card.className = `comment card mb-2 ${comment.type === 'internal' ? 'comment-internal' : ''}`;
                card.innerHTML = `
                    <div class="card-body py-2">
                        <div class="d-flex justify-content-between">
                            <strong>${comment.author}</strong>
                            <span class="small text-muted">${comment.time}</span>
                        </div>
                        <p class="mb-0">${comment.message}</p>
                        <span class="badge bg-light text-dark mt-1">${comment.type === 'internal' ? 'Internal' : 'Public'}</span>
                    </div>
                `;
                thread.appendChild(card);
            });
        }

        function bindTicketSelection() {
            document.querySelectorAll('.ticket-subject').forEach(cell => {
                cell.addEventListener('click', () => {
                    selectedTicketId = cell.dataset.id;
                    renderDetail();
                });
            });
        }

        function applyFiltersAndRender() {
            filteredTickets = tickets.filter(ticketMatchesFilters);
            renderTickets();
            if (!tickets.find(t => t.id === selectedTicketId) && filteredTickets.length > 0) {
                selectedTicketId = filteredTickets[0].id;
            }
            renderDetail();
        }

        function updateBadge() {
            document.getElementById('ticketNotificationBadge').textContent = notificationCount;
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

        function addHistory(ticket, entry) {
            ticket.history.unshift({ time: new Date().toLocaleString(), entry });
        }

        document.getElementById('applyFilters').addEventListener('click', applyFiltersAndRender);
        document.getElementById('resetFilters').addEventListener('click', () => {
            ['filterStatus','filterPriority','filterAssignee','filterGuild','filterFrom','filterTo','filterSearch'].forEach(id => {
                document.getElementById(id).value = '';
            });
            applyFiltersAndRender();
        });

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
                        addHistory(ticket, `Internal note added by ${activeUser || 'system'}`);
                    }
                });
                applyFiltersAndRender();
            });
        });

        document.getElementById('addComment').addEventListener('click', () => {
            const ticket = tickets.find(t => t.id === selectedTicketId);
            if (!ticket) return;
            const message = document.getElementById('commentInput').value.trim();
            if (!message) return;
            const isInternal = document.getElementById('visibilityInternal').checked;
            ticket.comments.push({
                author: activeUser || 'Staff',
                type: isInternal ? 'internal' : 'public',
                message,
                time: new Date().toLocaleTimeString()
            });
            addHistory(ticket, `${isInternal ? 'Internal note' : 'Comment'} added by ${activeUser || 'staff'}`);
            document.getElementById('commentInput').value = '';
            renderDetail();
            if (!isInternal && document.getElementById('notifyRequester').checked) {
                queueNotification(ticket, 'New ticket update posted.');
            }
        });

        document.getElementById('saveTicketMeta').addEventListener('click', () => {
            const ticket = tickets.find(t => t.id === selectedTicketId);
            if (!ticket) return;
            ticket.status = document.getElementById('detailStatusSelect').value;
            ticket.priority = document.getElementById('detailPrioritySelect').value;
            ticket.assignee = document.getElementById('detailAssignee').value;
            addHistory(ticket, `${activeUser || 'Staff'} updated status and assignment`);
            queueNotification(ticket, 'Ticket routing updated.');
            applyFiltersAndRender();
        });

        document.getElementById('assignSelf').addEventListener('click', () => {
            const ticket = tickets.find(t => t.id === selectedTicketId);
            if (!ticket || !activeUser) return;
            ticket.assignee = activeUser;
            addHistory(ticket, `Assigned to ${activeUser}`);
            queueNotification(ticket, 'Ticket assigned to you.');
            applyFiltersAndRender();
        });

        document.getElementById('assignGuild').addEventListener('click', () => {
            const ticket = tickets.find(t => t.id === selectedTicketId);
            if (!ticket) return;
            const guild = prompt('Assign to which guild?');
            if (!guild) return;
            ticket.assignee = guild + ' Guild';
            addHistory(ticket, `Reassigned to ${guild} Guild`);
            queueNotification(ticket, 'Ticket routed to guild.');
            applyFiltersAndRender();
        });

        document.getElementById('escalatePriority').addEventListener('click', () => {
            const ticket = tickets.find(t => t.id === selectedTicketId);
            if (!ticket) return;
            ticket.priority = 'urgent';
            addHistory(ticket, 'Escalated to urgent');
            queueNotification(ticket, 'Priority escalated');
            applyFiltersAndRender();
        });

        document.getElementById('saveQuickNote').addEventListener('click', () => {
            const note = document.getElementById('quickNote').value.trim();
            const ticket = tickets.find(t => t.id === selectedTicketId);
            if (!ticket || !note) return;
            ticket.comments.push({ author: activeUser || 'Staff', type: 'internal', message: note, time: new Date().toLocaleTimeString() });
            addHistory(ticket, 'Internal note added');
            document.getElementById('quickNote').value = '';
            renderDetail();
        });

        document.getElementById('notificationTest').addEventListener('click', () => {
            const ticket = tickets.find(t => t.id === selectedTicketId);
            if (ticket) {
                queueNotification(ticket, 'Test notification from dashboard');
            }
        });

        applyPrefill();
        applyFiltersAndRender();
        updateBadge();
    </script>

    <style>
        .status-open { background: #e8f5ff; color: #0b6cbf; }
        .status-in-progress { background: #fff4e5; color: #c46a00; }
        .status-waiting { background: #f3e8ff; color: #6f42c1; }
        .status-resolved { background: #e9f9ee; color: #0f9d58; }
        .priority-urgent { background: #ffebee; color: #c62828; }
        .priority-high { background: #fff3cd; color: #a66f00; }
        .priority-medium { background: #e3f2fd; color: #1565c0; }
        .priority-low { background: #eef2ff; color: #4650dd; }
        .comment-thread .comment-internal { border-left: 4px solid #6c757d; background: #f8f9fa; }
        .comment-thread .comment { border: 1px solid #e9ecef; }
    </style>
</body>

</html>
