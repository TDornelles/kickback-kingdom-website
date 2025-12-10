<?php
require(__DIR__ . '/../../../engine/engine.php');

use Kickback\Backend\Controllers\TicketController;
use Kickback\Backend\Models\Response;

OnlyPOST();

$payload = [
    'name' => isset($_POST['name']) ? Validate($_POST['name']) : '',
    'slug' => isset($_POST['slug']) ? Validate($_POST['slug']) : '',
];

$resp = TicketController::createCategory($payload);
if (!$resp instanceof Response) {
    $resp = new Response(false, 'Unexpected response while creating category.', null);
}

return $resp;
?>
