<?php

require_once(($_SERVER['DOCUMENT_ROOT'] ?: __DIR__ . '/../../..') . '/Kickback/init.php');

use Kickback\Backend\Controllers\AbilityController;
use Kickback\Backend\Models\Ability;
use Kickback\Backend\Models\Response;
use Kickback\Services\Session;

header('Content-Type: application/json');

if (!Session::isAdmin()) {
    (new Response(false, 'Admin access required.'))->Return();
    return;
}

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

$resp = AbilityController::insertAbility($ability);
$resp->Return();

?>
