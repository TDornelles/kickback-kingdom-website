<?php
require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__ . '/..') . "/Kickback/init.php");

$session = require(\Kickback\SCRIPT_ROOT . "/api/v1/engine/session/verifySession.php");
require("../php-components/base-page-pull-active-account-info.php");

use Kickback\Common\Version;
use Kickback\Services\Session;

$ticketId = $_GET['id'] ?? '';

if (!Session::isLoggedIn()) {
    $redirectTarget = 'tickets/view.php' . ($ticketId ? ('?id=' . urlencode($ticketId)) : '');
    header('Location: ' . Version::urlBetaPrefix() . '/login.php?redirect=' . urlencode($redirectTarget));
    exit();
}

$currentAccount = Session::getCurrentAccount();
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
                    $activePageName = $ticketId ? "Ticket " . htmlspecialchars($ticketId) : "Ticket";
                    require("../php-components/base-page-breadcrumbs.php");
                ?>

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <a class="btn btn-link text-decoration-none ps-0" href="<?= Version::urlBetaPrefix(); ?>/tickets/dashboard.php"><i class="fa-solid fa-arrow-left me-1"></i>Back to dashboard</a>
                        <h1 class="h3 mb-0" id="ticketTitle">Ticket</h1>
                        <div class="text-muted" id="ticketMeta">Ticket details</div>
                    </div>
                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        <span class="badge text-bg-secondary" id="ticketStatus">Open</span>
                        <span class="badge text-bg-primary" id="ticketPriority">Medium</span>
                        <span class="badge text-bg-light" id="ticketIdBadge"></span>
                    </div>
                </div>

                <div class="card shadow-sm mb-3">
                    <div class="card-body">
                        <p class="lead" id="ticketDescription"></p>
                        <div class="d-flex gap-3 flex-wrap" id="ticketMetaBadges"></div>
                    </div>
                </div>

                <div class="row g-4">
                    <div class="col-12 col-lg-7">
                        <div class="card shadow-sm mb-3">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h2 class="h6 mb-0">Comments</h2>
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
                        </div>
                    </div>
                    <div class="col-12 col-lg-5">
                        <div class="card shadow-sm mb-3">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h2 class="h6 mb-0">Status & Assignment</h2>
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
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <h2 class="h6">History</h2>
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
        <?php require("../php-components/base-page-footer.php"); ?>
    </main>

    <?php require("../php-components/base-page-javascript.php"); ?>
    <script>
        const activeUser = <?= json_encode(isset($currentAccount->username) ? $currentAccount->username : ''); ?>;
        const ticketId = <?= json_encode($ticketId); ?>;

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

        let ticket = tickets.find(t => t.id === ticketId) || null;

        function renderTicket() {
            if (!ticket) {
                document.getElementById('ticketTitle').textContent = 'Ticket not found';
                document.getElementById('ticketMeta').textContent = 'The requested ticket does not exist or is unavailable.';
                document.getElementById('ticketDescription').textContent = '';
                document.querySelectorAll('button, input, textarea, select').forEach(el => el.disabled = true);
                return;
            }

            document.getElementById('ticketTitle').textContent = ticket.subject;
            document.getElementById('ticketMeta').textContent = `${ticket.requester} • Updated ${ticket.updated}`;
            document.getElementById('ticketDescription').textContent = ticket.description;
            document.getElementById('ticketStatus').textContent = ticket.status;
            document.getElementById('ticketStatus').className = `badge text-bg-secondary status-${ticket.status}`;
            document.getElementById('ticketPriority').textContent = ticket.priority;
            document.getElementById('ticketPriority').className = `badge text-bg-primary priority-${ticket.priority}`;
            document.getElementById('ticketIdBadge').textContent = ticket.id;
            document.getElementById('detailTicketId').textContent = ticket.id;
            document.getElementById('detailStatusSelect').value = ticket.status;
            document.getElementById('detailPrioritySelect').value = ticket.priority;
            document.getElementById('detailAssignee').value = ticket.assignee;

            const metaBadges = document.getElementById('ticketMetaBadges');
            metaBadges.innerHTML = '';
            const fields = [
                { label: 'Guild', value: ticket.guild },
                { label: 'Assignee', value: ticket.assignee || 'Unassigned' },
                { label: 'Requester', value: ticket.requester }
            ];
            fields.forEach(field => {
                const span = document.createElement('span');
                span.className = 'badge rounded-pill text-bg-light me-2';
                span.textContent = `${field.label}: ${field.value}`;
                metaBadges.appendChild(span);
            });

            renderHistory();
            renderComments();
        }

        function renderHistory() {
            const historyList = document.getElementById('historyList');
            historyList.innerHTML = '';
            ticket.history.forEach(item => {
                const li = document.createElement('li');
                li.className = 'mb-1';
                li.innerHTML = `<small class="text-muted">${item.time}</small><div>${item.entry}</div>`;
                historyList.appendChild(li);
            });
        }

        function renderComments() {
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

        function addHistory(entry) {
            ticket.history.unshift({ time: new Date().toLocaleString(), entry });
            renderHistory();
        }

        document.getElementById('addComment').addEventListener('click', () => {
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
            addHistory(`${isInternal ? 'Internal note' : 'Comment'} added by ${activeUser || 'staff'}`);
            document.getElementById('commentInput').value = '';
            renderComments();
        });

        document.getElementById('saveTicketMeta').addEventListener('click', () => {
            if (!ticket) return;
            ticket.status = document.getElementById('detailStatusSelect').value;
            ticket.priority = document.getElementById('detailPrioritySelect').value;
            ticket.assignee = document.getElementById('detailAssignee').value;
            addHistory(`${activeUser || 'Staff'} updated status and assignment`);
            renderTicket();
        });

        document.getElementById('saveQuickNote').addEventListener('click', () => {
            if (!ticket) return;
            const note = document.getElementById('quickNote').value.trim();
            if (!note) return;
            ticket.comments.push({ author: activeUser || 'Staff', type: 'internal', message: note, time: new Date().toLocaleTimeString() });
            addHistory('Internal note added');
            document.getElementById('quickNote').value = '';
            renderComments();
        });

        renderTicket();
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
