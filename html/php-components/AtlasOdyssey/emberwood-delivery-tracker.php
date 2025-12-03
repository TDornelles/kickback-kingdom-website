<?php
declare(strict_types=1);

use Kickback\AtlasOdyssey\Emberwood\EmberwoodTradingCargoship;
use Kickback\Backend\Controllers\AccountController;
use Kickback\Backend\Controllers\ShipmentController;
use Kickback\Backend\Views\vRecordId;
use Kickback\AtlasOdyssey\AtlasDateTime;
// Create an instance of the EmberwoodTradingCargoship
$emberwoodShip = new EmberwoodTradingCargoship(2);

// Journey progress and ATC date
$progressPercentage = $emberwoodShip->getJourneyPercentage();
$timeUntilNextDelivery = $emberwoodShip->getTimeUntilNextDeliveryInATC();
$currentATCDate = $emberwoodShip->getCurrentATCDateTime();

// Get status details with color and icon
$shipLocation = $emberwoodShip->getLocation();
$shipStatus = $emberwoodShip->getShipStatusWithDetails();
$trackingNumber = $emberwoodShip->getTrackingNumber();


$imgStarMap = "https://png.pngtree.com/background/20230612/original/pngtree-solar-system-with-many-planets-picture-image_3362535.jpg";
$imgShip = "https://i0.wp.com/thelegocarblog.com/wp-content/uploads/2024/09/Screenshot-2024-09-19-at-13.52.59.png";

// Calculate dynamic left positioning with boundaries for 0% and 100% to keep icon within bounds
$shipWidth = 60; // Ship image width in pixels
$shipIconPadding = 48; // breathing room to avoid clipping inside the route container
$adjustedProgress = max(0, min($progressPercentage, 100));
$leftPosition = "clamp({$shipIconPadding}px, {$adjustedProgress}%, calc(100% - {$shipIconPadding}px))";

$shipmentManifest = [];
$itemInfos = [];


$profile = AccountController::getAccountByUsername("Alibaba");
$profile = $profile->data;

$shipmentManifestResp = ShipmentController::getShipmentManifest($trackingNumber);

if ($shipmentManifestResp->success == false)
{
    print_r($shipmentManifestResp);
}
else{

    $shipmentManifest = $shipmentManifestResp->data;
    foreach ($shipmentManifest as $accountInventoryItemStack) {

        array_push($itemInfos, $accountInventoryItemStack->item);
    }

}



$itemInformationJSON = json_encode($itemInfos);
$itemStackInformationJSON = json_encode($shipmentManifest);

$journeyWaypoints = [
    ['label' => 'Port of Emberwood', 'icon' => 'fa-fire-alt'],
    ['label' => 'Mistwind Isles', 'icon' => 'fa-water'],
    ['label' => 'Driftglass Bay', 'icon' => 'fa-ship'],
    ['label' => 'Kingdom Docks', 'icon' => 'fa-flag-checkered'],
];

?>


