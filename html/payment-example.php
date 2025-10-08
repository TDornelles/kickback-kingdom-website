<?php
require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__) . "/Kickback/init.php");

$session = require(\Kickback\SCRIPT_ROOT . "/api/v1/engine/session/verifySession.php");
require("php-components/base-page-pull-active-account-info.php");
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
                $activePageName = "Payment Example";
                require("php-components/base-page-breadcrumbs.php");
                ?>

                <div class="card">
                    <div class="card-header">
                        <h3>Stripe Payment Element Example</h3>
                    </div>
                    <div class="card-body">
                        <p>This is a demonstration of the embedded Stripe Payment Element.</p>

                        <form id="payment-form">
                            <div class="mb-3">
                                <label for="amount" class="form-label">Amount ($)</label>
                                <input type="number" class="form-control" id="amount" value="19.99" step="0.01" min="0.50">
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <input type="text" class="form-control" id="description" value="Test Payment">
                            </div>

                            <!-- Payment Element container -->
                            <div id="payment-element" class="mb-3">
                                <!-- Stripe Payment Element will be inserted here -->
                            </div>

                            <button type="button" id="submit-payment" class="btn btn-primary">
                                Pay Now
                            </button>

                            <div id="payment-message" class="alert mt-3" style="display: none;"></div>
                        </form>
                    </div>
                </div>

            </div>

            <?php require("php-components/base-page-discord.php"); ?>
        </div>
        <?php require("php-components/base-page-footer.php"); ?>
    </main>

    <?php require("php-components/base-page-javascript.php"); ?>

    <!-- Load Stripe.js -->
    <script src="https://js.stripe.com/v3"></script>

    <!-- Load Payment Element Helper -->
    <script src="/assets/js/stripe-payment-element.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', async function() {
            const amountInput = document.getElementById('amount');
            const descriptionInput = document.getElementById('description');
            const submitButton = document.getElementById('submit-payment');
            const messageContainer = document.getElementById('payment-message');

            let paymentElementInstance = null;

            // Function to initialize payment element
            async function initializePayment() {
                const amountInCents = Math.round(parseFloat(amountInput.value) * 100);
                const description = descriptionInput.value;

                try {
                    paymentElementInstance = await StripePaymentElement.init({
                        amount: amountInCents,
                        currency: 'USD',
                        description: description,
                        metadata: {
                            example: 'true'
                        },
                        onSuccess: function(paymentIntent) {
                            showMessage('Payment successful! Payment ID: ' + paymentIntent.id, 'success');
                            console.log('Payment succeeded:', paymentIntent);
                        },
                        onError: function(error) {
                            showMessage('Payment failed: ' + error.message, 'danger');
                            console.error('Payment error:', error);
                        },
                        onReady: function() {
                            console.log('Payment Element is ready');
                        }
                    });

                    // Mount the element
                    paymentElementInstance.mount();

                } catch (error) {
                    showMessage('Failed to initialize payment: ' + error.message, 'danger');
                    console.error('Initialization error:', error);
                }
            }

            // Function to show messages
            function showMessage(text, type = 'info') {
                messageContainer.textContent = text;
                messageContainer.className = 'alert alert-' + type + ' mt-3';
                messageContainer.style.display = 'block';
            }

            // Initialize on page load
            initializePayment();
        });
    </script>

</body>

</html>
