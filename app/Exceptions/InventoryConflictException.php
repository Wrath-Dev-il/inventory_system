<?php

namespace App\Exceptions;

use Exception;

class InventoryConflictException extends Exception
{
    private array $conflicts;

    public function __construct(array $conflicts)
    {
        $this->conflicts = $conflicts;
        parent::__construct('Inventory quantity conflicts detected.');
    }

    public function getConflicts(): array
    {
        return $this->conflicts;
    }
}
