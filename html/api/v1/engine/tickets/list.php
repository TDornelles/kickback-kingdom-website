<?php
require(__DIR__ . '/../../engine/engine.php');

use Kickback\Backend\Controllers\TicketController;
use Kickback\Backend\Models\Response;

OnlyPOST();

$payload = [
    'status' => isset($_POST['status']) ? Validate($_POST['status']) : null,
    'priority' => isset($_POST['priority']) ? Validate($_POST['priority']) : null,
    'assignee' => isset($_POST['assignee']) ? Validate($_POST['assignee']) : null,
    'updatedFrom' => isset($_POST['from']) ? Validate($_POST['from']) : null,
    'updatedTo' => isset($_POST['to']) ? Validate($_POST['to']) : null,
    'search' => isset($_POST['search']) ? Validate($_POST['search']) : null,
];

$resp = TicketController::listTickets($payload);
if (!$resp instanceof Response) {
    $resp = new Response(false, 'Unexpected response while loading tickets.', null);
}

return $resp;
?>
