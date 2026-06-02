<?php

namespace App\Exceptions;

use RuntimeException;

class AccountNotFoundException extends RuntimeException
{
    public function __construct(int $userId)
    {
        parent::__construct("Account not found for user ID: {$userId}");
    }
}

