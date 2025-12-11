<?php

require_once(($_SERVER['DOCUMENT_ROOT'] ?: __DIR__ . '/../../..') . '/Kickback/init.php');

use Kickback\Backend\Controllers\AbilityController;
use Kickback\Backend\Models\Response;
use Kickback\Services\Session;

header('Content-Type: application/json');

if (!Session::isAdmin()) {
    (new Response(false, 'Admin access required.'))->Return();
    return;
}

$resp = AbilityController::getAbilityTable();
$resp->Return();

?>
