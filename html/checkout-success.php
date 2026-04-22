<?php
require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__) . "/Kickback/init.php");

$session = require(\Kickback\SCRIPT_ROOT . "/api/v1/engine/session/verifySession.php");
require("php-components/base-page-pull-active-account-info.php");

// Get session ID from URL
$sessionId = $_GET['session_id'] ?? null;
?>

<!DOCTYPE html>
<html lang="en">

<?php require("php-components/base-page-head.php"); ?>

<body class="bg-body-secondary container p-0">

    <?php

    require("php-components/base-page-components.php");

    require("php-components/ad-carousel.php");

    ?>

    <!--MAIN CONTENT-->
    <main class="container pt-3 bg-body" style="margin-bottom: 56px;">
        <div class="row">
            <div class="col-12 col-xl-9">

                <?php
                $activePageName = "Checkout Success";
                require("php-components/base-page-breadcrumbs.php");
                ?>

                <div class="card">
                    <div id="status-card" class="card-header bg-warning text-dark">
                        <h3 id="status-title">Processing your order...</h3>
                    </div>
                    <div class="card-body text-center">
                        <div id="status-icon" class="mb-4">
                            <div class="spinner-border text-warning" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>

                        <h4 id="status-heading">Please wait while we confirm your purchase.</h4>
                        <p id="status-message" class="text-muted">This may take a few moments...</p>

                        <?php if ($sessionId): ?>
                            <p class="small text-muted">Session ID: <?php echo htmlspecialchars($sessionId); ?></p>
                        <?php endif; ?>

                        <div id="action-buttons" class="mt-4" style="display: none;">
                            <a href="/market.php" class="btn btn-primary">Continue Shopping</a>
                            <a href="/" class="btn btn-secondary">Return to Home</a>
                        </div>
                    </div>
                </div>

                <script src="/api/v2/client/js/store-client.js"></script>
                <script>
                    const storeLocator = sessionStorage.getItem('storeLocator');
                    let pollCount = 0;
                    const maxPolls = 20;

                    function showSuccess() {
                        document.getElementById('status-card').className = 'card-header bg-success text-white';
                        document.getElementById('status-title').textContent = 'Payment Successful!';
                        document.getElementById('status-icon').innerHTML = `
                            <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" fill="currentColor" class="bi bi-check-circle-fill text-success" viewBox="0 0 16 16">
                                <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/>
                            </svg>`;
                        document.getElementById('status-heading').textContent = 'Thank you for your purchase!';
                        document.getElementById('status-message').textContent = 'Your order has been processed successfully.';
                        document.getElementById('action-buttons').style.display = 'block';

                        sessionStorage.removeItem('storeLocator');
                    }

                    function showError(message) {
                        document.getElementById('status-card').className = 'card-header bg-danger text-white';
                        document.getElementById('status-title').textContent = 'Checkout Issue';
                        document.getElementById('status-icon').innerHTML = `
                            <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" fill="currentColor" class="bi bi-exclamation-circle-fill text-danger" viewBox="0 0 16 16">
                                <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM8 4a.905.905 0 0 0-.9.995l.35 3.507a.552.552 0 0 0 1.1 0l.35-3.507A.905.905 0 0 0 8 4zm.002 6a1 1 0 1 0 0 2 1 1 0 0 0 0-2z"/>
                            </svg>`;
                        document.getElementById('status-heading').textContent = 'Something went wrong';
                        document.getElementById('status-message').textContent = message;
                        document.getElementById('action-buttons').style.display = 'block';
                    }

                    async function pollCheckoutStatus() {
                        if (!storeLocator) {
                            // No store locator — this was likely a loot-only checkout
                            showSuccess();
                            return;
                        }

                        pollCount++;
                        if (pollCount > maxPolls) {
                            showError('We could not confirm your payment status. If you were charged, your order will be processed automatically. Please contact support if you need help.');
                            return;
                        }

                        try {
                            const result = await StoreClient.getCheckoutStatus(storeLocator);
                            const statusData = result.data;

                            if (statusData.checkedOut || statusData.status === 'completed') {
                                showSuccess();
                            } else if (statusData.status === 'paid') {
                                // Payment received but checkout not yet processed by webhook
                                // Keep polling — the webhook should process it shortly
                                setTimeout(pollCheckoutStatus, 2000);
                            } else if (statusData.status === 'unpaid' || statusData.status === 'no_session') {
                                showError('Payment was not completed. Please try again.');
                            } else {
                                // Still processing
                                setTimeout(pollCheckoutStatus, 2000);
                            }
                        } catch (error) {
                            console.error('Error polling checkout status:', error);
                            setTimeout(pollCheckoutStatus, 3000);
                        }
                    }

                    // Start polling
                    pollCheckoutStatus();
                </script>

            </div>

            <?php require("php-components/base-page-discord.php"); ?>
        </div>
        <?php require("php-components/base-page-footer.php"); ?>
    </main>


    <?php require("php-components/base-page-javascript.php"); ?>

</body>

</html>
