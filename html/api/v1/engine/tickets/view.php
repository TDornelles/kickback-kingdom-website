<?php
require(__DIR__ . '/../../engine/engine.php');

use Kickback\Backend\Controllers\TicketController;
use Kickback\Backend\Models\Response;

OnlyPOST();

$payload = [
    'ctime' => isset($_POST['ctime']) ? Validate($_POST['ctime']) : '',
    'crand' => isset($_POST['crand']) ? (int) $_POST['crand'] : 0,
];

$resp = TicketController::viewTicket($payload);
if (!$resp instanceof Response) {
    $resp = new Response(false, 'Unexpected response while loading ticket.', null);
}

return $resp;
?>
