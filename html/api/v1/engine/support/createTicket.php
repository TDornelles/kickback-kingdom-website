<?php
require_once(__DIR__ . "/../../engine/engine.php");

use Kickback\Backend\Controllers\SupportTicketController;

OnlyPOST();

return SupportTicketController::createTicketFromRequest($_POST, $_FILES);
?>
