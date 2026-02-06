<?php

declare(strict_types = 1);

namespace Kickback\BackendV2\DAO\Item;

use Kickback\BackendV2\Persistance\Database;

class PDOItemDAO
{
    private Database $pdo;

    public function __construct(?Database $pdo = null)
    {
        $this->pdo = $pdo ?? new Database();
    }
}

?>