<div class="card mt-4 shadow-lg rounded emberwood-tracker-card overflow-hidden">
    <div class="card-body p-4">
        <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4">
            <div>
                <p class="text-uppercase fw-semibold text-secondary small mb-1">Atlas Odyssey · Emberwood Fleet</p>
                <h2 class="fw-bold mb-2">Cargo Delivery Status</h2>
                <p class="mb-0 text-secondary">Updated ATC <strong><?= $currentATCDate; ?></strong></p>
            </div>
            <div class="text-lg-end">
                <span class="badge bg-gradient-primary fs-6 px-3 py-2 shadow-sm">Journey <?= number_format($progressPercentage, 1); ?>%</span>
                <div class="mt-2 text-secondary">Tracking #<?= $trackingNumber; ?></div>
            </div>
        </div>

        <!-- Information Display with Ship Location, Shipment Number, and ETA -->
        <div class="emberwood-tracker-info-container mb-4">
            <div class="emberwood-tracker-info-box">
                <h5 class="emberwood-tracker-label"><i class="fas fa-map-marker-alt"></i> Ship Location</h5>
                <p class="text-primary emberwood-tracker-info-text"><?= $shipLocation; ?></p>
            </div>
            <div class="emberwood-tracker-info-box">
                <h5 class="emberwood-tracker-label"><i class="fas fa-box"></i> Tracking #</h5>
                <p class="emberwood-tracker-info-text"><?= $trackingNumber; ?></p>
            </div>
            <div class="emberwood-tracker-info-box">
                <h5 class="emberwood-tracker-label"><i class="far fa-clock"></i> Estimated Arrival</h5>
                <p class="text-danger emberwood-tracker-info-text"><?= $timeUntilNextDelivery; ?></p>
            </div>
        </div>

        <!-- Star Map and Ship Progress Display -->
        <div class="emberwood-tracker-progress-route mb-3">
            <div class="progress bg-dark bg-opacity-50 position-absolute top-50 start-0 w-100 translate-middle-y rounded-pill shadow-sm" style="height: 12px;">
                <div class="progress-bar bg-warning" role="progressbar" style="width: <?= $adjustedProgress; ?>%;"></div>
            </div>
            <div class="emberwood-tracker-ship-icon" style="left: <?= $leftPosition; ?>;">
                <img src="<?= $imgShip; ?>" alt="Emberwood Ship" class="emberwood-tracker-ship-image">
                <span class="ship-progress-label badge bg-dark bg-opacity-75 text-light mt-2"><?= $progressPercentage; ?>% complete</span>
            </div>
        </div>

        <div class="row g-3 align-items-stretch mb-4">
            <div class="col-12 col-lg-7">
                <div class="card h-100 border-0 glassy-panel">
                    <div class="card-body">
                        <h5 class="fw-bold mb-3 emberwood-tracker-label"><i class="fas fa-route me-2 text-warning"></i>Journey Waypoints</h5>
                        <div class="timeline">
                            <?php foreach ($journeyWaypoints as $index => $waypoint): ?>
                                <?php $isReached = $adjustedProgress >= (($index) * (100 / (count($journeyWaypoints) - 1))); ?>
                                <div class="timeline-item <?= $isReached ? 'active' : ''; ?>">
                                    <div class="timeline-icon"><i class="fas <?= $waypoint['icon']; ?>"></i></div>
                                    <div>
                                        <div class="fw-semibold mb-1"><?= $waypoint['label']; ?></div>
                                        <small class="text-secondary">Checkpoint <?= $index + 1; ?> of <?= count($journeyWaypoints); ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-lg-5">
                <div class="card h-100 border-0 glassy-panel text-center d-flex flex-column justify-content-center">
                    <div class="card-body fleet-status-body">
                        <div class="d-flex justify-content-center align-items-center gap-3 mb-3">
                            <div class="display-6 mb-0 text-warning"><i class="<?= $shipStatus->icon; ?>"></i></div>
                            <div class="text-start">
                                <p class="text-uppercase text-secondary small mb-1">Fleet Status</p>
                                <h4 class="mb-0"><?= $shipStatus->text; ?></h4>
                            </div>
                        </div>
                        <div class="badge bg-<?= $shipStatus->bootstrapColorClass; ?> text-bg-<?= $shipStatus->bootstrapColorClass; ?> px-3 py-2 shadow-sm">Currently <?= strtolower($shipStatus->text); ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card mt-4 shadow-lg rounded emberwood-tracker-card">
    <div class="card-body text-center py-4">
        <div class="display-6 tab-pane-title">Shipment Cargo</div>
        <div class="row">
            <div class="col-12">
                <!-- side-bar colleps block stat-->
                <div class="inventory-grid">
                    <?php
                    
                    // Show category title

                    foreach ($shipmentManifest as $shipmentCargoItemStack) {
                        ?>
                        <div class="inventory-item" onclick="ShowInventoryItemModal(<?= $shipmentCargoItemStack->item->crand; ?>);"  data-bs-toggle="tooltip" data-bs-dismiss="modal" data-bs-placement="bottom" data-bs-title="<?= htmlspecialchars($shipmentCargoItemStack->item->name)?>">
                            <img src="<?= $shipmentCargoItemStack->item->iconSmall->getFullPath(); ?>" alt="Item <?= $shipmentCargoItemStack->item->name; ?>">
                            <div class="item-count">x<?= $shipmentCargoItemStack->amount; ?></div>
                        </div>
                    
                    <?php
                    }

                    ?>
                </div>
            </div>
        </div> 
    </div>
</div>

