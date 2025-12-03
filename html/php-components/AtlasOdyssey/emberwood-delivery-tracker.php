<?php
declare(strict_types=1);

use Kickback\Backend\Controllers\ShipmentController;
$imgStarMap = "https://png.pngtree.com/background/20230612/original/pngtree-solar-system-with-many-planets-picture-image_3362535.jpg";
$imgShip = "https://i0.wp.com/thelegocarblog.com/wp-content/uploads/2024/09/Screenshot-2024-09-19-at-13.52.59.png";

$trackerData = ShipmentController::getEmberwoodTrackerData();

extract($trackerData, EXTR_SKIP);
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
                <span class="badge bg-gradient-primary fs-6 px-3 py-2 shadow-sm">Journey <?= number_format($roundedProgress, 1); ?>%</span>
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
                <div class="emberwood-progress-ambient">
                    <div class="emberwood-progress-glow"></div>
                    <div class="emberwood-progress-glow emberwood-progress-glow-secondary"></div>
                    <div class="emberwood-progress-stars"></div>
                </div>
                <div class="progress bg-dark bg-opacity-50 position-absolute top-50 translate-middle-y rounded-pill shadow-sm emberwood-progress-track">
                    <div class="progress-bar bg-warning" role="progressbar" style="width: <?= $adjustedProgress; ?>%;"></div>
                </div>
                <div class="emberwood-progress-overlay">
                    <div class="emberwood-tracker-ship-icon" style="left: <?= $leftPosition; ?>;">
                        <div class="emberwood-ship-trail"></div>
                        <img src="<?= $imgShip; ?>" alt="Emberwood Ship" class="emberwood-tracker-ship-image">
                    </div>
                </div>
            </div>

        <div class="alert alert-<?= $shipStatus->bootstrapColorClass; ?> bg-<?= $shipStatus->bootstrapColorClass; ?> bg-opacity-10 border-0 text-light d-flex align-items-center gap-3 mb-4">
            <div class="display-6 mb-0 text-warning"><i class="<?= $shipStatus->icon; ?>"></i></div>
            <div class="flex-grow-1">
                <p class="text-uppercase text-secondary small mb-1">Fleet Status</p>
                <h4 class="mb-0"><?= $fleetStatusHeadline; ?></h4>
            </div>
            <span class="badge bg-<?= $shipStatus->bootstrapColorClass; ?> text-bg-<?= $shipStatus->bootstrapColorClass; ?> px-3 py-2 shadow-sm text-wrap"><?= $fleetStatusBadge; ?></span>
        </div>

        <div class="row g-3 align-items-stretch mb-4">
            <div class="col-12">
                <div class="card h-100 border-0 glassy-panel">
                    <div class="card-body">
                        <h5 class="fw-bold mb-3 emberwood-tracker-label"><i class="fas fa-route me-2 text-warning"></i>Journey Waypoints</h5>
                        <div class="timeline-scroll">
                            <div class="timeline">
                                <?php foreach ($journeyWaypoints as $index => $waypoint): ?>
                                <?php
                                    $segmentStart = $index * $segmentPercentage;
                                    $segmentEnd = ($index + 1) * $segmentPercentage;
                                    $isReached = $adjustedProgress >= $segmentStart;
                                    $isCurrent = $currentWaypointIndex === $index;
                                    $progressIntoSegment = $adjustedProgress - $segmentStart;
                                    $segmentLength = max($segmentEnd - $segmentStart, 1);
                                    $segmentProgress = max(0, min(($progressIntoSegment / $segmentLength) * 100, 100));
                                    $isFinalWaypoint = $index === count($journeyWaypoints) - 1;
                                ?>
                                <div class="timeline-item mt-1 <?= $isReached ? 'active' : ''; ?> <?= $isCurrent ? 'current' : ''; ?>">
                                    <div class="timeline-icon"><i class="fas <?= $waypoint['icon']; ?>"></i></div>
                                    <div class="flex-grow-1">
                                        <div class="fw-semibold mb-1 d-flex justify-content-between align-items-center gap-2 flex-wrap">
                                            <span><?= $waypoint['label']; ?></span>
                                            <?php if ($isCurrent): ?>
                                                <span class="badge bg-warning bg-opacity-25 text-warning border border-warning">Current</span>
                                            <?php elseif ($isReached): ?>
                                                <span class="badge bg-success bg-opacity-25 text-success border border-success">Reached</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center small text-secondary">
                                            <span>Checkpoint <?= $index + 1; ?> of <?= count($journeyWaypoints); ?></span>
                                        </div>
                                        <div class="progress waypoint-progress mt-2" role="progressbar" aria-valuenow="<?= (int) $segmentProgress; ?>" aria-valuemin="0" aria-valuemax="100">
                                            <div class="progress-bar bg-ranked-1" style="width: <?= $isFinalWaypoint ? ($isReached ? '100' : (string) $segmentProgress) : (string) $segmentProgress; ?>%;"></div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
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

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const timelineContainer = document.querySelector('.timeline-scroll');
        if (!timelineContainer) return;

        const activeWaypoint = timelineContainer.querySelector('.timeline-item.current') || timelineContainer.querySelector('.timeline-item.active:last-child');

        if (activeWaypoint) {
            const offset = activeWaypoint.offsetTop - (timelineContainer.clientHeight / 2) + (activeWaypoint.clientHeight / 2);
            timelineContainer.scrollTo({ top: offset, behavior: 'smooth' });
        }
    });
