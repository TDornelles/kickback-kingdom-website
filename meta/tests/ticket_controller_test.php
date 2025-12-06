<?php
declare(strict_types=1);

use Kickback\Backend\Controllers\TicketController;
use Kickback\Backend\Models\Response;
use Kickback\Backend\Models\Ticket;

require_once __DIR__ . '/../../html/Kickback/InitializationScripts/init_for_phpstan.php';

error_reporting(E_ALL & ~E_DEPRECATED);

function invokeTicketController(string $method, array $args = [])
{
    $ref = new ReflectionMethod(TicketController::class, $method);
    $ref->setAccessible(true);
    return $ref->invokeArgs(null, $args);
}

function expect(bool $condition, string $message): void
{
    if (!$condition) {
        throw new \RuntimeException($message);
    }
}

// Priority normalization
expect(invokeTicketController('normalizePriority', ['low']) === 1, 'low priority should normalize to 1');
expect(invokeTicketController('normalizePriority', ['urgent']) === 4, 'urgent priority should normalize to 4');
expect(invokeTicketController('normalizePriority', [3]) === 3, 'numeric priority should remain unchanged');
expect(invokeTicketController('normalizePriority', ['unknown']) === null, 'unknown priority should be rejected');

// Severity normalization
expect(invokeTicketController('normalizeSeverity', ['minor']) === 2, 'minor severity should map to 2');
expect(invokeTicketController('normalizeSeverity', [null]) === null, 'null severity stays null');
expect(invokeTicketController('normalizeSeverity', ['']) === null, 'blank severity stays null');
expect(invokeTicketController('normalizeSeverity', [5]) === null, 'out-of-range severity rejected');

// Validation rules respect schema ranges
/** @var Response $valid */
$valid = invokeTicketController('validateTicketFields', ['Subject', 'Description', 2, 3, 'open']);
expect($valid instanceof Response && $valid->success === true, 'valid ticket fields should pass');

/** @var Response $invalidPriority */
$invalidPriority = invokeTicketController('validateTicketFields', ['Subject', 'Description', 5, null, 'open']);
expect($invalidPriority instanceof Response && $invalidPriority->success === false, 'priority above max should fail validation');

/** @var Response $invalidSeverity */
$invalidSeverity = invokeTicketController('validateTicketFields', ['Subject', 'Description', 2, 9, 'open']);
expect($invalidSeverity instanceof Response && $invalidSeverity->success === false, 'severity above max should fail validation');

// Ticket row hydration preserves numeric fields and context
$row = [
    'ctime' => '2025-01-01 00:00:00',
    'crand' => 123,
    'subject' => 'Hydrated',
    'description' => 'Row import',
    'status' => 'open',
    'priority' => 3,
    'severity' => 4,
    'tags_json' => json_encode(['a', 'b']),
    'guild_id' => 55,
    'game_id' => 77,
    'server_ctime' => '2025-01-01 01:00:00',
    'server_crand' => 999,
    'created_by_crand' => 11,
    'updated_by_crand' => 22,
    'updated_at' => '2025-01-02 00:00:00',
    'first_response_at' => '2025-01-02 02:00:00',
    'resolved_at' => '2025-01-03 00:00:00',
    'closed_at' => null,
];
$ticket = Ticket::fromRow($row);
expect($ticket->priority === 3, 'priority should hydrate as int');
expect($ticket->severity === 4, 'severity should hydrate as int');
expect($ticket->guildId === 55, 'guild id should hydrate');
expect($ticket->gameId === 77, 'game id should hydrate');
expect($ticket->serverCtime === '2025-01-01 01:00:00', 'server ctime should hydrate');
expect($ticket->serverCrand === 999, 'server crand should hydrate');
expect($ticket->firstResponseAt === '2025-01-02 02:00:00', 'first response timestamp should hydrate');
expect($ticket->resolvedAt === '2025-01-03 00:00:00', 'resolved timestamp should hydrate');
expect($ticket->closedAt === null, 'closed timestamp can remain null');

// Priority and severity labels remain user-friendly
expect(invokeTicketController('priorityLabel', [4]) === 'urgent', 'priority label should flip to urgent');
expect(invokeTicketController('severityLabel', [1]) === 'cosmetic', 'severity label should flip to cosmetic');
expect(invokeTicketController('severityLabel', [null]) === 'unspecified', 'null severity should show as unspecified');

echo "All TicketController tests passed\n";
