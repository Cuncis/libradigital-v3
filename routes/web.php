<?php

use App\Http\Controllers\MayarWebhookController;
use App\Http\Controllers\SubscriptionController;
use App\UserRole;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect(auth()->user()->role === UserRole::Admin ? '/admin' : '/user');
    }

    return view('welcome');
});

Route::post('/subscribe/{plan}', [SubscriptionController::class, 'checkout'])
    ->middleware('auth')
    ->name('subscribe');

Route::post('/webhooks/mayar', [MayarWebhookController::class, 'handle'])
    ->name('webhooks.mayar');
