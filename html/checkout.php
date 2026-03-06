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

                <script src="/api/v2/client/js/store-client.js"></script>
                <script>
                    const storeLocator = sessionStorage.getItem('storeLocator');

                    const checkoutButton = document.getElementById('checkout-button');
                    const errorMessage = document.getElementById('error-message');
                    const buttonText = document.getElementById('button-text');

                    if (storeLocator) {
                        loadCart();
                    } else {
                        showError('No store found. Please add items to your cart first.');
                        buttonText.textContent = 'No Cart Found';
                    }

                    async function loadCart() {
                        try {
                            const cartResponse = await StoreClient.getCart(storeLocator);
                            const cart = cartResponse.data;

                            if (!cart || !cart.cartProducts || cart.cartProducts.length === 0) {
                                showError('Your cart is empty.');
                                buttonText.textContent = 'Cart Empty';
                                return;
                            }

                            // Render cart items
                            let itemsHtml = '<ul class="list-group">';
                            cart.cartProducts.forEach(item => {
                                const productName = item.product?.name || 'Product';
                                let priceText = '';
                                if (item.product?.price) {
                                    item.product.price.forEach(pc => {
                                        if (pc.currencyCode) {
                                            priceText += `$${(pc.amount / 100).toFixed(2)} ${pc.currencyCode} `;
                                        } else if (pc.item) {
                                            priceText += `${pc.amount} ${pc.item.name || 'items'} `;
                                        }
                                    });
                                }
                                itemsHtml += `<li class="list-group-item d-flex justify-content-between align-items-center">
                                    ${productName}
                                    <span class="badge bg-primary rounded-pill">${priceText.trim()}</span>
                                </li>`;
                            });
                            itemsHtml += '</ul>';

                            // Render totals
                            let totalsHtml = '<h5>Totals</h5><ul class="list-group">';
                            if (cart.totals) {
                                cart.totals.forEach(total => {
                                    if (total.currencyCode) {
                                        totalsHtml += `<li class="list-group-item d-flex justify-content-between">
                                            <span>${total.currencyCode}</span>
                                            <strong>$${(total.amount / 100).toFixed(2)}</strong>
                                        </li>`;
                                    } else if (total.item) {
                                        totalsHtml += `<li class="list-group-item d-flex justify-content-between">
                                            <span>${total.item.name || 'Items'}</span>
                                            <strong>${total.amount}</strong>
                                        </li>`;
                                    }
                                });
                            }
                            totalsHtml += '</ul>';

                            document.getElementById('cart-items-container').innerHTML = itemsHtml;
                            document.getElementById('cart-totals-container').innerHTML = totalsHtml;
                            checkoutButton.disabled = false;
                            buttonText.textContent = 'Proceed to Checkout';
                        } catch (error) {
                            showError('Error loading cart: ' + error.message);
                        }
                    }

                    checkoutButton.addEventListener('click', async () => {
                        checkoutButton.disabled = true;
                        buttonText.textContent = 'Processing...';

                        try {
                            const result = await StoreClient.initiateCheckout(storeLocator);
                            const checkoutData = result.data;

                            if (checkoutData.requiresPayment) {
                                // USD purchase — redirect to Stripe hosted checkout
                                buttonText.textContent = 'Redirecting to payment...';
                                window.location.href = checkoutData.redirectUrl;
                            } else {
                                // Loot-only purchase — completed immediately
                                sessionStorage.removeItem('storeLocator');
                                window.location.href = '/checkout-success.php';
                            }
                        } catch (error) {
                            showError(error.message);
                            checkoutButton.disabled = false;
                            buttonText.textContent = 'Proceed to Checkout';
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
