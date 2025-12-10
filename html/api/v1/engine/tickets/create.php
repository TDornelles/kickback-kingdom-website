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

function parseOptionalInt(string $key): ?int
{
    if (!isset($_POST[$key])) {
        return null;
    }

    $value = $_POST[$key];
    if ($value === '' || is_null($value)) {
        return null;
    }

    $intVal = (int) $value;
    return $intVal > 0 ? $intVal : null;
}

$payload = [
    'subject' => Validate($_POST['subject'] ?? ''),
    'description' => trim((string) ($_POST['description'] ?? '')),
    'priority' => Validate($_POST['priority'] ?? 'medium'),
    'severity' => $_POST['severity'] ?? null,
    'tags' => parseList('tags'),
    'guildIds' => parseList('guildIds'),
    'guildId' => parseOptionalInt('guildId'),
    'gameId' => parseOptionalInt('gameId'),
    'serverCtime' => isset($_POST['serverCtime']) ? Validate($_POST['serverCtime']) : null,
    'serverCrand' => parseOptionalInt('serverCrand'),
    'assignees' => parseList('assignees'),
];

$resp = TicketController::createTicket($payload);
if (!$resp instanceof Response) {
    $resp = new Response(false, 'Unexpected response while creating ticket.', null);
}

return $resp;
?>
