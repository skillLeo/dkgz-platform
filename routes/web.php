<?php

use App\Http\Controllers\ContactController;
use App\Http\Controllers\LegalPageController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\RequestController;
use App\Http\Controllers\RequestOfferController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

/*
 * Public site. German URLs throughout — the interface has no English surface,
 * and that includes the address bar.
 */
Route::middleware('maintenance')->group(function () {
    Route::get('/', [PublicController::class, 'home'])->name('home');
    Route::get('/leistungen', [PublicController::class, 'services'])->name('services');
    Route::get('/leistungen/{serviceType}', [PublicController::class, 'service'])->name('services.show');
    Route::get('/ablauf', [PublicController::class, 'process'])->name('process');
    Route::get('/ueber-uns', [PublicController::class, 'about'])->name('about');
    Route::get('/fuer-sachverstaendige', [PublicController::class, 'partner'])->name('partner');

    Route::get('/kontakt', [ContactController::class, 'show'])->name('contact');
    Route::post('/kontakt', [ContactController::class, 'store'])
        ->middleware('throttle:kontakt')
        ->name('contact.store');

    Route::get('/anfrage', [RequestController::class, 'create'])->name('request.create');
    Route::post('/anfrage', [RequestController::class, 'store'])
        ->middleware('throttle:anfrage')
        ->name('request.store');
    Route::get('/anfrage/bestaetigung/{reference}', [RequestController::class, 'confirmation'])
        ->name('request.confirmation');

    Route::get('/bewertung/{token}', [ReviewController::class, 'show'])->name('review.show');
    Route::post('/bewertung/{token}', [ReviewController::class, 'store'])->name('review.store');
    Route::post('/bewertung/{token}/feedback', [ReviewController::class, 'feedback'])->name('review.feedback');
    Route::get('/bewertung/{token}/danke', [ReviewController::class, 'thanks'])->name('review.thanks');

    Route::get('/impressum', [LegalPageController::class, 'show'])->defaults('slug', 'impressum')->name('legal.imprint');
    Route::get('/datenschutz', [LegalPageController::class, 'show'])->defaults('slug', 'datenschutz')->name('legal.privacy');
    Route::get('/agb', [LegalPageController::class, 'show'])->defaults('slug', 'agb')->name('legal.terms');
    Route::get('/widerruf', [LegalPageController::class, 'show'])->defaults('slug', 'widerruf')->name('legal.withdrawal');
});

Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
Route::get('/robots.txt', [SitemapController::class, 'robots'])->name('robots');

Route::get('/api/plz/{code}', [PublicController::class, 'resolvePostalCode'])
    ->whereNumber('code')
    ->name('api.plz');

// Public branding assets when the storage symlink is unavailable on the host.
Route::get('/medien/{path}', [PublicController::class, 'media'])
    ->where('path', '.*')
    ->name('media.show');

/*
 * A request offered by hand to somebody who has no account yet. Public by
 * necessity — the recipient cannot log in — and guarded by the token alone,
 * which is why the page carries no customer contact data.
 */
Route::get('/auftrag-angebot/{token}', [RequestOfferController::class, 'show'])->name('offer.show');
Route::post('/auftrag-angebot/{token}/annehmen', [RequestOfferController::class, 'accept'])->name('offer.accept');
Route::post('/auftrag-angebot/{token}/ablehnen', [RequestOfferController::class, 'decline'])->name('offer.decline');
