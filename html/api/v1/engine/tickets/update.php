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

function parseEmailPrefs(): array
{
    $raw = $_POST['assignmentEmailOptIn'] ?? null;
    if ($raw === null) {
        return [];
    }
    if (is_string($raw)) {
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }
    return is_array($raw) ? $raw : [];
}

$payload = [
    'ctime' => Validate($_POST['ctime'] ?? ''),
    'crand' => (int) ($_POST['crand'] ?? 0),
    'subject' => isset($_POST['subject']) ? Validate($_POST['subject']) : null,
    'description' => isset($_POST['description']) ? trim((string) $_POST['description']) : null,
    'priority' => isset($_POST['priority']) ? Validate($_POST['priority']) : null,
    'status' => isset($_POST['status']) ? Validate($_POST['status']) : null,
    'tags' => isset($_POST['tags']) ? parseList('tags') : null,
    'guildIds' => isset($_POST['guildIds']) ? parseList('guildIds') : null,
    'assignees' => isset($_POST['assignees']) ? parseList('assignees') : null,
    'comment' => isset($_POST['comment']) ? trim((string) $_POST['comment']) : null,
    'unsubscribeToken' => isset($_POST['unsubscribeToken']) ? trim((string) $_POST['unsubscribeToken']) : null,
    'assignmentEmailOptIn' => parseEmailPrefs(),
];

$resp = TicketController::updateTicket($payload);
if (!$resp instanceof Response) {
    $resp = new Response(false, 'Unexpected response while updating ticket.', null);
}

return $resp;
?>
