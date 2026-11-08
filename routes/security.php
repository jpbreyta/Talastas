<?php

use App\Http\Controllers\AccountSecurityController as Security;
use App\Http\Controllers\InvitationManagementController as Invitations;
use Illuminate\Support\Facades\Route;

// Numeric throttle middleware otherwise shares a user/IP counter across every route.
// Aggregate and action budgets must have different prefixes, especially when nested.
Route::middleware('throttle:30,1,security-public:')->group(function () {
    Route::get('/invitations/{id}', [Security::class, 'invitation'])->name('security.invitation');
    Route::post('/invitations/{id}/exchange', [Security::class, 'exchange'])->middleware('throttle:10,1,invitation-exchange:')->name('security.invitation.exchange');
    Route::post('/invitations/{id}/accept', [Security::class, 'accept'])->middleware('throttle:5,1,invitation-accept:')->name('security.invitation.accept');
    Route::get('/forgot-password', [Security::class, 'recovery'])->name('security.recovery');
    Route::post('/forgot-password', [Security::class, 'requestCode'])->middleware('throttle:5,1,recovery-request:')->name('security.recovery.request');
    Route::post('/forgot-password/verify', [Security::class, 'verifyCode'])->middleware('throttle:10,1,recovery-verify:')->name('security.recovery.verify');
    Route::post('/forgot-password/reset', [Security::class, 'resetPassword'])->middleware('throttle:5,1,recovery-reset:')->name('security.recovery.reset');
});
Route::middleware(['auth:web', 'auth.session', 'account.secure'])->group(function () {
    Route::get('/security/mfa', [Security::class, 'mfa'])->name('security.mfa');
    Route::post('/security/mfa', [Security::class, 'verifyMfa'])->middleware('throttle:5,1,mfa-verify:')->name('security.mfa.verify');
    Route::post('/security/mfa/send', [Security::class, 'sendMfaCode'])->middleware('throttle:3,1,mfa-send:')->name('security.mfa.send');
    Route::post('/security/mfa/devices/revoke-all', [Security::class, 'revokeDevices'])->middleware(['throttle:5,1,mfa-devices-revoke-all:', 'mfa.fresh'])->name('security.mfa.devices.revoke-all');
    Route::post('/security/mfa/devices/{device}/revoke', [Security::class, 'revokeDevice'])->middleware(['throttle:5,1,mfa-devices-revoke:', 'mfa.fresh'])->name('security.mfa.devices.revoke');
});
Route::middleware(['auth:web', 'auth.session', 'account.secure', 'terms.accepted', 'rbac:admin', 'throttle:20,1,admin-account:'])->group(function () {
    Route::get('/admin/invitations', [Invitations::class, 'index'])->name('admin.invitations.index');
    Route::post('/admin/invitations', [Invitations::class, 'store'])->middleware('mfa.fresh')->name('admin.invitations.store');
    Route::post('/admin/invitations/{id}/resend', [Invitations::class, 'resend'])->middleware('mfa.fresh')->name('admin.invitations.resend');
    Route::post('/admin/invitations/{id}/revoke', [Invitations::class, 'revoke'])->middleware('mfa.fresh')->name('admin.invitations.revoke');
    Route::post('/admin/users/{user}/reset', [Invitations::class, 'reset'])->middleware('mfa.fresh')->name('admin.users.reset');
    Route::post('/admin/users/{user}/status', [Invitations::class, 'status'])->middleware('mfa.fresh')->name('admin.users.status');
    Route::post('/admin/users/{user}/trusted-devices/revoke', [Security::class, 'revokeUserDevices'])->middleware('mfa.fresh')->name('admin.users.trusted-devices.revoke');
});
