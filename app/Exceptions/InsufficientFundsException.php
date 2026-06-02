<?php

namespace App\Exceptions;

use RuntimeException;

class InsufficientFundsException extends RuntimeException
{
    public function __construct(float $balance, float $amount)
    {
        parent::__construct(
            "Insufficient funds. Current balance: {$balance}, attempted: {$amount}"
        );
    }
}

