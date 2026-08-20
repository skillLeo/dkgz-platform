<?php

use App\Models\Assessor;
use App\Models\Assignment;
use App\Models\Commission;
use App\Models\ContentBlock;
use App\Models\EmailTemplate;
use App\Models\Page;
use App\Models\ServiceRequest;
use App\Models\ServiceType;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Database\Seeders\ProductionSeeder;
use Illuminate\Support\Facades\Route;

/**
 * Requests every GET route in the application, detail pages included, and fails
 * on any 5xx.
 *
 * The props contract test covers parameterless routes; this reaches the ones
 * that need a record, which is where a missing eager load or a null relation
 * actually shows up.
 */
it('serves every GET route without a server error', function () {
    $this->seed(ProductionSeeder::class);
    $this->seed(DemoSeeder::class);

    $admin = User::factory()->create([
        'is_active' => true,
        'email_verified_at' => now(),
        'must_change_password' => false,
    ]);
    $admin->assignRole('super_admin');

    $ids = [
        'serviceRequest' => ServiceRequest::value('id'),
        'assignment' => Assignment::value('id'),
        'assessor' => Assessor::value('id'),
        'commission' => Commission::value('id'),
        'page' => Page::value('id'),
        'serviceType' => ServiceType::value('id'),
        'contentBlock' => ContentBlock::value('id'),
        'emailTemplate' => EmailTemplate::value('id'),
        'user' => $admin->id,
        'group' => 'business',
        'pageKey' => 'startseite',
        'slug' => Page::value('slug'),
    ];

    $failures = [];
    $checked = 0;

    foreach (Route::getRoutes() as $route) {
        if (! in_array('GET', $route->methods(), true)) {
            continue;
        }

        $uri = $route->uri();

        // Downloads stream from disk and exports build files; both are covered
        // by their own tests and need fixtures this sweep does not create.
        if (str_starts_with($uri, '_') || str_contains($uri, 'download')
            || str_contains($uri, 'export') || str_contains($uri, 'rechnung')) {
            continue;
        }

        $resolved = preg_replace_callback(
            '/\{(\w+)\??\}/',
            fn (array $m) => (string) ($ids[$m[1]] ?? '1'),
            $uri
        );

        if (str_contains($resolved, '{')) {
            continue;
        }

        $status = $this->actingAs($admin)->get('/'.ltrim($resolved, '/'))->status();
        $checked++;

        if ($status >= 500) {
            $failures[] = "/{$resolved} → {$status}";
        }
    }

    expect($checked)->toBeGreaterThan(40, 'Es wurden zu wenige Routen geprüft.');
    expect($failures)->toBe([]);
});
