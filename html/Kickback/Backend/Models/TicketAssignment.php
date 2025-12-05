<?php

declare(strict_types=1);

namespace Kickback\Backend\Models;

class TicketAssignment
{
    public string $ticketCtime;
    public int $ticketCrand;
    public int $accountCrand;
    public ?int $assignedByCrand;
    public string $assignedAt;
    public bool $emailOptIn;
    public string $unsubscribeToken;

    public function __construct(
        string $ticketCtime,
        int $ticketCrand,
        int $accountCrand,
        ?int $assignedByCrand,
        string $assignedAt,
        bool $emailOptIn,
        string $unsubscribeToken
    ) {
        $this->ticketCtime = $ticketCtime;
        $this->ticketCrand = $ticketCrand;
        $this->accountCrand = $accountCrand;
        $this->assignedByCrand = $assignedByCrand;
        $this->assignedAt = $assignedAt;
        $this->emailOptIn = $emailOptIn;
        $this->unsubscribeToken = $unsubscribeToken;
    }

    /**
     * @param array<string,mixed> $row
     */
    public static function fromRow(array $row): self
    {
        return new self(
            (string) $row['ticket_ctime'],
            (int) $row['ticket_crand'],
            (int) $row['account_crand'],
            isset($row['assigned_by_crand']) ? (int) $row['assigned_by_crand'] : null,
            (string) $row['assigned_at'],
            (bool) $row['email_opt_in'],
            (string) $row['unsubscribe_token']
        );
    }
}
