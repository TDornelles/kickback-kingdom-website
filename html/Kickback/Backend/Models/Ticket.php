<?php

declare(strict_types=1);

namespace Kickback\Backend\Models;

use Kickback\Backend\Views\vRecordId;

class Ticket extends vRecordId
{
    public string $subject;
    public string $description;
    public string $status;
    public int $priority;
    public ?int $severity;
    /** @var string[] */
    public array $tags;
    public ?int $guildId;
    public ?int $gameId;
    public ?string $serverCtime;
    public ?int $serverCrand;
    public ?int $createdByCrand;
    public ?int $updatedByCrand;
    public string $updatedAt;
    public ?string $firstResponseAt;
    public ?string $resolvedAt;
    public ?string $closedAt;

    /**
     * @param string[] $tags
     */
    public function __construct(
        string $ctime,
        int $crand,
        string $subject,
        string $description,
        string $status,
        int $priority,
        ?int $severity,
        array $tags,
        ?int $guildId,
        ?int $gameId,
        ?string $serverCtime,
        ?int $serverCrand,
        ?int $createdByCrand,
        ?int $updatedByCrand,
        string $updatedAt,
        ?string $firstResponseAt,
        ?string $resolvedAt,
        ?string $closedAt
    ) {
        parent::__construct($ctime, $crand);
        $this->subject = $subject;
        $this->description = $description;
        $this->status = $status;
        $this->priority = $priority;
        $this->severity = $severity;
        $this->tags = $tags;
        $this->guildId = $guildId;
        $this->gameId = $gameId;
        $this->serverCtime = $serverCtime;
        $this->serverCrand = $serverCrand;
        $this->createdByCrand = $createdByCrand;
        $this->updatedByCrand = $updatedByCrand;
        $this->updatedAt = $updatedAt;
        $this->firstResponseAt = $firstResponseAt;
        $this->resolvedAt = $resolvedAt;
        $this->closedAt = $closedAt;
    }

    /**
     * @param array<string,mixed> $row
     */
    public static function fromRow(array $row): self
    {
        $tags = [];
        if (isset($row['tags_json'])) {
            $decoded = json_decode((string) $row['tags_json'], true);
            if (is_array($decoded)) {
                $tags = array_values(array_map('strval', $decoded));
            }
        }

        return new self(
            (string) $row['ctime'],
            (int) $row['crand'],
            (string) $row['subject'],
            (string) $row['description'],
            (string) $row['status'],
            isset($row['priority']) ? (int) $row['priority'] : 0,
            isset($row['severity']) ? (int) $row['severity'] : null,
            $tags,
            isset($row['guild_id']) ? (int) $row['guild_id'] : null,
            isset($row['game_id']) ? (int) $row['game_id'] : null,
            isset($row['server_ctime']) ? (string) $row['server_ctime'] : null,
            isset($row['server_crand']) ? (int) $row['server_crand'] : null,
            isset($row['created_by_crand']) ? (int) $row['created_by_crand'] : null,
            isset($row['updated_by_crand']) ? (int) $row['updated_by_crand'] : null,
            (string) $row['updated_at'],
            isset($row['first_response_at']) ? (string) $row['first_response_at'] : null,
            isset($row['resolved_at']) ? (string) $row['resolved_at'] : null,
            isset($row['closed_at']) ? (string) $row['closed_at'] : null
        );
    }
}
