<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailLog;
use App\Models\Setting;
use App\Support\SafeStorage;
use App\Support\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;

class SystemController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Setting::class);

        return Inertia::render('Admin/System', [
            'health' => [
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
                'environment' => app()->environment(),
                'debug' => config('app.debug'),
                'database' => config('database.default'),
                'queue' => config('queue.default'),
                'cache' => config('cache.default'),
                'session' => config('session.driver'),
                'storage_link' => SafeStorage::symlinkWorks(),
                'smtp_configured' => filled(Settings::get('integrations.smtp_host')),
                'maintenance' => Settings::bool('features.maintenance_mode'),
            ],
            'queue' => [
                'pending' => Schema::hasTable('jobs') ? DB::table('jobs')->count() : 0,
                'failed' => Schema::hasTable('failed_jobs') ? DB::table('failed_jobs')->count() : 0,
                'oldest_pending' => Schema::hasTable('jobs')
                    ? optional(DB::table('jobs')->orderBy('created_at')->first())->created_at
                    : null,
            ],
            'emails' => [
                'queued' => EmailLog::where('status', EmailLog::STATUS_QUEUED)->count(),
                'sent_today' => EmailLog::where('status', EmailLog::STATUS_SENT)
                    ->whereDate('sent_at', today())->count(),
                'failed' => EmailLog::failed()->count(),
                'recent_failures' => EmailLog::failed()->latest()->limit(10)->get()
                    ->map(fn (EmailLog $log) => [
                        'id' => $log->id,
                        'template_key' => $log->template_key,
                        'recipient' => $log->recipient,
                        'error' => $log->error,
                        'created_at' => $log->created_at,
                    ]),
            ],
            'storage' => [
                'free_bytes' => @disk_free_space(base_path()) ?: null,
                'total_bytes' => @disk_total_space(base_path()) ?: null,
            ],
        ]);
    }

    public function activityLog(Request $request): Response
    {
        $this->authorize('logs.view');

        $activities = Activity::query()
            ->with('causer')
            ->when($request->filled('suche'), fn ($q) => $q->where('description', 'like', '%'.$request->string('suche')->toString().'%'))
            ->when($request->filled('protokoll'), fn ($q) => $q->where('log_name', $request->string('protokoll')))
            ->when($request->filled('benutzer'), fn ($q) => $q->where('causer_id', $request->integer('benutzer')))
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return Inertia::render('Admin/Protokoll', [
            'activities' => $activities->through(fn (Activity $a) => [
                'id' => $a->id,
                'log_name' => $a->log_name,
                'description' => $a->description,
                'subject_type' => $a->subject_type ? class_basename($a->subject_type) : null,
                'subject_id' => $a->subject_id,
                'causer' => $a->causer?->fullName(),
                'changes' => $a->properties,
                'created_at' => $a->created_at,
            ]),
            'filters' => $request->only(['suche', 'protokoll', 'benutzer']),
            'logNames' => Activity::query()->distinct()->pluck('log_name')->filter()->values(),
        ]);
    }

    public function retryFailedJobs(Request $request): RedirectResponse
    {
        $this->authorize('settings.edit');

        Artisan::call('queue:retry', ['id' => ['all']]);

        return back()->with('success', 'Fehlgeschlagene Aufträge wurden erneut eingereiht.');
    }
}
