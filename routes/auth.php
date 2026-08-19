<?php

use App\Http\Controllers\Auth\AccountStatusController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\InvitationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegistrationController;
use App\Http\Controllers\Auth\TwoFactorController;
use Illuminate\Support\Facades\Route;

/*
 * German URLs throughout — the interface has no English surface, and that
 * includes the address bar.
 */

Route::middleware('guest')->group(function () {
    Route::get('/anmelden', [LoginController::class, 'show'])->name('login');
    Route::post('/anmelden', [LoginController::class, 'store'])
        ->middleware('throttle:login')
        ->name('login.store');

    Route::get('/admin/anmelden', [LoginController::class, 'showAdmin'])->name('admin.login');
    Route::post('/admin/anmelden', [LoginController::class, 'store'])
        ->middleware('throttle:login')
        ->name('admin.login.store');

    Route::get('/admin/zwei-faktor', [TwoFactorController::class, 'challenge'])->name('two-factor.challenge');
    Route::post('/admin/zwei-faktor', [TwoFactorController::class, 'verify'])
        ->middleware('throttle:login')
        ->name('two-factor.verify');

    Route::get('/passwort-vergessen', [PasswordResetController::class, 'request'])->name('password.request');
    Route::post('/passwort-vergessen', [PasswordResetController::class, 'send'])
        ->middleware('throttle:passwort-reset')
        ->name('password.email');

    Route::get('/passwort-zuruecksetzen/{token}', [PasswordResetController::class, 'reset'])->name('password.reset');
    Route::post('/passwort-zuruecksetzen', [PasswordResetController::class, 'update'])
        ->middleware('throttle:passwort-reset')
        ->name('password.update');

    Route::get('/registrieren', [RegistrationController::class, 'show'])->name('register');
    Route::post('/registrieren', [RegistrationController::class, 'store'])->name('register.store');
    Route::post('/registrieren/schritt/{step}', [RegistrationController::class, 'saveStep'])
        ->whereNumber('step')
        ->name('register.step');

    Route::get('/einladung/{token}', [InvitationController::class, 'show'])->name('invitation.show');
    Route::post('/einladung/{token}', [InvitationController::class, 'accept'])->name('invitation.accept');
});

Route::middleware('auth')->group(function () {
    Route::post('/abmelden', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/email-bestaetigen', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/email-bestaetigen/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');
    Route::post('/email-bestaetigen/erneut', [EmailVerificationController::class, 'resend'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('/pruefung-laeuft', [AccountStatusController::class, 'pending'])->name('registration.pending');
    Route::get('/registrierung-abgelehnt', [AccountStatusController::class, 'rejected'])->name('registration.rejected');
    Route::get('/konto-gesperrt', [AccountStatusController::class, 'blocked'])->name('account.blocked');
});
