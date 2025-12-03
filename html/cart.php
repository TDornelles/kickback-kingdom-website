<?php
require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__) . "/Kickback/init.php");

$session = require(\Kickback\SCRIPT_ROOT . "/api/v1/engine/session/verifySession.php");
require("php-components/base-page-pull-active-account-info.php");

use \Kickback\Common\Version;
use \Kickback\Services\Session;

$locator = $_GET["store-locator"] ?? "kickback-market";
$isLoggedIn = Session::isLoggedIn();
?>

<!DOCTYPE html>
<html lang="en">

<?php require("php-components/base-page-head.php"); ?>

<body class="bg-body-secondary container p-0">

    <?php
    require("php-components/base-page-components.php");
    require("php-components/ad-carousel.php");
    ?>

    <main class="container py-4" id="cart-root" data-store-locator="<?= htmlspecialchars($locator) ?>">
        <div class="row mb-3">
            <div class="col-12 d-flex align-items-center justify-content-between">
                <div>
                    <h1 class="mb-1">Your Cart</h1>
                    <p class="text-muted mb-0">Review items before heading to checkout.</p>
                </div>
                <div>
                    <a class="btn btn-outline-secondary me-2" href="market.php?store-locator=<?= urlencode($locator) ?>">Back to Market</a>
                    <a class="btn btn-primary" href="checkout.php?store-locator=<?= urlencode($locator) ?>">Go to Checkout</a>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <div>
                            <div class="small text-uppercase text-muted">Store</div>
                            <h5 class="mb-0" id="store-name">Loading store...</h5>
                        </div>
                        <span class="badge text-bg-secondary" id="store-locator-label"></span>
                    </div>
                    <div class="card-body">
                        <div id="cart-status" class="alert alert-info" role="alert">Fetching your cart...</div>
                        <div id="cart-items" class="list-group"></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h5 class="mb-0">Order Summary</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush" id="cart-totals"></ul>
                        <div class="mt-3 d-grid gap-2">
                            <a class="btn btn-primary" href="checkout.php?store-locator=<?= urlencode($locator) ?>">Proceed to Checkout</a>
                        </div>
                    </div>
                </div>

                <div class="alert alert-secondary mt-3" role="alert">
                    <div class="fw-bold mb-1">Need help?</div>
                    If you experience issues removing items, refresh the page or try again in a moment.
                </div>
            </div>
        </div>
    </main>

    <!-- ERROR MODAL -->
    <div class="modal fade" id="errorModal" tabindex="-1" aria-labelledby="errorModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header text-bg-danger">
                    <h1 class="modal-title fs-5" id="errorModalLabel">Oops!</h1>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p id="errorModalMessage"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn bg-ranked-1" data-bs-dismiss="modal">Okay</button>
                </div>
            </div>
        </div>
    </div>

    <!-- SUCCESS MODAL -->
    <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="successModalLabel">Success</h1>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p id="successModalMessage"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn bg-ranked-1" data-bs-dismiss="modal">Okay</button>
                </div>
            </div>
        </div>
    </div>

    <?php require("php-components/base-page-javascript.php"); ?>
    <script>
        (function() {
            const root = document.getElementById('cart-root');
            const cartItems = document.getElementById('cart-items');
            const cartTotals = document.getElementById('cart-totals');
            const cartStatus = document.getElementById('cart-status');
            const storeName = document.getElementById('store-name');
            const storeLocatorLabel = document.getElementById('store-locator-label');
            const storeLocator = root?.dataset.storeLocator || '';
            const isLoggedIn = <?= json_encode($isLoggedIn); ?>;

            let cart = null;
            let store = null;

            function showModal(modalId, message) {
                const modalBody = document.getElementById(modalId + "Message");
                if (modalBody) {
                    modalBody.textContent = message;
                }

                const modalElement = document.getElementById(modalId);
                if (modalElement) {
                    const modal = new bootstrap.Modal(modalElement);
                    modal.show();
                }
            }

            function formatPriceComponents(priceComponents) {
                if (!Array.isArray(priceComponents) || priceComponents.length === 0) {
                    return '<span class="text-muted">Free</span>';
                }

                return priceComponents.map((component) => {
                    const amount = Number(component?.amount ?? 0);
                    if (component?.currencyCode) {
                        const symbol = component.currencyCode === 'ADA' ? '₳' : component.currencyCode === 'USD' ? '$' : component.currencyCode;
                        const precision = Math.floor(amount) === amount ? 0 : 2;
                        return `<span class="fw-semibold">${amount.toFixed(precision)} ${symbol}</span>`;
                    }

                    if (component?.item) {
                        const quantity = Math.max(1, Math.floor(amount));
                        const icon = component.item?.iconSmall?.fullPath || component.item?.iconSmall?.path || '';
                        const name = component.item?.name || 'Item';
                        const iconHtml = icon ? `<img src="${icon}" alt="${name}" style="height:16px; width:16px; object-fit:contain;" class="me-1">` : '';
                        return `<span>${quantity}x ${iconHtml}${name}</span>`;
                    }

                    return `<span class="text-muted">${amount}</span>`;
                }).join('<span class="text-muted mx-1">+</span>');
            }

            function renderTotals() {
                cartTotals.innerHTML = '';
                if (!cart || !cart.totals || Object.keys(cart.totals).length === 0) {
                    cartTotals.innerHTML = '<li class="list-group-item d-flex justify-content-between"><span>Total</span><span class="fw-bold">—</span></li>';
                    return;
                }

                Object.entries(cart.totals).forEach(([label, value]) => {
                    const display = Array.isArray(value) ? formatPriceComponents(value) : value;
                    const item = document.createElement('li');
                    item.className = 'list-group-item d-flex justify-content-between align-items-center';
                    item.innerHTML = `<span class="text-capitalize">${label}</span><span>${display}</span>`;
                    cartTotals.appendChild(item);
                });
            }

            function renderCart() {
                cartItems.innerHTML = '';

                if (!cart || !Array.isArray(cart.cartProducts) || cart.cartProducts.length === 0) {
                    cartStatus.className = 'alert alert-info';
                    cartStatus.textContent = 'Your cart is empty.';
                    return;
                }

                cartStatus.className = 'visually-hidden';
                cartStatus.textContent = '';

                cart.cartProducts.forEach((cartProduct, index) => {
                    const product = cartProduct?.product;
                    const item = document.createElement('div');
                    item.className = 'list-group-item list-group-item-action d-flex gap-3 align-items-start';

                    const media = product?.mediaSmall?.fullPath || product?.mediaLarge?.fullPath || '/assets/media/default.png';
                    const priceDisplay = formatPriceComponents(product?.price || []);
                    const couponText = cartProduct?.coupon ? `<div class="text-success small">Coupon: ${cartProduct.coupon?.name || cartProduct.coupon?.code || 'Applied'}</div>` : '';

                    item.innerHTML = `
                        <img src="${media}" class="rounded" alt="${product?.name || 'Product'}" style="height:64px; width:64px; object-fit:cover;">
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="mb-1">${product?.name || 'Product'}</h6>
                                    <div class="text-muted small mb-1">${product?.description || ''}</div>
                                    ${couponText}
                                </div>
                                <div class="text-nowrap fw-semibold">${priceDisplay}</div>
                            </div>
                        </div>
                        <button class="btn btn-outline-danger btn-sm" data-index="${index}" aria-label="Remove item">
                            <i class="fas fa-trash"></i>
                        </button>
                    `;

                    const removeButton = item.querySelector('button[data-index]');
                    removeButton?.addEventListener('click', async () => {
                        await removeCartProduct(index);
                    });

                    cartItems.appendChild(item);
                });
            }

            async function removeCartProduct(index) {
                if (!cart || !Array.isArray(cart.cartProducts)) {
                    return;
                }

                const cartProduct = cart.cartProducts[index];
                if (!cartProduct) {
                    return;
                }

                try {
                    await StoreClient.removeProductFromCart(cartProduct);
                    showModal('successModal', 'Removed item from your cart.');
                    await loadCart();
                } catch (error) {
                    console.error('Failed to remove product from cart', error);
                    const message = error?.message || 'Unable to remove this item right now.';
                    showModal('errorModal', message);
                }
            }

            async function loadStore() {
                if (!storeLocator) {
                    throw new Error('Missing store locator');
                }

                const storeResp = await StoreClient.getStoreByLocator(storeLocator);
                store = storeResp?.data || null;

                if (storeName) {
                    storeName.textContent = store?.name || 'Unknown Store';
                }

                if (storeLocatorLabel) {
                    storeLocatorLabel.textContent = storeLocator;
                }
            }

            async function loadCart() {
                if (!isLoggedIn) {
                    cartStatus.className = 'alert alert-warning';
                    cartStatus.innerHTML = 'Please <a href="<?= Version::urlBetaPrefix(); ?>/login.php?redirect=' + encodeURIComponent('cart.php?store-locator=' + storeLocator) + '">log in</a> to view your cart.';
                    cartItems.innerHTML = '';
                    cartTotals.innerHTML = '';
                    return;
                }

                try {
                    const cartResp = await StoreClient.getCart(storeLocator);
                    cart = cartResp?.data || null;
                    renderCart();
                    renderTotals();
                } catch (error) {
                    console.error('Failed to fetch cart', error);
                    cartStatus.className = 'alert alert-danger';
                    cartStatus.textContent = error?.message || 'Unable to load cart.';
                }
            }

            (async function init() {
                try {
                    await loadStore();
                    await loadCart();
                } catch (error) {
                    console.error('Cart page initialization failed', error);
                    cartStatus.className = 'alert alert-danger';
                    cartStatus.textContent = error?.message || 'Unable to load cart information right now.';
                }
            })();
        })();
    </script>
</body>

</html>
