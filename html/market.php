<?php
require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__) . "/Kickback/init.php");

$session = require(\Kickback\SCRIPT_ROOT . "/api/v1/engine/session/verifySession.php");
require("php-components/base-page-pull-active-account-info.php");

use \Kickback\Backend\Controllers\StoreController;
use \Kickback\Backend\Controllers\ProductController;
use \Kickback\Backend\Controllers\CartController;
use \Kickback\Backend\Config\StoreCategory;
use \Kickback\Backend\Views\vRecordId;
use \Kickback\Backend\Views\vMedia;
use \Kickback\Services\Session;
use \Kickback\Backend\Config\StoreTag;
use \Kickback\Backend\Models\Enums\CurrencyCode;
use \Kickback\Common\Version;

$products = [];
$store = null;
$cart = null;




$account = Session::getCurrentAccount();
$locator = $_GET["store-locator"] ?? "kickback-market";

$storeResp = StoreController::getStoreByLocator($locator);
if (!$storeResp || !$storeResp->success || empty($storeResp->data)) {
    throw new Exception("Failed to retrieve store with locator: $locator - {$storeResp->message}");
}

$store = $storeResp->data;

// Retrieve products for the store
$products = $store->products;




if (Session::isLoggedIn()) {
    
    // Retrieve cart for the account and store
    $cartResp = StoreController::getCartForAccount($account, $store);

    if (!$cartResp || !$cartResp->success) {
        throw new Exception("Failed to retrieve cart for account: {$cartResp->message}");
    }

    $cart = $cartResp->data;
}



?>

<!DOCTYPE html>
<html lang="en">


<?php require("php-components/base-page-head.php"); ?>

<body class="bg-body-secondary container p-0">
    
    <?php 
    
    require("php-components/base-page-components.php"); 
    
    require("php-components/ad-carousel.php"); 
    
    ?>
    
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Orbitron:wght@600&display=swap');

/* --- BASE STYLES --- */
.emberwood-store {
  position: relative;
  background-color: #000;
  background-size: cover;
  background-position: center;
  background-repeat: no-repeat;
  border: 2px solid #33ffee44;
  border-radius: 12px;
  box-shadow: 0 0 30px #00ffc855 inset, 0 0 10px #00ffc822;
  font-family: 'Orbitron', sans-serif;
  color: #cceeff;
  padding-top: 40px;
  overflow: hidden;
  transition: background-image 0.5s ease;
}

.emberwood-store::after {
  content: "";
  pointer-events: none;
  position: absolute;
  top: 0; left: 0;
  width: 100%; height: 100%;
  z-index: 1;
  background: repeating-linear-gradient(to bottom, rgba(255,255,255,0.03) 1px, transparent 4px);
  opacity: 0.08;
  mix-blend-mode: overlay;
  animation: scanScroll 8s linear infinite, scanPulse 5s ease-in-out infinite;
}

/* Theme backgrounds */

<?= StoreCategory::getThemeCss(); ?>

/* --- ANIMATIONS --- */
@keyframes scanScroll { from { background-position: 0 0; } to { background-position: 0 100%; } }
@keyframes scanPulse { 0%, 100% { opacity: 0.08; } 50% { opacity: 0.14; } }
@keyframes flicker {
  0%, 19%, 21%, 23%, 25%, 54%, 56%, 100% { opacity: 1; }
  20%, 22%, 55% { opacity: 0.7; }
}

/* --- GRID --- */
.store-header { text-align: center; margin-bottom: 2rem; }
.store-logo { max-width: 100%; }

.store-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
  gap: 1rem;
  padding: 0 1rem 2rem;
  justify-items: center;
}

@media (max-width: 1400px) { .store-grid { grid-template-columns: repeat(4, 1fr); } }
@media (max-width: 1200px) { .store-grid { grid-template-columns: repeat(3, 1fr); } }
@media (max-width: 900px)  { .store-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 576px)  { .store-grid { grid-template-columns: 1fr; } }

/* --- CARD & FLIP --- */
.card-flip {
  position: relative;
  width: 100%;
  max-width: 220px;
  aspect-ratio: 2 / 3;
  margin: 0 auto;
  perspective: 1000px;
}

.flip-inner {
  width: 100%;
  height: 100%;
  position: relative;
  transform-style: preserve-3d;
  transition: transform 0.8s ease;
}

