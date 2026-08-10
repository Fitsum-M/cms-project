<?php

use App\Http\Controllers\Auth\ActivateAccountController;
use App\Http\Controllers\Frontend\HomeController;
use App\Http\Controllers\Frontend\PageController;
use App\Http\Controllers\Frontend\PostController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('frontend.home');
Route::get('/posts/{slug}', [PostController::class, 'show'])->name('frontend.posts.show');
Route::get('/pages/{slug}', [PageController::class, 'show'])->name('frontend.pages.show');

Route::middleware('guest')->group(function (): void {
    Route::get('/activate/{token}', [ActivateAccountController::class, 'show'])
        ->name('activation.show');
    Route::post('/activate/{token}', [ActivateAccountController::class, 'store'])
        ->name('activation.store');
});
