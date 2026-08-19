<?php

use App\Http\Controllers\Portal\DashboardController;
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

        Route::get('/einsatzgebiet', [ServiceAreaController::class, 'index'])->name('service-areas');
        Route::post('/einsatzgebiet', [ServiceAreaController::class, 'store'])->name('service-areas.store');
        Route::delete('/einsatzgebiet/{area}', [ServiceAreaController::class, 'destroy'])->name('service-areas.destroy');
    });
