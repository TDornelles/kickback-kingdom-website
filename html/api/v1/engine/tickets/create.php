<?php
require(__DIR__ . '/../../engine/engine.php');

use Kickback\Backend\Controllers\TicketController;
use Kickback\Backend\Models\Response;

OnlyPOST();

function parseList(string $key): array
{
    if (!isset($_POST[$key])) {
        return [];
    }
    $value = $_POST[$key];
    if (is_string($value)) {
        $decoded = json_decode($value, true);
        if (is_array($decoded)) {
            return $decoded;
        }
        return array_map('trim', explode(',', $value));
    }
    if (is_array($value)) {
        return $value;
    }
    return [];
}

$payload = [
    'subject' => Validate($_POST['subject'] ?? ''),
    'description' => trim((string) ($_POST['description'] ?? '')),
    'priority' => Validate($_POST['priority'] ?? 'medium'),
    'tags' => parseList('tags'),
    'guildIds' => parseList('guildIds'),
    'assignees' => parseList('assignees'),
];

$resp = TicketController::createTicket($payload);
if (!$resp instanceof Response) {
    $resp = new Response(false, 'Unexpected response while creating ticket.', null);
}

return $resp;
?>
