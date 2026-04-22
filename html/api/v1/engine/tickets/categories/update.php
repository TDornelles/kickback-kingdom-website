<?php
require(__DIR__ . '/../../../engine/engine.php');

use Kickback\Backend\Controllers\TicketController;
use Kickback\Backend\Models\Response;

OnlyPOST();

$payload = [
    'ctime' => isset($_POST['ctime']) ? Validate($_POST['ctime']) : '',
    'crand' => isset($_POST['crand']) ? intval(Validate($_POST['crand'])) : 0,
    'name' => isset($_POST['name']) ? Validate($_POST['name']) : '',
    'slug' => isset($_POST['slug']) ? Validate($_POST['slug']) : '',
];

$resp = TicketController::updateCategory($payload);
if (!$resp instanceof Response) {
    $resp = new Response(false, 'Unexpected response while updating category.', null);
}

return $resp;
?>
