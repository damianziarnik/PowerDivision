<?php

namespace App\Services;

use App\Exceptions\InsufficientFundsException;
use App\Models\Account;
use App\Repositories\AccountRepository;
use App\Repositories\TransactionRepository;
use Illuminate\Contracts\Cache\Factory as CacheFactory;

class AccountService
{
    /**
     * Redis lock TTL in seconds — generous enough to cover sleep(5) plus DB work.
     */
    private const LOCK_TTL = 30;

    /**
     * Maximum time to wait for the lock before giving up.
     */
    private const LOCK_WAIT = 120;

    public function __construct(
        private readonly AccountRepository     $accountRepository,
        private readonly TransactionRepository $transactionRepository,
        private readonly CacheFactory          $cache,
    ) {}

    /**
     * Process a debit or credit transaction for the given user.
     *
     * A Redis distributed lock ensures only one transaction runs
     * per account at a time, preventing negative balance race conditions.
     *
     * @throws InsufficientFundsException
     * @throws \Illuminate\Contracts\Cache\LockTimeoutException
     */
    public function processTransaction(int $userId, float $amount): array
    {
        $lock = $this->cache->store('redis')->lock(
            "account:lock:user:{$userId}",
            self::LOCK_TTL
        );

        return $lock->block(self::LOCK_WAIT, function () use ($userId, $amount): array {
            $account = $this->accountRepository->findOrCreateByUserId($userId);

            $balanceBefore = (float) $account->balance;
            $balanceAfter  = round($balanceBefore + $amount, 2);

            if ($balanceAfter < 0) {
                throw new InsufficientFundsException($balanceBefore, $amount);
            }

            // Simulate external payment gateway delay
            sleep(5);

            $type = $amount >= 0 ? 'credit' : 'debit';

            $transaction = $account->getConnection()->transaction(
                fn () => $this->persistTransaction($account, $amount, $balanceBefore, $balanceAfter, $type)
            );

            return $this->buildResult($account, $transaction, $balanceBefore, $balanceAfter, $amount, $type);
        });
    }

    /**
     * Update balance and record the transaction inside a DB transaction.
     */
    private function persistTransaction(
        Account $account,
        float   $amount,
        float   $balanceBefore,
        float   $balanceAfter,
        string  $type
    ): \App\Models\Transaction {
        $this->accountRepository->updateBalance($account, $balanceAfter);

        return $this->transactionRepository->create(
            $account,
            $amount,
            $balanceBefore,
            $balanceAfter,
            $type
        );
    }

    /**
     * Build the response array from processed transaction data.
     */
    private function buildResult(
        Account              $account,
        \App\Models\Transaction $transaction,
        float                $balanceBefore,
        float                $balanceAfter,
        float                $amount,
        string               $type
    ): array {
        return [
            'transaction_id' => $transaction->id,
            'account_id'     => $account->id,
            'user_id'        => $account->user_id,
            'type'           => $type,
            'amount'         => $amount,
            'balance_before' => $balanceBefore,
            'balance_after'  => $balanceAfter,
        ];
    }
}

