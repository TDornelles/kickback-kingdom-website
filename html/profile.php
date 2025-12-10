<?php
require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__) . "/Kickback/init.php");

$session = require(\Kickback\SCRIPT_ROOT . "/api/v1/engine/session/verifySession.php");
require("php-components/base-page-pull-active-account-info.php");

use Kickback\Common\Utility\IDCrypt;

use Kickback\Backend\Controllers\AccountController;
use Kickback\Backend\Controllers\PrestigeController;
use Kickback\Backend\Controllers\ActivityController;
use Kickback\Backend\Controllers\FeedCardController;
use Kickback\Backend\Controllers\LootController;
use Kickback\Backend\Controllers\ItemController;
use Kickback\Backend\Models\ItemEquipmentSlot;
use Kickback\Common\Version;
use Kickback\Backend\Views\vRecordId;

if (isset($_GET['id'])) {
    $accountId = new vRecordId('', $_GET['delegateAccess']);
    $profileResp = AccountController::getAccountById($accountId);
}

if (isset($_GET['u']) && !isset($profile)) {
    $profileResp = AccountController::getAccountByUsername($_GET['u']);
}

if (isset($profileResp)) {
    $profile = $profileResp->data;
} else {
    $profile = null;
}

$thisProfile = $profile;

if (!isset($hasError))
    $hasError = false;
if (!isset($hasSuccess))
    $hasSuccess = false;
if (!isset($errorMessage))
    $errorMessage = '';
if (!isset($successMessage))
    $successMessage = '';

$isMyProfile = false;

$check_prestige_token_use = function()
    use($profile, &$isMyProfile, &$hasError, &$errorMessage, &$hasSuccess, &$successMessage) : void
{
    if (!Kickback\Services\Session::isLoggedIn()) {
        return;
    }

    if (!isset($profile)) {
        return;
    }

    $currentAccount = Kickback\Services\Session::getCurrentAccount();
    if (!isset($currentAccount)) {
        return;
    }

    $isMyProfile = ($profile->crand === $currentAccount->crand);
    if ($isMyProfile || !isset($_POST["review-submit"])) {
        return;
    }

    $desc = $_POST["review-message"];
    if (!isset($desc)) {
        $hasError = true;
        $errorMessage = "Failed to use prestige token because a null review was provided.";
        return;
    }

    $rating = $_POST["review-rating"];
    if ($rating == "-1") {
        $commend = false;
    } else
    if ($rating == "1") {
        $commend = true;
    } else {
        $hasError = true;
        $errorMessage = "Failed to use prestige token because a null rating was provided.";
        return;
    }

    $usePrestigeTokenResp = ItemController::usePrestigeToken(Kickback\Services\Session::getCurrentAccount(),$profile,$commend,$desc);
    if ($usePrestigeTokenResp->success == false)
    {
        $hasError = true;
        $errorMessage = $usePrestigeTokenResp->message;
        return;
    }
    else
    {
        $hasSuccess = true;
        $successMessage= $usePrestigeTokenResp->message;
        return;
    }
};

$check_prestige_token_use();

$badgesResp = LootController::getBadgesByAccount($profile);
$badges = $badgesResp->data;

$prestigeResp = PrestigeController::queryPrestigeReviewsByAccountAsResponse($profile);

if (!PrestigeController::convertPrestigeReviewsResponseInto($prestigeResp, $prestigeReviews))
{
    $showPopUpError = true;
    $PopUpTitle = "Error!";
    $PopUpMessage = $prestigeResp->message;
}

//$prestigeNet = PrestigeController::getAccountPrestigeValue($prestigeReviews);

$accountInventoryResp = AccountController::getAccountInventory($profile);
$accountInventory = $accountInventoryResp->data;

$accountActivityResp = ActivityController::getActivityByAccount($profile);
$accountActivity = $accountActivityResp->data;

$itemInfos = [];

$nextWritOfPassageId = null;
$nextWritOfPassageURL = null;
foreach ($accountInventory as $accountInventoryItemStack) {

    if ($accountInventoryItemStack->item->isWritOfPassage()) {
        if ($isMyProfile)
        {
            $kk_crypt_key_writ_id = \Kickback\Backend\Config\ServiceCredentials::get("crypt_key_quest_id");
            $crypt = new IDCrypt($kk_crypt_key_writ_id);
            $nextWritOfPassageId = urlencode($crypt->encrypt($accountInventoryItemStack->nextLootId->crand));
            $nextWritOfPassageURL = 'https://kickback-kingdom.com/register.php?wi='.$nextWritOfPassageId;//'https://kickback-kingdom.com/register.php?redirect='.urlencode(Version::urlBetaPrefix().'/blog/Kickback-Kingdom/introduction').'&wi='.$nextWritOfPassageId;
        }
        else
        {
            // BUG: This variable/array isn't used. Note the lack of an 's'. Typo?
            $itemInfo["useable"] = false;
        }
    }
    array_push($itemInfos, $accountInventoryItemStack->item);
}


$itemInformationJSON = json_encode($itemInfos);
$itemStackInformationJSON = json_encode($accountInventory);
$activeTab = 'active';
$activeTabPage = 'active show';

$emptySlotPath = 'http://localhost/assets/media/menu/empty_slot.jpg';

$equippedAvatarLootId = '';
$equippedAvatarIconPath = $emptySlotPath;
$equippedPcCardLootId = '';
$equippedPcCardPath = null;
$equippedBannerLootId = '';
$defaultDesktopBannerPath = '/assets/images/kk-1.jpg';
$defaultMobileBannerPath = '/assets/images/kk-2.jpg';
$equippedBannerPath = $defaultDesktopBannerPath;
$equippedBannerMobilePath = $defaultMobileBannerPath;

$equippedBackgroundLootId = '';
$equippedBackgroundPath = null;

$equippedCharmLootId = '';
$equippedCharmPath = null;

$equippedPetLootId = '';
$equippedPetPath = null;

