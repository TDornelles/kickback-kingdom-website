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


                $activePageName = "Checkout";
                require("php-components/base-page-breadcrumbs.php");

                ?>

                <div class="card">
                    <div class="card-header">
                        <h3>Checkout</h3>
                    </div>
                    <div class="card-body">
                        <div id="cart-items-container">
                            <p class="text-center">Loading cart...</p>
                        </div>

                        <div id="cart-totals-container" class="mt-3">
                        </div>

                        <div class="mt-4 d-grid">
                            <button id="checkout-button" class="btn btn-primary btn-lg" disabled>
                                <span id="button-text">Loading...</span>
                            </button>
                        </div>
                        <div id="error-message" class="alert alert-danger mt-3" style="display: none;"></div>
                    </div>
                </div>

                <script src="https://js.stripe.com/v3/"></script>
                <script>
                    // Initialize Stripe
                    const stripe = Stripe('<?php echo \Kickback\Backend\Controllers\StripeController::publicKey(); ?>');

                    // Get cart ID from session storage or URL
                    const cartCtime = sessionStorage.getItem('cartCtime');
                    const cartCrand = sessionStorage.getItem('cartCrand');

                    const checkoutButton = document.getElementById('checkout-button');
                    const errorMessage = document.getElementById('error-message');
                    const buttonText = document.getElementById('button-text');

                    // Load cart details
                    if (cartCtime && cartCrand) {
                        loadCart();
                    } else {
                        showError('No cart found. Please add items to your cart first.');
                        buttonText.textContent = 'No Cart Found';
                    }

                    function loadCart() {
                        fetch('/php-components/store/get-cart.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: `cartCtime=${cartCtime}&cartCrand=${cartCrand}`
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                document.getElementById('cart-items-container').innerHTML = data.data.cartHtml;
                                document.getElementById('cart-totals-container').innerHTML = data.data.totalsHtml;
                                checkoutButton.disabled = false;
                                buttonText.textContent = 'Proceed to Payment';
                            } else {
                                showError('Error loading cart: ' + data.message);
                            }
                        })
                        .catch(error => {
                            showError('Error loading cart: ' + error.message);
                        });
                    }

                    checkoutButton.addEventListener('click', async () => {
                        checkoutButton.disabled = true;
                        buttonText.textContent = 'Creating checkout session...';

                        try {
                            const response = await fetch('/php-components/Stripe/create-checkout-session.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded',
                                },
                                body: `cartCtime=${cartCtime}&cartCrand=${cartCrand}`
                            });

                            const data = await response.json();

                            if (data.success) {
                                // Redirect to Stripe Checkout
                                const result = await stripe.redirectToCheckout({
                                    sessionId: data.data.sessionId
                                });

                                if (result.error) {
                                    showError(result.error.message);
                                    checkoutButton.disabled = false;
                                    buttonText.textContent = 'Proceed to Payment';
                                }
                            } else {
                                showError(data.message);
                                checkoutButton.disabled = false;
                                buttonText.textContent = 'Proceed to Payment';
                            }
                        } catch (error) {
                            showError('Error: ' + error.message);
                            checkoutButton.disabled = false;
                            buttonText.textContent = 'Proceed to Payment';
                        }
                    });

                    function showError(message) {
                        errorMessage.textContent = message;
                        errorMessage.style.display = 'block';
                    }
                </script>

            </div>
            
            <?php require("php-components/base-page-discord.php"); ?>
        </div>
        <?php require("php-components/base-page-footer.php"); ?>
    </main>

    
    <?php require("php-components/base-page-javascript.php"); ?>

</body>

</html>