.card-flip.flipped .flip-inner { transform: rotateY(180deg); }

.item-front, .item-back {
  position: absolute;
  top: 0; left: 0;
  width: 100%;
  height: 100%;
  backface-visibility: hidden;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  padding: 1rem;
  border: 2px solid #3af7ff33;
  border-radius: 10px;
  box-shadow: 0 0 10px #0ff2;
  background: #111d22;
}

.item-front {
  transform: rotateY(0deg);
  z-index: 2;
}
.item-back {
  transform: rotateY(180deg);
  color: #9fcedd;
  overflow: hidden;
}

/* --- INTERACTIONS --- */
.card-flip:hover .item-front {
  box-shadow: 0 0 15px #00ffee, 0 0 10px #ffee00 inset;
}

/* --- IMAGE --- */
.item-frame {
  height: 180px;
  overflow: hidden;
  display: flex;
  justify-content: center;
  align-items: center;
}

.tilt-perspective {
  perspective: 600px;
  width: 100%;
  height: 100%;
}

.image-tilt-wrapper {
  transition: transform 0.3s ease;
  will-change: transform;
}

.item-image {
  max-width: 100%;
  height: auto;
  object-fit: contain;
  transform-style: preserve-3d;
  transition: transform 0.3s ease, filter 0.3s ease;
  will-change: transform, filter;
}

/* --- CONTENT --- */
.item-card, .item-info {
  flex: 1;
  display: flex;
  flex-direction: column;
}

.item-title {
  font-size: 1rem;
  color: #ffeeaa;
  text-shadow: 0 0 4px #ffaa00aa;
  animation: flicker 3s infinite;
  margin: 0.75rem 0 0.5rem;
}

.item-desc {
  flex-grow: 1;
  overflow-y: auto;
  font-size: 0.8rem;
  line-height: 1.4;
  margin-bottom: 0.75rem;
  padding-right: 4px;
  -webkit-overflow-scrolling: touch;
}

/* --- STOCK & TAG --- */
.item-stock {
  position: absolute;
  top: 8px;
  right: 8px;
  background: rgba(0, 0, 0, 0.8);
  color: #33f7ff;
  padding: 2px 6px;
  font-size: 0.75rem;
  border-radius: 4px;
  z-index: 10;
}

.item-ribbon {
  position: absolute;
  top: 0;
  left: 0;
  background: #ff3366;
  color: #fff;
  font-size: 0.65rem;
  font-weight: bold;
  padding: 4px 8px;
  border-bottom-right-radius: 6px;
  box-shadow: 0 0 6px #ff336688;
  text-transform: uppercase;
  z-index: 10;
}
<?= StoreTag::getTagCss(); ?>

/* --- FOOTER --- */
.item-footer {
  margin-top: auto;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 0.5rem;
  padding-top: 0.5rem;
}

/* --- PRICE --- */
.price-text {
  font-weight: bold;
  font-size: 0.9rem;
  color: #33f7ff;
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
}

.price-plus {
  margin: 0 0.3rem;
  color: #33f7ffcc;
}

.currency-icon {
  width: 16px;
  height: 16px;
  object-fit: contain;
}

/* --- BUTTONS --- */
.buy-btn, .flip-btn-icon {
  width: 38px;
  height: 38px;
  background: #111;
  border-radius: 50%;
  font-size: 1rem;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.25s ease;
  box-shadow: inset 0 0 4px #000;
}

.buy-btn {
  color: #ffee00;
  border: 2px solid #ffee00aa;
  box-shadow: 0 0 6px #ffee00aa, inset 0 0 4px #000;
}
.buy-btn:hover {
  background: #ffee00;
  color: #000;
  transform: scale(1.05) translateY(-1px);
  box-shadow: 0 0 12px #ffee00cc, inset 0 0 6px #000;
}
.buy-btn:active {
  transform: scale(0.95);
  box-shadow: 0 0 4px #ffee00aa, inset 0 0 8px #000;
}

.flip-btn-icon {
  color: #77c8ff;
  border: 2px solid #77c8ff88;
  box-shadow: 0 0 6px #77c8ff66, inset 0 0 4px #000;
  margin-bottom: 0.5rem;
}
.flip-btn-icon:hover {
  background: #77c8ff;
  color: #000;
  transform: scale(1.05) translateY(-1px);
  box-shadow: 0 0 10px #77c8ffcc, inset 0 0 5px #000;
}
.flip-btn-icon:active {
  transform: scale(0.95);
  box-shadow: 0 0 4px #77c8ff88, inset 0 0 8px #000;
}

