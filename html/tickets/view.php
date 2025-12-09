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
                        <div class="text-muted small" id="ticketMeta"></div>
                    </div>
                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        <span class="badge text-bg-secondary" id="ticketStatus">Open</span>
                        <span class="badge text-bg-primary" id="ticketPriority">Medium</span>
                        <span class="badge text-bg-light" id="ticketIdBadge"></span>
                    </div>
                </div>

                <div class="card shadow-sm mb-3">
                    <div class="card-body">
                        <div class="lead markdown-content" id="ticketDescription"></div>
                        <div class="d-flex gap-2 flex-wrap" id="ticketMetaBadges"></div>
                    </div>
                </div>

                <div class="row g-4">
                    <div class="col-12 col-lg-7">
                        <div class="card shadow-sm mb-3">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                                    <h2 class="h6 mb-0">Comments</h2>
                                    <div class="btn-group btn-group-sm" role="group" aria-label="comment visibility">
                                        <input type="radio" class="btn-check" name="commentVisibility" id="visibilityPublic" value="public" autocomplete="off" checked>
                                        <label class="btn btn-outline-primary" for="visibilityPublic"><i class="fa-regular fa-message me-1"></i>Public</label>
                                        <input type="radio" class="btn-check" name="commentVisibility" id="visibilityInternal" value="internal" autocomplete="off">
                                        <label class="btn btn-outline-secondary" for="visibilityInternal"><i class="fa-solid fa-user-shield me-1"></i>Internal</label>
                                    </div>
                                </div>
                                <div id="commentThread" class="comment-thread"></div>
                                <div class="alert alert-light border d-none" id="emptyComments" role="alert">
                                    <i class="fa-regular fa-circle-dot me-2"></i>No comments to show yet.
                                </div>
                                <div class="mt-3">
                                    <label for="commentInput" class="form-label">Add a comment</label>
                                    <textarea class="form-control" id="commentInput" rows="3" placeholder="Share an update or internal note"></textarea>
                                    <div class="d-flex justify-content-between align-items-center mt-2 flex-wrap gap-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="1" id="notifyRequester" checked>
                                            <label class="form-check-label" for="notifyRequester">Notify requester by email</label>
                                        </div>
                                        <button class="btn btn-primary" id="addComment"><i class="fa-solid fa-paper-plane me-1"></i>Post update</button>
                                    </div>
                                    <div class="form-text">Use the toggle above to switch between public updates and internal notes.</div>
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
                                <ul class="list-group list-group-flush" id="historyList"></ul>
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
        const isSteward = <?= json_encode($currentAccount?->isSteward ?? false); ?>;

        const priorityLabels = { 1: 'low', 2: 'medium', 3: 'high', 4: 'urgent' };
        const priorityNames = { 1: 'Low', 2: 'Medium', 3: 'High', 4: 'Urgent' };

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

        function renderDateTag(raw) {
            if (!raw) {
                return '<span class="text-muted">N/A</span>';
            }
            const parsed = new Date(raw);
            const display = isNaN(parsed.getTime()) ? raw : parsed.toLocaleString();
            return `<span class="date" data-datetime-utc="${raw}">${display}</span>`;
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

        function priorityLabel(value) {
            const numeric = Number(value) || 2;
            const label = priorityLabels[numeric] ?? 'medium';
            return { label, text: priorityNames[numeric] ?? 'Medium', numeric };
        }

        function severityLabel(value) {
            if (!value) return 'Not set';
            const map = { 1: 'Cosmetic', 2: 'Minor', 3: 'Major', 4: 'Critical' };
            return map[value] ?? `Severity ${value}`;
        }

        function getCommentVisibility(comment) {
            if (!comment?.body) return 'public';
            return /^\s*\[internal\]/i.test(comment.body) ? 'internal' : 'public';
        }

        function stripVisibilityTag(body) {
            return (body || '').replace(/^\s*\[internal\]\s*/i, '');
        }

        function selectedVisibility() {
            const checked = document.querySelector('input[name="commentVisibility"]:checked');
            return checked?.value ?? 'public';
        }

        function toggleEmptyState(isEmpty) {
            const empty = document.getElementById('emptyComments');
            if (!empty) return;
            empty.classList.toggle('d-none', !isEmpty);
        }

        async function updateTicket(payload) {
            const formData = new FormData();
            formData.append('ctime', ticketCtime);
            formData.append('crand', ticketCrand);

            Object.entries(payload).forEach(([key, value]) => {
                if (value === undefined || value === null) return;
                if (Array.isArray(value)) {
                    value.forEach(v => formData.append(`${key}[]`, v));
                } else {
                    formData.append(key, value);
                }
            });

            const response = await fetch('<?= Version::urlBetaPrefix(); ?>/api/v1/tickets/update.php', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
            });

            const result = await response.json();
            if (!result.success) {
                throw new Error(result.message || 'Unable to update ticket.');
            }

            ticket = result.data.ticket ?? ticket;
            assignments = Array.isArray(result.data.assignments) ? result.data.assignments : assignments;
            if (result.data.comment) {
                comments.push(result.data.comment);
            }
            renderTicket();
            return result;
        }

        function renderTicket() {
            if (!ticket) {
                markUnavailable('Ticket not found');
                return;
            }

            const ticketId = `${ticket.ctime}-${ticket.crand}`;
            const { label: priorityClass, text: priorityText } = priorityLabel(ticket.priority);

            document.getElementById('ticketTitle').textContent = ticket.subject;
            document.getElementById('ticketMeta').innerHTML = `Updated ${renderDateTag(ticket.updatedAt)} • Created ${renderDateTag(ticket.ctime)}`;
            document.getElementById('ticketDescription').innerHTML = renderCommentMarkdown(ticket.description);
            document.getElementById('ticketStatus').textContent = formatStatus(ticket.status);
            document.getElementById('ticketStatus').className = `badge text-bg-secondary status-${ticket.status}`;
            document.getElementById('ticketPriority').textContent = priorityText;
            document.getElementById('ticketPriority').className = `badge text-bg-primary priority-${priorityClass}`;
            document.getElementById('ticketIdBadge').textContent = ticketId;
            document.getElementById('detailTicketId').textContent = ticketId;

            const statusSelect = document.getElementById('detailStatusSelect');
            if (statusSelect) {
                statusSelect.value = ticket.status;
            }

            const prioritySelect = document.getElementById('detailPrioritySelect');
            if (prioritySelect) {
                prioritySelect.value = priorityClass;
                prioritySelect.disabled = !isSteward;
            }

            const assigneeInput = document.getElementById('detailAssignee');
            if (assigneeInput) {
                assigneeInput.value = assignments[0]?.accountCrand ? `Account #${assignments[0].accountCrand}` : '';
            }

            const metaBadges = document.getElementById('ticketMetaBadges');
            metaBadges.innerHTML = '';
            const fields = [
                { label: 'Tags', value: (ticket.tags || []).join(', ') || 'None' },
                { label: 'Severity', value: severityLabel(ticket.severity) },
                { label: 'Guild', value: ticket.guildId ?? 'None' },
                { label: 'Game', value: ticket.gameId ?? 'Not set' },
                { label: 'Server', value: ticket.serverCtime && ticket.serverCrand ? `${ticket.serverCtime}-${ticket.serverCrand}` : 'Not set' },
                { label: 'First response', value: renderDateTag(ticket.firstResponseAt) },
                { label: 'Resolved', value: renderDateTag(ticket.resolvedAt) },
            ];
            fields.forEach(field => {
                const span = document.createElement('span');
                span.className = 'badge rounded-pill text-bg-light me-1';
                span.innerHTML = `<strong>${field.label}:</strong> ${field.value}`;
                metaBadges.appendChild(span);
            });

            renderHistory();
            renderComments();
        }

        function renderHistory() {
            const historyList = document.getElementById('historyList');
            historyList.innerHTML = '';

            const entries = [];
            entries.push({ time: ticket?.ctime, entry: 'Ticket created', type: 'creation' });
            assignments.forEach(assign => entries.push({ time: assign.assignedAt, entry: `Assigned to account #${assign.accountCrand}`, type: 'assignment' }));
            comments.forEach(comment => entries.push({ time: comment.ctime, entry: `${getCommentVisibility(comment) === 'internal' ? 'Internal' : 'Public'} comment from ${comment.authorCrand ? 'account #' + comment.authorCrand : 'system'}`, type: 'comment' }));

            entries
                .filter(entry => entry.time)
                .sort((a, b) => new Date(b.time).getTime() - new Date(a.time).getTime())
                .forEach(item => {
                    const li = document.createElement('li');
                    li.className = 'list-group-item';
                    const badgeClass = item.type === 'comment' ? 'text-bg-primary' : item.type === 'assignment' ? 'text-bg-info' : 'text-bg-secondary';
                    li.innerHTML = `
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="fw-semibold">${item.entry}</div>
                                <div class="small text-muted">${renderDateTag(item.time)}</div>
                            </div>
                            <span class="badge ${badgeClass}">${item.type}</span>
                        </div>
                    `;
                    historyList.appendChild(li);
                });
        }

        function renderComments() {
            const thread = document.getElementById('commentThread');
            thread.innerHTML = '';
            const visibility = selectedVisibility();
            const filtered = comments.filter(comment => getCommentVisibility(comment) === visibility);

            toggleEmptyState(filtered.length === 0);

            filtered.forEach(comment => {
                const isInternal = getCommentVisibility(comment) === 'internal';
                const cleanBody = stripVisibilityTag(comment.body || '');
                const card = document.createElement('div');
                card.className = `comment card mb-2 ${isInternal ? 'comment-internal' : ''}`;
                card.innerHTML = `
                    <div class="card-body py-2">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <div>
                                <div class="d-flex align-items-center gap-2">
                                    <strong>${comment.authorCrand ? 'Account #' + comment.authorCrand : 'System'}</strong>
                                    <span class="badge rounded-pill ${isInternal ? 'text-bg-secondary' : 'text-bg-success'}">${isInternal ? 'Internal' : 'Public'}</span>
                                </div>
                                <div class="small text-muted">${renderDateTag(comment.ctime)}</div>
                            </div>
                        </div>
                        <div class="comment-content markdown-content mb-0 mt-2"></div>
                    </div>
                `;
                const content = card.querySelector('.comment-content');
                content.innerHTML = renderCommentMarkdown(cleanBody);
                thread.appendChild(card);
            });
        }

        function handleVisibilityToggle() {
            const notifyCheckbox = document.getElementById('notifyRequester');
            const visibility = selectedVisibility();
            if (notifyCheckbox) {
                notifyCheckbox.disabled = visibility === 'internal';
                if (visibility === 'internal') {
                    notifyCheckbox.checked = false;
                }
            }
            renderComments();
        }

        async function handleCommentSubmit(event) {
            event.preventDefault();
            if (!ticket) return;

            const input = document.getElementById('commentInput');
            const notifyCheckbox = document.getElementById('notifyRequester');
            const raw = input.value.trim();
            if (!raw) return;

            const visibility = selectedVisibility();
            const shouldNotify = notifyCheckbox?.checked ?? false;
            let body = raw;
            if (visibility === 'internal' && !/^\s*\[internal\]/i.test(body)) {
                body = `[internal] ${body}`;
            }
            if (!shouldNotify) {
                body += `\n\n_(Requester not notified)_`;
            }

            try {
                await updateTicket({ comment: body });
                input.value = '';
            } catch (error) {
                console.error(error);
                alert(error.message || 'Unable to post comment.');
            }
        }

        async function handleMetaSave(event) {
            event.preventDefault();
            if (!ticket) return;

            const status = document.getElementById('detailStatusSelect')?.value || ticket.status;
            const priorityChoice = document.getElementById('detailPrioritySelect')?.value;
            const assigneeRaw = document.getElementById('detailAssignee')?.value.trim();

            const payload = { status };
            if (priorityChoice && isSteward) {
                const reverseMap = { low: 1, medium: 2, high: 3, urgent: 4 };
                payload.priority = reverseMap[priorityChoice] ?? ticket.priority;
            }

            if (assigneeRaw) {
                const numeric = parseInt(assigneeRaw.replace(/\D/g, ''), 10);
                if (!Number.isNaN(numeric) && numeric > 0) {
                    payload.assignees = [numeric];
                }
            }

            try {
                await updateTicket(payload);
            } catch (error) {
                console.error(error);
                alert(error.message || 'Unable to save updates.');
            }
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

        document.getElementById('addComment').addEventListener('click', handleCommentSubmit);
        document.getElementById('saveTicketMeta').addEventListener('click', handleMetaSave);
        document.querySelectorAll('input[name="commentVisibility"]').forEach(radio => {
            radio.addEventListener('change', handleVisibilityToggle);
        });

        handleVisibilityToggle();
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
