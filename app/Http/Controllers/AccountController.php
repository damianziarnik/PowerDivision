<?php

namespace App\Http\Controllers;

use App\Exceptions\AccountNotFoundException;
use App\Exceptions\InsufficientFundsException;
use App\Http\Requests\TransactionRequest;
use App\Services\AccountService;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\JsonResponse;

class AccountController extends Controller
{
    public function __construct(
        private readonly AccountService $accountService,
    ) {}

    /**
     * Process a credit (positive amount) or debit (negative amount) transaction
     * for the given user account.
     */
    public function transaction(TransactionRequest $request, int $userId): JsonResponse
    {
        try {
            $result = $this->accountService->processTransaction(
                $userId,
                (float) $request->validated('amount')
            );

            return new JsonResponse(['data' => $result], JsonResponse::HTTP_OK);

        } catch (AccountNotFoundException $e) {
            return new JsonResponse(
                ['error' => 'account_not_found', 'message' => $e->getMessage()],
                JsonResponse::HTTP_NOT_FOUND
            );

        } catch (InsufficientFundsException $e) {
            return new JsonResponse(
                ['error' => 'insufficient_funds', 'message' => $e->getMessage()],
                JsonResponse::HTTP_UNPROCESSABLE_ENTITY
            );

        } catch (LockTimeoutException) {
            return new JsonResponse(
                ['error' => 'lock_timeout', 'message' => 'Could not acquire account lock. Please try again.'],
                JsonResponse::HTTP_SERVICE_UNAVAILABLE
            );
        }
    }
}