/* --- CATEGORY FILTER --- */
.category-pills {
  display: flex;
  justify-content: center;
  gap: 0.75rem;
  flex-wrap: wrap;
  margin-bottom: 1.5rem;
}
.pill-btn {
  padding: 6px 16px;
  border-radius: 999px;
  background-color: #112233;
  color: #cceeff;
  border: 2px solid #33ffeeaa;
  font-weight: bold;
  font-size: 0.85rem;
  cursor: pointer;
  transition: all 0.3s ease;
}

.pill-btn:hover:not(.active) {
  background-color: #1a3344;
  border-color: #55ffff;
  color: #99eeff;
  box-shadow: 0 0 6px #33ffee88;
}

.pill-btn.active {
  background-color: #33ffee;
  color: #000;
}

/* --- UPCOMING DELIVERIES --- */
.deliveries-panel {
  position: relative;
  margin-top: 0.5rem;
  padding: 1.75rem 1.5rem;
  border-radius: 18px;
  background: radial-gradient(circle at 20% 20%, rgba(51, 255, 238, 0.08), transparent 30%),
              radial-gradient(circle at 80% 0%, rgba(255, 215, 0, 0.08), transparent 25%),
              linear-gradient(135deg, #0a1a26 0%, #0d2533 50%, #0b1d29 100%);
  border: 1px solid #33ffee55;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.45), 0 0 25px rgba(51, 255, 238, 0.25);
  overflow: hidden;
}

.deliveries-panel::before,
.deliveries-panel::after {
  content: "";
  position: absolute;
  inset: 0;
  pointer-events: none;
}

.deliveries-panel::before {
  background: linear-gradient(120deg, transparent 0%, rgba(51, 255, 238, 0.15) 40%, transparent 70%);
  filter: blur(10px);
  opacity: 0.6;
}

.deliveries-panel::after {
  background: repeating-linear-gradient(90deg, rgba(255, 255, 255, 0.03) 0 2px, transparent 2px 6px);
  mix-blend-mode: soft-light;
  opacity: 0.6;
}

.deliveries-grid {
  position: relative;
  z-index: 1;
  display: grid;
  grid-template-columns: 2.2fr 1fr;
  gap: 1.25rem;
  align-items: center;
}

@media (max-width: 992px) {
  .deliveries-grid { grid-template-columns: 1fr; }
}

.deliveries-chip {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.35rem 0.75rem;
  border-radius: 999px;
  background: rgba(51, 255, 238, 0.12);
  color: #86f7ff;
  border: 1px solid #33ffee55;
  font-size: 0.8rem;
  letter-spacing: 0.04em;
  text-transform: uppercase;
}

.deliveries-title {
  margin: 0.35rem 0 0.4rem;
  font-size: 1.6rem;
  color: #e6fbff;
  text-shadow: 0 0 12px rgba(51, 255, 238, 0.3);
}

.deliveries-subtext {
  color: #b7d8e6;
  margin-bottom: 1rem;
}

.delivery-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin-bottom: 1rem;
}

.delivery-pill {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  padding: 0.4rem 0.75rem;
  border-radius: 999px;
  border: 1px solid #33ffee44;
  background: rgba(12, 32, 44, 0.75);
  color: #c7eafd;
  font-size: 0.9rem;
  box-shadow: 0 0 12px rgba(51, 255, 238, 0.25) inset;
}

.delivery-timeline {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 0.75rem;
}

.timeline-card {
  padding: 0.85rem 0.9rem;
  border-radius: 12px;
  background: rgba(9, 23, 34, 0.85);
  border: 1px solid #33ffee33;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.35);
}

.timeline-card h4 {
  margin: 0 0 0.25rem;
  font-size: 1rem;
  color: #e6fbff;
}

.timeline-card p {
  margin: 0;
  color: #9fcedd;
  font-size: 0.95rem;
}

.timeline-icon {
  width: 38px;
  height: 38px;
  display: grid;
  place-items: center;
  border-radius: 10px;
  background: linear-gradient(135deg, rgba(51, 255, 238, 0.18), rgba(255, 215, 0, 0.14));
  color: #fff0c2;
  margin-bottom: 0.45rem;
  box-shadow: 0 0 12px rgba(51, 255, 238, 0.35);
}

