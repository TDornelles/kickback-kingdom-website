<?php

declare(strict_types=1);

namespace Kickback\Backend\Models;

use Kickback\Backend\Views\vRecordId;

class TicketComment extends vRecordId
{
    public ?int $authorCrand;
    public ?string $authorUsername;
    public string $body;
    public string $ticketCtime;
    public int $ticketCrand;

    public function __construct(
        string $ctime,
        int $crand,
        string $ticketCtime,
        int $ticketCrand,
        ?int $authorCrand,
        string $body,
        ?string $authorUsername = null
    ) {
        parent::__construct($ctime, $crand);
        $this->ticketCtime = $ticketCtime;
        $this->ticketCrand = $ticketCrand;
        $this->authorCrand = $authorCrand;
        $this->authorUsername = $authorUsername;
        $this->body = $body;
    }

    /**
     * @param array<string,mixed> $row
     */
    public static function fromRow(array $row): self
    {
        return new self(
            (string) $row['ctime'],
            (int) $row['crand'],
            (string) $row['ticket_ctime'],
            (int) $row['ticket_crand'],
            isset($row['author_crand']) ? (int) $row['author_crand'] : null,
            (string) $row['body'],
            array_key_exists('author_username', $row) ? (string) $row['author_username'] : null
        );
    }
}
