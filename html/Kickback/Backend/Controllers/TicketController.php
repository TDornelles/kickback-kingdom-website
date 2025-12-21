<?php

declare(strict_types=1);

namespace Kickback\Backend\Controllers;

use Kickback\Backend\Config\ServiceCredentials;
use Kickback\Backend\Models\RecordId;
use Kickback\Backend\Models\Response;
use Kickback\Backend\Models\Ticket;
use Kickback\Backend\Models\TicketAssignment;
use Kickback\Backend\Models\TicketComment;
use Kickback\Backend\Models\TicketCategory;
use Kickback\Services\Database;
use Kickback\Services\Session;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;

class TicketController
{
    private const TICKET_TABLE = 'ticket';
    private const COMMENT_TABLE = 'ticket_comment';
    private const ASSIGNMENT_TABLE = 'ticket_assignment';
    private const TAG_TABLE = 'ticket_tag';
    private const CATEGORY_TABLE = 'ticket_category';
    private const ATTACHMENT_TABLE = 'ticket_attachment';

    /** @var string[] */
    private const VALID_STATUSES = ['open', 'in_progress', 'resolved', 'closed'];
    /** @var array<string,int> */
    private const PRIORITY_LABELS = [
        'low' => 1,
        'medium' => 2,
        'high' => 3,
        'urgent' => 4,
    ];
    /** @var array<string,int> */
    private const SEVERITY_LABELS = [
        'cosmetic' => 1,
        'minor' => 2,
        'major' => 3,
        'critical' => 4,
    ];

