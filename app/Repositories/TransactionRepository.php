<?php

namespace App\Repositories;

use App\Models\Account;
use App\Models\Transaction;

class TransactionRepository
{
    /**
     * Record a new transaction entry in the database.
     */
    public function create(
        Account $account,
        float $amount,
        float $balanceBefore,
        float $balanceAfter,
        string $type
    ): Transaction {
        return Transaction::create([
            'account_id'     => $account->id,
            'amount'         => $amount,
            'balance_before' => $balanceBefore,
            'balance_after'  => $balanceAfter,
            'type'           => $type,
        ]);
    }
}

