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
                $activePageName = "Stripe Purchase";
                require("php-components/base-page-breadcrumbs.php"); 
                ?>

                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">Test Stripe Purchase</h4>
                    </div>
                    <div class="card-body">
                        <!-- Product Selection -->
                        <div class="mb-4">
                            <h5>Select Product</h5>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <div class="card product-card" data-name="Epic Sword" data-price="1999" data-currency="usd">
                                        <div class="card-body text-center">
                                            <h6>Epic Sword</h6>
                                            <p class="text-muted">Legendary weapon</p>
                                            <p class="h5 text-primary">$19.99</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="card product-card" data-name="Health Potion" data-price="499" data-currency="usd">
                                        <div class="card-body text-center">
                                            <h6>Health Potion</h6>
                                            <p class="text-muted">Restore health</p>
                                            <p class="h5 text-primary">$4.99</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="card product-card" data-name="Magic Scroll" data-price="999" data-currency="usd">
                                        <div class="card-body text-center">
                                            <h6>Magic Scroll</h6>
                                            <p class="text-muted">Cast spells</p>
                                            <p class="h5 text-primary">$9.99</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Payment Form -->
                        <div id="paymentForm" style="display: none;">
                            <h5>Payment Details</h5>
                            <div class="mb-3">
                                <label class="form-label">Selected Product</label>
                                <div id="selectedProduct" class="form-control-plaintext fw-bold"></div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Amount</label>
                                <div id="selectedAmount" class="form-control-plaintext fw-bold"></div>
                            </div>
                            
                            <!-- Stripe Elements -->
                            <div class="mb-3">
                                <label class="form-label">Card Details</label>
                                <div id="card-element" class="form-control p-3">
                                    <!-- Stripe Elements will create form elements here -->
                                </div>
                                <div id="card-errors" class="text-danger mt-2"></div>
                            </div>

                            <button id="submit-payment" class="btn btn-primary w-100">
                                <span id="button-text">Pay Now</span>
                                <span id="spinner" class="spinner-border spinner-border-sm ms-2" style="display: none;"></span>
                            </button>
                        </div>

                        <!-- Payment Status -->
                        <div id="paymentStatus" class="mt-4" style="display: none;">
                            <div class="alert" id="statusAlert">
                                <h6 id="statusTitle"></h6>
                                <p id="statusMessage"></p>
                                <div id="paymentDetails" style="display: none;">
                                    <small class="text-muted">
                                        Payment ID: <span id="paymentId"></span><br>
                                        Status: <span id="paymentStatusText"></span>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php require("php-components/base-page-discord.php"); ?>
        </div>
        <?php require("php-components/base-page-footer.php"); ?>
    </main>

    <?php require("php-components/base-page-javascript.php"); ?>
    
    <!-- Stripe.js -->
    <script src="https://js.stripe.com/v3/"></script>
    
    <script>
        // Initialize Stripe
        const stripe = Stripe('<?php echo \Kickback\Services\StripeService::getPublishableKey(); ?>');
        const elements = stripe.elements();
        
        // Create card element
        const cardElement = elements.create('card', {
            style: {
                base: {
                    fontSize: '16px',
                    color: '#424770',
                    '::placeholder': {
                        color: '#aab7c4',
                    },
                },
            },
        });
        
        cardElement.mount('#card-element');
        
        // Handle card element changes
        cardElement.on('change', function(event) {
            const displayError = document.getElementById('card-errors');
            if (event.error) {
                displayError.textContent = event.error.message;
            } else {
                displayError.textContent = '';
            }
        });
        
        // Product selection
        let selectedProduct = null;
        document.querySelectorAll('.product-card').forEach(card => {
            card.addEventListener('click', function() {
                // Remove previous selection
                document.querySelectorAll('.product-card').forEach(c => c.classList.remove('border-primary'));
                
                // Add selection to clicked card
                this.classList.add('border-primary');
                
                // Store selected product data
                selectedProduct = {
                    name: this.dataset.name,
                    price: parseInt(this.dataset.price),
                    currency: this.dataset.currency
                };
                
                // Show payment form
                document.getElementById('selectedProduct').textContent = selectedProduct.name;
                document.getElementById('selectedAmount').textContent = 
                    `$${(selectedProduct.price / 100).toFixed(2)} ${selectedProduct.currency.toUpperCase()}`;
                document.getElementById('paymentForm').style.display = 'block';
            });
        });
        
        // Payment submission
        document.getElementById('submit-payment').addEventListener('click', async function() {
            if (!selectedProduct) {
                alert('Please select a product first');
                return;
            }
            
            const button = this;
            const buttonText = document.getElementById('button-text');
            const spinner = document.getElementById('spinner');
            
            // Show loading state
            button.disabled = true;
            buttonText.textContent = 'Processing...';
            spinner.style.display = 'inline-block';
            
            try {
                // Create payment intent
                const response = await fetch('/api/v2/payments/create-payment-intent.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        amount: selectedProduct.price,
                        currency: selectedProduct.currency,
                        product_name: selectedProduct.name
                    })
                });
                
                const { client_secret, payment_id } = await response.json();
                
                if (!response.ok) {
                    throw new Error(client_secret || 'Failed to create payment intent');
                }
                
                // Confirm payment
                const { error, paymentIntent } = await stripe.confirmCardPayment(client_secret, {
                    payment_method: {
                        card: cardElement,
                    }
                });
                
                if (error) {
                    throw new Error(error.message);
                }
                
                // Payment succeeded
                showPaymentStatus('success', 'Payment Successful!', 
                    `Your payment of $${(selectedProduct.price / 100).toFixed(2)} has been processed successfully.`, 
                    payment_id, paymentIntent.status);
                
                // Start polling for webhook updates
                pollPaymentStatus(payment_id);
                
            } catch (error) {
                showPaymentStatus('danger', 'Payment Failed', error.message);
            } finally {
                // Reset button state
                button.disabled = false;
                buttonText.textContent = 'Pay Now';
                spinner.style.display = 'none';
            }
        });
        
        function showPaymentStatus(type, title, message, paymentId = null, status = null) {
            const statusDiv = document.getElementById('paymentStatus');
            const alert = document.getElementById('statusAlert');
            const titleEl = document.getElementById('statusTitle');
            const messageEl = document.getElementById('statusMessage');
            const detailsDiv = document.getElementById('paymentDetails');
            const paymentIdEl = document.getElementById('paymentId');
            const statusEl = document.getElementById('paymentStatusText');
            
            alert.className = `alert alert-${type}`;
            titleEl.textContent = title;
            messageEl.textContent = message;
            
            if (paymentId) {
                paymentIdEl.textContent = paymentId;
                statusEl.textContent = status || 'Processing...';
                detailsDiv.style.display = 'block';
            } else {
                detailsDiv.style.display = 'none';
            }
            
            statusDiv.style.display = 'block';
        }
        
        // Poll for webhook updates (runtime tracking)
        function pollPaymentStatus(paymentId) {
            const maxAttempts = 30; // 5 minutes max
            let attempts = 0;
            
            const poll = async () => {
                try {
                    const response = await fetch(`/api/v2/payments/check-status.php?payment_id=${paymentId}`, {
                        credentials: 'same-origin'
                    });
                    
                    const data = await response.json();
                    
                    if (data.status === 'succeeded') {
                        showPaymentStatus('success', 'Payment Confirmed!', 
                            'Your payment has been confirmed via webhook.', 
                            paymentId, data.status);
                        return;
                    } else if (data.status === 'failed') {
                        showPaymentStatus('danger', 'Payment Failed', 
                            'Your payment failed and was not processed.', 
                            paymentId, data.status);
                        return;
                    }
                    
                    // Continue polling if still processing
                    attempts++;
                    if (attempts < maxAttempts) {
                        setTimeout(poll, 10000); // Poll every 10 seconds
                    } else {
                        showPaymentStatus('warning', 'Status Unknown', 
                            'Payment status could not be confirmed. Please check your account or contact support.', 
                            paymentId, 'unknown');
                    }
                    
                } catch (error) {
                    console.error('Error polling payment status:', error);
                    attempts++;
                    if (attempts < maxAttempts) {
                        setTimeout(poll, 10000);
                    }
                }
            };
            
            // Start polling after 5 seconds
            setTimeout(poll, 5000);
        }
    </script>
    
    <style>
        .product-card {
            cursor: pointer;
            transition: all 0.2s;
        }
        .product-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .product-card.border-primary {
            border-width: 2px !important;
        }
        #card-element {
            min-height: 50px;
        }
    </style>
</body>
</html>