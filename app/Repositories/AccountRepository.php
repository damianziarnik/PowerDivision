<?php

namespace App\Repositories;

use App\Models\Account;

class AccountRepository
{
    /**
     * Find account by user ID or create a new one with zero balance.
     */
    public function findOrCreateByUserId(int $userId): Account
    {
        return Account::firstOrCreate(
            ['user_id' => $userId],
            ['balance' => 0.00]
        );
    }

    /**
     * Persist updated balance on the account instance.
     */
    public function updateBalance(Account $account, float $newBalance): void
    {
        $account->balance = $newBalance;
        $account->save();
    }
}