$equippedAvatarPreviewPath = $emptySlotPath;
$equippedPcCardPreviewPath = $emptySlotPath;
$equippedBannerPreviewPath = $emptySlotPath;
$equippedBackgroundPreviewPath = $emptySlotPath;
$equippedCharmPreviewPath = $emptySlotPath;
$equippedPetPreviewPath = $emptySlotPath;

if (isset($profile) && isset($profile->avatar)) {
    $equippedAvatarIconPath = $profile->avatar->getFullPath();
}

if (isset($profile) && isset($profile->playerCardBorder)) {
    $equippedPcCardPath = $profile->playerCardBorder->getFullPath();
}

if (isset($profile) && isset($profile->banner)) {
    $equippedBannerPath = $profile->banner->getFullPath();
    $equippedBannerMobilePath = $profile->banner->getFullPath();
}

if (isset($profile) && isset($profile->background)) {
    $equippedBackgroundPath = $profile->background->getFullPath();
}

if (isset($profile) && isset($profile->charm)) {
    $equippedCharmPath = $profile->charm->getFullPath();
}

if (isset($profile) && isset($profile->companion)) {
    $equippedPetPath = $profile->companion->getFullPath();
}

foreach ($accountInventory as $accountInventoryItemStack) {
    if ($accountInventoryItemStack->item->equipable && $accountInventoryItemStack->item->equipmentSlot == ItemEquipmentSlot::AVATAR) {
        if ($accountInventoryItemStack->item->iconSmall->getFullPath() === $equippedAvatarIconPath) {
            if (isset($accountInventoryItemStack->nextLootId)) {
                $equippedAvatarLootId = $accountInventoryItemStack->nextLootId->crand;
            } elseif (isset($accountInventoryItemStack->itemLootId)) {
                $equippedAvatarLootId = $accountInventoryItemStack->itemLootId->crand;
            }
            break;
        }
    }
}

foreach ($accountInventory as $accountInventoryItemStack) {
    if ($accountInventoryItemStack->item->equipable && $accountInventoryItemStack->item->equipmentSlot == ItemEquipmentSlot::PC_BORDER) {
        $pcBorderMediaPaths = array_filter([
            $accountInventoryItemStack->item->iconBack?->getFullPath(),
            $accountInventoryItemStack->item->iconBig?->getFullPath(),
            $accountInventoryItemStack->item->iconSmall->getFullPath(),
        ]);

        if ($equippedPcCardPath !== null && in_array($equippedPcCardPath, $pcBorderMediaPaths, true)) {
            if (isset($accountInventoryItemStack->nextLootId)) {
                $equippedPcCardLootId = $accountInventoryItemStack->nextLootId->crand;
            } elseif (isset($accountInventoryItemStack->itemLootId)) {
                $equippedPcCardLootId = $accountInventoryItemStack->itemLootId->crand;
            }
            break;
        }
    }
}

foreach ($accountInventory as $accountInventoryItemStack) {
    if ($accountInventoryItemStack->item->equipable && $accountInventoryItemStack->item->equipmentSlot == ItemEquipmentSlot::BANNER) {
        $bannerMediaPaths = [
            $accountInventoryItemStack->item->iconBig->getFullPath(),
            $accountInventoryItemStack->item->iconSmall->getFullPath(),
            $accountInventoryItemStack->item->iconBack->getFullPath(),
        ];

        if (in_array($equippedBannerPath, $bannerMediaPaths, true)) {
            if (isset($accountInventoryItemStack->nextLootId)) {
                $equippedBannerLootId = $accountInventoryItemStack->nextLootId->crand;
            } elseif (isset($accountInventoryItemStack->itemLootId)) {
                $equippedBannerLootId = $accountInventoryItemStack->itemLootId->crand;
            }
            break;
        }
    }
}

foreach ($accountInventory as $accountInventoryItemStack) {
    if ($accountInventoryItemStack->item->equipable && $accountInventoryItemStack->item->equipmentSlot == ItemEquipmentSlot::BACKGROUND) {
        $backgroundMediaPaths = array_filter([
            $accountInventoryItemStack->item->iconBack?->getFullPath(),
            $accountInventoryItemStack->item->iconBig?->getFullPath(),
            $accountInventoryItemStack->item->iconSmall->getFullPath(),
        ]);

        if ($equippedBackgroundPath !== null && in_array($equippedBackgroundPath, $backgroundMediaPaths, true)) {
            if (isset($accountInventoryItemStack->nextLootId)) {
                $equippedBackgroundLootId = $accountInventoryItemStack->nextLootId->crand;
            } elseif (isset($accountInventoryItemStack->itemLootId)) {
                $equippedBackgroundLootId = $accountInventoryItemStack->itemLootId->crand;
            }
            break;
        }
    }
}

$equippedCharmItemMediaPaths = array_filter([$equippedCharmPath]);
foreach ($accountInventory as $accountInventoryItemStack) {
    if ($accountInventoryItemStack->item->equipable && $accountInventoryItemStack->item->equipmentSlot == ItemEquipmentSlot::CHARM) {
        $charmMediaPaths = array_filter([
            $accountInventoryItemStack->item->iconBack?->getFullPath(),
            $accountInventoryItemStack->item->iconBig?->getFullPath(),
            $accountInventoryItemStack->item->iconSmall->getFullPath(),
        ]);

        if ($equippedCharmPath !== null && array_intersect($equippedCharmItemMediaPaths, $charmMediaPaths)) {
            if (isset($accountInventoryItemStack->nextLootId)) {
                $equippedCharmLootId = $accountInventoryItemStack->nextLootId->crand;
            } elseif (isset($accountInventoryItemStack->itemLootId)) {
                $equippedCharmLootId = $accountInventoryItemStack->itemLootId->crand;
            }
            break;
        }
    }
}

foreach ($accountInventory as $accountInventoryItemStack) {
    if ($accountInventoryItemStack->item->equipable && $accountInventoryItemStack->item->equipmentSlot == ItemEquipmentSlot::PET) {
        $petMediaPaths = array_filter([
            $accountInventoryItemStack->item->iconBack?->getFullPath(),
            $accountInventoryItemStack->item->iconBig?->getFullPath(),
            $accountInventoryItemStack->item->iconSmall->getFullPath(),
        ]);

        if ($equippedPetPath !== null && in_array($equippedPetPath, $petMediaPaths, true)) {
            if (isset($accountInventoryItemStack->nextLootId)) {
                $equippedPetLootId = $accountInventoryItemStack->nextLootId->crand;
            } elseif (isset($accountInventoryItemStack->itemLootId)) {
                $equippedPetLootId = $accountInventoryItemStack->itemLootId->crand;
            }
            break;
        }
    }
}

