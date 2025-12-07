<?php
require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__ . '/..') . "/Kickback/init.php");

$session = require(\Kickback\SCRIPT_ROOT . "/api/v1/engine/session/verifySession.php");
require("../php-components/base-page-pull-active-account-info.php");

use Kickback\Common\Version;
use Kickback\Services\Session;
use Kickback\Backend\Controllers\GameController;
use Kickback\Backend\Controllers\ServerController;

$currentAccount = Session::getCurrentAccount();
$isSteward = $currentAccount?->isSteward ?? false;

$isLoggedIn = Session::isLoggedIn();

$gamesResp = GameController::getGames();
$games = $gamesResp->success && is_array($gamesResp->data) ? $gamesResp->data : [];

$serversResp = ServerController::getAllServers();
$servers = $serversResp->success && is_array($serversResp->data) ? $serversResp->data : [];
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
                    $activePageName = "Support Tickets";
                    require("../php-components/base-page-breadcrumbs.php");
                ?>

                <div class="card shadow-sm mb-3">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-3">
                            <div class="me-3 text-primary"><i class="fa-solid fa-life-ring fa-2x"></i></div>
                            <div>
                                <h2 class="h4 mb-1">Submit a Ticket</h2>
                                <p class="mb-0 text-muted">Tell us what you need help with and we'll follow up by email.</p>
                            </div>
                        </div>

                        <div class="alert alert-success d-none" id="ticketConfirmation" role="alert">
                            <i class="fa-solid fa-circle-check me-2"></i>
                            Thanks for reaching out! Your ticket is in our queue and we'll be in touch soon.
                        </div>

                        <?php if (!$isLoggedIn): ?>
                            <div class="alert alert-warning" role="alert">
                                <i class="fa-solid fa-circle-info me-2"></i>
                                Please sign in to submit a support ticket.
                            </div>
                        <?php endif; ?>

                        <form id="ticketForm" class="ticket-form" enctype="multipart/form-data">
                            <input type="hidden" name="serverCtime" id="ticketServerCtimeHidden" value="">
                            <input type="hidden" name="serverCrand" id="ticketServerCrandHidden" value="">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="ticketCategory" class="form-label">Category</label>
                                    <select class="form-select" id="ticketCategory" name="category" required>
                                        <option value="" selected disabled>Loading categories...</option>
                                    </select>
                                    <div class="form-text" id="ticketCategoryStatus">Choose a category so we can route your ticket.</div>
                                </div>
                                <?php if ($isSteward): ?>
                                    <div class="col-md-6">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <label for="ticketPriority" class="form-label mb-0">Priority</label>
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="badge text-bg-secondary" id="ticketPriorityValue">2 (Medium)</span>
                                                <i class="fa-regular fa-circle-question text-muted" data-bs-toggle="tooltip" data-bs-placement="top" title="1 = Low, 2 = Medium, 3 = High, 4 = Urgent"></i>
                                            </div>
                                        </div>
                                        <input type="range" class="form-range" id="ticketPriority" name="priority" min="1" max="4" step="1" value="2" required>
                                        <div class="d-flex justify-content-between small text-muted px-1">
                                            <span>Low</span>
                                            <span>Medium</span>
                                            <span>High</span>
                                            <span>Urgent</span>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <input type="hidden" name="priority" id="ticketPriorityHidden" value="2">
                                    <div class="col-md-6">
                                    <label class="form-label">Priority</label>
                                        <div class="form-control bg-light">
                                            <span class="text-muted">Default priority: Medium (set by Stewards)</span>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                <div class="col-md-6">
                                    <label for="ticketSubject" class="form-label">Subject</label>
                                    <input type="text" class="form-control" id="ticketSubject" name="subject" maxlength="255" placeholder="Short summary" required>
                                </div>
                                <div class="col-md-6">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <label for="ticketSeverity" class="form-label mb-0">Severity</label>
                                        <i class="fa-regular fa-circle-question text-muted" data-bs-toggle="tooltip" data-bs-placement="top" title="1 = Cosmetic, 2 = Minor, 3 = Major, 4 = Critical"></i>
                                    </div>
                                    <select class="form-select" id="ticketSeverity" name="severity">
                                        <option value="">Not sure</option>
                                        <option value="1">Cosmetic</option>
                                        <option value="2">Minor</option>
                                        <option value="3">Major</option>
                                        <option value="4">Critical</option>
                                    </select>
                                    <div class="form-text">Severity helps us triage impact. If unsure, leave as "Not sure."</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="ticketGuild" class="form-label">Guild Context</label>
                                    <select class="form-select" id="ticketGuild" name="guildId">
                                        <option value="">General</option>
                                    </select>
                                    <div class="form-text" id="ticketGuildStatus">Select a guild by ID for context (optional).</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="ticketGame" class="form-label">Game</label>
                                    <select class="form-select" id="ticketGame" name="gameId">
                                        <option value="">Select a game (optional)</option>
                                        <?php foreach ($games as $game): ?>
                                            <option value="<?= htmlspecialchars($game->crand); ?>">
                                                <?= htmlspecialchars($game->name); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="form-text">Choose the game for context or leave blank if unsure.</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="ticketServer" class="form-label">Server</label>
                                    <select class="form-select" id="ticketServer">
                                        <option value="">Select a server (optional)</option>
                                    </select>
                                    <div class="form-text" id="ticketServerStatus">Pick a server after selecting a game.</div>
                                </div>
                                <div class="col-12">
                                    <label for="ticketDescription" class="form-label">Description</label>
                                    <div class="d-flex align-items-center mb-2 gap-2 flex-wrap">
                                        <div class="btn-group" role="group" aria-label="Description editor mode">
                                            <button type="button" class="btn btn-outline-primary active" id="descriptionWriteTab">Write</button>
                                            <button type="button" class="btn btn-outline-primary" id="descriptionPreviewTab">Preview</button>
                                        </div>
                                        <small class="text-muted">Markdown supported</small>
                                    </div>
                                    <div class="btn-toolbar flex-wrap mb-2" id="ticketMarkdownToolbar" role="toolbar" aria-label="Markdown toolbar">
                                        <div class="btn-group btn-group-sm me-2 mb-2" role="group" aria-label="Text formatting">
                                            <button type="button" class="btn btn-outline-secondary" title="Bold" onclick="window.ticketMarkdownEditor && window.ticketMarkdownEditor.applyWrap('**','**','bold text')"><i class="fa-solid fa-bold"></i></button>
                                            <button type="button" class="btn btn-outline-secondary" title="Italic" onclick="window.ticketMarkdownEditor && window.ticketMarkdownEditor.applyWrap('*','*','italic text')"><i class="fa-solid fa-italic"></i></button>
                                            <button type="button" class="btn btn-outline-secondary" title="Strikethrough" onclick="window.ticketMarkdownEditor && window.ticketMarkdownEditor.applyWrap('~~','~~','strikethrough')"><i class="fa-solid fa-strikethrough"></i></button>
                                            <button type="button" class="btn btn-outline-secondary" title="Inline code" onclick="window.ticketMarkdownEditor && window.ticketMarkdownEditor.applyWrap('`','`','code')"><i class="fa-solid fa-terminal"></i></button>
                                        </div>
                                        <div class="btn-group btn-group-sm me-2 mb-2" role="group" aria-label="Headings">
                                            <button type="button" class="btn btn-outline-secondary" title="Heading 1" onclick="window.ticketMarkdownEditor && window.ticketMarkdownEditor.applyHeading(1)">H1</button>
                                            <button type="button" class="btn btn-outline-secondary" title="Heading 2" onclick="window.ticketMarkdownEditor && window.ticketMarkdownEditor.applyHeading(2)">H2</button>
                                            <button type="button" class="btn btn-outline-secondary" title="Heading 3" onclick="window.ticketMarkdownEditor && window.ticketMarkdownEditor.applyHeading(3)">H3</button>
                                        </div>
                                        <div class="btn-group btn-group-sm me-2 mb-2" role="group" aria-label="Blocks">
                                            <button type="button" class="btn btn-outline-secondary" title="Blockquote" onclick="window.ticketMarkdownEditor && window.ticketMarkdownEditor.applyPrefix('> ')"><i class="fa-solid fa-quote-left"></i></button>
                                            <button type="button" class="btn btn-outline-secondary" title="Code block" onclick="window.ticketMarkdownEditor && window.ticketMarkdownEditor.applyBlock('```\n','\n```','code block')"><i class="fa-solid fa-code"></i></button>
                                            <button type="button" class="btn btn-outline-secondary" title="Horizontal rule" onclick="window.ticketMarkdownEditor && window.ticketMarkdownEditor.insertHorizontalRule()"><i class="fa-solid fa-grip-lines"></i></button>
                                        </div>
                                        <div class="btn-group btn-group-sm me-2 mb-2" role="group" aria-label="Lists">
                                            <button type="button" class="btn btn-outline-secondary" title="Bulleted list" onclick="window.ticketMarkdownEditor && window.ticketMarkdownEditor.applyList('unordered')"><i class="fa-solid fa-list-ul"></i></button>
                                            <button type="button" class="btn btn-outline-secondary" title="Numbered list" onclick="window.ticketMarkdownEditor && window.ticketMarkdownEditor.applyList('ordered')"><i class="fa-solid fa-list-ol"></i></button>
                                            <button type="button" class="btn btn-outline-secondary" title="Task list" onclick="window.ticketMarkdownEditor && window.ticketMarkdownEditor.applyList('task')"><i class="fa-regular fa-square-check"></i></button>
                                        </div>
                                        <div class="btn-group btn-group-sm mb-2" role="group" aria-label="Links and media">
                                            <button type="button" class="btn btn-outline-secondary" title="Link" onclick="window.ticketMarkdownEditor && window.ticketMarkdownEditor.insertLink()"><i class="fa-solid fa-link"></i></button>
                                            <button type="button" class="btn btn-outline-secondary" title="Image" onclick="window.ticketMarkdownEditor && window.ticketMarkdownEditor.insertImage()"><i class="fa-regular fa-image"></i></button>
                                            <button type="button" class="btn btn-outline-secondary" title="Table" onclick="window.ticketMarkdownEditor && window.ticketMarkdownEditor.insertTable()"><i class="fa-solid fa-table"></i></button>
                                        </div>
                                    </div>
                                    <textarea class="form-control" id="ticketDescription" name="description" rows="6" maxlength="5000" placeholder="Share steps to reproduce, expected behavior, links, or extra context." required></textarea>
                                    <div id="ticketDescriptionPreview" class="d-none form-control bg-light markdown-preview"></div>
                                    <div class="form-text">We'll notify you at your account email.</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="ticketAttachments" class="form-label">Attachments</label>
                                    <input class="form-control" type="file" id="ticketAttachments" name="attachments[]" multiple>
                                    <div class="form-text">Up to 5 files, 8MB each. Screenshots encouraged!</div>
                                </div>
                            </div>

                            <div class="d-flex align-items-center justify-content-between mt-4">
                                <div id="ticketStatus" class="text-muted"></div>
                                <button class="btn btn-primary" type="submit" id="ticketSubmit">
                                    <i class="fa-solid fa-paper-plane me-1"></i> Submit Ticket
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <?php require("../php-components/base-page-discord.php"); ?>
        </div>
        <?php require("../php-components/base-page-footer.php"); ?>
    </main>

    <?php require("../php-components/base-page-javascript.php"); ?>
    <?php
    if (!isset($_vPageContent)) {
        $_vPageContent = (object) ['data' => []];
    }
    if (!isset($_vPageContentEditMode)) {
        $_vPageContentEditMode = false;
    }
    ?>
    <?php require("../php-components/content-viewer-javascript.php"); ?>
    <script>
        (function() {
            const isSteward = <?= json_encode($isSteward); ?>;
            const form = document.getElementById('ticketForm');
            const statusEl = document.getElementById('ticketStatus');
            const confirmation = document.getElementById('ticketConfirmation');
            const submitBtn = document.getElementById('ticketSubmit');
            const categorySelect = document.getElementById('ticketCategory');
            const categoryStatus = document.getElementById('ticketCategoryStatus');
            const guildSelect = document.getElementById('ticketGuild');
            const guildStatus = document.getElementById('ticketGuildStatus');
            const severitySelect = document.getElementById('ticketSeverity');
            const gameSelect = document.getElementById('ticketGame');
            const serverSelect = document.getElementById('ticketServer');
            const serverStatus = document.getElementById('ticketServerStatus');
            const serverCtimeHidden = document.getElementById('ticketServerCtimeHidden');
            const serverCrandHidden = document.getElementById('ticketServerCrandHidden');
            const priorityInput = document.getElementById('ticketPriority');
            const priorityHidden = document.getElementById('ticketPriorityHidden');
            const priorityValueBadge = document.getElementById('ticketPriorityValue');
            const descriptionInput = document.getElementById('ticketDescription');
            const descriptionPreview = document.getElementById('ticketDescriptionPreview');
            const descriptionWriteTab = document.getElementById('descriptionWriteTab');
            const descriptionPreviewTab = document.getElementById('descriptionPreviewTab');
            const ticketSubject = document.getElementById('ticketSubject');
            const defaultCategoryOptions = [{
                value: '',
                name: 'Select a category',
                disabled: true,
                selected: true,
            }];
            const defaultGuildOption = { value: '', name: 'General', context: '' };
            const priorityLabels = {
                1: 'Low',
                2: 'Medium',
                3: 'High',
                4: 'Urgent',
            };
            const urlParams = new URLSearchParams(window.location.search || '');
            const templateParam = urlParams.get('template') || '';
            const requestedCategorySlug = urlParams.get('category') || (templateParam === 'request-new-game' ? 'game_request' : '');
            const requestedSubject = urlParams.get('subject') || (templateParam === 'request-new-game' ? 'New game request' : '');
            const requestedDescription = urlParams.get('description') || (templateParam === 'request-new-game'
                ? "## New game request\n\n**Game name:**\n**Platform(s):**\n**Why should we add it?**\n**Links or references:**\n"
                : '');
            const ticketMarkdownEditor = window.MarkdownEditor ? window.MarkdownEditor.create({
                textareaId: 'ticketDescription',
                previewId: 'ticketDescriptionPreview',
                writeToggleId: 'descriptionWriteTab',
                previewToggleId: 'descriptionPreviewTab',
            }) : null;
            window.ticketMarkdownEditor = ticketMarkdownEditor;
            const availableServers = <?php
                $serverOptions = array_map(static function ($server) {
                    return [
                        'name' => $server->name ?? 'Unnamed server',
                        'ctime' => $server->ctime ?? '',
                        'crand' => $server->crand ?? '',
                        'gameId' => $server->game_id ?? null,
                        'region' => $server->region ?? '',
                    ];
                }, $servers);
                echo json_encode($serverOptions, JSON_THROW_ON_ERROR);
            ?>;

            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.forEach(tooltipTriggerEl => {
                new bootstrap.Tooltip(tooltipTriggerEl);
            });

            function updatePriorityBadge(value) {
                if (!priorityValueBadge) {
                    return;
                }
                const numericValue = Math.min(4, Math.max(1, Number(value) || 2));
                const label = priorityLabels[numericValue] ?? 'Medium';
                priorityValueBadge.textContent = `${numericValue} (${label})`;
            }

            function applyRequestedCategorySelection() {
                if (!requestedCategorySlug) {
                    return false;
                }

                const matchingOption = Array.from(categorySelect.options).find(option => option.value === requestedCategorySlug);
                if (matchingOption) {
                    categorySelect.value = requestedCategorySlug;
                    return true;
                }

                return false;
            }

            function renderCategoryOptions(options, state = { loading: false, error: '' }) {
                categorySelect.innerHTML = '';
                const optionsToRender = options.length > 0 ? options : defaultCategoryOptions;

                optionsToRender.forEach(option => {
                    const opt = document.createElement('option');
                    opt.value = option.value;
                    opt.textContent = option.name;
                    if (option.disabled) {
                        opt.disabled = true;
                    }
                    if (option.selected) {
                        opt.selected = true;
                    }
                    categorySelect.appendChild(opt);
                });

                const appliedRequested = applyRequestedCategorySelection();
                if (!appliedRequested && categorySelect.options.length > 0 && !categorySelect.value) {
                    const firstEnabledIndex = Array.from(categorySelect.options).findIndex(opt => !opt.disabled);
                    if (firstEnabledIndex >= 0) {
                        categorySelect.selectedIndex = firstEnabledIndex;
                    }
                }

                if (state.loading && categoryStatus) {
                    categoryStatus.textContent = 'Loading categories...';
                } else if (state.error && categoryStatus) {
                    categoryStatus.textContent = state.error;
                } else if (categoryStatus) {
                    categoryStatus.textContent = 'Choose a category so we can route your ticket.';
                }
            }

            async function loadCategories() {
                renderCategoryOptions(defaultCategoryOptions, { loading: true, error: '' });

                try {
                    const response = await fetch('<?= Version::urlBetaPrefix(); ?>/api/v1/tickets/categories/list.php', {
                        method: 'POST',
                        credentials: 'same-origin'
                    });

                    const result = await response.json();
                    if (result.success && Array.isArray(result.data) && result.data.length > 0) {
                        const fetchedOptions = result.data.map(category => ({
                            value: category.slug,
                            name: category.name
                        }));
                        renderCategoryOptions(fetchedOptions, { loading: false, error: '' });
                    } else {
                        renderCategoryOptions(defaultCategoryOptions, { loading: false, error: result.message || 'No categories available.' });
                    }
                } catch (error) {
                    console.error('Failed to load categories', error);
                    renderCategoryOptions([{ value: '', name: 'Unable to load categories.', disabled: true, selected: true }], { loading: false, error: 'Unable to load categories.' });
                }
            }

            function renderGuildOptions(options, state = { loading: false, error: '' }) {
                guildSelect.innerHTML = '';
                options.forEach(option => {
                    const opt = document.createElement('option');
                    opt.value = option.value;
                    opt.textContent = option.name;
                    opt.dataset.name = option.context ?? option.name ?? '';
                    guildSelect.appendChild(opt);
                });

                guildSelect.disabled = !!state.loading;
                if (state.loading && guildStatus) {
                    guildStatus.textContent = 'Loading guilds...';
                } else if (state.error && guildStatus) {
                    guildStatus.textContent = `${state.error} Using defaults.`;
                } else if (guildStatus) {
                    guildStatus.textContent = 'Select a guild ID for context (optional).';
                }
            }

            async function loadGuilds() {
                renderGuildOptions([defaultGuildOption], { loading: true, error: '' });

                try {
                    const response = await fetch('<?= Version::urlBetaPrefix(); ?>/api/v1/guild/list.php');
                    const result = await response.json();

                    if (result.success && Array.isArray(result.data)) {
                        const guildOptions = result.data
                            .map(guild => {
                                const name = guild.name ?? guild.Name ?? '';
                                const id = guild.id ?? guild.Id ?? '';
                                if (!name) {
                                    return null;
                                }
                                return {
                                    value: id !== null ? String(id) : '',
                                    name,
                                    context: name
                                };
                            })
                            .filter(Boolean);

                        const optionsToRender = [defaultGuildOption, ...guildOptions];
                        const errorMessage = guildOptions.length === 0 ? 'No guilds available.' : '';
                        renderGuildOptions(optionsToRender, { loading: false, error: errorMessage });
                    } else {
                        renderGuildOptions([defaultGuildOption], { loading: false, error: result.message || 'Unable to load guilds.' });
                    }
                } catch (error) {
                    console.error('Failed to load guilds', error);
                    renderGuildOptions([defaultGuildOption], { loading: false, error: 'Unable to load guilds.' });
                }
            }

            loadCategories();
            loadGuilds();

            function buildServerLabel(server) {
                const region = server.region ? ` (${server.region})` : '';
                return `${server.name}${region}`;
            }

            function renderServersForGame(gameId) {
                serverSelect.innerHTML = '';
                const placeholder = document.createElement('option');
                placeholder.value = '';
                placeholder.textContent = 'Select a server (optional)';
                serverSelect.appendChild(placeholder);

                const filtered = availableServers.filter(server => {
                    if (!gameId) {
                        return true;
                    }
                    if (server.gameId === null || server.gameId === undefined) {
                        return false;
                    }
                    return String(server.gameId) === String(gameId);
                });

                filtered.forEach(server => {
                    const opt = document.createElement('option');
                    opt.value = `${server.ctime}|${server.crand}`;
                    opt.textContent = buildServerLabel(server);
                    serverSelect.appendChild(opt);
                });

                serverSelect.disabled = filtered.length === 0;
                if (serverStatus) {
                    serverStatus.textContent = filtered.length === 0
                        ? 'No servers available for this game.'
                        : 'Pick a server after selecting a game.';
                }
            }

            function clearServerSelection() {
                serverSelect.value = '';
                serverCtimeHidden.value = '';
                serverCrandHidden.value = '';
            }

            gameSelect.addEventListener('change', () => {
                clearServerSelection();
                renderServersForGame(gameSelect.value || '');
            });

            serverSelect.addEventListener('change', () => {
                const value = serverSelect.value;
                if (!value || !value.includes('|')) {
                    serverCtimeHidden.value = '';
                    serverCrandHidden.value = '';
                    return;
                }
                const [ctime, crand] = value.split('|');
                serverCtimeHidden.value = ctime || '';
                serverCrandHidden.value = crand || '';
            });

            renderServersForGame('');

            if (ticketSubject && requestedSubject) {
                ticketSubject.value = requestedSubject;
            }

            if (descriptionInput && requestedDescription) {
                descriptionInput.value = requestedDescription;
                ticketMarkdownEditor?.updatePreview();
            }

            if (ticketMarkdownEditor) {
                ticketMarkdownEditor.setMode('write');
            }

            if (descriptionInput) {
                descriptionInput.addEventListener('input', () => {
                    ticketMarkdownEditor?.handleInput();
                });
            }
            const priorityDefault = priorityInput?.value ?? priorityHidden?.value ?? 2;
            updatePriorityBadge(priorityDefault);
            if (priorityInput) {
                priorityInput.addEventListener('input', (event) => {
                    updatePriorityBadge(event.target.value);
                });
            } else if (priorityHidden) {
                priorityHidden.value = priorityDefault;
            }

            form.addEventListener('submit', async (event) => {
                event.preventDefault();
                statusEl.classList.remove('text-danger', 'text-success');
                statusEl.textContent = 'Submitting your ticket...';
                confirmation.classList.add('d-none');
                submitBtn.disabled = true;

                const formData = new FormData(form);

                if (!isSteward) {
                    formData.set('priority', '2');
                }

                const priorityValue = Math.min(4, Math.max(1, parseInt(formData.get('priority'), 10) || 2));
                formData.set('priority', String(priorityValue));

                const severityValue = formData.get('severity');
                if (severityValue === '') {
                    formData.delete('severity');
                }

                const guildId = formData.get('guildId');
                if (!guildId || guildId === '') {
                    formData.delete('guildId');
                } else if (Number.isNaN(Number(guildId))) {
                    statusEl.classList.add('text-danger');
                    statusEl.textContent = 'Guild selection must be valid or left blank.';
                    submitBtn.disabled = false;
                    return;
                }

                const gameId = formData.get('gameId');
                if (gameId === '' || gameId === null) {
                    formData.delete('gameId');
                } else if (Number(gameId) <= 0) {
                    statusEl.classList.add('text-danger');
                    statusEl.textContent = 'Game must be selected from the list.';
                    submitBtn.disabled = false;
                    return;
                }

                const serverCtime = formData.get('serverCtime');
                const serverCrand = formData.get('serverCrand');
                if ((serverCtime && !serverCrand) || (!serverCtime && serverCrand)) {
                    statusEl.classList.add('text-danger');
                    statusEl.textContent = 'Please pick a full server selection.';
                    submitBtn.disabled = false;
                    return;
                }

                try {
                    const response = await fetch('<?= Version::urlBetaPrefix(); ?>/api/v1/tickets/create.php', {
                        method: 'POST',
                        body: formData,
                    });

                    const result = await response.json();
                    if (result.success) {
                        statusEl.classList.add('text-success');
                        statusEl.textContent = 'Ticket submitted!';
                        confirmation.classList.remove('d-none');
                        form.reset();
                        clearServerSelection();
                        renderServersForGame('');
                        updatePriorityBadge(priorityInput?.value ?? 2);
                        ticketMarkdownEditor?.setMode('write');
                        ticketMarkdownEditor?.updatePreview();
                    } else {
                        statusEl.classList.add('text-danger');
                        statusEl.textContent = result.message || 'Unable to submit ticket right now.';
                    }
                } catch (error) {
                    console.error('Ticket submission failed', error);
                    statusEl.classList.add('text-danger');
                    statusEl.textContent = 'Unable to submit ticket right now.';
                } finally {
                    submitBtn.disabled = false;
                }
            });
        })();
    </script>
    <style>
        .ticket-form .form-label {
            font-weight: 600;
        }

        .ticket-form .form-control,
        .ticket-form .form-select {
            background: linear-gradient(145deg, #ffffff, #f5f6fa);
            border-color: #dfe6f0;
        }

        .ticket-form textarea {
            resize: vertical;
        }

        .markdown-preview {
            min-height: 180px;
            white-space: pre-wrap;
            overflow-y: auto;
        }
    </style>
</body>

</html>
