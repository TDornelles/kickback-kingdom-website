<?php

declare(strict_types=1);

namespace Kickback\Backend\Models;

use Kickback\Backend\Views\vRecordId;

class Ticket extends vRecordId
{
    public string $subject;
    public string $description;
    public string $status;
    public string $priority;
    /** @var string[] */
    public array $tags;
    public ?int $createdByCrand;
    public string $updatedAt;

    /**
     * @param string[] $tags
     */
    public function __construct(
        string $ctime,
        int $crand,
        string $subject,
        string $description,
        string $status,
        string $priority,
        array $tags,
        ?int $createdByCrand,
        string $updatedAt
    ) {
        parent::__construct($ctime, $crand);
        $this->subject = $subject;
        $this->description = $description;
        $this->status = $status;
        $this->priority = $priority;
        $this->tags = $tags;
        $this->createdByCrand = $createdByCrand;
        $this->updatedAt = $updatedAt;
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
            (string) $row['priority'],
            $tags,
            isset($row['created_by_crand']) ? (int) $row['created_by_crand'] : null,
            (string) $row['updated_at']
        );
    }
}
