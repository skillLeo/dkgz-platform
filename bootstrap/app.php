<?php

use App\Http\Middleware\EnsureAssessorIsApproved;
use App\Http\Middleware\EnsureMaintenanceMode;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\RequirePasswordChange;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetLocale;
use App\Support\Content;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        then: function () {
            Route::middleware('web')
                ->group(base_path('routes/auth.php'));

            Route::middleware('web')
                ->group(base_path('routes/portal.php'));

            Route::middleware('web')
                ->group(base_path('routes/admin.php'));
        },
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            SetLocale::class,
            SecurityHeaders::class,
            HandleInertiaRequests::class,
            RequirePasswordChange::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'assessor.approved' => EnsureAssessorIsApproved::class,
            'maintenance' => EnsureMaintenanceMode::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Error pages render in the design language rather than the framework's
        // default, with copy the admin can edit like any other page text.
        $exceptions->respond(function (Response $response, Throwable $exception, Request $request) {
            if ($request->expectsJson() || app()->environment('local', 'testing')) {
                return $response;
            }

            if (! in_array($response->getStatusCode(), [404, 419, 500, 503], true)) {
                return $response;
            }

            return Inertia::render('Fehler/Fehlerseite', [
                'status' => $response->getStatusCode(),
                'content' => Content::page('fehler'),
            ])->toResponse($request)->setStatusCode($response->getStatusCode());
        });
    })->create();
