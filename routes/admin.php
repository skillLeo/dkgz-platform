<?php

use App\Http\Controllers\Admin\AssessorController;
use App\Http\Controllers\Admin\AssignmentController;
use App\Http\Controllers\Admin\CityController;
use App\Http\Controllers\Admin\CommissionController;
use App\Http\Controllers\Admin\ContentController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EmailTemplateController;
use App\Http\Controllers\Admin\InvitationController;
use App\Http\Controllers\Admin\PartnerMailController;
use App\Http\Controllers\Admin\PostController;
use App\Http\Controllers\Admin\ProfileController as AdminProfileController;
use App\Http\Controllers\Admin\RequestController;
use App\Http\Controllers\Admin\SeoController;
use App\Http\Controllers\Admin\ServiceTypeController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\SystemController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

/*
 * The admin panel. Gated per route by permission, never by role name — roles
 * are only bundles of permissions and an admin may recompose them freely.
 * Policies enforce the same rules again at the model level.
 */
Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth'])
    ->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        // Requests, including the full matching trail
        Route::middleware('can:requests.view')->group(function () {
            Route::get('/anfragen', [RequestController::class, 'index'])->name('requests');
            Route::get('/in-vermittlung', [RequestController::class, 'inPlacement'])->name('requests.in-placement');
            Route::get('/anfragen/neu', [RequestController::class, 'create'])
                ->middleware('can:requests.create')->name('requests.create');
            Route::get('/anfragen/{serviceRequest}', [RequestController::class, 'show'])->name('requests.show');
        });
        Route::post('/anfragen', [RequestController::class, 'store'])
            ->middleware('can:requests.create')->name('requests.store');
        Route::middleware('can:requests.view')->group(function () {
            Route::post('/anfragen/{serviceRequest}/externer-sachverstaendiger', [RequestController::class, 'offerExternally'])
                ->name('requests.offer');
            // Two models in one address, and naming a key on the second makes
            // Laravel look for it through the first. There is no such relation
            // and no such intent: the assessor here is any assessor, addressed
            // by id because the admin panel holds ids and the model itself
            // prefers the slug its public profile is built from.
            Route::post('/anfragen/{serviceRequest}/senden/{assessor:id}', [RequestController::class, 'notifyAssessor'])
                ->withoutScopedBindings()
                ->middleware('can:requests.reassign')->name('requests.notify-assessor');
            Route::post('/anfragen/{serviceRequest}/kunde-benachrichtigen', [RequestController::class, 'notifyCustomer'])
                ->name('requests.notify-customer');
            Route::post('/anfragen/{serviceRequest}/schliessen', [RequestController::class, 'close'])
                ->name('requests.close');
        });
        Route::post('/anfragen/{serviceRequest}/erneut-vermitteln', [RequestController::class, 'rematch'])
            ->middleware('can:requests.reassign')->name('requests.rematch');
        Route::get('/anfragen-export', [RequestController::class, 'export'])
            ->middleware('can:requests.export')->name('requests.export');

        // Assignments
        Route::middleware('can:assignments.view')->group(function () {
            Route::get('/auftraege', [AssignmentController::class, 'index'])->name('assignments');
            Route::get('/auftraege/{assignment}', [AssignmentController::class, 'show'])->name('assignments.show');
            Route::get('/auftraege/{assignment}/dokumente/{document}/download', [AssignmentController::class, 'downloadDocument'])
                ->name('assignments.documents.download');
        });
        Route::post('/auftraege/{assignment}/stornieren', [AssignmentController::class, 'cancel'])
            ->middleware('can:assignments.cancel')->name('assignments.cancel');
        Route::get('/auftraege-export', [AssignmentController::class, 'export'])
            ->middleware('can:assignments.export')->name('assignments.export');

        // Assessors
        Route::middleware('can:assessors.view')->group(function () {
            Route::get('/sachverstaendige', [AssessorController::class, 'index'])->name('assessors');
            Route::get('/sachverstaendige/{assessor:id}/nachweise/{document}', [AssessorController::class, 'downloadDocument'])
                ->name('assessors.documents.download');
            Route::get('/sachverstaendige/{assessor:id}', [AssessorController::class, 'show'])->name('assessors.show');
        });
        Route::post('/sachverstaendige/{assessor:id}/freigeben', [AssessorController::class, 'approve'])
            ->middleware('can:assessors.approve')->name('assessors.approve');
        Route::post('/sachverstaendige/{assessor:id}/ablehnen', [AssessorController::class, 'reject'])
            ->middleware('can:assessors.reject')->name('assessors.reject');
        Route::post('/sachverstaendige/{assessor:id}/sperren', [AssessorController::class, 'suspend'])
            ->middleware('can:assessors.suspend')->name('assessors.suspend');
        Route::post('/sachverstaendige/{assessor:id}/entsperren', [AssessorController::class, 'unsuspend'])
            ->middleware('can:assessors.suspend')->name('assessors.unsuspend');
        // Bound by id throughout, not by the slug the model prefers for its
        // public profile. The admin panel holds the row's id and posts the id,
        // and a partner renaming their firm must not take every admin action
        // on them with it.
        Route::post('/sachverstaendige/{assessor:id}', [AssessorController::class, 'update'])
            ->middleware('can:assessors.edit')->name('assessors.update');
        Route::get('/sachverstaendige-export', [AssessorController::class, 'export'])
            ->middleware('can:assessors.export')->name('assessors.export');

        // Invitations
        Route::get('/einladungen', [InvitationController::class, 'index'])
            ->middleware('can:invitations.view')->name('invitations');
        Route::post('/einladungen', [InvitationController::class, 'store'])
            ->middleware('can:invitations.create')->name('invitations.store');
        Route::post('/einladungen/import-vorschau', [InvitationController::class, 'preview'])
            ->middleware('can:invitations.create')->name('invitations.preview');
        Route::post('/einladungen/import', [InvitationController::class, 'importSend'])
            ->middleware('can:invitations.create')->name('invitations.import');
        Route::post('/einladungen/{invitation}/erneut', [InvitationController::class, 'resend'])
            ->middleware('can:invitations.create')->name('invitations.resend');
        Route::delete('/einladungen/{invitation}', [InvitationController::class, 'revoke'])
            ->middleware('can:invitations.revoke')->name('invitations.revoke');

        // Commissions
        Route::middleware('can:commissions.view')->group(function () {
            Route::get('/provisionen', [CommissionController::class, 'index'])->name('commissions');
            Route::get('/provisionen/{commission}', [CommissionController::class, 'show'])->name('commissions.show');
            Route::get('/provisionen/{commission}/rechnung', [CommissionController::class, 'downloadInvoice'])->name('commissions.invoice.download');
        });
        Route::post('/provisionen/{commission}/rechnung', [CommissionController::class, 'invoice'])
            ->middleware('can:commissions.invoice')->name('commissions.invoice');
        Route::post('/provisionen/{commission}/beglichen', [CommissionController::class, 'settle'])
            ->middleware('can:commissions.settle')->name('commissions.settle');
        Route::post('/provisionen/{commission}/erlassen', [CommissionController::class, 'waive'])
            ->middleware('can:commissions.waive')->name('commissions.waive');
        Route::get('/provisionen-export', [CommissionController::class, 'export'])
            ->middleware('can:commissions.export')->name('commissions.export');

        // Service types
        Route::middleware('can:servicetypes.manage')->group(function () {
            Route::get('/leistungsarten', [ServiceTypeController::class, 'index'])->name('service-types');

            // Städte und ihre Regionalseiten
            // Rundmail an Partner
            Route::middleware('can:assessors.view')->group(function () {
                Route::get('/partnermail', [PartnerMailController::class, 'index'])->name('partner-mail');
                Route::post('/partnermail', [PartnerMailController::class, 'send'])->name('partner-mail.send');
            });

            // Bound by id, not by the slug the model prefers for public URLs.
            // The admin panel holds the row's id and posts the id, so binding
            // by slug meant every save and every delete came back 404 — and
            // renaming a city changes its slug, so even matching them up would
            // have broken on the one action most likely to be taken.
            Route::middleware('can:cities.manage')->group(function () {
                Route::get('/staedte', [CityController::class, 'index'])->name('cities');
                Route::post('/staedte', [CityController::class, 'store'])->name('cities.store');
                Route::post('/staedte/{city:id}', [CityController::class, 'update'])->name('cities.update');
                Route::delete('/staedte/{city:id}', [CityController::class, 'destroy'])->name('cities.destroy');
            });
            Route::post('/leistungsarten', [ServiceTypeController::class, 'store'])->name('service-types.store');
            Route::post('/leistungsarten/reihenfolge', [ServiceTypeController::class, 'reorder'])->name('service-types.reorder');
            // Bound by id, not by the slug the model prefers: the public URL
            // wants a readable slug, but the slug is rebuilt from the name on
            // every save, so an admin renaming a service would be editing a row
            // that no longer answers to the key they arrived with. Posting the
            // id at a route expecting a slug is why saving returned 404.
            Route::post('/leistungsarten/{serviceType:id}', [ServiceTypeController::class, 'update'])->name('service-types.update');
            Route::delete('/leistungsarten/{serviceType:id}', [ServiceTypeController::class, 'destroy'])->name('service-types.destroy');
        });

        // Content, pages, FAQ
        Route::middleware('can:content.view')->group(function () {
            Route::get('/inhalte/{pageKey?}', [ContentController::class, 'index'])->name('content');
        });
        Route::post('/inhalte/{pageKey}', [ContentController::class, 'update'])
            ->middleware('can:content.edit')->name('content.update');
        Route::delete('/inhalte-bild/{contentBlock}', [ContentController::class, 'destroyImage'])
            ->name('content.image.destroy');
        Route::post('/inhalte-bild/{contentBlock}', [ContentController::class, 'uploadImage'])
            ->middleware('can:content.edit')->name('content.image');

        Route::middleware('can:pages.manage')->group(function () {
            Route::get('/seiten', [ContentController::class, 'pages'])->name('pages');
            Route::get('/seiten/{page}', [ContentController::class, 'editPage'])->name('pages.edit');
            Route::post('/seiten/{page}', [ContentController::class, 'updatePage'])->name('pages.update');
        });

        // Bound by id, not by the slug the model prefers: the public address
        // follows the title, and renaming a draft must not take the form's own
        // URL with it mid-edit.
        // Reads and writes the wording where it already lives, so it needs
        // only the ordinary content permissions.
        Route::middleware('can:content.view')->group(function () {
            Route::get('/seo', [SeoController::class, 'index'])->name('seo');
        });
        Route::post('/seo', [SeoController::class, 'update'])
            ->middleware('can:content.edit')->name('seo.update');

        Route::middleware('can:posts.manage')->group(function () {
            Route::get('/ratgeber', [PostController::class, 'index'])->name('posts');
            Route::post('/ratgeber', [PostController::class, 'store'])->name('posts.store');
            Route::post('/ratgeber/{post:id}', [PostController::class, 'update'])->name('posts.update');
            Route::delete('/ratgeber/{post:id}/bild', [PostController::class, 'destroyCover'])->name('posts.cover.destroy');
            Route::delete('/ratgeber/{post:id}', [PostController::class, 'destroy'])->name('posts.destroy');
        });

        Route::middleware('can:faqs.manage')->group(function () {
            Route::get('/faq', [ContentController::class, 'faqs'])->name('faqs');
            Route::post('/faq', [ContentController::class, 'storeFaq'])->name('faqs.store');
            Route::post('/faq/reihenfolge', [ContentController::class, 'reorderFaqs'])->name('faqs.reorder');
            Route::post('/faq/{faq}', [ContentController::class, 'updateFaq'])->name('faqs.update');
            Route::delete('/faq/{faq}', [ContentController::class, 'destroyFaq'])->name('faqs.destroy');
        });

        // Email templates
        Route::middleware('can:emails.view')->group(function () {
            Route::get('/email-vorlagen', [EmailTemplateController::class, 'index'])->name('emails');
            Route::get('/email-vorlagen/{emailTemplate}', [EmailTemplateController::class, 'edit'])->name('emails.edit');
            Route::get('/email-vorlagen/{emailTemplate}/vorschau', [EmailTemplateController::class, 'preview'])->name('emails.preview');
        });
        Route::post('/email-vorlagen/{emailTemplate}', [EmailTemplateController::class, 'update'])
            ->middleware('can:emails.edit')->name('emails.update');
        Route::post('/email-vorlagen/{emailTemplate}/test', [EmailTemplateController::class, 'test'])
            ->middleware(['can:emails.test', 'throttle:smtp-test'])->name('emails.test');

        // Settings, branding, integrations
        Route::get('/branding', [SettingsController::class, 'index'])->defaults('group', 'branding')
            ->middleware('can:branding.edit')->name('branding');
        Route::get('/integrationen', [SettingsController::class, 'index'])->defaults('group', 'integrations')
            ->middleware('can:integrations.manage')->name('integrations');
        Route::post('/e-mail-zustellbarkeit/pruefen', [SettingsController::class, 'checkMailDomain'])
            ->name('settings.mail-domain.check');
        Route::post('/integrationen/smtp-test', [SettingsController::class, 'testMail'])
            ->middleware(['can:integrations.manage', 'throttle:smtp-test'])->name('integrations.smtp-test');
        // The staff member's own account — reachable by every signed-in user, since
        // changing your own password must never depend on a permission.
        Route::get('/profil', [AdminProfileController::class, 'edit'])->name('profile');
        Route::post('/profil', [AdminProfileController::class, 'update'])->name('profile.update');
        Route::post('/profil/passwort', [AdminProfileController::class, 'updatePassword'])->name('profile.password');

        Route::get('/einstellungen/{group?}', [SettingsController::class, 'index'])
            ->middleware('can:settings.view')->name('settings');
        Route::post('/einstellungen/{group}', [SettingsController::class, 'update'])
            ->middleware('can:settings.view')->name('settings.update');
        Route::delete('/einstellungen/{group}/datei/{field}', [SettingsController::class, 'destroyFile'])
            ->middleware('can:settings.view')->name('settings.file.destroy');

        // Users and roles
        Route::middleware('can:users.view')->group(function () {
            Route::get('/benutzer', [UserController::class, 'index'])->name('users');
        });
        Route::post('/benutzer', [UserController::class, 'store'])->middleware('can:users.create')->name('users.store');
        Route::post('/benutzer/{user}', [UserController::class, 'update'])->middleware('can:users.edit')->name('users.update');
        Route::delete('/benutzer/{user}', [UserController::class, 'destroy'])->middleware('can:users.delete')->name('users.destroy');

        Route::get('/rollen', [UserController::class, 'roles'])->middleware('can:roles.view')->name('roles');
        Route::middleware('can:roles.manage')->group(function () {
            Route::post('/rollen', [UserController::class, 'storeRole'])->name('roles.store');
            Route::post('/rollen/abgleichen', [UserController::class, 'syncPermissions'])->name('roles.sync');
            Route::post('/rollen/{role}', [UserController::class, 'updateRole'])->name('roles.update');
            Route::delete('/rollen/{role}', [UserController::class, 'destroyRole'])->name('roles.destroy');
        });

        // Log and system
        Route::get('/protokoll', [SystemController::class, 'activityLog'])->middleware('can:logs.view')->name('activity-log');
        Route::get('/system', [SystemController::class, 'index'])->middleware('can:settings.view')->name('system');
        Route::post('/system/jobs-wiederholen', [SystemController::class, 'retryFailedJobs'])
            ->middleware('can:settings.edit')->name('system.retry-jobs');
        Route::post('/system/warteschlange', [SystemController::class, 'runQueue'])
            ->middleware('can:settings.edit')->name('system.queue');
    });
