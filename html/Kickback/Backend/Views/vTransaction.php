<?php

declare(strict_types = 1);

namespace Kickback\Backend\Views;

class vTransaction extends vRecordId
{
    public bool $complete;
    public bool $void;
    public string $description;
    public string $type;
    public vAccount $firstAccount;
    public vAccount $secondAccount;

    /**
     * Access $transactionComponets through functions in order to allow type-checking when adding components.
     * Only vTransactionComponents should be in the array
     */
    private array $transactionComponents = [];

    
    public function getComponents() : array
    {
        return $this->transactionComponents;
    }

    public function addComponent(vTransactionComponent $component) : void
    {
        array_push($this->transactionComponents, $component);
    }
}

?>