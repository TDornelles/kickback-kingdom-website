<?php
$pageTitle = "Ability Manager";
$pageImage = "https://kickback-kingdom.com/assets/media/context/market-hall.png";
$pageDesc = "Admin panel to browse, create, update, and remove abilities from the database.";

require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__) . "/Kickback/init.php");

$session = require(\Kickback\SCRIPT_ROOT . "/api/v1/engine/session/verifySession.php");
require("php-components/base-page-pull-active-account-info.php");

use Kickback\Backend\Controllers\AbilityController;
use Kickback\Backend\Models\Ability;
use Kickback\Backend\Views\vRecordId;
use Kickback\Backend\Views\vAbility;
use Kickback\Common\Version;
use Kickback\Services\Session;

if (!Session::isAdmin()) {
    header('Location: index.php');
    exit();
}

$alertMessage = '';
$alertVariant = '';

function buildAbilityFromPost(): Ability
{
    $ability = new Ability();
    $ability->name = trim($_POST['name'] ?? '');
    $ability->desc = trim($_POST['description'] ?? '');
    $ability->prestigeGain = (int)($_POST['prestige_gain'] ?? 0);
    $ability->prestigeMultiplier = (float)($_POST['prestige_multiplier'] ?? 0);
    $ability->expGain = (int)($_POST['exp_gain'] ?? 0);
    $ability->expMultiplier = (float)($_POST['exp_multiplier'] ?? 0);
    $ability->levelGain = (int)($_POST['level_gain'] ?? 0);
    $ability->levelMultiplier = (float)($_POST['level_multiplier'] ?? 0);
    $ability->titleChange = trim($_POST['title_change'] ?? '');

    return $ability;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'update') {
        $ability = buildAbilityFromPost();

        if ($action === 'update') {
            $ability->crand = (int)($_POST['ability_id'] ?? -1);
        }

        $response = $action === 'create'
            ? AbilityController::insertAbility($ability)
            : AbilityController::updateAbility($ability);

        $alertMessage = $response->message;
        $alertVariant = $response->success ? 'success' : 'danger';
    } elseif ($action === 'delete') {
        $abilityId = new vRecordId('', (int)($_POST['ability_id'] ?? -1));
        $response = AbilityController::deleteAbility($abilityId);

        $alertMessage = $response->message;
        $alertVariant = $response->success ? 'success' : 'danger';
    }
}

$abilityTableResp = AbilityController::getAbilityTable();
$abilities = $abilityTableResp->success ? $abilityTableResp->data : [];
?>

<!DOCTYPE html>
<html lang="en">

<?php require("php-components/base-page-head.php"); ?>

