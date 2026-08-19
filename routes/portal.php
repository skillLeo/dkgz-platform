<?php

use App\Http\Controllers\Portal\AssignmentController;
use App\Http\Controllers\Portal\DashboardController;
use App\Http\Controllers\Portal\NotificationController;
use App\Http\Controllers\Portal\ProfileController;
use App\Http\Controllers\Portal\RequestController;
use App\Http\Controllers\Portal\ServiceAreaController;
use Illuminate\Support\Facades\Route;

/*
 * The assessor portal. Every route sits behind auth + the approval gate, so a
 * partner who is not yet cleared is redirected to the screen that explains why
 * rather than hitting a bare 403.
 */
Route::prefix('portal')
    ->name('portal.')
    ->middleware(['auth', 'assessor.approved'])
    ->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        // Incoming requests
        Route::get('/anfragen', [RequestController::class, 'index'])->name('requests');
        Route::get('/anfragen/{serviceRequest}', [RequestController::class, 'show'])->name('requests.show');
        Route::post('/anfragen/{serviceRequest}/annehmen', [RequestController::class, 'accept'])->name('requests.accept');
        Route::post('/anfragen/{serviceRequest}/ablehnen', [RequestController::class, 'decline'])->name('requests.decline');
        Route::get('/abgelehnt', [RequestController::class, 'declined'])->name('requests.declined');

        // Assignments
        Route::get('/auftraege', [AssignmentController::class, 'index'])->name('assignments');
        Route::get('/auftraege/{assignment}', [AssignmentController::class, 'show'])->name('assignments.show');
        Route::post('/auftraege/{assignment}/status', [AssignmentController::class, 'updateStatus'])->name('assignments.status');
        Route::post('/auftraege/{assignment}/dokumente', [AssignmentController::class, 'storeDocument'])->name('assignments.documents.store');
        Route::delete('/auftraege/{assignment}/dokumente/{document}', [AssignmentController::class, 'destroyDocument'])->name('assignments.documents.destroy');
        Route::get('/auftraege/{assignment}/dokumente/{document}/download', [AssignmentController::class, 'downloadDocument'])->name('assignments.documents.download');
        Route::post('/auftraege/{assignment}/abschliessen', [AssignmentController::class, 'complete'])->name('assignments.complete');

        // Commissions, read-only for the partner
        Route::get('/provisionen', [ProfileController::class, 'commissions'])->name('commissions');

        // Service areas and services
        Route::get('/einsatzgebiet', [ServiceAreaController::class, 'index'])->name('service-areas');
        Route::post('/einsatzgebiet', [ServiceAreaController::class, 'store'])->name('service-areas.store');
        Route::delete('/einsatzgebiet/{area}', [ServiceAreaController::class, 'destroy'])->name('service-areas.destroy');
        Route::get('/leistungen', [ProfileController::class, 'services'])->name('services');
        Route::post('/leistungen', [ProfileController::class, 'updateServices'])->name('services.update');

        // Availability, profile, settings
        Route::post('/verfuegbarkeit', [ProfileController::class, 'availability'])->name('availability');
        Route::get('/profil', [ProfileController::class, 'edit'])->name('profile');
        Route::post('/profil', [ProfileController::class, 'update'])->name('profile.update');
        Route::get('/einstellungen', [ProfileController::class, 'settings'])->name('settings');
        Route::post('/einstellungen/passwort', [ProfileController::class, 'updatePassword'])->name('settings.password');

        // Notifications
        Route::get('/benachrichtigungen', [NotificationController::class, 'index'])->name('notifications');
        Route::post('/benachrichtigungen/gelesen', [NotificationController::class, 'markRead'])->name('notifications.read');
    });

// Polling endpoint: outside the approval gate so a pending partner's shell
// still works, but always behind auth.
Route::get('/api/notifications/poll', [NotificationController::class, 'poll'])
    ->middleware(['auth', 'throttle:polling'])
    ->name('notifications.poll');