<style>
.emberwood-tracker-card {
    background: radial-gradient(circle at 20% 20%, rgba(255, 200, 100, 0.12), transparent 35%),
        radial-gradient(circle at 80% 0%, rgba(0, 123, 255, 0.08), transparent 30%),
        linear-gradient(180deg, #0c1524 0%, #0f172a 35%, #0b1220 100%);
    color: #e9edf5;
    border: 1px solid rgba(255, 255, 255, 0.05);
}

.emberwood-tracker-card h2,
.emberwood-tracker-card h4,
.emberwood-tracker-card h5,
.emberwood-tracker-card .tab-pane-title {
    color: #f8fafc;
}

.emberwood-tracker-label {
    color: #e2e8f0;
    letter-spacing: 0.02em;
}

.emberwood-tracker-card .text-secondary {
    color: #cbd5e1 !important;
}

.bg-gradient-primary {
    background: linear-gradient(135deg, #f59e0b 0%, #fb923c 50%, #f97316 100%);
}

.glassy-panel {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.08) !important;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
    backdrop-filter: blur(6px);
}

.glassy-panel h5 {
    color: #fcd34d;
}

/* Info Box Container */
.emberwood-tracker-info-container {
    display: flex;
    justify-content: space-between;
    flex-wrap: wrap;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    padding-bottom: 1rem;
    margin-bottom: 1rem;
    gap: 0.5rem;
}

/* Info Box Styling */
.emberwood-tracker-info-box {
    flex: 1 1 30%;
    padding: 0.75rem;
    text-align: center;
    background: rgba(255, 255, 255, 0.04);
    border-radius: 12px;
    margin: 0.5rem 0;
    border: 1px solid rgba(255, 255, 255, 0.06);
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.04);
    transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
}

.emberwood-tracker-info-box:hover {
    transform: translateY(-3px);
    box-shadow: 0px 8px 18px rgba(0, 0, 0, 0.2);
}

.emberwood-tracker-info-text {
    font-size: 1.35rem;
    font-weight: bold;
    color: #f8fafc;
}

.emberwood-tracker-info-box h5 {
    color: #e7edf7;
}

/* Ensure responsiveness: Stack boxes on small screens like phones */
@media (max-width: 768px) {
    .emberwood-tracker-info-container {
        display: block;
        text-align: center;
    }
    .emberwood-tracker-info-box {
        flex: 1 1 100%;
        margin: 0.75rem 0;
    }
}

/* Star Map and Ship Progress */
.emberwood-tracker-progress-route {
    position: relative;
    height: 240px;
    border-radius: 14px;
    margin-bottom: 1rem;
    overflow: visible;
    padding: 1.25rem 3rem;
    background: linear-gradient(135deg, rgba(17, 24, 39, 0.95), rgba(15, 23, 42, 0.9)),
        url("<?= $imgStarMap; ?>") no-repeat center center;
    background-size: cover;
    border: 1px solid rgba(255, 255, 255, 0.08);
}

.emberwood-tracker-progress-route .progress {
    left: 2.75rem;
    right: 2.75rem;
    width: auto;
    height: 14px;
    border: 1px solid rgba(255, 255, 255, 0.08);
}

/* Ship Icon */
.emberwood-tracker-ship-icon {
    position: absolute;
    top: 50%;
    transform: translate(-50%, -50%);
    transition: left 1s ease-in-out;
    text-align: center;
    max-width: 180px;
}

.ship-progress-label {
    display: inline-block;
    margin-top: 0.35rem;
    font-size: 0.85rem;
}

.emberwood-tracker-ship-image {
    width: 60px;
    border-radius: 50%;
    box-shadow: 0px 0px 18px rgba(255, 200, 50, 0.9);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.emberwood-tracker-ship-image:hover {
    transform: scale(1.15) rotate(8deg);
    box-shadow: 0px 0px 24px rgba(255, 150, 0, 1);
    cursor: pointer;
}

.timeline {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.timeline-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem;
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.06);
    transition: border-color 0.2s ease, transform 0.2s ease;
}

.timeline-item.active {
    border-color: #fbbf24;
    box-shadow: 0 10px 20px rgba(251, 191, 36, 0.15);
    transform: translateY(-2px);
}

.timeline-icon {
    width: 42px;
    height: 42px;
    display: grid;
    place-items: center;
    border-radius: 50%;
    background: rgba(251, 191, 36, 0.14);
    color: #fbbf24;
    font-size: 1.1rem;
}

.ship-status-text {
    font-size: 1.1rem;
    line-height: 1.5;
    font-family: 'Poppins', sans-serif;
}

.fleet-status-body {
    color: #e2e8f0;
    background: rgba(255, 255, 255, 0.04);
    border-radius: 14px;
    padding: 1.25rem 1.5rem;
    overflow: hidden;
    word-break: break-word;
}

.fleet-status-body h4 {
    color: #f8fafc;
}

.fleet-status-body .badge {
    white-space: normal;
    line-height: 1.4;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    max-width: 100%;
}
</style>