<body class="bg-body-secondary container p-0">
    <?php
    require("php-components/base-page-components.php");
    require("php-components/ad-carousel.php");
    ?>

    <main class="container pt-3 bg-body" style="margin-bottom: 56px;">
        <div class="row">
            <div class="col-12">
                <?php
                $activePageName = "Ability Manager";
                require("php-components/base-page-breadcrumbs.php");
                ?>

                <div class="card shadow-sm mb-3">
                    <div class="card-body d-flex flex-column flex-md-row gap-2 align-items-md-center justify-content-between">
                        <div>
                            <h5 class="mb-1">Ability Manager</h5>
                            <p class="text-body-secondary mb-0">View, create, edit, or delete abilities.</p>
                        </div>
                        <div class="d-flex flex-column flex-sm-row gap-2 align-items-stretch align-items-sm-center">
                            <a class="btn bg-ranked-1 flex-shrink-0 text-nowrap" href="#create-ability">
                                <i class="fa-solid fa-plus"></i> New Ability
                            </a>
                            <a class="btn btn-outline-primary flex-shrink-0 text-nowrap" href="#edit-ability">
                                <i class="fa-solid fa-pen"></i> Edit Existing
                            </a>
                        </div>
                    </div>
                </div>

                <?php if ($alertMessage !== '') { ?>
                    <div class="alert alert-<?= $alertVariant; ?> alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($alertMessage); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php } ?>

                <?php if (!$abilityTableResp->success) { ?>
                    <div class="alert alert-danger" role="alert">
                        <?= htmlspecialchars($abilityTableResp->message); ?>
                    </div>
                <?php } ?>

                <div class="card shadow-sm mb-4" id="create-ability">
                    <div class="card-body">
                        <h5 class="card-title mb-3">Create Ability</h5>
                        <form method="post" class="row g-3">
                            <input type="hidden" name="action" value="create">
                            <div class="col-md-6">
                                <label for="name" class="form-label">Name</label>
                                <input type="text" class="form-control" id="name" name="name" required maxlength="45">
                            </div>
                            <div class="col-12">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="2" maxlength="255" required></textarea>
                            </div>
                            <div class="col-md-3">
                                <label for="prestige_gain" class="form-label">Prestige Gain</label>
                                <input type="number" class="form-control" id="prestige_gain" name="prestige_gain" value="0" min="0" required>
                            </div>
                            <div class="col-md-3">
                                <label for="prestige_multiplier" class="form-label">Prestige Multiplier</label>
                                <input type="number" step="0.01" class="form-control" id="prestige_multiplier" name="prestige_multiplier" value="0" min="0" required>
                            </div>
                            <div class="col-md-3">
                                <label for="exp_gain" class="form-label">EXP Gain</label>
                                <input type="number" class="form-control" id="exp_gain" name="exp_gain" value="0" min="0" required>
                            </div>
                            <div class="col-md-3">
                                <label for="exp_multiplier" class="form-label">EXP Multiplier</label>
                                <input type="number" step="0.01" class="form-control" id="exp_multiplier" name="exp_multiplier" value="0" min="0" required>
                            </div>
                            <div class="col-md-3">
                                <label for="level_gain" class="form-label">Level Gain</label>
                                <input type="number" class="form-control" id="level_gain" name="level_gain" value="0" min="0" required>
                            </div>
                            <div class="col-md-3">
                                <label for="level_multiplier" class="form-label">Level Multiplier</label>
                                <input type="number" step="0.01" class="form-control" id="level_multiplier" name="level_multiplier" value="0" min="0" required>
                            </div>
                            <div class="col-md-3">
                                <label for="title_change" class="form-label">Title Change</label>
                                <input type="text" class="form-control" id="title_change" name="title_change" value="" maxlength="45">
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn bg-ranked-1">Create Ability</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card shadow-sm mb-4" id="edit-ability">
                    <div class="card-body">
                        <h5 class="card-title mb-3">Edit Ability</h5>
                        <form method="post" id="editAbilityForm" class="row g-3">
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="ability_id" id="edit_ability_id" value="">

                            <div class="col-md-6">
                                <label for="edit_ability_select" class="form-label">Select Ability</label>
                                <select id="edit_ability_select" class="form-select" required>
                                    <option value="" selected disabled>Choose an ability</option>
                                    <?php foreach ($abilities as $ability) { /** @var vAbility $ability */ ?>
                                        <option value="<?= $ability->crand; ?>"
                                            data-name="<?= htmlspecialchars($ability->name); ?>"
                                            data-description="<?= htmlspecialchars($ability->description); ?>"
                                            data-prestige_gain="<?= $ability->prestigeGain; ?>"
                                            data-prestige_multiplier="<?= $ability->prestigeMultiplier; ?>"
                                            data-exp_gain="<?= $ability->expGain; ?>"
                                            data-exp_multiplier="<?= $ability->expMultiplier; ?>"
                                            data-level_gain="<?= $ability->levelGain; ?>"
                                            data-level_multiplier="<?= $ability->levelMultiplier; ?>"
                                            data-title_change="<?= htmlspecialchars($ability->titleChange); ?>">
                                            <?= htmlspecialchars($ability->name); ?> (ID: <?= $ability->crand; ?>)
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="edit_title_change" class="form-label">Title Change</label>
                                <input type="text" class="form-control" id="edit_title_change" name="title_change" maxlength="45">
                            </div>
                            <div class="col-md-6">
                                <label for="edit_name" class="form-label">Name</label>
                                <input type="text" class="form-control" id="edit_name" name="name" required maxlength="45">
                            </div>
                            <div class="col-md-6">
                                <label for="edit_description" class="form-label">Description</label>
                                <textarea class="form-control" id="edit_description" name="description" rows="2" maxlength="255" required></textarea>
                            </div>
                            <div class="col-md-3">
                                <label for="edit_prestige_gain" class="form-label">Prestige Gain</label>
                                <input type="number" class="form-control" id="edit_prestige_gain" name="prestige_gain" min="0" required>
                            </div>
                            <div class="col-md-3">
                                <label for="edit_prestige_multiplier" class="form-label">Prestige Multiplier</label>
                                <input type="number" step="0.01" class="form-control" id="edit_prestige_multiplier" name="prestige_multiplier" min="0" required>
                            </div>
                            <div class="col-md-3">
                                <label for="edit_exp_gain" class="form-label">EXP Gain</label>
                                <input type="number" class="form-control" id="edit_exp_gain" name="exp_gain" min="0" required>
                            </div>
                            <div class="col-md-3">
                                <label for="edit_exp_multiplier" class="form-label">EXP Multiplier</label>
                                <input type="number" step="0.01" class="form-control" id="edit_exp_multiplier" name="exp_multiplier" min="0" required>
                            </div>
                            <div class="col-md-3">
                                <label for="edit_level_gain" class="form-label">Level Gain</label>
                                <input type="number" class="form-control" id="edit_level_gain" name="level_gain" min="0" required>
                            </div>
                            <div class="col-md-3">
                                <label for="edit_level_multiplier" class="form-label">Level Multiplier</label>
                                <input type="number" step="0.01" class="form-control" id="edit_level_multiplier" name="level_multiplier" min="0" required>
                            </div>
                            <div class="col-12 d-flex gap-2">
                                <button type="submit" class="btn bg-ranked-1" id="saveAbilityBtn" disabled>Save Changes</button>
                            </div>
                        </form>
                        <form method="post" class="mt-2" id="deleteAbilityForm" onsubmit="return confirm('Delete this ability? This will also remove any linked item associations.');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="ability_id" id="delete_ability_id" value="">
                            <button type="submit" class="btn btn-outline-danger" disabled id="deleteAbilityBtn">Delete Ability</button>
                        </form>
                    </div>
                </div>

                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <h5 class="card-title mb-3">Ability Table</h5>
                        <div class="table-responsive">
                            <table class="table table-striped align-middle">
                                <thead>
                                    <tr>
                                        <th scope="col">ID</th>
                                        <th scope="col">Name</th>
                                        <th scope="col">Title Change</th>
                                        <th scope="col">Prestige</th>
                                        <th scope="col">EXP</th>
                                        <th scope="col">Level</th>
                                        <th scope="col">Description</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($abilities as $ability) { /** @var vAbility $ability */ ?>
                                        <tr>
                                            <td><?= $ability->crand; ?></td>
                                            <td><?= htmlspecialchars($ability->name); ?></td>
                                            <td><?= htmlspecialchars($ability->titleChange); ?></td>
                                            <td>
                                                <div class="small">Gain: <?= $ability->prestigeGain; ?></div>
                                                <div class="small">Multiplier: <?= $ability->prestigeMultiplier; ?></div>
                                            </td>
                                            <td>
                                                <div class="small">Gain: <?= $ability->expGain; ?></div>
                                                <div class="small">Multiplier: <?= $ability->expMultiplier; ?></div>
                                            </td>
                                            <td>
                                                <div class="small">Gain: <?= $ability->levelGain; ?></div>
                                                <div class="small">Multiplier: <?= $ability->levelMultiplier; ?></div>
                                            </td>
                                            <td><?= htmlspecialchars($ability->description); ?></td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </main>

    <script>
        const abilitySelect = document.getElementById('edit_ability_select');
        const abilityFields = {
            ability_id: document.getElementById('edit_ability_id'),
            name: document.getElementById('edit_name'),
            description: document.getElementById('edit_description'),
            prestige_gain: document.getElementById('edit_prestige_gain'),
            prestige_multiplier: document.getElementById('edit_prestige_multiplier'),
            exp_gain: document.getElementById('edit_exp_gain'),
            exp_multiplier: document.getElementById('edit_exp_multiplier'),
            level_gain: document.getElementById('edit_level_gain'),
            level_multiplier: document.getElementById('edit_level_multiplier'),
            title_change: document.getElementById('edit_title_change'),
        };
        const saveButton = document.getElementById('saveAbilityBtn');
        const deleteButton = document.getElementById('deleteAbilityBtn');
        const deleteIdInput = document.getElementById('delete_ability_id');

        function populateAbilityForm(option) {
            abilityFields.ability_id.value = option.value;
            abilityFields.name.value = option.dataset.name ?? '';
            abilityFields.description.value = option.dataset.description ?? '';
            abilityFields.prestige_gain.value = option.dataset.prestige_gain ?? 0;
            abilityFields.prestige_multiplier.value = option.dataset.prestige_multiplier ?? 0;
            abilityFields.exp_gain.value = option.dataset.exp_gain ?? 0;
            abilityFields.exp_multiplier.value = option.dataset.exp_multiplier ?? 0;
            abilityFields.level_gain.value = option.dataset.level_gain ?? 0;
            abilityFields.level_multiplier.value = option.dataset.level_multiplier ?? 0;
            abilityFields.title_change.value = option.dataset.title_change ?? '';

            saveButton.disabled = false;
            deleteButton.disabled = false;
            deleteIdInput.value = option.value;
        }

        abilitySelect?.addEventListener('change', (event) => {
            const selectedOption = event.target.selectedOptions[0];
            if (!selectedOption) {
                return;
            }
            populateAbilityForm(selectedOption);
        });
    </script>

</body>

</html>
