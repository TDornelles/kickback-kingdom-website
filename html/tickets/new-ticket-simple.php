<?php
require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__ . '/..') . "/Kickback/init.php");

$session = require(\Kickback\SCRIPT_ROOT . "/api/v1/engine/session/verifySession.php");
require("../php-components/base-page-pull-active-account-info.php");

use Kickback\Common\Version;
use Kickback\Services\Session;
use Kickback\Backend\Controllers\GameController;
use Kickback\Backend\Controllers\ServerController;

$prefilledEmail = '';
if (Session::isLoggedIn()) {
    $account = Session::getCurrentAccount();
    if (!is_null($account) && isset($account->email)) {
        $prefilledEmail = $account->email;
    }
}

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
                            <div class="me-3 text-primary"><i class="fa-solid fa-ticket fa-2x"></i></div>
                            <div>
                                <h2 class="h4 mb-1">Quick Ticket</h2>
                                <p class="mb-0 text-muted">Submit a simple request with the essentials.</p>
                            </div>
                        </div>

                        <div class="alert alert-success d-none" id="ticketConfirmation" role="alert">
                            <i class="fa-solid fa-circle-check me-2"></i>
                            Thanks! Your ticket has been submitted.
                        </div>

                        <?php if ($prefilledEmail === ''): ?>
                            <div class="alert alert-warning" role="alert">
                                <i class="fa-solid fa-circle-info me-2"></i>
                                Please sign in to submit a support ticket.
                            </div>
                        <?php endif; ?>

                        <form id="ticketForm" class="ticket-form" enctype="multipart/form-data">
                            <input type="hidden" name="priority" id="ticketPriorityHidden" value="2">
                            <input type="hidden" name="serverCtime" id="ticketServerCtimeHidden" value="">
                            <input type="hidden" name="serverCrand" id="ticketServerCrandHidden" value="">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="ticketCategory" class="form-label">Category</label>
                                    <select class="form-select" id="ticketCategory" name="category" required>
                                        <option value="bug">Bug</option>
                                        <option value="feature">Feature</option>
                                        <option value="todo">To-do / Task</option>
                                    </select>
                                    <div class="form-text" id="ticketCategoryStatus">Choose a category so we can route your ticket.</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="ticketSubject" class="form-label">Subject</label>
                                    <input type="text" class="form-control" id="ticketSubject" name="subject" maxlength="255" placeholder="Short summary" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="ticketGame" class="form-label">Game</label>
                                    <select class="form-select" id="ticketGame" name="gameId">
                                        <option value="">Select a game (optional)</option>
                                        <?php foreach ($games as $game): ?>
                                            <option value="<?= htmlspecialchars($game->Id); ?>">
                                                <?= htmlspecialchars($game->Name); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="ticketServer" class="form-label">Server</label>
                                    <select class="form-select" id="ticketServer">
                                        <option value="">Select a server (optional)</option>
                                    </select>
                                    <div class="form-text" id="ticketServerStatus">Pick a server after choosing a game.</div>
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
                                </div>
                                <div class="col-md-6">
                                    <label for="ticketGuild" class="form-label">Guild Context</label>
                                    <select class="form-select" id="ticketGuild" name="guildId">
                                        <option value="">General</option>
                                    </select>
                                    <div class="form-text" id="ticketGuildStatus">Select a guild by ID for context (optional).</div>
                                </div>
                                <div class="col-12">
                                    <label for="ticketDescription" class="form-label">Description</label>
                                    <textarea class="form-control" id="ticketDescription" name="description" rows="6" maxlength="5000" placeholder="Share details about the issue." required></textarea>
                                </div>
                                <div class="col-md-6">
                                    <label for="ticketEmail" class="form-label">Contact Email</label>
                                    <input type="email" class="form-control" id="ticketEmail" value="<?= htmlspecialchars($prefilledEmail); ?>" disabled readonly>
                                    <div class="form-text">We'll use your account email for updates.</div>
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
    <script>
        (function() {
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
            const priorityHidden = document.getElementById('ticketPriorityHidden');
            const defaultCategoryOptions = Array.from(categorySelect.options).map(option => ({
                value: option.value,
                name: option.textContent
            }));
            const defaultGuildOption = { value: '', name: 'General', context: '' };
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

            function renderCategoryOptions(options, state = { loading: false, error: '' }) {
                categorySelect.innerHTML = '';
                options.forEach(option => {
                    const opt = document.createElement('option');
                    opt.value = option.value;
                    opt.textContent = option.name;
                    categorySelect.appendChild(opt);
                });

                if (state.loading && categoryStatus) {
                    categoryStatus.textContent = 'Loading categories...';
                } else if (state.error && categoryStatus) {
                    categoryStatus.textContent = `${state.error} Using defaults.`;
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
                    renderCategoryOptions(defaultCategoryOptions, { loading: false, error: 'Unable to load categories.' });
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
                        : 'Pick a server after choosing a game.';
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

            loadCategories();
            loadGuilds();
            renderServersForGame('');

            form.addEventListener('submit', async (event) => {
                event.preventDefault();
                statusEl.classList.remove('text-danger', 'text-success');
                statusEl.textContent = 'Submitting your ticket...';
                confirmation.classList.add('d-none');
                submitBtn.disabled = true;

                const formData = new FormData(form);
                const priorityValue = Math.min(4, Math.max(1, parseInt(priorityHidden.value, 10) || 2));
                formData.set('priority', String(priorityValue));

                const severityValue = formData.get('severity');
                if (severityValue === '') {
                    formData.delete('severity');
                }

                const guildId = formData.get('guildId');
                if (!guildId || guildId === '') {
                    formData.delete('guildId');
                } else if (Number(guildId) <= 0) {
                    statusEl.classList.add('text-danger');
                    statusEl.textContent = 'Guild must be selected by a valid ID.';
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
    </style>
</body>

</html>
