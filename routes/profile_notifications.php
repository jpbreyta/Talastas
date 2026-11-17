<?php

use App\Http\Controllers\AccountProfileController;
use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:web', 'auth.session', 'account.secure', 'terms.accepted'])->group(function (): void {
    Route::get('/profile', [AccountProfileController::class, 'show'])
        ->name('profile.account');

    Route::put('/profile/name', [AccountProfileController::class, 'updateName'])
        ->middleware(['throttle:5,1,profile-name:', 'mfa.fresh'])->name('profile.name.update');
    Route::post('/profile/email-change', [AccountProfileController::class, 'requestEmailChange'])
        ->middleware(['throttle:5,60,profile-email-request:', 'mfa.fresh'])->name('profile.email.request');
    Route::post('/profile/email-change/confirm', [AccountProfileController::class, 'confirmEmailChange'])
        ->middleware(['throttle:10,1,profile-email-confirm:', 'mfa.fresh'])->name('profile.email.confirm');

    Route::put('/profile/password', [AccountProfileController::class, 'updatePassword'])
        ->middleware(['throttle:5,1,profile-password:', 'mfa.fresh'])
        ->name('profile.password.update');

    Route::get('/notifications', [NotificationController::class, 'index'])
        ->name('notifications.index');

    Route::get('/notifications/recent', [NotificationController::class, 'recent'])
        ->middleware('throttle:120,1,notifications-recent:')
        ->name('notifications.recent');

    Route::get('/notifications/stream', [NotificationController::class, 'stream'])
        ->middleware('throttle:30,1,notifications-stream:')
        ->name('notifications.stream');

    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount'])
        ->middleware('throttle:60,1,notifications-unread:')
        ->name('notifications.unread-count');

    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])
        ->name('notifications.read');

    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])
        ->name('notifications.read-all');

    Route::delete('/notifications/read', [NotificationController::class, 'destroyRead'])
        ->name('notifications.destroy-read');
});
