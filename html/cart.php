<?php
require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__) . "/Kickback/init.php");

$session = require(\Kickback\SCRIPT_ROOT . "/api/v1/engine/session/verifySession.php");
require("php-components/base-page-pull-active-account-info.php");

use \Kickback\Common\Version;
use \Kickback\Services\Session;

$locator = $_GET["store-locator"] ?? "emberwood-market";
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

    <main class="container pt-3 bg-body" style="margin-bottom: 56px;" id="cart-root" data-store-locator="<?= htmlspecialchars($locator) ?>">
        <div class="row mb-3">
            <div class="col-12">
                <?php
                $activePageName = "Cart";
                require("php-components/base-page-breadcrumbs.php");
                ?>
            </div>
        </div>
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
                    <div class="card-header">
                        <h5 class="mb-0">Cart Items</h5>
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

        <?php require("php-components/base-page-footer.php"); ?>
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
            const storeLocator = root?.dataset.storeLocator || '';
            const isLoggedIn = <?= json_encode($isLoggedIn); ?>;

            let cart = null;

            const getMediaPath = (media) => media?.fullPath || media?.url || media?.path || '';

            const getProductMediaPath = (product) =>
                getMediaPath(product?.mediaSmall)
                || getMediaPath(product?.mediaLarge)
                || '/assets/media/default.png';

            const normalizePriceComponents = (priceComponents) => {
                if (Array.isArray(priceComponents)) {
                    return priceComponents;
                }

                if (priceComponents && typeof priceComponents === 'object') {
                    return Object.values(priceComponents);
                }

                return [];
            };

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
                const components = normalizePriceComponents(priceComponents);

                if (components.length === 0) {
                    return '<span class="text-muted">Free</span>';
                }

                return components.map((component) => {
                    const amount = Number(component?.amount ?? 0);
                    if (component?.currencyCode) {
                        const symbol = component.currencyCode === 'ADA' ? '₳' : component.currencyCode === 'USD' ? '$' : component.currencyCode;
                        const precision = Math.floor(amount) === amount ? 0 : 2;
                        return `<span class="fw-semibold">${amount.toFixed(precision)} ${symbol}</span>`;
                    }

                    if (component?.item) {
                        const quantity = Math.max(1, Math.floor(amount));
                        const icon = component.item?.iconSmall?.fullPath || component.item?.iconSmall?.path || component.item?.iconSmall?.url || '';
                        const name = component.item?.name || 'Item';
                        const iconHtml = icon ? `<img src="${icon}" alt="${name}" style="height:16px; width:16px; object-fit:contain;" class="me-1">` : '';
                        return `<span>${quantity}x ${iconHtml}${name}</span>`;
                    }

                    return `<span class="text-muted">${amount}</span>`;
                }).join('<span class="text-muted mx-1">+</span>');
            }

            const aggregateTotals = (priceComponents) => {
                const totals = {
                    items: [],
                    ada: 0,
                    usd: 0,
                    otherCurrencies: {},
                };

                normalizePriceComponents(priceComponents).forEach((component) => {
                    const amount = Number(component?.amount ?? 0);

                    if (component?.item) {
                        totals.items.push({
                            quantity: Math.max(1, Math.floor(amount)),
                            name: component.item?.name || 'Item',
                            icon: component.item?.iconSmall?.fullPath || component.item?.iconSmall?.path || component.item?.iconSmall?.url || '',
                        });
                        return;
                    }

                    if (component?.currencyCode === 'ADA') {
                        totals.ada += amount;
                        return;
                    }

                    if (component?.currencyCode === 'USD') {
                        totals.usd += amount;
                        return;
                    }

                    if (component?.currencyCode) {
                        totals.otherCurrencies[component.currencyCode] = (totals.otherCurrencies[component.currencyCode] || 0) + amount;
                    }
                });

                return totals;
            };

            function renderTotals() {
                cartTotals.innerHTML = '';

                const totals = aggregateTotals(cart?.totals);

                const addLine = (label, value, isBold = false) => {
                    const item = document.createElement('li');
                    item.className = 'list-group-item d-flex justify-content-between align-items-center';
                    item.innerHTML = `<span>${label}</span><span class="${isBold ? 'fw-bold' : ''}">${value}</span>`;
                    cartTotals.appendChild(item);
                };

                const hasTotals = totals.items.length || totals.ada || totals.usd || Object.keys(totals.otherCurrencies).length;

                const formatNumber = (value, symbol) => {
                    const precision = Math.floor(value) === value ? 0 : 2;
                    return `${symbol}${value.toFixed(precision)}`;
                };

                const itemsValue = totals.items.length
                    ? totals.items.map((item) => {
                        const iconHtml = item.icon ? `<img src="${item.icon}" alt="${item.name}" style="height:16px; width:16px; object-fit:contain;" class="me-1">` : '';
                        return `${item.quantity}x ${iconHtml}${item.name}`;
                    }).join(', ')
                    : '0 items';

                if (!hasTotals) {
                    addLine('Items', itemsValue);
                    addLine('ADA', formatNumber(0, '₳'));
                    addLine('USD', formatNumber(0, '$'), true);
                    return;
                }

                addLine('Items', itemsValue);
                addLine('ADA', formatNumber(totals.ada, '₳'));
                addLine('USD', formatNumber(totals.usd, '$'), true);

                Object.entries(totals.otherCurrencies).forEach(([code, amount]) => {
                    addLine(code, formatNumber(amount, ''));
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
                    const quantity = Math.max(1, Number(cartProduct?.quantity) || 1);
                    const item = document.createElement('div');
                    item.className = 'list-group-item list-group-item-action d-flex gap-3 align-items-start';

                    const media = getProductMediaPath(product);
                    const priceDisplay = formatPriceComponents(product?.price || []);
                    const couponText = cartProduct?.coupon ? `<div class="text-success small">Coupon: ${cartProduct.coupon?.name || cartProduct.coupon?.code || 'Applied'}</div>` : '';

                    item.innerHTML = `
                        <img src="${media}" class="rounded" alt="${product?.name || 'Product'}" style="height:64px; width:64px; object-fit:cover;">
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="mb-1">${product?.name || 'Product'} <span class="badge text-bg-secondary">x${quantity}</span></h6>
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
