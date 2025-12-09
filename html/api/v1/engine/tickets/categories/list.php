<?php
require(__DIR__ . '/../../../engine/engine.php');

use Kickback\Backend\Controllers\TicketController;
use Kickback\Backend\Models\Response;

OnlyPOST();

$resp = TicketController::listCategories();
if (!$resp instanceof Response) {
    $resp = new Response(false, 'Unexpected response while loading categories.', null);
}

return $resp;
?>
