<?php

require_once(__DIR__ . '/../engine.php');

use Kickback\Backend\Controllers\AbilityController;
use Kickback\Backend\Models\Ability;
use Kickback\Backend\Models\Response;
use Kickback\Services\Session;

OnlyPOST();

if (!Session::isAdmin()) {
    return new Response(false, 'Admin access required.');
}

function sanitizeIconClass(string $icon): string
{
    $normalized = preg_replace('/[^a-z0-9\-\s]/i', '', trim($icon));
    return $normalized === '' ? 'fa-wand-sparkles' : $normalized;
}

$ability = new Ability();
$ability->crand = (int)Validate($_POST['ability_id'] ?? 0);
$ability->name = Validate($_POST['name'] ?? '');
$ability->desc = Validate($_POST['description'] ?? '');
$ability->icon = sanitizeIconClass($_POST['icon'] ?? '');
$ability->prestigeGain = (int)Validate($_POST['prestige_gain'] ?? 0);
$ability->prestigeMultiplier = (float)Validate($_POST['prestige_multiplier'] ?? 0);
$ability->expGain = (int)Validate($_POST['exp_gain'] ?? 0);
$ability->expMultiplier = (float)Validate($_POST['exp_multiplier'] ?? 0);
$ability->levelGain = (int)Validate($_POST['level_gain'] ?? 0);
$ability->levelMultiplier = (float)Validate($_POST['level_multiplier'] ?? 0);
$ability->titleChange = Validate($_POST['title_change'] ?? '');

return AbilityController::updateAbility($ability);

?>
