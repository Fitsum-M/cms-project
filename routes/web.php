<?php

use App\Http\Controllers\Auth\ActivateAccountController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin');

Route::middleware('guest')->group(function (): void {
    Route::get('/activate/{token}', [ActivateAccountController::class, 'show'])
        ->name('activation.show');
    Route::post('/activate/{token}', [ActivateAccountController::class, 'store'])
        ->name('activation.store');
});