</script>

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
    padding: 1.5rem 2rem;
    background: linear-gradient(135deg, rgba(17, 24, 39, 0.95), rgba(15, 23, 42, 0.9)),
        url("<?= $imgStarMap; ?>") no-repeat center center;
    background-size: cover;
    border: 1px solid rgba(255, 255, 255, 0.08);
}

.emberwood-progress-ambient {
    position: absolute;
    inset: 0;
    overflow: hidden;
    pointer-events: none;
    border-radius: 14px;
}

.emberwood-progress-ambient::before,
.emberwood-progress-ambient::after {
    content: "";
    position: absolute;
    inset: 5%;
    background: conic-gradient(from 180deg, rgba(251, 191, 36, 0.04), rgba(249, 115, 22, 0.12), rgba(14, 165, 233, 0.08), rgba(251, 191, 36, 0.04));
    filter: blur(24px);
    animation: emberwood-aurora 12s ease-in-out infinite;
    opacity: 0.6;
}

.emberwood-progress-ambient::after {
    animation-direction: reverse;
    animation-duration: 16s;
    opacity: 0.35;
}

.emberwood-progress-glow {
    position: absolute;
    inset: 18% 8%;
    background: radial-gradient(circle at 40% 50%, rgba(251, 191, 36, 0.35), transparent 50%);
    filter: blur(16px);
    animation: emberwood-pulse 5s ease-in-out infinite;
}

.emberwood-progress-glow-secondary {
    inset: 55% 20% 10% 45%;
    background: radial-gradient(circle at 60% 50%, rgba(59, 130, 246, 0.25), transparent 45%);
    animation-duration: 7s;
}

.emberwood-progress-stars {
    position: absolute;
    inset: 0;
    background-image:
        radial-gradient(1px 1px at 10% 20%, rgba(255, 255, 255, 0.8), transparent),
        radial-gradient(2px 2px at 25% 60%, rgba(255, 220, 150, 0.75), transparent),
        radial-gradient(1px 1px at 70% 35%, rgba(255, 255, 255, 0.6), transparent),
        radial-gradient(2px 2px at 85% 75%, rgba(255, 255, 255, 0.7), transparent);
    animation: emberwood-twinkle 8s ease-in-out infinite;
    opacity: 0.7;
}

.emberwood-progress-track {
    left: 2.5rem;
    right: 2.5rem;
    width: auto;
    height: 16px;
    border: 1px solid rgba(255, 255, 255, 0.12);
    box-shadow: inset 0 0 0 1px rgba(0, 0, 0, 0.35);
}

