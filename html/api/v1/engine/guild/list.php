<?php
require_once(__DIR__ . '/../engine.php');

use Kickback\Backend\Controllers\GuildController;
use Kickback\Backend\Models\Response;

OnlyGET();

$resp = GuildController::listGuilds();
if (!$resp instanceof Response) {
    $resp = new Response(false, 'Unexpected response while loading guilds.', null);
}

return $resp;
?>
