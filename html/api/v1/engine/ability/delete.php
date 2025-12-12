<?php

require_once(__DIR__ . '/../engine.php');

use Kickback\Backend\Controllers\AbilityController;
use Kickback\Backend\Models\Response;
use Kickback\Backend\Views\vRecordId;
use Kickback\Services\Session;

OnlyPOST();

if (!Session::isAdmin()) {
    return new Response(false, 'Admin access required.');
}

$abilityId = new vRecordId('', (int)Validate($_POST['ability_id'] ?? 0));

return AbilityController::deleteAbility($abilityId);

?>