$equippedAvatarPreviewPath = $equippedAvatarLootId === '' ? $emptySlotPath : $equippedAvatarIconPath;
$equippedPcCardPreviewPath = $equippedPcCardLootId === '' ? $emptySlotPath : ($equippedPcCardPath ?? $emptySlotPath);
$equippedBannerPreviewPath = $equippedBannerLootId === '' ? $emptySlotPath : $equippedBannerPath;
$equippedBackgroundPreviewPath = $equippedBackgroundLootId === '' ? $emptySlotPath : ($equippedBackgroundPath ?? $emptySlotPath);
$equippedCharmPreviewPath = $equippedCharmLootId === '' ? $emptySlotPath : ($equippedCharmPath ?? $emptySlotPath);
$equippedPetPreviewPath = $equippedPetLootId === '' ? $emptySlotPath : ($equippedPetPath ?? $emptySlotPath);
?>

<!DOCTYPE html>
<html lang="en">


<?php require("php-components/base-page-head.php"); ?>

<body class="bg-body-secondary container p-0" <?php if ($equippedBackgroundPath !== null) { ?>style="background-image: url('<?= htmlspecialchars($equippedBackgroundPath); ?>') !important;"<?php } ?>>
    
    <?php 
    
    require("php-components/base-page-components.php"); 
    
    
    ?>

    <div>
        <!--TOP BANNER-->
        <div class="d-none d-md-block w-100 ratio" style="--bs-aspect-ratio: 26%; margin-top: 56px">
            <img src="<?= htmlspecialchars($equippedBannerPath); ?>" class="object-fit-cover w-100 h-100" />
            <img class="img-fluid img-thumbnail" src="<?= $profile->profilePictureURL(); ?>" style="width: auto;height: 90%;top: 5%;left: 5%;">
        </div>
        <div class="d-block d-md-none w-100 ratio" style="margin-top: 56px; --bs-aspect-ratio: 46.3%;">
            <img src="<?= htmlspecialchars($equippedBannerMobilePath); ?>" class="object-fit-cover w-100 h-100" />
            <img class="img-fluid img-thumbnail" src="<?= $profile->profilePictureURL(); ?>" style="width: auto;height: 90%;top: 5%;left: 5%;">
        </div>
    </div>

    <!--REVIEW MODAL-->
    <form method="POST">
        <div class="modal fade" id="reviewModal" tabindex="-1" aria-labelledby="reviewModalLabel" style="display: none;" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="reviewModalLabel">Write about:</h5>
                        <button class="btn-close btn-close-white" type="button" data-bs-dismiss="modal" aria-label="Close" data-bs-original-title="" title=""></button>
                    </div>
                    <div class="modal-body">  
                        <div class="mb-3">
                            <label class="col-form-label" for="textareaReviewModal">Message:</label>
                            <textarea class="form-control" id="textareaReviewModal" name="review-message" required="" maxlength="500" style="height: 103px;"></textarea>
                            <input id="review-rating" type="hidden" name="review-rating" required=""/>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <input id="plusButton" class="badge" onclick="rate(1)" type="button" value="+1" required style="display:block; background-color: rgb(143, 151, 178);"/>
                        <input id="minusButton" class="badge" onclick="rate(2)" type="button" value="-1" required style="display:block; background-color: rgb(143, 151, 178);"/>
                        <button class="btn btn-primary" type="button" data-bs-dismiss="modal" data-bs-original-title="" title="">Close</button>
                        <input class="btn bg-ranked-1" name="review-submit" onclick="validateSelection(event)" type="submit" value="Submit"/>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <!--EQUIPMENT MODAL-->
    <form method="POST">
            
        <input id="equipment-account-id" type="hidden" name="equipment-account-id" required="" value="<?php echo $profile->crand; ?>"/>
        <input id="equipment-avatar" type="hidden" name="equipment-avatar" required="" value="<?= htmlspecialchars((string)$equippedAvatarLootId); ?>"/>
        <input id="equipment-pc-card" type="hidden" name="equipment-pc-card" required="" value="<?= htmlspecialchars((string)$equippedPcCardLootId); ?>"/>
        <input id="equipment-banner" type="hidden" name="equipment-banner" required="" value="<?= htmlspecialchars((string)$equippedBannerLootId); ?>"/>
        <input id="equipment-background" type="hidden" name="equipment-background" required="" value="<?= htmlspecialchars((string)$equippedBackgroundLootId); ?>"/>
        <input id="equipment-charm" type="hidden" name="equipment-charm" required="" value="<?= htmlspecialchars((string)$equippedCharmLootId); ?>"/>
        <input id="equipment-pet" type="hidden" name="equipment-pet" required="" value="<?= htmlspecialchars((string)$equippedPetLootId); ?>"/>

        <div class="modal fade" id="equipmentModal" tabindex="-1" aria-labelledby="equipmentModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" id="equipmentModalLabel">Equipment</h1>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-12 col-lg-6">
                                <div class="card border-dark mb-3">
                                    
                                    <div class="row g-0">
                                        <div class="col-4">
                                            <button type="button"  class="btn btn-dark p-0" data-bs-target="#selectAvatarModal" data-bs-toggle="modal"><img id="equipment-preview-avatar" src="<?= htmlspecialchars($equippedAvatarPreviewPath); ?>" class="img-fluid"></button>
                                        </div>
                                        <div class="col-8">

                                            <div class="card-header">Avatar</div>
                                            <div class="card-body pt-1">
                                                <p class="card-text">This is how others will see you.</p>
                                                <?php if ($isMyProfile) { ?>
                                                <button type="button" class="btn btn-outline-danger btn-sm" data-unequip-slot="AVATAR" onclick="unequipSlot('AVATAR')">Unequip</button>
                                                <?php } ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-lg-6">
                                <div class="card border-dark mb-3">
                                    
                                    <div class="row g-0">
                                        <div class="col-4">
                                            <button type="button" data-bs-target="#selectPCBorderModal" data-bs-toggle="modal" class="btn btn-dark p-0"><img id="equipment-preview-pc-card" src="<?= htmlspecialchars($equippedPcCardPreviewPath); ?>" class="img-fluid"></button>
                                        </div>
                                        <div class="col-8">

                                            <div class="card-header">Player Card Border</div>
                                            <div class="card-body pt-1">
                                                <p class="card-text">This is a border that will be on top of your player card.</p>
                                                <?php if ($isMyProfile) { ?>
                                                <button type="button" class="btn btn-outline-danger btn-sm" data-unequip-slot="PC_CARD" onclick="unequipSlot('PC_CARD')">Unequip</button>
                                                <?php } ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>
                            <div class="col-12 col-lg-6">
                                <div class="card border-dark mb-3">

                                    <div class="row g-0">
                                        <div class="col-4">
                                            <button type="button" data-bs-target="#selectBannerModal" data-bs-toggle="modal" class="btn btn-dark p-0"><img id="equipment-preview-banner" src="<?= htmlspecialchars($equippedBannerPreviewPath); ?>" class="img-fluid object-fit-cover"></button>
                                        </div>
                                        <div class="col-8">

                                            <div class="card-header">Banner</div>
                                            <div class="card-body pt-1">
                                                <p class="card-text">This is what shows at the top of your profile page.</p>
                                                <?php if ($isMyProfile) { ?>
                                                <button type="button" class="btn btn-outline-danger btn-sm" data-unequip-slot="BANNER" onclick="unequipSlot('BANNER')">Unequip</button>
                                                <?php } ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            
                            </div>
                            <div class="col-12 col-lg-6">
                                <div class="card border-dark mb-3">
                                    
                                    <div class="row g-0">
                                        <div class="col-4">
                                            <button type="button" data-bs-target="#selectBackgroundModal" data-bs-toggle="modal" class="btn btn-dark p-0"><img id="equipment-preview-background" src="<?= htmlspecialchars($equippedBackgroundPreviewPath); ?>" class="img-fluid object-fit-cover"></button>
                                        </div>
                                        <div class="col-8">

                                            <div class="card-header">Background</div>
                                            <div class="card-body pt-1">
                                                <p class="card-text">This is the background of your profile page.</p>
                                                <?php if ($isMyProfile) { ?>
                                                <button type="button" class="btn btn-outline-danger btn-sm" data-unequip-slot="BACKGROUND" onclick="unequipSlot('BACKGROUND')">Unequip</button>
                                                <?php } ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-lg-6">
                                <div class="card border-dark mb-3">

                                    <div class="row g-0">
                                        <div class="col-4">
                                            <button type="button" data-bs-target="#selectCharmModal" data-bs-toggle="modal" class="btn btn-dark p-0"><img id="equipment-preview-charm" src="<?= htmlspecialchars($equippedCharmPreviewPath); ?>" class="img-fluid"></button>
                                        </div>
                                        <div class="col-8">

                                            <div class="card-header">Charm</div>
                                            <div class="card-body pt-1">
                                                <p class="card-text">Can effect your experiences in the kingdom</p>
                                                <?php if ($isMyProfile) { ?>
                                                <button type="button" class="btn btn-outline-danger btn-sm" data-unequip-slot="CHARM" onclick="unequipSlot('CHARM')">Unequip</button>
                                                <?php } ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-lg-6">
                                <div class="card border-dark mb-3">

                                    <div class="row g-0">
                                        <div class="col-4">
                                            <button type="button" data-bs-target="#selectPetModal" data-bs-toggle="modal" class="btn btn-dark p-0"><img id="equipment-preview-pet" src="<?= htmlspecialchars($equippedPetPreviewPath); ?>" class="img-fluid"></button>
                                        </div>
                                        <div class="col-8">

                                            <div class="card-header">Pet</div>
                                            <div class="card-body pt-1">
                                                <p class="card-text">Can effect your experiences in the kingdom</p>
                                                <?php if ($isMyProfile) { ?>
                                                <button type="button" class="btn btn-outline-danger btn-sm" data-unequip-slot="PET" onclick="unequipSlot('PET')">Unequip</button>
                                                <?php } ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>

                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button>
                        
                        <?php 

                        if ($isMyProfile)
                        {

                        ?>
                        <input type="submit" name="submit-equipment" class="btn bg-ranked-1" value="Save changes" />
                        <?php 
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </form>
    

    <!--SELECT AVATAR MODAL-->
    <div class="modal fade" id="selectAvatarModal" tabindex="-1" aria-labelledby="selectAvatarModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="selectAvatarModalLabel">Select an Avatar</h1>
                <button type="button" class="btn-close btn-close-white"  data-bs-target="#equipmentModal" data-bs-toggle="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-12">
                        <!-- side-bar colleps block stat-->
                        <div class="inventory-grid-sm">
                            <?php
                            
                            // Show category title

                            foreach ($accountInventory as $accountInventoryItemStack) {
                                if ($accountInventoryItemStack->item->equipable && $accountInventoryItemStack->item->equipmentSlot == ItemEquipmentSlot::AVATAR)
                                {
                                ?>
                            <div class="inventory-item" onclick="SelectInventoryItemStackEquipment(<?= $accountInventoryItemStack->item->crand; ?>);"  data-bs-toggle="tooltip" data-bs-dismiss="modal" data-bs-placement="bottom" data-bs-title="<?= htmlspecialchars($accountInventoryItemStack->item->name)?>">
                                <img src="<?= $accountInventoryItemStack->item->iconSmall->getFullPath(); ?>" alt="Item <?= $accountInventoryItemStack->item->name; ?>">
                                <div class="item-count">x<?= $accountInventoryItemStack->amount; ?></div>
                            </div>
                            
                            <?php
                                }
                            }

                            ?>
                        </div>
                    </div>
                </div> 
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-target="#equipmentModal" data-bs-toggle="modal">Back</button>
            </div>
            </div>
        </div>
    </div>

    
    <!--SELECT PET MODAL-->
    <div class="modal fade" id="selectPetModal" tabindex="-1" aria-labelledby="selectPetModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="selectPetModalLabel">Select a Pet</h1>
                <button type="button" class="btn-close btn-close-white"  data-bs-target="#equipmentModal" data-bs-toggle="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-12">
                        <!-- side-bar colleps block stat-->
                        <div class="inventory-grid-sm">
                            
                        <?php
                            
                            // Show category title

                            foreach ($accountInventory as $accountInventoryItemStack) {
                                if ($accountInventoryItemStack->item->equipable && $accountInventoryItemStack->item->equipmentSlot == ItemEquipmentSlot::PET)
                                {
                                ?>
                                <div class="inventory-item" onclick="SelectInventoryItemStackEquipment(<?= $accountInventoryItemStack->item->crand; ?>);"  data-bs-toggle="tooltip" data-bs-dismiss="modal" data-bs-placement="bottom" data-bs-title="<?= htmlspecialchars($accountInventoryItemStack->item->name)?>">
                                    <img src="<?= $accountInventoryItemStack->item->iconSmall->getFullPath(); ?>" alt="Item <?= $accountInventoryItemStack->item->name; ?>">
                                    <div class="item-count">x<?= $accountInventoryItemStack->amount; ?></div>
                                </div>
                            
                            <?php
                                }
                            }

                            ?>
                        </div>
                    </div>
                </div> 
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-target="#equipmentModal" data-bs-toggle="modal">Back</button>
            </div>
            </div>
        </div>
    </div>

    
    <!--SELECT CHARM MODAL-->
    <div class="modal fade" id="selectCharmModal" tabindex="-1" aria-labelledby="selectCharmModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="selectCharmModalLabel">Select a Charm</h1>
                <button type="button" class="btn-close btn-close-white"  data-bs-target="#equipmentModal" data-bs-toggle="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-12">
                        <!-- side-bar colleps block stat-->
                        <div class="inventory-grid-sm">
                            
                        <?php
                            
                            // Show category title

                            foreach ($accountInventory as $accountInventoryItemStack) {
                                if ($accountInventoryItemStack->item->equipable && $accountInventoryItemStack->item->equipmentSlot == ItemEquipmentSlot::CHARM)
                                {
                                ?>
                                <div class="inventory-item" onclick="SelectInventoryItemStackEquipment(<?= $accountInventoryItemStack->item->crand; ?>);"  data-bs-toggle="tooltip" data-bs-dismiss="modal" data-bs-placement="bottom" data-bs-title="<?= htmlspecialchars($accountInventoryItemStack->item->name)?>">
                                    <img src="<?= $accountInventoryItemStack->item->iconSmall->getFullPath(); ?>" alt="Item <?= $accountInventoryItemStack->item->name; ?>">
                                    <div class="item-count">x<?= $accountInventoryItemStack->amount; ?></div>
                                </div>
                            
                            <?php
                                }
                            }

                            ?>
                        </div>
                    </div>
                </div> 
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-target="#equipmentModal" data-bs-toggle="modal">Back</button>
            </div>
            </div>
        </div>
    </div>

    
    <!--SELECT PC BORDER MODAL-->
    <div class="modal fade" id="selectPCBorderModal" tabindex="-1" aria-labelledby="selectPCBorderModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="selectPCBorderModalLabel">Select a Player Card Border</h1>
                <button type="button" class="btn-close btn-close-white"  data-bs-target="#equipmentModal" data-bs-toggle="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-12">
                        <!-- side-bar colleps block stat-->
                        <div class="inventory-grid-sm">
                            
                        <?php
                            
                            // Show category title

                            foreach ($accountInventory as $accountInventoryItemStack) {
                                if ($accountInventoryItemStack->item->equipable && $accountInventoryItemStack->item->equipmentSlot == ItemEquipmentSlot::PC_BORDER)
                                {
                                ?>
                                <div class="inventory-item" onclick="SelectInventoryItemStackEquipment(<?= $accountInventoryItemStack->item->crand; ?>);"  data-bs-toggle="tooltip" data-bs-dismiss="modal" data-bs-placement="bottom" data-bs-title="<?= htmlspecialchars($accountInventoryItemStack->item->name)?>">
                                    <img src="<?= $accountInventoryItemStack->item->iconSmall->getFullPath(); ?>" alt="Item <?= $accountInventoryItemStack->item->name; ?>">
                                    <div class="item-count">x<?= $accountInventoryItemStack->amount; ?></div>
                                </div>
                            
                            <?php
                                }
                            }

                            ?>
                        </div>
                    </div>
                </div> 
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-target="#equipmentModal" data-bs-toggle="modal">Back</button>
            </div>
            </div>
        </div>
    </div>

    
    <!--SELECT BANNER MODAL-->
    <div class="modal fade" id="selectBannerModal" tabindex="-1" aria-labelledby="selectBannerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="selectBannerModalLabel">Select a Banner</h1>
                <button type="button" class="btn-close btn-close-white"  data-bs-target="#equipmentModal" data-bs-toggle="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-12">
                        <!-- side-bar colleps block stat-->
                        <div class="inventory-grid-sm">
                            
                        <?php
                            
                            // Show category title

                            foreach ($accountInventory as $accountInventoryItemStack) {
                                if ($accountInventoryItemStack->item->equipable && $accountInventoryItemStack->item->equipmentSlot == ItemEquipmentSlot::BANNER)
                                {
                                ?>
                                <div class="inventory-item" onclick="SelectInventoryItemStackEquipment(<?= $accountInventoryItemStack->item->crand; ?>);"  data-bs-toggle="tooltip" data-bs-dismiss="modal" data-bs-placement="bottom" data-bs-title="<?= htmlspecialchars($accountInventoryItemStack->item->name)?>">
                                    <img src="<?= $accountInventoryItemStack->item->iconSmall->getFullPath(); ?>" alt="Item <?= $accountInventoryItemStack->item->name; ?>">
                                    <div class="item-count">x<?= $accountInventoryItemStack->amount; ?></div>
                                </div>
                            
                            <?php
                                }
                            }

                            ?>
                        </div>
                    </div>
                </div> 
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-target="#equipmentModal" data-bs-toggle="modal">Back</button>
            </div>
            </div>
        </div>
    </div>

    
    <!--SELECT BACKGROUND MODAL-->
    <div class="modal fade" id="selectBackgroundModal" tabindex="-1" aria-labelledby="selectBackgroundModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="selectBackgroundModalLabel">Select a Background</h1>
                <button type="button" class="btn-close btn-close-white"  data-bs-target="#equipmentModal" data-bs-toggle="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-12">
                        <!-- side-bar colleps block stat-->
                        <div class="inventory-grid-sm">
                            
                        <?php
                            
                            // Show category title

                            foreach ($accountInventory as $accountInventoryItemStack) {
                                if ($accountInventoryItemStack->item->equipable && $accountInventoryItemStack->item->equipmentSlot == ItemEquipmentSlot::BACKGROUND)
                                {
                                ?>
                                <div class="inventory-item" onclick="SelectInventoryItemStackEquipment(<?= $accountInventoryItemStack->item->crand; ?>);"  data-bs-toggle="tooltip" data-bs-dismiss="modal" data-bs-placement="bottom" data-bs-title="<?= htmlspecialchars($accountInventoryItemStack->item->name)?>">
                                    <img src="<?= $accountInventoryItemStack->item->iconSmall->getFullPath(); ?>" alt="Item <?= $accountInventoryItemStack->item->name; ?>">
                                    <div class="item-count">x<?= $accountInventoryItemStack->amount; ?></div>
                                </div>
                            
                            <?php
                                }
                            }

                            ?>
                        </div>
                    </div>
                </div> 
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-target="#equipmentModal" data-bs-toggle="modal">Back</button>
            </div>
            </div>
        </div>
    </div>

    <!--TRADE MODAL-->
    <div class="modal fade" id="tradeModal" tabindex="-1" aria-labelledby="tradeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="tradeModalLabel">Offer a Trade</h1>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                ...
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary">Save changes</button>
            </div>
            </div>
        </div>
    </div>
    
    <!--NOMINATE MODAL-->
    <div class="modal fade" id="nominateModal" tabindex="-1" aria-labelledby="nominateModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="nominateModalLabel">Nominate <?php echo $profile->username; ?></h1>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                ...
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary">Save changes</button>
            </div>
            </div>
        </div>
    </div>

    
    <!--CRAFT MODAL-->
    <div class="modal fade" id="craftModal" tabindex="-1" aria-labelledby="craftModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="craftModalLabel">Craft</h1>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                ...
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary">Save changes</button>
            </div>
            </div>
        </div>
    </div>

    <!--MAIN CONTENT-->
    <main class="container pt-3 bg-body" style="margin-bottom: 56px;">
        <div class="row">
            <div class="col-12">
                <?php if ($hasError) {?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <strong>Oh snap!</strong> <?php echo $errorMessage; ?>
                </div>
                <?php } ?>
                <?php if ($hasSuccess) {?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <strong>Congrats!</strong> <?php echo $successMessage; ?>
                </div>
                <?php } ?>
            </div>
        </div>
        <div class="row">
            <div class="col-12 col-xl-9">
                
                
                <?php 

                $activePageName = $profile->username;
                require("php-components/base-page-breadcrumbs.php");

                ?>
                <div class="row">
                    <div class="col-12">
                        <nav>
                            <div class="nav nav-tabs" id="nav-tab" role="tablist">
                            <button class="nav-link active" id="nav-reviews-tab" data-bs-toggle="tab" data-bs-target="#nav-reviews" type="button" role="tab" aria-controls="nav-reviews" aria-selected="true"><i class="fa-solid fa-crown"></i></button>
                            <button class="nav-link" id="nav-badges-tab" data-bs-toggle="tab" data-bs-target="#nav-badges" type="button" role="tab" aria-controls="nav-badges" aria-selected="true"><i class="fa-solid fa-medal"></i></button>
                            <button class="nav-link" id="nav-inventory-tab" data-bs-toggle="tab" data-bs-target="#nav-inventory" type="button" role="tab" aria-controls="nav-inventory" aria-selected="true"><i class="fa-solid fa-suitcase"></i></button>
                            <button class="nav-link" id="nav-activity-tab" data-bs-toggle="tab" data-bs-target="#nav-activity" type="button" role="tab" aria-controls="nav-activity" aria-selected="true"><i class="fa-solid fa-bolt"></i></button>
                            </div>
                        </nav>
                        <div class="tab-content" id="nav-tabContent">
                            <div class="tab-pane fade active show" id="nav-reviews" role="tabpanel" aria-labelledby="nav-reviews-tab" tabindex="0">
                                <div class="display-6 tab-pane-title">Prestige
                        <?php if (!$isMyProfile) { ?><span class="float-end" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="Commend or Denounce"><button class="btn bg-ranked-1" type="button" data-bs-toggle="modal" data-bs-target="#reviewModal"><i class="fa-solid fa-crown"></i>/<i class="fa-solid fa-biohazard"></i></button></span><?php } ?></div>    
                                <div class="row">
                                <?php
                                    for ($i=0; $i < count($prestigeReviews); $i++) { 
                                        $prestigeReview = $prestigeReviews[$i];
                                        ?>
                                    <div class="col-12 col-lg-6">
                                        <div class="card mb-2 <?php echo ($prestigeReview->commend?"border-success":"border-danger"); ?>">
                                            <div class="card-header <?php echo ($prestigeReview->commend?"text-bg-success":"text-bg-danger"); ?>">
                                                <?php echo ($prestigeReview->commend?"<i class='fa-solid fa-crown'></i> Commend":"<i class='fa-solid fa-biohazard'></i> Denounce"); ?>
                                            </div>
                                            
                                            <div class="row g-0">
                                                <div class="col-md-12">
                                                    <div class="card-body">
                                                        <div class="row">
                                                            <div class="col-4">
                                                            
                                                                <img src="<?= $prestigeReview->fromAccount->avatar->url; ?>" class="img-fluid img-thumbnail">
                                                            </div>
                                                            <div class="col-8">
                                                                <blockquote class="blockquote mb-0">
                                                                <p><?= $prestigeReview->message; ?></p>
                                                                <footer class="blockquote-footer"><?= $prestigeReview->fromAccount->getAccountElement();?></footer>
                                                                </blockquote>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="card-footer">
                                            <?php
                                            if ($prestigeReview->fromQuest != null)
                                            {
                                                ?>
                                                <a class="btn btn-primary btn-sm" href="<?php echo Version::urlBetaPrefix(); ?>/q/<?php echo $prestigeReview->fromQuest->locator; ?>"><?php echo $prestigeReview->fromQuest->title; ?></a>
                                                <?php 

                                            }

                                            ?>
                                                <small class="text-body-secondary float-end"><?= $prestigeReview->dateTime->formattedBasic; ?></small>
                                            </div>
                                        </div>
                                    </div>
                                        <?php
                                    }
                                ?>
                                </div>
                            </div>
                            
                            <div class="tab-pane fade" id="nav-badges" role="tabpanel" aria-labelledby="nav-badges-tab" tabindex="0">
                                <div class="display-6 tab-pane-title">Badges<?php if (!$isMyProfile) { ?><button class="btn float-end bg-ranked-1" type="button" data-bs-toggle="modal" data-bs-target="#nominateModal"><i class="fa-solid fa-bullhorn"></i></button><?php } ?></div> 
                                <div class="row">
                                    <div class="col-12">
                                        <?php for ($i=0; $i < count($badges) ; $i++) { 
                                            $badge = $badges[$i];
                                            $earnedInQuest = ($badge->quest != null);
                                            $nominatedByUser = ($badge->item->nominatedBy != null);
                                            $awardedByHorsemen = (!$earnedInQuest && !$nominatedByUser);
                                            $title = "";
                                            if ($earnedInQuest)
                                            {
                                                $title = "<i class='fa-solid fa-medal'></i> Earned by Quest";
                                            }
                                            if ($nominatedByUser)
                                            {
                                                $title = "<i class='fa-solid fa-bullhorn'></i> Earned by Nomination";
                                            }
                                            if ($awardedByHorsemen)
                                            {
                                                $title = "<i class='fa-solid fa-gift'></i> Gifted by Horsemen";
                                            }

                                        ?>
                                        <div class="card mb-3">
                                            
                                            <div class="card-header <?php echo ($awardedByHorsemen?"bg-ranked-1":""); ?>">
                                            <?php echo $title; ?>
                                            </div>
                                            <div class="row g-0">
                                                <div class="col-12 col-sm-3 col-md-2">
                                                    <img src="<?= $badge->item->iconSmall->url; ?>" class="img-fluid p-3">
                                                </div>
                                                <div class="col-12 col-sm-9 col-md-10">
                                                    <div class="card-body">
                                                        <h5 class="card-title"><?= $badge->item->name; ?></h5>
                                                        <blockquote class="blockquote mb-0">
                                                                <p><?php echo $badge->item->description; ?></p>
                                                                <?php if ($nominatedByUser)
                                                                {
                                                                    ?>
                                                                
                                                                <footer class="blockquote-footer"><?= $badge->item->nominatedBy->getAccountElement(); ?></footer>
                                                            <?php 
                                                            
                                                                }

                                                                ?>
                                                            </blockquote>
                                                    </div>
                                                    
                                                </div>
                                            </div>
                                            
                                            <div class="card-footer <?= ($awardedByHorsemen?"bg-ranked-1":""); ?>">
                                                        <?php
                                                        if ($badge->quest != null)
                                                        {
                                                            ?>
                                                            <a class="btn btn-primary btn-sm" href="<?= $badge->quest->url(); ?>"><?= $badge->quest->title; ?></a>
                                                            <?php 

                                                        }
                                                        ?>
                                                        <p class="card-text float-end"><?= $badge->dateObtained->formattedBasic; ?></p>
                                                    </div>
                                        </div>
                                        <?php 
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="tab-pane fade" id="nav-inventory" role="tabpanel" aria-labelledby="nav-inventory-tab" tabindex="0">
                                <div class="display-6 tab-pane-title">Inventory<?php if (!$isMyProfile) { ?><button class="btn float-end bg-ranked-1" type="button" data-bs-toggle="modal" data-bs-target="#tradeModal"><i class="fa-solid fa-arrow-right-arrow-left"></i></button><?php } ?><button class="btn float-end bg-ranked-1 me-2" type="button" data-bs-toggle="modal" data-bs-target="#equipmentModal"><i class="fa-solid fa-shirt"></i></button><?php if ($isMyProfile) { ?><button class="btn float-end bg-ranked-1 me-2" type="button" data-bs-toggle="modal" data-bs-target="#craftModal"><i class="fa-solid fa-flask-vial"></i></button><?php } ?></div>
                                <div class="row">
                                        <div class="col-12">
                                            <!-- side-bar colleps block stat-->
                                            <div class="inventory-grid">
                                                <?php
                                                
                                                // Show category title

                                                foreach ($accountInventory as $accountInventoryItemStack) {
                                                    ?>
                                                    <div class="inventory-item" onclick="ShowInventoryItemModal(<?= $accountInventoryItemStack->item->crand; ?>, <?= $accountInventoryItemStack->itemLootId->crand; ?>);"  data-bs-toggle="tooltip" data-bs-dismiss="modal" data-bs-placement="bottom" data-bs-title="<?= htmlspecialchars($accountInventoryItemStack->GetName())?>">
                                                        <img src="<?= $accountInventoryItemStack->item->iconSmall->getFullPath(); ?>" alt="Item <?= $accountInventoryItemStack->GetName(); ?>">
                                                        <?php if ($accountInventoryItemStack->isContainer) { ?>
                                                        <div class="item-count"><i class="fa-solid fa-box"></i> <?= $accountInventoryItemStack->amount; ?></div>
                                                        <?php } else { ?>
                                                            <div class="item-count">x<?= $accountInventoryItemStack->amount; ?></div>
                                                        <?php } ?>
                                                    </div>
                                                
                                                <?php
                                                }

                                                ?>
                                            </div>
                                        </div>
                                </div> 
                            </div>
                            
                            <div class="tab-pane fade" id="nav-activity" role="tabpanel" aria-labelledby="nav-activity-tab" tabindex="0">
                                <div class="display-6 tab-pane-title">Activity</div> 
                               
                                    <?php 
                                    foreach ($accountActivity as $activity) {
                                    
                                        
                                        $_vFeedCard = FeedCardController::vActivity_to_vFeedCard($activity);
                                        require("php-components/vFeedCardRenderer.php");
                                    ?>

                                    <?php
                                    }
                                    
                                    ?>

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
    <script>
        var myNextWritOfPassageURL = "<?= $nextWritOfPassageURL; ?>";
        function openReviewModal() {
            document.getElementById("reviewModal").style.display = "block";
        }

        function closeReviewModal() {
            document.getElementById("reviewModal").style.display = "none";
        } 

        var selectedButtonReviewModal = null;
        function rate(buttonNumber) {
            var plus = document.getElementById("plus");
            var minus = document.getElementById("minus");
            var reviewRating = document.getElementById("review-rating");

            if (selectedButtonReviewModal !== buttonNumber) {
                selectedButtonReviewModal = buttonNumber;

                // Update button styles
                if (buttonNumber === 1) {
                    plusButton.style.backgroundColor = "#7dc006";
                    minusButton.style.backgroundColor = "#8f97b2";
                    reviewRating.value = "1";

                } else if (buttonNumber === 2) {
                    plusButton.style.backgroundColor = "#8f97b2";
                    minusButton.style.backgroundColor = "#e52727";
                    reviewRating.value = "-1";
                }

                // Perform button-specific actions
                if (buttonNumber === 1) {
                    // +1
                    console.log("Button 1 clicked");
                } else if (buttonNumber === 2) {
                    // -1
                    console.log("Button 2 clicked");
                }
            }
        }

        
        function validateSelection(event) {
            if (selectedButtonReviewModal === null) {
              alert("Please give a rating");
              event.preventDefault(); // Prevent the default form submission behavior
              return;
            }
        }

        var textareaReviewModal = document.getElementById("textareaReviewModal");

        textareaReviewModal.addEventListener("input", function() {   
            var maxLength = parseInt(textareaReviewModal.getAttribute("maxlength"));
            var currentLength = textareaReviewModal.value.length;

            if (currentLength > maxLength) {
                textareaReviewModal.value = textareaReviewModal.value.slice(0, maxLength);
            }
        });

        const emptySlotImagePath = <?= json_encode($emptySlotPath); ?>;
        const equipmentInputMap = {
            AVATAR: "#equipment-avatar",
            PC_CARD: "#equipment-pc-card",
            BANNER: "#equipment-banner",
            BACKGROUND: "#equipment-background",
            CHARM: "#equipment-charm",
            PET: "#equipment-pet",
        };
        const equipmentPreviewMap = {
            AVATAR: "#equipment-preview-avatar",
            PC_CARD: "#equipment-preview-pc-card",
            BANNER: "#equipment-preview-banner",
            BACKGROUND: "#equipment-preview-background",
            CHARM: "#equipment-preview-charm",
            PET: "#equipment-preview-pet",
        };

        function GetItemStackInformationById(id)
        {
            for (let index = 0; index < itemStackInformation.length; index++) {
                var stack = itemStackInformation[index];
                if (stack.item.crand == id)
                {
                    return stack;
                }
            }
            return null;
        }

        function updateUnequipButtons()
        {
            Object.entries(equipmentInputMap).forEach(([slot, selector]) => {
                const button = document.querySelector(`[data-unequip-slot="${slot}"]`);
                if (!button)
                {
                    return;
                }

                const hasValue = $(selector).val() !== "";
                button.classList.toggle("d-none", !hasValue);
            });
        }

        function unequipSlot(slot)
        {
            const inputSelector = equipmentInputMap[slot];
            const previewSelector = equipmentPreviewMap[slot];

            if (inputSelector !== undefined)
            {
                $(inputSelector).val("");
            }

            if (previewSelector !== undefined)
            {
                $(previewSelector).attr("src", emptySlotImagePath);
            }

            updateUnequipButtons();
        }

        function SelectInventoryItemStackEquipment(item_id)
        {
            var stack = GetItemStackInformationById(item_id);
            var item = stack.item;
            $("#equipmentModal").modal("show");

            <?php 

            if ($isMyProfile)
            {

            ?>
            
            if (item.equipmentSlot == "AVATAR")
            {
                $("#equipment-avatar").val(stack.nextLootId.crand);
                $("#equipment-preview-avatar").attr("src",item.iconSmall.url);
            }
            if (item.equipmentSlot == "PC_CARD")
            {
                $("#equipment-pc-card").val(stack.nextLootId.crand);
                $("#equipment-preview-pc-card").attr("src",item.iconSmall.url);
            }
            if (item.equipmentSlot == "BANNER")
            {
                $("#equipment-banner").val(stack.nextLootId.crand);
                $("#equipment-preview-banner").attr("src",item.iconSmall.url);
            }
            if (item.equipmentSlot == "BACKGROUND")
            {
                $("#equipment-background").val(stack.nextLootId.crand);
                $("#equipment-preview-background").attr("src",item.iconSmall.url);
            }
            if (item.equipmentSlot == "CHARM")
            {
                $("#equipment-charm").val(stack.nextLootId.crand);
                $("#equipment-preview-charm").attr("src",item.iconSmall.url);
            }
            if (item.equipmentSlot == "PET")
            {
                $("#equipment-pet").val(stack.nextLootId.crand);
                $("#equipment-preview-pet").attr("src",item.iconSmall.url);
            }

            <?php
            }

            ?>
            updateUnequipButtons();
            console.log(item);
        }

        updateUnequipButtons();

    </script>
</body>

</html>