.emberwood-progress-track .progress-bar {
    background: linear-gradient(90deg, #fbbf24 0%, #f97316 50%, #fbbf24 100%);
    background-size: 220% 100%;
    animation: emberwood-progress-flow 6s ease-in-out infinite;
    box-shadow: 0 0 18px rgba(251, 191, 36, 0.6), 0 0 40px rgba(249, 115, 22, 0.35);
}

.emberwood-progress-track::after {
    content: "";
    position: absolute;
    inset: -6px;
    border-radius: 999px;
    border: 1px solid rgba(251, 191, 36, 0.25);
    animation: emberwood-track-glow 4s ease-in-out infinite;
    pointer-events: none;
}

.emberwood-progress-overlay {
    position: absolute;
    top: 0;
    bottom: 0;
    left: 2.5rem;
    right: 2.5rem;
}

/* Ship Icon */
.emberwood-tracker-ship-icon {
    position: absolute;
    top: 50%;
    transform: translate(-50%, -50%);
    transition: left 1s ease-in-out;
    text-align: center;
    max-width: 220px;
    animation: emberwood-ship-bob 5s ease-in-out infinite;
}

.emberwood-ship-trail {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-65%, -45%);
    width: 110px;
    height: 24px;
    background: radial-gradient(circle at 0% 50%, rgba(251, 191, 36, 0.75), transparent 55%);
    filter: blur(12px);
    opacity: 0.75;
    animation: emberwood-trail-fade 2.5s ease-in-out infinite;
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

@keyframes emberwood-progress-flow {
    0% { background-position: 0% 50%; }
    50% { background-position: 120% 50%; }
    100% { background-position: 0% 50%; }
}

@keyframes emberwood-track-glow {
    0%, 100% { box-shadow: 0 0 12px rgba(251, 191, 36, 0.25); opacity: 0.9; }
    50% { box-shadow: 0 0 24px rgba(249, 115, 22, 0.35); opacity: 0.55; }
}

@keyframes emberwood-ship-bob {
    0%, 100% { transform: translate(-50%, -50%) translateY(0); }
    50% { transform: translate(-50%, -50%) translateY(-6px); }
}

@keyframes emberwood-trail-fade {
    0% { opacity: 0.85; transform: translate(-65%, -45%) scaleX(1); }
    50% { opacity: 0.45; transform: translate(-65%, -45%) scaleX(0.7); }
    100% { opacity: 0.85; transform: translate(-65%, -45%) scaleX(1); }
}

@keyframes emberwood-aurora {
    0% { transform: translateX(-6%) rotate(0deg); }
    50% { transform: translateX(6%) rotate(180deg); }
    100% { transform: translateX(-6%) rotate(360deg); }
}

@keyframes emberwood-pulse {
    0%, 100% { opacity: 0.5; transform: scale(1); }
    50% { opacity: 0.85; transform: scale(1.15); }
}

@keyframes emberwood-twinkle {
    0%, 100% { opacity: 0.45; filter: drop-shadow(0 0 6px rgba(255, 255, 255, 0.6)); }
    50% { opacity: 0.9; filter: drop-shadow(0 0 12px rgba(251, 191, 36, 0.9)); }
}

.timeline {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.timeline-scroll {
    max-height: 360px;
    overflow-y: auto;
    padding-right: 6px;
}

.timeline-scroll::-webkit-scrollbar {
    width: 8px;
}

.timeline-scroll::-webkit-scrollbar-thumb {
    background: linear-gradient(180deg, rgba(251, 191, 36, 0.7), rgba(59, 130, 246, 0.5));
    border-radius: 999px;
}

.timeline-scroll::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 999px;
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
    min-height: 110px;
}

.timeline-item .fw-semibold {
    color: #f1f5f9;
}

.timeline-item.active {
    border-color: #fbbf24;
    box-shadow: 0 10px 20px rgba(251, 191, 36, 0.15);
    transform: translateY(-2px);
}

.timeline-item.current {
    border-color: #38bdf8;
    box-shadow: 0 10px 24px rgba(56, 189, 248, 0.2);
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

.waypoint-progress {
    height: 8px;
    background-color: rgba(255, 255, 255, 0.1);
    border-radius: 999px;
    overflow: hidden;
}

.waypoint-progress .progress-bar {
    background: linear-gradient(90deg, #fbbf24 0%, #f97316 50%, #fbbf24 100%);
}

.ship-status-text {
    font-size: 1.1rem;
    line-height: 1.5;
    font-family: 'Poppins', sans-serif;
}

</style>