    /**
     * @param array<string,mixed> $payload
     */
    public static function createTicket(array $payload): Response
    {
        if (!Session::readCurrentAccountInto($account)) {
            return new Response(false, 'You must be logged in to create tickets.', null);
        }

        $subject = trim((string) ($payload['subject'] ?? ''));
        $description = trim((string) ($payload['description'] ?? ''));
        $category = self::normalizeCategory($payload['category'] ?? null);
        $priorityInput = $payload['priority'] ?? 'medium';
        $severityInput = $payload['severity'] ?? null;
        $tags = self::normalizeTags($payload['tags'] ?? []);
        $guildIds = self::normalizeIntList($payload['guildIds'] ?? []);
        $guildId = self::normalizeNullableInt($payload['guildId'] ?? ($guildIds[0] ?? null));
        $gameId = self::normalizeNullableInt($payload['gameId'] ?? null);
        $serverCtime = self::normalizeNullableString($payload['serverCtime'] ?? null);
        $serverCrand = self::normalizeNullableInt($payload['serverCrand'] ?? null);
        $assignees = self::normalizeIntList($payload['assignees'] ?? []);
        $createdIp = self::normalizeCreatedIp($payload['createdIp'] ?? null);
        $userAgent = self::normalizeUserAgent($payload['userAgent'] ?? null);

        if ($category === null) {
            return new Response(false, 'Category is invalid.', null);
        }

        $priority = self::normalizePriority($priorityInput);
        if ($priority === null) {
            return new Response(false, 'Priority is invalid.', null);
        }

        $severity = self::normalizeSeverity($severityInput);
        if ($severityInput !== null && $severityInput !== '' && is_null($severity)) {
            return new Response(false, 'Severity is invalid.', null);
        }

        $validation = self::validateTicketFields($subject, $description, $priority, $severity);
        if (!$validation->success) {
            return $validation;
        }

        $recordId = new RecordId();
        $now = $recordId->ctime;

        $conn = Database::getConnection();
        $stmt = $conn->prepare(
            'INSERT INTO ' . self::TICKET_TABLE
            . ' (ctime, crand, created_by_crand, category, guild_id, game_id, server_ctime, server_crand, status, priority, severity,'
            . ' subject, description, tags_json, created_ip, user_agent, updated_at, updated_by_crand)'
            . ' VALUES (?, ?, ?, ?, ?, ?, ?, ?, "open", ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );

        if ($stmt === false) {
            return new Response(false, 'Unable to prepare ticket insert.', null);
        }

        $tagsJson = json_encode($tags, JSON_THROW_ON_ERROR);
        $createdBy = $account->crand;
        $updatedBy = $account->crand;

        $stmt->bind_param(
            'siisiisiiissssssi',
            $recordId->ctime,
            $recordId->crand,
            $createdBy,
            $category,
            $guildId,
            $gameId,
            $serverCtime,
            $serverCrand,
            $priority,
            $severity,
            $subject,
            $description,
            $tagsJson,
            $createdIp,
            $userAgent,
            $now,
            $updatedBy
        );

        if (!$stmt->execute()) {
            $stmt->close();
            return new Response(false, 'Failed to create ticket.', null);
        }
        $stmt->close();

        self::replaceTags($recordId->ctime, $recordId->crand, $tags);
        $assignments = self::replaceAssignments($recordId->ctime, $recordId->crand, $assignees, $account->crand);

        $ticket = new Ticket(
            $recordId->ctime,
            $recordId->crand,
            $subject,
            $description,
            $category,
            'open',
            $priority,
            $severity,
            $tags,
            $guildId,
            $gameId,
            $serverCtime,
            $serverCrand,
            $createdBy,
            isset($account->username) ? $account->username : null,
            $updatedBy,
            $now,
            null,
            null,
            null,
            $createdIp,
            $userAgent
        );

        if (!empty($assignments)) {
            self::notifyAssignees($ticket, $assignments, 'created', $account, null);
        }

        return new Response(true, 'Ticket created.', [
            'ticket' => $ticket,
            'assignments' => $assignments,
        ]);
    }

    /**

     * @param array<string,mixed> $payload
     */
    public static function updateTicket(array $payload): Response
    {
        $ticketCtime = trim((string) ($payload['ctime'] ?? ''));
        $ticketCrand = (int) ($payload['crand'] ?? 0);

        if ($ticketCtime === '' || $ticketCrand === 0) {
            return new Response(false, 'Ticket identifiers are required.', null);
        }

        if (!Session::readCurrentAccountInto($account)) {
            return new Response(false, 'You must be logged in to update tickets.', null);
        }

        $ticket = self::fetchTicket($ticketCtime, $ticketCrand);
        if ($ticket === null) {
            return new Response(false, 'Ticket not found.', null);
        }

        if (!self::canModifyTicket($ticket, $account->crand, $account->isSteward)) {
            return new Response(false, 'You do not have permission to update this ticket.', null);
        }

        $subject = isset($payload['subject']) ? trim((string) $payload['subject']) : $ticket->subject;
        $description = isset($payload['description']) ? trim((string) $payload['description']) : $ticket->description;
        $priorityInput = $payload['priority'] ?? $ticket->priority;
        $severityInput = array_key_exists('severity', $payload) ? $payload['severity'] : $ticket->severity;
        $status = isset($payload['status']) ? strtolower(trim((string) $payload['status'])) : $ticket->status;
        $tags = array_key_exists('tags', $payload) ? self::normalizeTags($payload['tags']) : $ticket->tags;
        $guildIds = array_key_exists('guildIds', $payload) ? self::normalizeIntList($payload['guildIds']) : [];
        $guildId = (array_key_exists('guildId', $payload) || !empty($guildIds))
            ? self::normalizeNullableInt($payload['guildId'] ?? ($guildIds[0] ?? null))
            : $ticket->guildId;
        $gameId = array_key_exists('gameId', $payload)
            ? self::normalizeNullableInt($payload['gameId'])
            : $ticket->gameId;
        $serverCtime = array_key_exists('serverCtime', $payload)
            ? self::normalizeNullableString($payload['serverCtime'])
            : $ticket->serverCtime;
        $serverCrand = array_key_exists('serverCrand', $payload)
            ? self::normalizeNullableInt($payload['serverCrand'])
            : $ticket->serverCrand;
        $assignees = array_key_exists('assignees', $payload) ? self::normalizeIntList($payload['assignees']) : null;
        $commentBody = isset($payload['comment']) ? trim((string) $payload['comment']) : null;
        $unsubscribeToken = isset($payload['unsubscribeToken']) ? trim((string) $payload['unsubscribeToken']) : null;
        $assignmentEmailPrefs = array_key_exists('assignmentEmailOptIn', $payload)
            ? self::normalizeAssignmentEmailMap($payload['assignmentEmailOptIn'])
            : [];

        $priority = isset($payload['priority']) ? self::normalizePriority($priorityInput) : $ticket->priority;
        if ($priority === null) {
            return new Response(false, 'Priority is invalid.', null);
        }

        $severity = array_key_exists('severity', $payload) ? self::normalizeSeverity($severityInput) : $ticket->severity;
        if (array_key_exists('severity', $payload) && $severityInput !== null && $severityInput !== '' && is_null($severity)) {
            return new Response(false, 'Severity is invalid.', null);
        }

        $validation = self::validateTicketFields($subject, $description, $priority, $severity, $status);
        if (!$validation->success) {
            return $validation;
        }

        $now = (new RecordId())->ctime;
        $firstResponseAt = $ticket->firstResponseAt;
        $resolvedAt = $ticket->resolvedAt;
        $closedAt = $ticket->closedAt;

        if (in_array($status, ['in_progress', 'resolved', 'closed'], true) && $firstResponseAt === null) {
            $firstResponseAt = $now;
        }
        if ($status === 'resolved' && $resolvedAt === null) {
            $resolvedAt = $now;
        }
        if ($status === 'closed') {
            if ($resolvedAt === null) {
                $resolvedAt = $now;
            }
            if ($closedAt === null) {
                $closedAt = $now;
            }
        }

        if (!empty($commentBody) && $firstResponseAt === null) {
            $firstResponseAt = $now;
        }

        $updatedBy = $account->crand;
        $conn = Database::getConnection();

        $stmt = $conn->prepare(
            'UPDATE ' . self::TICKET_TABLE
            . ' SET subject = ?, description = ?, priority = ?, severity = ?, status = ?, tags_json = ?, guild_id = ?, game_id = ?,'
            . ' server_ctime = ?, server_crand = ?, updated_at = ?, updated_by_crand = ?, first_response_at = ?, resolved_at = ?,'
            . ' closed_at = ? WHERE ctime = ? AND crand = ?'
        );

        if ($stmt === false) {
            return new Response(false, 'Unable to prepare ticket update.', null);
        }

        $tagsJson = json_encode($tags, JSON_THROW_ON_ERROR);

        $stmt->bind_param(
            'ssiissiisisissssi',
            $subject,
            $description,
            $priority,
            $severity,
            $status,
            $tagsJson,
            $guildId,
            $gameId,
            $serverCtime,
            $serverCrand,
            $now,
            $updatedBy,
            $firstResponseAt,
            $resolvedAt,
            $closedAt,
            $ticketCtime,
            $ticketCrand
        );

        if (!$stmt->execute()) {
            $stmt->close();
            return new Response(false, 'Failed to update ticket.', null);
        }
        $stmt->close();

        if (!is_null($tags)) {
            self::replaceTags($ticketCtime, $ticketCrand, $tags);
        }

        $assignments = self::getAssignments($ticketCtime, $ticketCrand);
        if (!is_null($assignees)) {
            $assignments = self::replaceAssignments($ticketCtime, $ticketCrand, $assignees, $account->crand);
        }

        if (!empty($assignmentEmailPrefs)) {
            self::applyAssignmentEmailPreferences($ticketCtime, $ticketCrand, $assignmentEmailPrefs);
            $assignments = self::getAssignments($ticketCtime, $ticketCrand);
        }

        if (!is_null($unsubscribeToken)) {
            self::disableNotificationsByToken($ticketCtime, $ticketCrand, $unsubscribeToken);
            $assignments = self::getAssignments($ticketCtime, $ticketCrand);
        }

        $newComment = null;
        if (!empty($commentBody)) {
            $commentResp = self::createComment($ticketCtime, $ticketCrand, $account->crand, $commentBody);
            if (!$commentResp->success) {
                return $commentResp;
            }
            $newComment = $commentResp->data;
        }

        $updatedTicket = new Ticket(
            $ticketCtime,
            $ticketCrand,
            $subject,
            $description,
            $ticket->category,
            $status,
            $priority,
            $severity,
            $tags,
            $guildId,
            $gameId,
            $serverCtime,
            $serverCrand,
            $ticket->createdByCrand,
            property_exists($ticket, 'createdByUsername') ? $ticket->createdByUsername : null,
            $updatedBy,
            $now,
            $firstResponseAt,
            $resolvedAt,
            $closedAt,
            $ticket->createdIp,
            $ticket->userAgent
        );

        if (!empty($assignments)) {
            self::notifyAssignees($updatedTicket, $assignments, 'updated', $account, $commentBody);
        }

        return new Response(true, 'Ticket updated.', [
            'ticket' => $updatedTicket,
            'assignments' => $assignments,
            'comment' => $newComment,
        ]);
    }

    /**
     * @param array<string,mixed> $payload
     */
    public static function listTickets(array $payload): Response
    {
        if (!Session::readCurrentAccountInto($account)) {
            return new Response(false, 'You must be logged in to view tickets.', null);
        }

        $statusFilter = isset($payload['status']) ? strtolower(trim((string) $payload['status'])) : null;
        $priorityFilter = $payload['priority'] ?? null;
        $severityFilter = $payload['severity'] ?? null;
        $assigneeFilter = isset($payload['assignee']) ? strtolower(trim((string) $payload['assignee'])) : null;
        $fromFilter = isset($payload['updatedFrom']) ? trim((string) $payload['updatedFrom']) : null;
        $toFilter = isset($payload['updatedTo']) ? trim((string) $payload['updatedTo']) : null;
        $searchFilter = isset($payload['search']) ? trim((string) $payload['search']) : null;

        $conn = Database::getConnection();

        $conditions = [];
        $params = [];
        $types = '';

        if ($statusFilter !== null) {
            $conditions[] = 't.status = ?';
            $params[] = $statusFilter;
            $types .= 's';
        }

        if ($priorityFilter !== null) {
            $priorityValue = self::normalizePriority($priorityFilter);
            if ($priorityValue === null) {
                return new Response(false, 'Priority filter is invalid.', null);
            }
            $conditions[] = 't.priority = ?';
            $params[] = $priorityValue;
            $types .= 'i';
        }

        if ($severityFilter !== null) {
            $severityValue = self::normalizeSeverity($severityFilter);
            if ($severityFilter !== '' && $severityValue === null) {
                return new Response(false, 'Severity filter is invalid.', null);
            }
            if ($severityValue !== null) {
                $conditions[] = 't.severity = ?';
                $params[] = $severityValue;
                $types .= 'i';
            }
        }

        if ($fromFilter !== null && $fromFilter !== '') {
            $conditions[] = 'DATE(t.updated_at) >= ?';
            $params[] = $fromFilter;
            $types .= 's';
        }

        if ($toFilter !== null && $toFilter !== '') {
            $conditions[] = 'DATE(t.updated_at) <= ?';
            $params[] = $toFilter;
            $types .= 's';
        }

        if ($searchFilter !== null && $searchFilter !== '') {
            $conditions[] = '(t.subject LIKE ? OR t.description LIKE ?)';
            $params[] = '%' . $searchFilter . '%';
            $params[] = '%' . $searchFilter . '%';
            $types .= 'ss';
        }

        if (!$account->isSteward) {
            $conditions[] = '(t.created_by_crand = ? OR EXISTS (SELECT 1 FROM ' . self::ASSIGNMENT_TABLE . ' a WHERE a.ticket_ctime = t.ctime AND a.ticket_crand = t.crand AND a.account_crand = ?))';
            $params[] = $account->crand;
            $params[] = $account->crand;
            $types .= 'ii';
        }

        $whereClause = empty($conditions) ? '' : ('WHERE ' . implode(' AND ', $conditions));

        $query = 'SELECT t.*, acc.Username AS created_by_username FROM ' . self::TICKET_TABLE . ' t '
            . 'LEFT JOIN account acc ON acc.Id = t.created_by_crand '
            . $whereClause . ' ORDER BY t.updated_at DESC';
        $stmt = $conn->prepare($query);
        if ($stmt === false) {
            return new Response(false, 'Unable to prepare ticket list.', null);
        }

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        if (!$stmt->execute()) {
            $stmt->close();
            return new Response(false, 'Failed to load tickets.', null);
        }

        $result = $stmt->get_result();
        $tickets = [];
        while ($row = $result->fetch_assoc()) {
            $tickets[] = Ticket::fromRow($row);
        }
        $stmt->close();

        $assignments = self::getAssigneesForTickets($tickets);

        $filteredTickets = [];
        foreach ($tickets as $ticket) {
            $ticketKey = $ticket->ctime . '-' . $ticket->crand;
            $assignees = $assignments[$ticketKey] ?? [];

            if ($assigneeFilter !== null && $assigneeFilter !== '') {
                $matchesAssignee = array_reduce(
                    $assignees,
                    static fn(bool $carry, string $name) => $carry || strtolower($name) === $assigneeFilter,
                    false
                );
                if (!$matchesAssignee) {
                    continue;
                }
            }

            $priorityLabel = self::priorityLabel($ticket->priority);
            $primaryAssignee = $assignees[0] ?? '';

            $filteredTickets[] = [
                'ctime' => $ticket->ctime,
                'crand' => $ticket->crand,
                'subject' => $ticket->subject,
                'description' => $ticket->description,
                'category' => $ticket->category,
                'status' => $ticket->status,
                'priority' => $priorityLabel,
                'priorityValue' => $ticket->priority,
                'severity' => $ticket->severity,
                'tags' => $ticket->tags,
                'guildId' => $ticket->guildId,
                'gameId' => $ticket->gameId,
                'serverCtime' => $ticket->serverCtime,
                'serverCrand' => $ticket->serverCrand,
                'createdByUsername' => $ticket->createdByUsername,
                'updatedByCrand' => $ticket->updatedByCrand,
                'updatedAt' => $ticket->updatedAt,
                'firstResponseAt' => $ticket->firstResponseAt,
                'resolvedAt' => $ticket->resolvedAt,
                'closedAt' => $ticket->closedAt,
                'assignees' => $assignees,
                'assignee' => $primaryAssignee,
            ];
        }

        return new Response(true, 'Tickets loaded.', $filteredTickets);
    }

    /**
     * @param Ticket[] $tickets
     * @return array<string,string[]>
     */
    private static function getAssigneesForTickets(array $tickets): array
    {
        if (empty($tickets)) {
            return [];
        }

        $conn = Database::getConnection();
        $placeholders = implode(',', array_fill(0, count($tickets), '(?, ?)'));
        $types = str_repeat('si', count($tickets));
        $params = [];

        foreach ($tickets as $ticket) {
            $params[] = $ticket->ctime;
            $params[] = $ticket->crand;
        }

        $query =
            'SELECT ta.ticket_ctime, ta.ticket_crand, '
            . "COALESCE(NULLIF(a.Username, ''), 'Unknown user') AS username "
            . 'FROM ' . self::ASSIGNMENT_TABLE . ' ta '
            . 'LEFT JOIN account a ON a.Id = ta.account_crand '
            . 'WHERE (ta.ticket_ctime, ta.ticket_crand) IN (' . $placeholders . ') '
            . 'ORDER BY username ASC';

        $stmt = $conn->prepare($query);
        if ($stmt === false) {
            return [];
        }

        $stmt->bind_param($types, ...$params);

        if (!$stmt->execute()) {
            $stmt->close();
            return [];
        }

        $result = $stmt->get_result();
        $assignees = [];
        while ($row = $result->fetch_assoc()) {
            $key = $row['ticket_ctime'] . '-' . $row['ticket_crand'];
            $assignees[$key][] = (string) $row['username'];
        }
        $stmt->close();

        return $assignees;
    }

    public static function listAssignees(): Response
    {
        if (!Session::readCurrentAccountInto($account)) {
            return new Response(false, 'You must be logged in to view assignees.', null);
        }

        $conn = Database::getConnection();

        $baseQuery =
            'SELECT DISTINCT ta.account_crand AS id, acc.Username AS username '
            . 'FROM ' . self::ASSIGNMENT_TABLE . ' ta '
            . 'JOIN ' . self::TICKET_TABLE . ' t ON t.ctime = ta.ticket_ctime AND t.crand = ta.ticket_crand '
            . 'LEFT JOIN account acc ON acc.Id = ta.account_crand';

        $conditions = [];
        $params = [];
        $types = '';

        if (!$account->isSteward) {
            $conditions[] =
                '(t.created_by_crand = ? OR EXISTS (SELECT 1 FROM ' . self::ASSIGNMENT_TABLE . ' ta2 '
                . 'WHERE ta2.ticket_ctime = t.ctime AND ta2.ticket_crand = t.crand AND ta2.account_crand = ?))';
            $params[] = $account->crand;
            $params[] = $account->crand;
            $types .= 'ii';
        }

        $whereClause = empty($conditions) ? '' : (' WHERE ' . implode(' AND ', $conditions));
        $query = $baseQuery . $whereClause . ' ORDER BY username ASC';

        $stmt = $conn->prepare($query);
        if ($stmt === false) {
            return new Response(false, 'Unable to load assignees.', null);
        }

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        if (!$stmt->execute()) {
            $stmt->close();
            return new Response(false, 'Failed to load assignees.', null);
        }

        $result = $stmt->get_result();
        $assignees = [];
        while ($row = $result->fetch_assoc()) {
            $assignees[] = [
                'id' => (int) $row['id'],
                'username' => isset($row['username']) && $row['username'] !== '' ? (string) $row['username'] : 'Unknown user',
            ];
        }
        $stmt->close();

        return new Response(true, 'Assignees loaded.', $assignees);
    }

    public static function listStewards(): Response
    {
        if (!Session::readCurrentAccountInto($account)) {
            return new Response(false, 'You must be logged in to view stewards.', null);
        }

        $conn = Database::getConnection();
        $query =
            "SELECT a.Id AS id, COALESCE(NULLIF(a.Username, ''), 'Unknown steward') AS username "
            . "FROM account a "
            . "INNER JOIN v_stewards s ON s.account_id = a.Id "
            . "ORDER BY username ASC";

        $stmt = $conn->prepare($query);
        if ($stmt === false) {
            return new Response(false, 'Unable to load stewards.', null);
        }

        if (!$stmt->execute()) {
            $stmt->close();
            return new Response(false, 'Failed to load stewards.', null);
        }

        $result = $stmt->get_result();
        $stewards = [];
        while ($row = $result->fetch_assoc()) {
            $stewards[] = [
                'id' => (int) $row['id'],
                'username' => (string) $row['username'],
            ];
        }
        $stmt->close();

        return new Response(true, 'Stewards loaded.', $stewards);
    }

    /**
     * @param array<string,mixed> $payload
     */
    public static function viewTicket(array $payload): Response
    {
        $ticketCtime = trim((string) ($payload['ctime'] ?? ''));
        $ticketCrand = (int) ($payload['crand'] ?? 0);

        if ($ticketCtime === '' || $ticketCrand === 0) {
            return new Response(false, 'Ticket identifiers are required.', null);
        }

        if (!Session::readCurrentAccountInto($account)) {
            return new Response(false, 'You must be logged in to view tickets.', null);
        }

        $ticket = self::fetchTicket($ticketCtime, $ticketCrand);
        if ($ticket === null) {
            return new Response(false, 'Ticket not found.', null);
        }

        if (!self::canModifyTicket($ticket, $account->crand, $account->isSteward)) {
            return new Response(false, 'You do not have permission to view this ticket.', null);
        }

        $assignments = self::getAssignments($ticketCtime, $ticketCrand);
        $comments = self::getComments($ticketCtime, $ticketCrand);

        return new Response(true, 'Ticket loaded.', [
            'ticket' => $ticket,
            'assignments' => $assignments,
            'comments' => $comments,
        ]);
    }

    private static function validateTicketFields(
        string $subject,
        string $description,
        int $priority,
        ?int $severity = null,
        ?string $status = null
    ): Response {
        if ($subject === '' || $description === '') {
            return new Response(false, 'Subject and description are required.', null);
        }

        if (strlen($subject) > 255) {
            return new Response(false, 'Subject must be 255 characters or less.', null);
        }

        if ($priority < 1 || $priority > 4) {
            return new Response(false, 'Priority is invalid.', null);
        }

        if ($severity !== null && ($severity < 1 || $severity > 4)) {
            return new Response(false, 'Severity is invalid.', null);
        }

        if ($status !== null && !in_array($status, self::VALID_STATUSES, true)) {
            return new Response(false, 'Status is invalid.', null);
        }

        return new Response(true, 'Validated', null);
    }

    private static function normalizeCategory(mixed $input): ?string
    {
        if (is_null($input)) {
            return null;
        }

        $category = strtolower(trim((string) $input));
        if ($category === '') {
            return null;
        }

        return in_array($category, self::getAllowedCategories(), true) ? $category : null;
    }

    private static function normalizeCreatedIp(mixed $input): ?string
    {
        if (is_null($input)) {
            return null;
        }

        $ip = trim((string) $input);
        if ($ip === '') {
            return null;
        }

        $validated = filter_var($ip, FILTER_VALIDATE_IP);
        if ($validated === false) {
            return null;
        }

        return substr($validated, 0, 45);
    }

    private static function normalizeUserAgent(mixed $input): ?string 
    {
        if ($input === null) {
            return null;
        }

        $userAgent = trim((string) $input);
        if ($userAgent === '') {
            return null;
        }

        // Call global function explicitly
        return \mb_substr($userAgent, 0, 255);
    }


    private static function normalizePriority(mixed $input): ?int
    {
        if (is_null($input) || $input === '') {
            return null;
        }

        if (is_int($input)) {
            $value = $input;
        } elseif (is_numeric($input)) {
            $value = (int) $input;
        } else {
            $normalized = strtolower(trim((string) $input));
            $value = self::PRIORITY_LABELS[$normalized] ?? null;
        }

        if (is_null($value) || $value < 1 || $value > 4) {
            return null;
        }

        return $value;
    }

    private static function normalizeSeverity(mixed $input): ?int
    {
        if (is_null($input) || $input === '') {
            return null;
        }

        if (is_int($input)) {
            $value = $input;
        } elseif (is_numeric($input)) {
            $value = (int) $input;
        } else {
            $normalized = strtolower(trim((string) $input));
            $value = self::SEVERITY_LABELS[$normalized] ?? null;
        }

        if (is_null($value) || $value < 1 || $value > 4) {
            return null;
        }

        return $value;
    }

    private static function normalizeNullableInt(mixed $input): ?int
    {
        if (is_null($input)) {
            return null;
        }

        $value = (int) $input;
        return $value > 0 ? $value : null;
    }

    private static function normalizeNullableString(mixed $input): ?string
    {
        if (is_null($input)) {
            return null;
        }

        $trimmed = trim((string) $input);
        return $trimmed === '' ? null : $trimmed;
    }

    private static function priorityLabel(int $priority): string
    {
        $label = array_search($priority, self::PRIORITY_LABELS, true);
        return $label === false ? (string) $priority : (string) $label;
    }

    private static function severityLabel(?int $severity): string
    {
        if (is_null($severity)) {
            return 'unspecified';
        }

        $label = array_search($severity, self::SEVERITY_LABELS, true);
        return $label === false ? (string) $severity : (string) $label;
    }

    /**
     * @param array<string|int>|string $input
     * @return string[]
     */
    private static function normalizeTags(array|string|null $input): array
    {
        if ($input === null || $input === '') {
            return [];
        }
        
        if (is_string($input)) {
            $parts = array_map('trim', explode(',', $input));
            return array_values(array_filter($parts, fn ($tag) => $tag !== ''));
        }

        $tags = [];
        foreach ($input as $tag) {
            $trimmed = trim((string) $tag);
            if ($trimmed !== '') {
                $tags[] = $trimmed;
            }
        }
        return $tags;
    }

    /**
     * @param array<int|string>|string $input
     * @return int[]
     */
    private static function normalizeIntList(array|string|null $input): array
    {
        
        if ($input === null || $input === '') {
            return [];
        }
        
        if (is_string($input)) {
            $input = $input === '' ? [] : explode(',', $input);
        }

        $normalized = [];
        foreach ($input as $value) {
            $intVal = (int) $value;
            if ($intVal > 0) {
                $normalized[] = $intVal;
            }
        }
        return array_values(array_unique($normalized));
    }

    /**
     * @param array<string|int,bool>|string $input
     * @return array<int,bool>
     */
    private static function normalizeAssignmentEmailMap(array|string $input): array
    {
        if (is_string($input)) {
            $decoded = json_decode($input, true);
            if (!is_array($decoded)) {
                return [];
            }
            $input = $decoded;
        }

        $map = [];
        foreach ($input as $accountId => $optIn) {
            $map[(int) $accountId] = (bool) $optIn;
        }
        return $map;
    }

    private static function replaceTags(string $ticketCtime, int $ticketCrand, array $tags): void
    {
        $conn = Database::getConnection();
        $delete = $conn->prepare('DELETE FROM ' . self::TAG_TABLE . ' WHERE ticket_ctime = ? AND ticket_crand = ?');
        if ($delete) {
            $delete->bind_param('si', $ticketCtime, $ticketCrand);
            $delete->execute();
            $delete->close();
        }

        if (empty($tags)) {
            return;
        }

        $insert = $conn->prepare('INSERT INTO ' . self::TAG_TABLE . ' (ticket_ctime, ticket_crand, tag) VALUES (?, ?, ?)');
        if ($insert === false) {
            return;
        }

        foreach ($tags as $tag) {
            $safeTag = mb_substr($tag, 0, 64);
            $insert->bind_param('sis', $ticketCtime, $ticketCrand, $safeTag);
            $insert->execute();
        }
        $insert->close();
    }

    /**
     * @return TicketAssignment[]
     */
    private static function replaceAssignments(string $ticketCtime, int $ticketCrand, array $assignees, ?int $actorCrand): array
    {
        $conn = Database::getConnection();

        $delete = $conn->prepare('DELETE FROM ' . self::ASSIGNMENT_TABLE . ' WHERE ticket_ctime = ? AND ticket_crand = ?');
        if ($delete) {
            $delete->bind_param('si', $ticketCtime, $ticketCrand);
            $delete->execute();
            $delete->close();
        }

        if (empty($assignees)) {
            return [];
        }

        $insertSql = sprintf(
            'INSERT INTO %s (ticket_ctime, ticket_crand, account_crand, assigned_by_crand, assigned_at, email_opt_in, unsubscribe_token)'
            . ' VALUES (?, ?, ?, ?, ?, 1, ?)',
            self::ASSIGNMENT_TABLE
        );
        $insert = $conn->prepare($insertSql);
        if ($insert === false) {
            return [];
        }

        $now = (new RecordId())->ctime;
        $assignments = [];
        foreach ($assignees as $assignee) {
            $token = bin2hex(random_bytes(16));
            $insert->bind_param('siiiss', $ticketCtime, $ticketCrand, $assignee, $actorCrand, $now, $token);
            $insert->execute();
            $assignments[] = new TicketAssignment($ticketCtime, $ticketCrand, $assignee, $actorCrand, $now, true, $token, null, null);
        }
        $insert->close();

        return $assignments;
    }

    /**
     * @return TicketAssignment[]
     */
    private static function getAssignments(string $ticketCtime, int $ticketCrand): array
    {
        $conn = Database::getConnection();
        $stmt = $conn->prepare(
            'SELECT ta.*, a.Username AS account_username, ab.Username AS assigned_by_username'
            . ' FROM ' . self::ASSIGNMENT_TABLE . ' ta'
            . ' LEFT JOIN account a ON a.Id = ta.account_crand'
            . ' LEFT JOIN account ab ON ab.Id = ta.assigned_by_crand'
            . ' WHERE ta.ticket_ctime = ? AND ta.ticket_crand = ?'
        );
        if ($stmt === false) {
            return [];
        }

        $stmt->bind_param('si', $ticketCtime, $ticketCrand);
        if (!$stmt->execute()) {
            $stmt->close();
            return [];
        }

        $result = $stmt->get_result();
        $assignments = [];
        while ($row = $result->fetch_assoc()) {
            $assignments[] = TicketAssignment::fromRow($row);
        }
        $stmt->close();
        return $assignments;
    }

    private static function applyAssignmentEmailPreferences(string $ticketCtime, int $ticketCrand, array $preferences): void
    {
        if (empty($preferences)) {
            return;
        }

        $conn = Database::getConnection();
        $stmt = $conn->prepare('UPDATE ' . self::ASSIGNMENT_TABLE . ' SET email_opt_in = ? WHERE ticket_ctime = ? AND ticket_crand = ? AND account_crand = ?');
        if ($stmt === false) {
            return;
        }

        foreach ($preferences as $accountCrand => $optIn) {
            $optInInt = $optIn ? 1 : 0;
            $stmt->bind_param('isii', $optInInt, $ticketCtime, $ticketCrand, $accountCrand);
            $stmt->execute();
        }
        $stmt->close();
    }

    private static function disableNotificationsByToken(string $ticketCtime, int $ticketCrand, string $token): void
    {
        $conn = Database::getConnection();
        $stmt = $conn->prepare('UPDATE ' . self::ASSIGNMENT_TABLE . ' SET email_opt_in = 0 WHERE ticket_ctime = ? AND ticket_crand = ? AND unsubscribe_token = ?');
        if ($stmt === false) {
            return;
        }
        $stmt->bind_param('sis', $ticketCtime, $ticketCrand, $token);
        $stmt->execute();
        $stmt->close();
    }

    private static function createComment(string $ticketCtime, int $ticketCrand, ?int $authorCrand, string $body): Response
    {
        $recordId = new RecordId();
        $conn = Database::getConnection();
        $stmt = $conn->prepare(
            'INSERT INTO ' . self::COMMENT_TABLE . ' (ctime, crand, ticket_ctime, ticket_crand, author_crand, body)
             VALUES (?, ?, ?, ?, ?, ?)' 
        );

        if ($stmt === false) {
            return new Response(false, 'Unable to prepare comment insert.', null);
        }

        $stmt->bind_param(
            'sisiis',
            $recordId->ctime,
            $recordId->crand,
            $ticketCtime,
            $ticketCrand,
            $authorCrand,
            $body
        );

        if (!$stmt->execute()) {
            $stmt->close();
            return new Response(false, 'Failed to save comment.', null);
        }
        $stmt->close();

        return new Response(true, 'Comment added.', new TicketComment(
            $recordId->ctime,
            $recordId->crand,
            $ticketCtime,
            $ticketCrand,
            $authorCrand,
            $body
        ));
    }

    /**
     * @return TicketComment[]
     */
    private static function getComments(string $ticketCtime, int $ticketCrand): array
    {
        $conn = Database::getConnection();
        $stmt = $conn->prepare(
            'SELECT tc.*, a.Username AS author_username'
            . ' FROM ' . self::COMMENT_TABLE . ' tc'
            . ' LEFT JOIN account a ON a.Id = tc.author_crand'
            . ' WHERE tc.ticket_ctime = ? AND tc.ticket_crand = ?'
            . ' ORDER BY tc.ctime ASC'
        );
        if ($stmt === false) {
            return [];
        }

        $stmt->bind_param('si', $ticketCtime, $ticketCrand);
        if (!$stmt->execute()) {
            $stmt->close();
            return [];
        }

        $result = $stmt->get_result();
        $comments = [];
        while ($row = $result->fetch_assoc()) {
            $comments[] = TicketComment::fromRow($row);
        }
        $stmt->close();

        return $comments;
    }

    private static function fetchTicket(string $ctime, int $crand): ?Ticket
    {
        $conn = Database::getConnection();
        $stmt = $conn->prepare(
            'SELECT t.*, acc.Username AS created_by_username FROM ' . self::TICKET_TABLE . ' t'
            . ' LEFT JOIN account acc ON acc.Id = t.created_by_crand'
            . ' WHERE t.ctime = ? AND t.crand = ?'
        );
        if ($stmt === false) {
            return null;
        }

        $stmt->bind_param('si', $ctime, $crand);
        if (!$stmt->execute()) {
            $stmt->close();
            return null;
        }

        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        if (!$row) {
            return null;
        }

        return Ticket::fromRow($row);
    }

    private static function canModifyTicket(Ticket $ticket, int $accountCrand, bool $isSteward): bool
    {
        if ($isSteward) {
            return true;
        }

        if ($ticket->createdByCrand === $accountCrand) {
            return true;
        }

        return self::isAssigned($ticket->ctime, $ticket->crand, $accountCrand);
    }

    private static function isAssigned(string $ticketCtime, int $ticketCrand, int $accountCrand): bool
    {
        $conn = Database::getConnection();
        $stmt = $conn->prepare('SELECT 1 FROM ' . self::ASSIGNMENT_TABLE . ' WHERE ticket_ctime = ? AND ticket_crand = ? AND account_crand = ? LIMIT 1');
        if ($stmt === false) {
            return false;
        }

        $stmt->bind_param('sii', $ticketCtime, $ticketCrand, $accountCrand);
        if (!$stmt->execute()) {
            $stmt->close();
            return false;
        }

        $result = $stmt->get_result();
        $isAssigned = $result->num_rows > 0;
        $stmt->close();

        return $isAssigned;
    }

    /**
     * @param TicketAssignment[] $assignments
     */
    private static function notifyAssignees(Ticket $ticket, array $assignments, string $context, $actor, ?string $comment): void
    {
        if (empty($assignments)) {
            return;
        }

        $accountIds = array_map(fn (TicketAssignment $a) => $a->accountCrand, $assignments);
        $emails = self::getAccountEmails($accountIds);

        $credentials = ServiceCredentials::instance();
        $fromEmail = $credentials['smtp_from_email'] ?? null;
        $fromName = $credentials['smtp_from_name'] ?? null;
        if ($fromEmail === null || $fromName === null) {
            return;
        }

        foreach ($assignments as $assignment) {
            if (!$assignment->emailOptIn || !isset($emails[$assignment->accountCrand])) {
                continue;
            }

            $recipient = $emails[$assignment->accountCrand];
            $mail = new PHPMailer(true);

            try {
                $mail->isSMTP();
                $mail->SMTPAuth = filter_var($credentials['smtp_auth'] ?? false, FILTER_VALIDATE_BOOLEAN);
                $mail->SMTPSecure = $credentials['smtp_secure'] ?? '';
                $mail->Host = $credentials['smtp_host'] ?? '';
                $mail->Port = intval($credentials['smtp_port'] ?? 0);
                $mail->Username = $credentials['smtp_username'] ?? '';
                $mail->Password = $credentials['smtp_password'] ?? '';

                $mail->setFrom($fromEmail, $fromName);
                $mail->addAddress($recipient['email'], $recipient['username']);
                if (!empty($credentials['smtp_replyto_email'] ?? null)) {
                    $mail->addReplyTo((string) $credentials['smtp_replyto_email'], (string) ($credentials['smtp_replyto_name'] ?? 'Kickback Kingdom'));
                }

                $mail->isHTML(true);
                $mail->Subject = '[Ticket ' . $context . '] ' . $ticket->subject;

                $actorName = isset($actor->username) ? $actor->username : 'System';
                $bodyLines = [
                    '<p>A ticket assigned to you was ' . htmlspecialchars($context) . '.</p>',
                    '<p><strong>Title:</strong> ' . htmlspecialchars($ticket->subject) . '</p>',
                    '<p><strong>Status:</strong> ' . htmlspecialchars($ticket->status) . ' | <strong>Priority:</strong> '
                        . htmlspecialchars(self::priorityLabel($ticket->priority))
                        . ' | <strong>Severity:</strong> ' . htmlspecialchars(self::severityLabel($ticket->severity)) . '</p>',
                    '<p><strong>Updated by:</strong> ' . htmlspecialchars($actorName) . '</p>',
                ];

                if ($comment) {
                    $bodyLines[] = '<p><strong>Comment:</strong><br>' . nl2br(htmlspecialchars($comment)) . '</p>';
                }

                $bodyLines[] = '<p>To stop receiving updates for this ticket, provide this token in your preferences: <code>' . htmlspecialchars($assignment->unsubscribeToken) . '</code></p>';

                $mail->Body = implode('\n', $bodyLines);
                $mail->AltBody = "A ticket assigned to you was {$context}. Title: {$ticket->subject}. Status: {$ticket->status}."
                    . " Priority: " . self::priorityLabel($ticket->priority)
                    . ". Severity: " . self::severityLabel($ticket->severity)
                    . ". Updated by: {$actorName}. Unsubscribe token: {$assignment->unsubscribeToken}";

                $mail->send();
            } catch (MailException $exception) {
                error_log('Ticket notification failed: ' . $exception->getMessage());
            }
        }
    }

    /**
     * @param int[] $accountIds
     * @return array<int,array{email:string,username:string}>
     */
    private static function getAccountEmails(array $accountIds): array
    {
        if (empty($accountIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($accountIds), '?'));
        $types = str_repeat('i', count($accountIds));

        $conn = Database::getConnection();
        $stmt = $conn->prepare('SELECT Id, Email, Username FROM account WHERE Id IN (' . $placeholders . ')');
        if ($stmt === false) {
            return [];
        }

        $stmt->bind_param($types, ...$accountIds);
        if (!$stmt->execute()) {
            $stmt->close();
            return [];
        }

        $result = $stmt->get_result();
        $emails = [];
        while ($row = $result->fetch_assoc()) {
            $emails[(int) $row['Id']] = [
                'email' => (string) $row['Email'],
                'username' => (string) $row['Username'],
            ];
        }

        $stmt->close();
        return $emails;
    }

    /**
     * Support ticket endpoints (legacy public form) - merged from SupportTicketController.
     *
     * @param array<string,mixed> $post
     * @param array<string,mixed> $files
     */
    public static function createTicketFromRequest(array $post, array $files): Response
    {
        if (!Session::readCurrentAccountInto($account)) {
            return new Response(false, 'You must be logged in to submit a support ticket.', null);
        }

        $category = strtolower(trim((string) ($post['category'] ?? '')));
        $priorityInput = $post['priority'] ?? '';
        $severityInput = $post['severity'] ?? null;
        $subject = trim((string) ($post['subject'] ?? ''));
        $description = trim((string) ($post['description'] ?? ''));
        $guildContext = isset($post['guildContext']) ? trim((string) $post['guildContext']) : null;
        $contactEmail = isset($account->email)
            ? trim((string) filter_var($account->email, FILTER_SANITIZE_EMAIL))
            : '';

        $priority = self::normalizePriority($priorityInput);
        if ($priority === null) {
            return new Response(false, 'Please select a valid priority.', null);
        }

        $severity = self::normalizeSeverity($severityInput);
        if ($severityInput !== null && $severityInput !== '' && is_null($severity)) {
            return new Response(false, 'Please select a valid severity.', null);
        }

        $validation = self::validateTicketInputs(
            $category,
            $priority,
            $subject,
            $description,
            $guildContext,
            $contactEmail,
            $severity
        );
        if (!$validation->success) {
            return $validation;
        }

        $attachments = self::prepareAttachments($files['attachments'] ?? null);
        if (!$attachments->success) {
            return $attachments;
        }

        return self::createTicketFromPublicForm(
            $category,
            $priority,
            $subject,
            $description,
            $guildContext === '' ? null : $guildContext,
            $contactEmail,
            $attachments->data ?? [],
            $account->crand,
            $severity
        );
    }

    /**
     * @param array<int,array<string,mixed>> $attachments
     */
    public static function createTicketFromPublicForm(
        string $category,
        int $priority,
        string $subject,
        string $description,
        ?string $guildContext,
        string $contactEmail,
        array $attachments = [],
        ?int $accountCrand = null,
        ?int $severity = null
    ): Response {
        $recordId = new RecordId();
        $accountCrand = is_null($accountCrand) && Session::readCurrentAccountInto($account)
            ? $account->crand
            : $accountCrand;

        $conn = Database::getConnection();

        $stmt = $conn->prepare(
            'INSERT INTO ' . self::TICKET_TABLE
            . ' (ctime, crand, created_by_crand, category, priority, severity, subject, description, status, updated_at, updated_by_crand)'
            . ' VALUES (?, ?, ?, ?, ?, ?, ?, ?, "open", ?, ?)'
        );

        if ($stmt === false) {
            return new Response(false, 'Failed to prepare support ticket save.', null);
        }

        $safeSubject = mb_substr($subject, 0, 255);

        $stmt->bind_param(
            'siisiisssi',
            $recordId->ctime,
            $recordId->crand,
            $accountCrand,
            $category,
            $priority,
            $severity,
            $safeSubject,
            $description,
            $recordId->ctime,
            $accountCrand
        );

        if (!$stmt->execute()) {
            $stmt->close();
            return new Response(false, 'Failed to save support ticket.', null);
        }

        $stmt->close();

        if (!empty($attachments)) {
            $attachmentResp = self::storeAttachments($recordId, $attachments);
            if (!$attachmentResp->success) {
                return $attachmentResp;
            }
        }

        return new Response(true, 'Ticket submitted.', [
            'ctime' => $recordId->ctime,
            'crand' => $recordId->crand,
        ]);
    }

    private static function validateTicketInputs(
        string $category,
        int $priority,
        string $subject,
        string $description,
        ?string $guildContext,
        string $contactEmail,
        ?int $severity = null
    ): Response {
        $allowedCategories = self::getAllowedCategories();

        if ($subject === '' || $description === '' || $contactEmail === '') {
            return new Response(false, 'Subject, description, and an account email are required.', null);
        }

        if (!filter_var($contactEmail, FILTER_VALIDATE_EMAIL)) {
            return new Response(false, 'Please ensure your account email is valid.', null);
        }

        if (!in_array($category, $allowedCategories, true)) {
            return new Response(false, 'Please select a valid category.', null);
        }

        if ($priority < 1 || $priority > 4) {
            return new Response(false, 'Please select a valid priority.', null);
        }

        if ($severity !== null && ($severity < 1 || $severity > 4)) {
            return new Response(false, 'Please select a valid severity.', null);
        }

        if (strlen($subject) > 255 || strlen($contactEmail) > 255) {
            return new Response(false, 'Subject or email is too long.', null);
        }

        if (strlen($description) > 5000) {
            return new Response(false, 'Description is too long (5000 character limit).', null);
        }

        if (!is_null($guildContext) && strlen($guildContext) > 255) {
            return new Response(false, 'Guild context must be 255 characters or less.', null);
        }

        return new Response(true, 'Validated', null);
    }

    /**
     * @return string[]
     */
    private static function getAllowedCategories(): array
    {
        $categoryResp = self::listCategories();
        if ($categoryResp->success && is_array($categoryResp->data)) {
            $categories = [];
            foreach ($categoryResp->data as $category) {
                if ($category instanceof TicketCategory) {
                    $categories[] = $category->slug;
                } elseif (is_array($category) && isset($category['slug'])) {
                    $categories[] = (string) $category['slug'];
                }
            }
            if (!empty($categories)) {
                return $categories;
            }
        }

        return ['bug', 'feature', 'todo'];
    }

    public static function listCategories(): Response
    {
        $conn = Database::getConnection();
        $query = 'SELECT ctime, crand, slug, name FROM ' . self::CATEGORY_TABLE . ' ORDER BY name ASC';
        $result = $conn->query($query);

        if ($result === false) {
            return new Response(false, 'Unable to load categories.', null);
        }

        $categories = [];
        while ($row = $result->fetch_assoc()) {
            $categories[] = TicketCategory::fromRow($row);
        }

        return new Response(true, 'Categories loaded.', $categories);
    }

    /**
     * @param array<string,mixed> $payload
     */
    public static function createCategory(array $payload): Response
    {
        if (!self::canManageCategories()) {
            return new Response(false, 'You do not have permission to create categories.', null);
        }

        $name = trim((string) ($payload['name'] ?? ''));
        $slug = trim((string) ($payload['slug'] ?? ''));

        if ($name === '') {
            return new Response(false, 'Category name is required.', null);
        }

        if (strlen($name) > 100) {
            return new Response(false, 'Category name must be 100 characters or less.', null);
        }

        $slug = self::normalizeSlug($slug === '' ? $name : $slug);
        if ($slug === '') {
            return new Response(false, 'Category slug is invalid.', null);
        }

        if (strlen($slug) > 100) {
            return new Response(false, 'Category slug must be 100 characters or less.', null);
        }

        $recordId = new RecordId();

        $conn = Database::getConnection();
        $stmt = $conn->prepare('INSERT INTO ' . self::CATEGORY_TABLE . ' (ctime, crand, slug, name) VALUES (?, ?, ?, ?)');
        if ($stmt === false) {
            return new Response(false, 'Unable to prepare category insert.', null);
        }

        $stmt->bind_param('siss', $recordId->ctime, $recordId->crand, $slug, $name);

        if (!$stmt->execute()) {
            $message = 'Failed to create category.';
            if ($stmt->errno === 1062) {
                $constraint = (string) $stmt->error;
                if (str_contains($constraint, 'slug')) {
                    $message = 'A category with that slug already exists.';
                } else {
                    $message = 'A category with that name already exists.';
                }
            }
            $stmt->close();
            return new Response(false, $message, null);
        }

        $stmt->close();

        return new Response(true, 'Category created.', new TicketCategory($recordId->ctime, $recordId->crand, $slug, $name));
    }

    /**
     * @param array<string,mixed> $payload
     */
    public static function updateCategory(array $payload): Response
    {
        if (!self::canManageCategories()) {
            return new Response(false, 'You do not have permission to update categories.', null);
        }

        $ctime = trim((string) ($payload['ctime'] ?? ''));
        $crand = (int) ($payload['crand'] ?? 0);
        $nameInput = trim((string) ($payload['name'] ?? ''));
        $slugInput = trim((string) ($payload['slug'] ?? ''));

        if ($ctime === '' || $crand <= 0) {
            return new Response(false, 'A category identifier is required.', null);
        }

        $conn = Database::getConnection();
        $selectStmt = $conn->prepare('SELECT slug, name FROM ' . self::CATEGORY_TABLE . ' WHERE ctime = ? AND crand = ? LIMIT 1');
        if ($selectStmt === false) {
            return new Response(false, 'Unable to load category for update.', null);
        }

        $selectStmt->bind_param('si', $ctime, $crand);
        if (!$selectStmt->execute()) {
            $selectStmt->close();
            return new Response(false, 'Unable to load category for update.', null);
        }

        $result = $selectStmt->get_result();
        if ($result === false || $result->num_rows === 0) {
            $selectStmt->close();
            return new Response(false, 'Category not found.', null);
        }

        $existing = $result->fetch_assoc();
        $selectStmt->close();

        $currentName = (string) ($existing['name'] ?? '');
        $currentSlug = (string) ($existing['slug'] ?? '');

        $newName = $nameInput !== '' ? $nameInput : $currentName;
        $newSlug = $slugInput !== '' ? self::normalizeSlug($slugInput) : $currentSlug;

        if ($newName === '') {
            return new Response(false, 'Category name is required.', null);
        }

        if (strlen($newName) > 100) {
            return new Response(false, 'Category name must be 100 characters or less.', null);
        }

        if ($newSlug === '') {
            return new Response(false, 'Category slug is invalid.', null);
        }

        if (strlen($newSlug) > 100) {
            return new Response(false, 'Category slug must be 100 characters or less.', null);
        }

        if ($newName === $currentName && $newSlug === $currentSlug) {
            return new Response(true, 'No changes made.', new TicketCategory($ctime, $crand, $currentSlug, $currentName));
        }

        $updateStmt = $conn->prepare('UPDATE ' . self::CATEGORY_TABLE . ' SET slug = ?, name = ? WHERE ctime = ? AND crand = ?');
        if ($updateStmt === false) {
            return new Response(false, 'Unable to prepare category update.', null);
        }

        $updateStmt->bind_param('sssi', $newSlug, $newName, $ctime, $crand);
        if (!$updateStmt->execute()) {
            $message = 'Failed to update category.';
            if ($updateStmt->errno === 1062) {
                $constraint = (string) $updateStmt->error;
                if (str_contains($constraint, 'slug')) {
                    $message = 'A category with that slug already exists.';
                } else {
                    $message = 'A category with that name already exists.';
                }
            }
            $updateStmt->close();
            return new Response(false, $message, null);
        }

        $updateStmt->close();
        return new Response(true, 'Category updated.', new TicketCategory($ctime, $crand, $newSlug, $newName));
    }

    /**
     * @param array<string,mixed> $payload
     */
    public static function deleteCategory(array $payload): Response
    {
        if (!self::canManageCategories()) {
            return new Response(false, 'You do not have permission to delete categories.', null);
        }

        $ctime = trim((string) ($payload['ctime'] ?? ''));
        $crand = (int) ($payload['crand'] ?? 0);

        if ($ctime === '' || $crand <= 0) {
            return new Response(false, 'A category identifier is required.', null);
        }

        $conn = Database::getConnection();
        $stmt = $conn->prepare('DELETE FROM ' . self::CATEGORY_TABLE . ' WHERE ctime = ? AND crand = ?');
        if ($stmt === false) {
            return new Response(false, 'Unable to prepare category deletion.', null);
        }

        $stmt->bind_param('si', $ctime, $crand);
        if (!$stmt->execute()) {
            $stmt->close();
            return new Response(false, 'Failed to delete category.', null);
        }

        $stmt->close();
        return new Response(true, 'Category deleted.', ['ctime' => $ctime, 'crand' => $crand]);
    }

    private static function canManageCategories(): bool
    {
        return Session::isSteward();
    }

    private static function normalizeSlug(string $input): string
    {
        $slug = strtolower($input);
        $slug = preg_replace('/[^a-z0-9]+/i', '-', $slug) ?? '';
        $slug = trim($slug, '-');
        return $slug;
    }

    /**
     * @param array<string,mixed>|null $uploadedFiles
     * @return Response<array<int,array<string,mixed>>>
     */
    private static function prepareAttachments($uploadedFiles): Response
    {
        if (!isset($uploadedFiles) || !is_array($uploadedFiles['name'] ?? null)) {
            return new Response(true, 'No attachments provided.', []);
        }

        $maxFiles = 5;
        $maxSize = 8 * 1024 * 1024; // 8 MB per file
        $totalFiles = count($uploadedFiles['name']);

        if ($totalFiles > $maxFiles) {
            return new Response(false, 'Please limit attachments to five files.', null);
        }

        $attachments = [];
        for ($i = 0; $i < $totalFiles; $i++) {
            $name = $uploadedFiles['name'][$i];
            $tmpName = $uploadedFiles['tmp_name'][$i];
            $error = $uploadedFiles['error'][$i];
            $size = (int) $uploadedFiles['size'][$i];

            if ($error === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            if ($error !== UPLOAD_ERR_OK) {
                return new Response(false, 'One of the attachments failed to upload.', null);
            }

            if ($size > $maxSize) {
                return new Response(false, 'Attachments are limited to 8MB each.', null);
            }

            $mime = mime_content_type($tmpName) ?: ($uploadedFiles['type'][$i] ?? 'application/octet-stream');
            $safeOriginalName = preg_replace('/[^A-Za-z0-9_. -]/', '_', basename((string) $name));
            $storedName = uniqid('ticket_', true) . '_' . $safeOriginalName;

            $attachments[] = [
                'tmp_name' => $tmpName,
                'original_name' => $safeOriginalName,
                'mime_type' => $mime,
                'size' => $size,
                'stored_name' => $storedName,
            ];
        }

        return new Response(true, 'Attachments prepared.', $attachments);
    }
    /**
     * @param array<int,array<string,mixed>> $attachments
     */
    private static function storeAttachments(RecordId $ticketId, array $attachments): Response
    {
        $uploadDir = rtrim(\Kickback\SCRIPT_ROOT, '/') . '/assets/tickets';
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
            return new Response(false, 'Unable to create attachment directory.', null);
        }

        $conn = Database::getConnection();
        $stmt = $conn->prepare(
            'INSERT INTO ' . self::ATTACHMENT_TABLE . ' (
                ctime,
                crand,
                ticket_ctime,
                ticket_crand,
                stored_name,
                original_name,
                mime_type,
                size_bytes
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );

        if ($stmt === false) {
            return new Response(false, 'Failed to prepare attachment save.', null);
        }

        foreach ($attachments as $attachment) {
            // attachment's own ID (ctime + crand)
            $attachmentId = new RecordId();

            $storedName = $attachment['stored_name'];
            $targetPath = $uploadDir . '/' . $storedName;

            if (!move_uploaded_file($attachment['tmp_name'], $targetPath)) {
                $stmt->close();
                return new Response(false, 'Failed to store attachment on disk.', null);
            }

            $stmt->bind_param(
                'sisssssi',
                $attachmentId->ctime,          // ctime (attachment)
                $attachmentId->crand,          // crand (attachment)
                $ticketId->ctime,              // ticket_ctime
                $ticketId->crand,              // ticket_crand
                $storedName,                   // stored_name
                $attachment['original_name'],  // original_name
                $attachment['mime_type'],      // mime_type
                $attachment['size']            // size_bytes
            );

            if (!$stmt->execute()) {
                $stmt->close();
                return new Response(false, 'Failed to store attachment metadata.', null);
            }
        }

        $stmt->close();
        return new Response(true, 'Attachments saved.', null);
    }

}
