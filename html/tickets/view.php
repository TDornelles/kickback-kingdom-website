<?php
require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__ . '/..') . "/Kickback/init.php");

$session = require(\Kickback\SCRIPT_ROOT . "/api/v1/engine/session/verifySession.php");
require("../php-components/base-page-pull-active-account-info.php");

use Kickback\Common\Version;
use Kickback\Services\Session;

$ticketId = $_GET['id'] ?? '';
$ticketCtime = $_GET['ctime'] ?? '';
$ticketCrand = isset($_GET['crand']) ? (int) $_GET['crand'] : 0;

if ($ticketCtime === '' && $ticketId !== '' && preg_match('/^([0-9]{10,})[-:](\d+)$/', $ticketId, $matches)) {
    $ticketCtime = $matches[1];
    $ticketCrand = (int) $matches[2];
}

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
                    $activePageName = ($ticketCtime && $ticketCrand) ? "Ticket {$ticketCtime}-{$ticketCrand}" : "Ticket";
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
                                        <option value="in_progress">In Progress</option>
                                        <option value="waiting">Waiting on Customer</option>
                                        <option value="resolved">Resolved</option>
                                        <option value="closed">Closed</option>
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
    <?php require("../php-components/content-viewer-javascript.php"); ?>
    <script>
        const activeUser = <?= json_encode(isset($currentAccount->username) ? $currentAccount->username : ''); ?>;
        const ticketCtime = <?= json_encode($ticketCtime); ?>;
        const ticketCrand = <?= json_encode($ticketCrand); ?>;

        let ticket = null;
        let assignments = [];
        let comments = [];

        function renderCommentMarkdown(markdownText) {
            if (typeof renderMarkdownToHtml === 'function') {
                return renderMarkdownToHtml(markdownText || '');
            }

            if (typeof marked !== 'undefined' && typeof DOMPurify !== 'undefined') {
                return DOMPurify.sanitize(marked.parse(markdownText || ''));
            }

            const fallback = document.createElement('div');
            fallback.textContent = markdownText || '';
            return fallback.innerHTML;
        }

        function markUnavailable(message) {
            document.getElementById('ticketTitle').textContent = message;
            document.getElementById('ticketMeta').textContent = 'Please return to the dashboard and try another ticket.';
            document.getElementById('ticketDescription').textContent = '';
            document.querySelectorAll('button, input, textarea, select').forEach(el => el.disabled = true);
        }

        function formatStatus(status) {
            return (status || '').replace(/_/g, ' ');
        }

        function formatDate(raw) {
            if (!raw) return '';
            const parsed = new Date(raw);
            return isNaN(parsed.getTime()) ? raw : parsed.toLocaleString();
        }

        function renderTicket() {
            if (!ticket) {
                markUnavailable('Ticket not found');
                return;
            }

            const ticketId = `${ticket.ctime}-${ticket.crand}`;

            document.getElementById('ticketTitle').textContent = ticket.subject;
            document.getElementById('ticketMeta').textContent = `Updated ${formatDate(ticket.updatedAt)}`;
            document.getElementById('ticketDescription').textContent = ticket.description;
            document.getElementById('ticketStatus').textContent = formatStatus(ticket.status);
            document.getElementById('ticketStatus').className = `badge text-bg-secondary status-${ticket.status}`;
            document.getElementById('ticketPriority').textContent = ticket.priority;
            document.getElementById('ticketPriority').className = `badge text-bg-primary priority-${ticket.priority}`;
            document.getElementById('ticketIdBadge').textContent = ticketId;
            document.getElementById('detailTicketId').textContent = ticketId;
            document.getElementById('detailStatusSelect').value = ticket.status;
            document.getElementById('detailPrioritySelect').value = ticket.priority;
            document.getElementById('detailAssignee').value = assignments[0]?.accountCrand ? `Account #${assignments[0].accountCrand}` : '';

            const metaBadges = document.getElementById('ticketMetaBadges');
            metaBadges.innerHTML = '';
            const fields = [
                { label: 'Tags', value: (ticket.tags || []).join(', ') || 'None' },
                { label: 'Created', value: formatDate(ticket.ctime) },
                { label: 'Updated', value: formatDate(ticket.updatedAt) },
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

            const entries = [];
            entries.push({ time: ticket?.ctime, entry: 'Ticket created' });
            assignments.forEach(assign => entries.push({ time: assign.assignedAt, entry: `Assigned to account #${assign.accountCrand}` }));
            comments.forEach(comment => entries.push({ time: comment.ctime, entry: `Comment from ${comment.authorCrand ? 'account #' + comment.authorCrand : 'system'}` }));

            entries
                .filter(entry => entry.time)
                .sort((a, b) => new Date(b.time).getTime() - new Date(a.time).getTime())
                .forEach(item => {
                    const li = document.createElement('li');
                    li.className = 'mb-1';
                    li.innerHTML = `<small class="text-muted">${formatDate(item.time)}</small><div>${item.entry}</div>`;
                    historyList.appendChild(li);
                });
        }

        function renderComments() {
            const thread = document.getElementById('commentThread');
            thread.innerHTML = '';
            comments.forEach(comment => {
                const card = document.createElement('div');
                card.className = 'comment card mb-2';
                card.innerHTML = `
                    <div class="card-body py-2">
                        <div class="d-flex justify-content-between">
                            <strong>${comment.authorCrand ? 'Account #' + comment.authorCrand : 'System'}</strong>
                            <span class="small text-muted">${formatDate(comment.ctime)}</span>
                        </div>
                        <div class="comment-content markdown-content mb-0"></div>
                    </div>
                `;
                const content = card.querySelector('.comment-content');
                content.innerHTML = renderCommentMarkdown(comment.body || '');
                thread.appendChild(card);
            });
        }

        function bindReadOnlyHandlers() {
            document.getElementById('addComment').addEventListener('click', (event) => {
                event.preventDefault();
                alert('Commenting from this view is not available yet.');
            });
            document.getElementById('saveTicketMeta').addEventListener('click', (event) => {
                event.preventDefault();
                alert('Editing ticket metadata is not available yet.');
            });
            document.getElementById('saveQuickNote').addEventListener('click', (event) => {
                event.preventDefault();
                alert('Adding notes from this page is not available yet.');
            });
        }

        async function loadTicket() {
            if (!ticketCtime || !ticketCrand) {
                markUnavailable('Ticket not found');
                return;
            }

            try {
                const formData = new FormData();
                formData.append('ctime', ticketCtime);
                formData.append('crand', ticketCrand);

                const response = await fetch('<?= Version::urlBetaPrefix(); ?>/api/v1/tickets/view.php', {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin',
                });

                const result = await response.json();
                if (!result.success) {
                    markUnavailable(result.message || 'Unable to load ticket.');
                    return;
                }

                ticket = result.data.ticket || null;
                assignments = Array.isArray(result.data.assignments) ? result.data.assignments : [];
                comments = Array.isArray(result.data.comments) ? result.data.comments : [];
                renderTicket();
            } catch (error) {
                console.error('Failed to load ticket', error);
                markUnavailable('Unable to load ticket right now.');
            }
        }

        bindReadOnlyHandlers();
        loadTicket();
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
