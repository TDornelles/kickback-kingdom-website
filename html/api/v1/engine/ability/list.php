<?php

require_once(__DIR__ . '/../engine.php');

use Kickback\Backend\Controllers\AbilityController;
use Kickback\Backend\Models\Response;
use Kickback\Services\Session;

OnlyGET();

if (!Session::isAdmin()) {
    return new Response(false, 'Admin access required.');
}

return AbilityController::getAbilityTable();

?>
