<?php

use App\Http\Controllers\AccountController;
use Illuminate\Support\Facades\Route;

Route::prefix('accounts')->group(function (): void {
    Route::post('{userId}/transactions', [AccountController::class, 'transaction'])
        ->where('userId', '[0-9]+');
});