.deliveries-cta {
  justify-self: end;
}

.cta-card {
  padding: 1.1rem;
  border-radius: 14px;
  border: 1px solid #ffd70066;
  background: linear-gradient(145deg, #17293a 0%, #213b4d 100%);
  box-shadow: 0 12px 30px rgba(0, 0, 0, 0.4);
  color: #ffeec6;
  text-align: center;
}

.cta-card h3 {
  margin-top: 0;
  margin-bottom: 0.35rem;
  font-size: 1.2rem;
}

.cta-card p { margin-bottom: 0.75rem; color: #f5dfa5; }

.cta-card .btn {
  border-radius: 999px;
  padding: 0.55rem 1.25rem;
  box-shadow: 0 8px 18px rgba(0, 0, 0, 0.35);
}

.cta-footer {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  font-size: 0.9rem;
  color: #d8f3ff;
}


    </style>



<!-- HTML -->
<main class="container pt-3 bg-body" style="margin-bottom: 56px;">

  <div class="row">
    <div class="col-12">
      <?php
      $activePageName = htmlspecialchars($store->name ?? "Market");
      require("php-components/base-page-breadcrumbs.php");
      ?>
    </div>
  </div>

  <div class="row">
    <div class="col-12">
      <button id="open-store-json" class="btn btn-primary">
        Open Store JSON
      </button>
    </div>
  </div>


  <div class="row">
    <div class="col-12">
      <section class="emberwood-store theme-default">
        <header class="store-header">
          <img src="/assets/images/store/store-logo.png" class="store-logo" alt="Emberwood Trading Company">
          <div class="category-pills">
            <?= StoreCategory::renderCategoryPills() ?>
          </div>

          <div id="category-flavor-text" style="margin-top: 0.5rem; font-size: 0.95rem; color: #99eeff; text-shadow: 0 0 3px #33ffee66;">
            <?= htmlspecialchars(StoreCategory::getCategory('all')['description']) ?>
          </div>

        </header>
        <div
          id="store-grid"
          class="store-grid"
          data-store-ctime="<?= htmlspecialchars($store->ctime ?? '') ?>"
          data-store-crand="<?= htmlspecialchars((string)($store->crand ?? '')) ?>"
          data-store-locator="<?= htmlspecialchars($locator ?? '') ?>"
        >
          <?php foreach ($products as $product): ?>
            <?php
              $priceComponents = is_array($product->price) ? $product->price : [];
              $priceDisplayParts = [];

              foreach ($priceComponents as $priceComponent) {
                $amount = $priceComponent->amount ?? 0;

                if ($priceComponent->currencyCode instanceof CurrencyCode) {
                  $symbol = match ($priceComponent->currencyCode) {
                    CurrencyCode::ADA => '₳',
                    CurrencyCode::USD => '$',
                    default => $priceComponent->currencyCode->value,
                  };

                  $precision = (floor((float) $amount) == (float) $amount) ? 0 : 2;
                  $formattedAmount = number_format((float) $amount, $precision);
                  $priceDisplayParts[] = "<span class='price-text'>{$formattedAmount} {$symbol}</span>";
                  continue;
                }

                if (isset($priceComponent->item) && $priceComponent->item instanceof \Kickback\Backend\Views\vItem) {
                  $amountText = number_format((float) $amount, 0);
                  $iconPath = $priceComponent->item->iconSmall?->getFullPath() ?: '';
                  $itemName = htmlspecialchars($priceComponent->item->name ?? '', ENT_QUOTES);
                  if (!empty($iconPath)) {
                    $iconHtml = "<img src='{$iconPath}' class='currency-icon' alt='{$itemName}'>";
                  } else {
                    $iconHtml = $itemName;
                  }
                  $priceDisplayParts[] = "<span class='price-text'>{$amountText} {$iconHtml}</span>";
                }
              }

              if (empty($priceDisplayParts)) {
                $priceDisplayParts[] = "<span class='price-text'>Free</span>";
              }

              $currencyDisplay = implode("<span class='price-plus'>+</span>", $priceDisplayParts);
              $imageUrl = $product->mediaLarge->getFullPath() ?? "/assets/media/default.png";
              $altText = htmlspecialchars($product->name);
              $descText = htmlspecialchars($product->description);
              $stockCount = $product->amountAvailable;
              $stockLabel = is_null($stockCount) ? "" : "<div class='item-stock'>In Stock: $stockCount</div>";
              $tagSlug = $product->tag;
              $ribbonHtml = StoreTag::renderRibbon($tagSlug);

              $categoryList = $product->categories;
              $categoryAttr = htmlspecialchars(implode(' ', $categoryList));


              
            ?>
            <div class="card-flip" data-category="<?= $categoryAttr ?>">
              <div class="flip-inner">
                <!-- FRONT -->
                <div class="item-card item-front">
                <?= $ribbonHtml ?>

                <?= $stockLabel ?>
                <div class="item-frame" onclick="flipCard(this)">
                  <div  class="tilt-perspective">
                    <img src="<?= $imageUrl ?>" alt="<?= $altText ?>" class="item-image">
                  </div>
                </div>

                  <div class="item-info">
                    <h3 class="item-title"><?= $altText ?></h3>
                    <div class="item-footer">
                      <button class="flip-btn-icon" onclick="flipCard(this)" title="View Details">
                        <i class="fas fa-eye"></i>
                      </button>
                      <div style="display: flex; align-items: center; gap: 0.5rem; margin-left: auto;">
                        <?= $currencyDisplay ?>
                        <button class="buy-btn"
                                data-product-ctime="<?= htmlspecialchars($product->ctime) ?>"
                                data-product-crand="<?= htmlspecialchars($product->crand) ?>"
                                data-product-locator="<?= htmlspecialchars((string)($product->locator ?? '')) ?>"
                                title="Add to Cart">
                          <i class="fas fa-cart-plus"></i>
                        </button>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- BACK -->
                <div class="item-back">
                  <p class="item-desc"><?= $descText ?></p>
                  <button class="flip-btn-icon" onclick="flipCard(this)" title="Back">
                    <i class="fas fa-undo"></i>
                  </button>
                </div>

              </div>
            </div>
          <?php endforeach; ?>
        </div>
        <div id="no-results-message" style="text-align: center; padding: 2rem; font-size: 1rem; color: #ffeeaa; display: none;">
          Nothing in stock here... yet. Check back soon, traveler.
        </div>

      </section>
    </div>
  </div>
  
  <div class="row mt-3">
    <div class="col-12">
      <section class="deliveries-panel" role="region" aria-label="Upcoming store deliveries">
        <div class="deliveries-grid">
          <div>
            <div class="deliveries-chip">
              <i class="fa-solid fa-satellite-dish"></i>
              Logistics feed
            </div>
            <h2 class="deliveries-title">Upcoming Deliveries</h2>
            <p class="deliveries-subtext mb-0">See what shipments are headed to Emberwood Market next and plan your haul.</p>

            <div class="delivery-meta">
              <span class="delivery-pill"><i class="fa-solid fa-sparkles"></i> Featured crates</span>
              <span class="delivery-pill"><i class="fa-solid fa-fire"></i> Hot drop ETA</span>
              <span class="delivery-pill"><i class="fa-solid fa-shield-heart"></i> Guild dispatch</span>
            </div>

            <div class="delivery-timeline">
              <div class="timeline-card">
                <div class="timeline-icon"><i class="fa-solid fa-truck-fast"></i></div>
                <h4>Supply Run</h4>
                <p class="mb-0">Priority delivery from Emberwood docks with fresh stock rotations.</p>
              </div>
              <div class="timeline-card">
                <div class="timeline-icon"><i class="fa-solid fa-box-open"></i></div>
                <h4>Premium Crates</h4>
                <p class="mb-0">Limited bundles and rare artifacts getting queued for the next sale window.</p>
              </div>
              <div class="timeline-card">
                <div class="timeline-icon"><i class="fa-solid fa-people-carry-box"></i></div>
                <h4>Community Picks</h4>
                <p class="mb-0">Top-voted items from guild requests will ship with this convoy.</p>
              </div>
            </div>
          </div>

          <div class="deliveries-cta">
            <div class="cta-card">
              <h3 class="mb-1">Track the convoy</h3>
              <p class="mb-0">Live schedule with drop times and crate manifests.</p>
              <a class="btn btn-warning mt-3" href="<?= Version::urlBetaPrefix(); ?>/emberwood-delivery.php">
                <i class="fa-solid fa-route me-1"></i>
                View schedule
              </a>
              <div class="cta-footer mt-3">
                <i class="fa-regular fa-clock"></i>
                Updated hourly via Emberwood dispatch
              </div>
            </div>
          </div>
        </div>
      </section>
    </div>
  </div>
  <?php require("php-components/base-page-footer.php"); ?>
</main>






    <script>
        (function(){
            const productsGrid = document.getElementById('store-grid');
            if(!productsGrid)
            {
                console.warn('Store grid element not found. Cart actions are disabled.');
                return;
            }

            const storeLocator = productsGrid.dataset.storeLocator || '';
            const storeCtime = productsGrid.dataset.storeCtime || '';
            const storeCrand = productsGrid.dataset.storeCrand || '';
            let cart = <?php echo json_encode($cart, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_PARTIAL_OUTPUT_ON_ERROR); ?>;

            async function refreshProducts()
            {
                if(!storeLocator)
                {
                    console.error("Missing store locator; unable to refresh products");
                    return;
                }

                try
                {
                    const storeResp = await StoreClient.getStoreByLocator(storeLocator);
                    const products = Array.isArray(storeResp?.data?.products) ? storeResp.data.products : [];
                    const productMap = new Map(products.map((p) => [p.locator, p]));

                    productsGrid.dataset.storeCtime = storeResp?.data?.ctime ?? '';
                    productsGrid.dataset.storeCrand = storeResp?.data?.crand ?? '';

                    productsGrid.querySelectorAll('.card-flip').forEach((card) =>
                    {
                        const addButton = card.querySelector('.buy-btn');
                        const productLocator = addButton?.dataset.productLocator || '';
                        const product = productMap.get(productLocator);
                        const stockEl = card.querySelector('.item-stock');

                        if(!product)
                        {
                            card.style.display = 'none';
                            return;
                        }

                        const available = product.amountAvailable;
                        const hasStockInfo = available !== null && available !== undefined;

                        if(stockEl)
                        {
                            if(hasStockInfo)
                            {
                                stockEl.textContent = `In Stock: ${available}`;
                                stockEl.style.display = '';
                            }
                            else
                            {
                                stockEl.style.display = 'none';
                            }
                        }

                        if(addButton)
                        {
                            const disablePurchase = hasStockInfo && Number(available) <= 0;
                            addButton.disabled = disablePurchase;
                            addButton.title = disablePurchase ? 'Out of stock' : 'Add to Cart';
                        }
                    });
                }
                catch(e)
                {
                    console.error("Failed to refresh products", e);
                }
            }


            function requireLogin()
            {
                return cart === null;
            }

            function showModal(modalId, message, title)
            {
                const modalBody = document.getElementById(modalId + "Message");
                if(modalBody)
                {
                    modalBody.textContent = message;
                }

                const modalTitle = document.getElementById(modalId + "Label");
                if(modalTitle)
                {
                    const fallbackTitle = modalId === "errorModal" ? "Error" : modalId === "successModal" ? "Success" : modalTitle.textContent;
                    modalTitle.textContent = title || fallbackTitle;
                }

                const modalElement = document.getElementById(modalId);
                if(modalElement)
                {
                    const modal = new bootstrap.Modal(modalElement);
                    modal.show();
                }
            }

            async function refreshCartFromShared()
            {
                const fetchCartFn = window.fetchCart;

                if (typeof fetchCartFn !== 'function')
                {
                    console.warn('Cart handler unavailable; unable to refresh cart.');
                    return null;
                }

                const refreshedCart = await fetchCartFn({ storeLocatorOverride: storeLocator, showLoading: true });

                if (refreshedCart)
                {
                    cart = refreshedCart;
                }

                return refreshedCart;
            }

            async function addProductToCart(productLocator)
            {
                if(productLocator === undefined || productLocator === '')
                {
                    console.error("Missing product identifiers for cart action");
                    return;
                }

                if(requireLogin())
                {
                    const redirectUrl = encodeURIComponent("market.php");
                    window.location.href = `<?= Version::urlBetaPrefix(); ?>/login.php?redirect=${redirectUrl}`;
                    showModal("errorModal", "You must be logged in to add items to your cart.", "Login required");

                    return;
                }

                try
                {
                    await StoreClient.addProductToCartByLocator(cart, productLocator);
                    showModal("successModal", "Successfully added product to cart.", "Added to cart");
                    await refreshCartFromShared();
                    await refreshProducts();
                }
                catch(e)
                {
                    console.error("Exception caught while adding product to cart", e);
                    const message = e?.message || "An unexpected error occurred while adding the product to your cart.";
                    showModal("errorModal", message, "Unable to add to cart");
                }
            }

            productsGrid.querySelectorAll('.buy-btn').forEach((button) =>
            {
                button.addEventListener('click', async(event) =>
                {
                    event.preventDefault();

                    const productLocator = button.dataset.productLocator || '';
                    await addProductToCart(productLocator);
                });
            });
        })();
        
    </script>

    <!-- ERROR MODAL -->
        <div class="modal fade" id="errorModal" tabindex="-1" aria-labelledby="errorModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header text-bg-danger">
                        <h1 class="modal-title fs-5" id="errorModalLabel">Error</h1>
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

function flipCard(button) {
  const card = button.closest('.card-flip');
  card.classList.toggle('flipped');
}

document.querySelectorAll('.item-frame').forEach(frame => {
  const tiltTarget = frame.querySelector('.item-image');
  if (!tiltTarget) return;

  const maxRotate = 45;
  const maxBrightness = 0.5;
  const minBrightness = 1.2;

  frame.addEventListener('mousemove', (e) => {
    const rect = frame.getBoundingClientRect();
    const offsetX = e.clientX - rect.left;
    const offsetY = e.clientY - rect.top;
    const centerX = rect.width / 2;
    const centerY = rect.height / 2;

    const normX = (offsetX - centerX) / centerX;
    const normY = (offsetY - centerY) / centerY;

    const rotateX = normY * -maxRotate;
    const rotateY = normX * maxRotate;

    const brightness = maxBrightness - ((rotateX + maxRotate) / (2 * maxRotate)) * (maxBrightness - minBrightness);

    tiltTarget.style.transform = `rotateX(${rotateX}deg) rotateY(${rotateY}deg)`;
    tiltTarget.style.filter = `brightness(${brightness.toFixed(2)})`;
    tiltTarget.style.transition = 'transform 0.1s ease-out, filter 0.1s ease-out';
  });

  frame.addEventListener('mouseleave', () => {
    tiltTarget.style.transform = 'rotateX(0deg) rotateY(0deg)';
    tiltTarget.style.filter = 'brightness(1)';
    tiltTarget.style.transition = 'transform 0.3s ease, filter 0.3s ease';
  });
});


const flavorText = document.getElementById('category-flavor-text');

document.querySelectorAll('.pill-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    const category = btn.dataset.category;
    const theme = btn.dataset.theme;

    // Toggle active pill
    document.querySelectorAll('.pill-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');

    // Change store theme
    const store = document.querySelector('.emberwood-store');
    store.className = 'emberwood-store';
    if (theme) store.classList.add('theme-' + theme);

    // Update flavor text (from description stored in button title)
    const description = btn.getAttribute('title');
    if (flavorText) flavorText.textContent = description || '';

    // Filter product cards
    let anyVisible = false;
    document.querySelectorAll('.card-flip').forEach(card => {
      const itemCats = (card.dataset.category || 'general').split(/\s+/);
      const show = category === 'all' || itemCats.includes(category);
      card.style.display = show ? 'block' : 'none';
      if (show) anyVisible = true;
    });

    const noResults = document.getElementById('no-results-message');
    if (noResults) {
      noResults.style.display = anyVisible ? 'none' : 'block';
    }
  });
});

    </script>
<script>
(function () {
  const btn = document.getElementById('open-store-json');
  if (!btn) return;

  // Serialize the PHP response into JS
  const storeResp = <?= json_encode(
      $storeResp,
      JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION
  ); ?>;

  btn.addEventListener('click', () => {
    try {
      const pretty = JSON.stringify(storeResp, null, 2);
      const blob = new Blob([pretty], { type: 'application/json' });
      const url = URL.createObjectURL(blob);

      // Open in a new tab
      window.open(url, '_blank', 'noopener');

      // Clean up the blob URL later
      setTimeout(() => URL.revokeObjectURL(url), 30000);
    } catch (e) {
      console.error('Failed to open store JSON', e);
    }
  });
})();
</script>

</body>

</html>
