/**
 * Stripe Payment Element Helper
 *
 * This module provides a simple interface for embedding Stripe's Payment Element
 * on any page. It handles initialization, payment processing, and error handling.
 *
 * Usage:
 * 1. Include Stripe.js in your page: <script src="https://js.stripe.com/v3"></script>
 * 2. Include this script: <script src="/assets/js/stripe-payment-element.js"></script>
 * 3. Create a container element: <div id="payment-element"></div>
 * 4. Initialize with: StripePaymentElement.init({ amount: 1999, onSuccess: callback })
 */

const StripePaymentElement = (function() {
    'use strict';

    let stripe = null;
    let elements = null;
    let paymentElement = null;

    /**
     * Initialize the Payment Element
     *
     * @param {Object} options - Configuration options
     * @param {number} options.amount - Amount in cents (required)
     * @param {string} [options.currency='USD'] - Currency code
     * @param {string} [options.description] - Payment description
     * @param {Object} [options.metadata] - Additional metadata
     * @param {string} [options.containerSelector='#payment-element'] - CSS selector for payment element container
     * @param {string} [options.submitButtonSelector='#submit-payment'] - CSS selector for submit button
     * @param {Function} [options.onSuccess] - Callback when payment succeeds
     * @param {Function} [options.onError] - Callback when payment fails
     * @param {Function} [options.onReady] - Callback when element is ready
     * @returns {Promise<Object>} - Returns object with mount and submit methods
     */
    async function init(options = {}) {
        // Validate required options
        if (!options.amount || options.amount <= 0) {
            throw new Error('Amount is required and must be greater than 0');
        }

        // Check if Stripe.js is loaded
        if (typeof Stripe === 'undefined') {
            throw new Error('Stripe.js is not loaded. Include <script src="https://js.stripe.com/v3"></script>');
        }

        // Set defaults
        const config = {
            currency: options.currency || 'USD',
            description: options.description || '',
            metadata: options.metadata || {},
            containerSelector: options.containerSelector || '#payment-element',
            submitButtonSelector: options.submitButtonSelector || '#submit-payment',
            onSuccess: options.onSuccess || function() {},
            onError: options.onError || function(error) { console.error('Payment error:', error); },
            onReady: options.onReady || function() {},
            ...options
        };

        try {
            // Fetch publishable key from server
            const keyResponse = await fetch('/api/v2/payments/get-publishable-key.php', {
                method: 'GET',
                credentials: 'include'
            });

            if (!keyResponse.ok) {
                throw new Error('Failed to fetch Stripe publishable key');
            }

            const keyData = await keyResponse.json();
            if (!keyData.success) {
                throw new Error(keyData.message || 'Failed to get publishable key');
            }

            // Initialize Stripe
            stripe = Stripe(keyData.data.publishable_key);

            // Create PaymentIntent
            const intentResponse = await fetch('/api/v2/payments/create-payment-intent.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                credentials: 'include',
                body: JSON.stringify({
                    amount: config.amount,
                    currency: config.currency,
                    description: config.description,
                    metadata: config.metadata
                })
            });

            if (!intentResponse.ok) {
                const errorData = await intentResponse.json();
                throw new Error(errorData.message || 'Failed to create payment intent');
            }

            const intentData = await intentResponse.json();
            if (!intentData.success) {
                throw new Error(intentData.message || 'Failed to create payment intent');
            }

            const clientSecret = intentData.data.client_secret;

            // Create Elements instance
            elements = stripe.elements({
                clientSecret: clientSecret,
                appearance: {
                    theme: 'stripe',
                    variables: {
                        colorPrimary: '#0570de',
                    }
                }
            });

            // Create Payment Element
            paymentElement = elements.create('payment');

            // Set up form submission handler
            const submitButton = document.querySelector(config.submitButtonSelector);
            if (submitButton) {
                submitButton.addEventListener('click', async (e) => {
                    e.preventDefault();
                    await handleSubmit(config);
                });
            }

            // Call onReady callback
            paymentElement.on('ready', () => {
                config.onReady();
            });

            return {
                mount: () => mountElement(config.containerSelector),
                submit: () => handleSubmit(config),
                destroy: destroy
            };

        } catch (error) {
            config.onError(error);
            throw error;
        }
    }

    /**
     * Mount the payment element to the DOM
     * @param {string} selector - CSS selector for container
     */
    function mountElement(selector) {
        if (!paymentElement) {
            throw new Error('Payment element not initialized. Call init() first.');
        }

        const container = document.querySelector(selector);
        if (!container) {
            throw new Error(`Container element not found: ${selector}`);
        }

        paymentElement.mount(selector);
    }

    /**
     * Handle payment submission
     * @param {Object} config - Configuration object
     */
    async function handleSubmit(config) {
        if (!stripe || !elements) {
            throw new Error('Stripe not initialized');
        }

        // Disable submit button
        const submitButton = document.querySelector(config.submitButtonSelector);
        if (submitButton) {
            submitButton.disabled = true;
            submitButton.textContent = 'Processing...';
        }

        try {
            // Confirm payment
            const { error, paymentIntent } = await stripe.confirmPayment({
                elements,
                confirmParams: {
                    return_url: window.location.origin + '/payment-success',
                },
                redirect: 'if_required'
            });

            if (error) {
                // Payment failed
                config.onError(error);
            } else if (paymentIntent && paymentIntent.status === 'succeeded') {
                // Payment succeeded
                config.onSuccess(paymentIntent);
            } else {
                // Handle other statuses
                config.onError({ message: 'Payment status: ' + (paymentIntent?.status || 'unknown') });
            }
        } catch (error) {
            config.onError(error);
        } finally {
            // Re-enable submit button
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.textContent = 'Pay Now';
            }
        }
    }

    /**
     * Destroy the payment element and clean up
     */
    function destroy() {
        if (paymentElement) {
            paymentElement.destroy();
            paymentElement = null;
        }
        elements = null;
        stripe = null;
    }

    // Public API
    return {
        init: init
    };
})();
