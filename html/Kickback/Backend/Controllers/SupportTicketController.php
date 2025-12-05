<?php

declare(strict_types=1);

namespace Kickback\Backend\Controllers;
use Kickback\Backend\Models\RecordId;
use Kickback\Backend\Models\Response;
use Kickback\Services\Database;
use Kickback\Services\Session;

class SupportTicketController
{
    private const TABLE_NAME = 'support_tickets';
    private const ATTACHMENT_TABLE = 'support_ticket_attachments';

    public static function createTicketFromRequest(array $post, array $files): Response
    {
        $category = strtolower(trim($post['category'] ?? ''));
        $priority = strtolower(trim($post['priority'] ?? ''));
        $subject = trim($post['subject'] ?? '');
        $description = trim($post['description'] ?? '');
        $guildContext = isset($post['guildContext']) ? trim($post['guildContext']) : null;
        $contactEmail = isset($post['contactEmail']) ? trim(filter_var($post['contactEmail'], FILTER_SANITIZE_EMAIL)) : '';

        $validation = self::validateTicketInputs($category, $priority, $subject, $description, $guildContext, $contactEmail);
        if (!$validation->success) {
            return $validation;
        }

        $attachments = self::prepareAttachments($files['attachments'] ?? null);
        if (!$attachments->success) {
            return $attachments;
        }

        return self::createTicket(
            $category,
            $priority,
            $subject,
            $description,
            $guildContext === '' ? null : $guildContext,
            $contactEmail,
            $attachments->data ?? []
        );
    }

    public static function createTicket(
        string $category,
        string $priority,
        string $subject,
        string $description,
        ?string $guildContext,
        string $contactEmail,
        array $attachments = []
    ): Response {
        self::ensureTables();

        $recordId = new RecordId();
        $accountCrand = null;
        if (Session::readCurrentAccountInto($account)) {
            $accountCrand = $account->crand;
        }

        $conn = Database::getConnection();

        $stmt = $conn->prepare(
            'INSERT INTO ' . self::TABLE_NAME . ' (ctime, crand, account_crand, category, priority, subject, description, guild_context, contact_email, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, "open")'
        );

        if ($stmt === false) {
            return new Response(false, 'Failed to prepare support ticket save.', null);
        }

        $safeSubject = mb_substr($subject, 0, 255);
        $safeGuildContext = is_null($guildContext) ? null : mb_substr($guildContext, 0, 255);

        $stmt->bind_param(
            'sisssssss',
            $recordId->ctime,
            $recordId->crand,
            $accountCrand,
            $category,
            $priority,
            $safeSubject,
            $description,
            $safeGuildContext,
            $contactEmail
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
        string $priority,
        string $subject,
        string $description,
        ?string $guildContext,
        string $contactEmail
    ): Response {
        $allowedCategories = ['bug', 'feature', 'todo'];
        $allowedPriorities = ['low', 'medium', 'high', 'urgent'];

        if ($subject === '' || $description === '' || $contactEmail === '') {
            return new Response(false, 'Subject, description, and contact email are required.', null);
        }

        if (!filter_var($contactEmail, FILTER_VALIDATE_EMAIL)) {
            return new Response(false, 'Please provide a valid contact email.', null);
        }

        if (!in_array($category, $allowedCategories, true)) {
            return new Response(false, 'Please select a valid category.', null);
        }

        if (!in_array($priority, $allowedPriorities, true)) {
            return new Response(false, 'Please select a valid priority.', null);
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
            $safeOriginalName = preg_replace('/[^A-Za-z0-9_. -]/', '_', basename($name));
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

    private static function ensureTables(): void
    {
        $conn = Database::getConnection();

        $conn->query(
            'CREATE TABLE IF NOT EXISTS ' . self::TABLE_NAME . ' (
                ctime DATETIME(6) NOT NULL,
                crand INT NOT NULL,
                account_crand INT NULL,
                category VARCHAR(32) NOT NULL,
                priority VARCHAR(32) NOT NULL,
                subject VARCHAR(255) NOT NULL,
                description TEXT NOT NULL,
                guild_context VARCHAR(255) NULL,
                contact_email VARCHAR(255) NOT NULL,
                status VARCHAR(32) NOT NULL DEFAULT "open",
                PRIMARY KEY (ctime, crand),
                INDEX idx_support_ticket_account (account_crand)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;'
        );

        $conn->query(
            'CREATE TABLE IF NOT EXISTS ' . self::ATTACHMENT_TABLE . ' (
                ticket_ctime DATETIME(6) NOT NULL,
                ticket_crand INT NOT NULL,
                stored_name VARCHAR(255) NOT NULL,
                original_name VARCHAR(255) NOT NULL,
                mime_type VARCHAR(128) NOT NULL,
                size_bytes INT NOT NULL,
                PRIMARY KEY (ticket_ctime, ticket_crand, stored_name),
                CONSTRAINT fk_ticket_attachment_ticket FOREIGN KEY (ticket_ctime, ticket_crand)
                    REFERENCES ' . self::TABLE_NAME . ' (ctime, crand)
                    ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;'
        );
    }

    private static function storeAttachments(RecordId $ticketId, array $attachments): Response
    {
        $uploadDir = rtrim(\Kickback\SCRIPT_ROOT, '/') . '/assets/support-tickets';
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
            return new Response(false, 'Unable to create attachment directory.', null);
        }

        $conn = Database::getConnection();
        $stmt = $conn->prepare(
            'INSERT INTO ' . self::ATTACHMENT_TABLE . ' (ticket_ctime, ticket_crand, stored_name, original_name, mime_type, size_bytes)
             VALUES (?, ?, ?, ?, ?, ?)'
        );

        if ($stmt === false) {
            return new Response(false, 'Failed to prepare attachment save.', null);
        }

        foreach ($attachments as $attachment) {
            $storedName = $attachment['stored_name'];
            $targetPath = $uploadDir . '/' . $storedName;

            if (!move_uploaded_file($attachment['tmp_name'], $targetPath)) {
                $stmt->close();
                return new Response(false, 'Failed to store attachment on disk.', null);
            }

            $stmt->bind_param(
                'sisssi',
                $ticketId->ctime,
                $ticketId->crand,
                $storedName,
                $attachment['original_name'],
                $attachment['mime_type'],
                $attachment['size']
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

?>
