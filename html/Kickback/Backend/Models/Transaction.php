<?php

declare(strict_types = 1);

namespace Kickback\Backend\Models;

use Kickback\Backend\Models\Enums\TransactionType;
use Kickback\Backend\Views\vAccount;
use Kickback\Backend\Views\vRecordId;

class Transaction extends RecordId
{
    public bool $complete;
    public bool $void;
    public string $description;
    public string $type;
    public vAccount $firstAccount;
    public vAccount $secondAccount;

    /**
     * Access $transactionComponents through functions in order to allow type-checking when adding components.
     * Only TransactionComponents should be in the array
     */
    private array $transactionComponents;

    
    public function getComponents() : array
    {
        return $this->transactionComponents;
    }

    public function addComponent(TransactionComponent $component) : void
    {
        array_push($this->transactionComponents, $component);
    }
}

?>