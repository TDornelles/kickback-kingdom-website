<?php
require_once(__DIR__ . "/../../engine/engine.php");

use Kickback\Backend\Controllers\TicketController;

OnlyPOST();

return TicketController::createTicketFromRequest($_POST, $_FILES);
?>
