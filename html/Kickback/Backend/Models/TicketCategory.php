<?php

declare(strict_types=1);

namespace Kickback\Backend\Models;

use Kickback\Backend\Views\vRecordId;

class TicketCategory extends vRecordId
{
    public string $slug;
    public string $name;

    public function __construct(string $ctime, int $crand, string $slug, string $name)
    {
        parent::__construct($ctime, $crand);
        $this->slug = $slug;
        $this->name = $name;
    }

    /**
     * @param array<string,mixed> $row
     */
    public static function fromRow(array $row): self
    {
        return new self(
            (string) $row['ctime'],
            (int) $row['crand'],
            (string) $row['slug'],
            (string) $row['name']
        );
    }
}
