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
                    <div class="card-header bg-success text-white">
                        <h3>Payment Successful!</h3>
                    </div>
                    <div class="card-body text-center">
                        <div class="mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" fill="currentColor" class="bi bi-check-circle-fill text-success" viewBox="0 0 16 16">
                                <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/>
                            </svg>
                        </div>

                        <h4>Thank you for your purchase!</h4>
                        <p class="text-muted">Your payment has been processed successfully.</p>

                        <?php if ($sessionId): ?>
                            <p class="small text-muted">Session ID: <?php echo htmlspecialchars($sessionId); ?></p>
                        <?php endif; ?>

                        <div class="mt-4">
                            <a href="/market.php" class="btn btn-primary">Continue Shopping</a>
                            <a href="/" class="btn btn-secondary">Return to Home</a>
                        </div>
                    </div>
                </div>

                <script>
                    // Clear cart from session storage after successful purchase
                    sessionStorage.removeItem('cartCtime');
                    sessionStorage.removeItem('cartCrand');
                </script>

            </div>

            <?php require("php-components/base-page-discord.php"); ?>
        </div>
        <?php require("php-components/base-page-footer.php"); ?>
    </main>


    <?php require("php-components/base-page-javascript.php"); ?>

</body>

</html>
