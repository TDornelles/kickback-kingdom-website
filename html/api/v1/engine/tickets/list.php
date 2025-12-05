<?php
require(__DIR__ . '/../../engine/engine.php');

use Kickback\Backend\Controllers\TicketController;
use Kickback\Backend\Models\Response;

OnlyPOST();

$payload = [
    'status' => isset($_POST['status']) ? Validate($_POST['status']) : null,
    'priority' => isset($_POST['priority']) ? Validate($_POST['priority']) : null,
];

$resp = TicketController::listTickets($payload);
if (!$resp instanceof Response) {
    $resp = new Response(false, 'Unexpected response while loading tickets.', null);
}

return $resp;
?>
