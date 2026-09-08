<?php

use App\Http\Controllers\CustomRequestBuildController;
use App\Http\Controllers\CustomRequestController;
use App\Http\Controllers\InvitationPageController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\MayarWebhookController;
use App\Http\Controllers\RsvpExportController;
use App\Http\Controllers\RsvpSubmissionController;
use App\Http\Controllers\SubscriptionController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LandingController::class, 'index'])->name('landing');

Route::post('/custom-requests', [CustomRequestController::class, 'store'])
    ->name('custom-requests.store');

Route::post('/subscribe/{plan}', [SubscriptionController::class, 'checkout'])
    ->middleware('auth')
    ->name('subscribe');

Route::get('/user/rsvps/export', [RsvpExportController::class, 'export'])
    ->middleware('auth')
    ->name('rsvps.export');

Route::post('/webhooks/mayar', [MayarWebhookController::class, 'handle'])
    ->name('webhooks.mayar');

Route::get('/admin/custom-requests/{customRequest}/build', CustomRequestBuildController::class)
    ->middleware('auth')
    ->name('admin.custom-requests.build');

Route::get('/i/{slug}', InvitationPageController::class)
    ->name('invitations.show');

Route::post('/i/{invitation:slug}/rsvp', [RsvpSubmissionController::class, 'store'])
    ->name('rsvps.store');
