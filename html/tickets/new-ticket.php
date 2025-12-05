<?php
require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__ . '/..') . "/Kickback/init.php");

$session = require(\Kickback\SCRIPT_ROOT . "/api/v1/engine/session/verifySession.php");
require("../php-components/base-page-pull-active-account-info.php");

use Kickback\Common\Version;
use Kickback\Services\Session;

$prefilledEmail = '';
if (Session::isLoggedIn()) {
    $account = Session::getCurrentAccount();
    if (!is_null($account) && isset($account->email)) {
        $prefilledEmail = $account->email;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<?php require("../php-components/base-page-head.php"); ?>

<body class="bg-body-secondary container p-0">
    <?php require("../php-components/base-page-components.php"); ?>

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

                        <form id="ticketForm" class="ticket-form" enctype="multipart/form-data">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="ticketCategory" class="form-label">Category</label>
                                    <select class="form-select" id="ticketCategory" name="category" required>
                                        <option value="bug">Bug</option>
                                        <option value="feature">Feature</option>
                                        <option value="todo">To-do / Task</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="ticketPriority" class="form-label">Priority</label>
                                    <select class="form-select" id="ticketPriority" name="priority" required>
                                        <option value="medium" selected>Medium</option>
                                        <option value="low">Low</option>
                                        <option value="high">High</option>
                                        <option value="urgent">Urgent</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="ticketSubject" class="form-label">Subject</label>
                                    <input type="text" class="form-control" id="ticketSubject" name="subject" maxlength="255" placeholder="Short summary" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="ticketGuild" class="form-label">Guild Context</label>
                                    <select class="form-select" id="ticketGuild" name="guildContext">
                                        <option value="">General</option>
                                        <option value="Adventurers Guild">Adventurers Guild</option>
                                        <option value="Merchants Guild">Merchants Guild</option>
                                        <option value="Craftsmen's Guild">Craftsmen's Guild</option>
                                        <option value="Stewards Guild">Stewards Guild</option>
                                        <option value="Lich Studies">Lich & Card Games</option>
                                        <option value="Events & Community">Events & Community</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label for="ticketDescription" class="form-label">Description</label>
                                    <textarea class="form-control" id="ticketDescription" name="description" rows="6" maxlength="5000" placeholder="Share steps to reproduce, expected behavior, links, or extra context." required></textarea>
                                </div>
                                <div class="col-md-6">
                                    <label for="ticketEmail" class="form-label">Contact Email</label>
                                    <input type="email" class="form-control" id="ticketEmail" name="contactEmail" maxlength="255" value="<?= htmlspecialchars($prefilledEmail); ?>" required>
                                    <div class="form-text">We'll use this email for updates on your request.</div>
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

            form.addEventListener('submit', async (event) => {
                event.preventDefault();
                statusEl.classList.remove('text-danger', 'text-success');
                statusEl.textContent = 'Submitting your ticket...';
                confirmation.classList.add('d-none');
                submitBtn.disabled = true;

                const formData = new FormData(form);

                try {
                    const response = await fetch('<?= Version::urlBetaPrefix(); ?>/api/v1/support/createTicket.php', {
                        method: 'POST',
                        body: formData,
                    });

                    const result = await response.json();
                    if (result.success) {
                        statusEl.classList.add('text-success');
                        statusEl.textContent = 'Ticket submitted!';
                        confirmation.classList.remove('d-none');
                        form.reset();
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
