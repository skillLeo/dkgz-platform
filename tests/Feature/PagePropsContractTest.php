<?php

use App\Models\Assessor;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Database\Seeders\ProductionSeeder;
use Illuminate\Support\Facades\Route;

/**
 * Every GET route is requested for real, and the props it returns are checked
 * against what its Vue component declares required.
 *
 * The render harness proves a template holds together; the route-gate tests
 * prove who may reach a screen. Neither proves the controller actually sends
 * the data the screen needs — a page can return 200 with a required prop
 * missing and only break once a person opens it. This closes that gap.
 */
function propsManifest(): array
{
    exec('node --version 2>/dev/null', $out, $code);

    if ($code !== 0) {
        return [];
    }

    exec(sprintf(
        'cd %s && npx vite build --config vite.ssr.config.js > /dev/null 2>&1 && node tests/Js/props-runner.mjs 2>/dev/null',
        escapeshellarg(base_path())
    ), $lines);

    return json_decode(implode('', $lines), true) ?: [];
}

beforeEach(function () {
    $this->seed(ProductionSeeder::class);
    $this->seed(DemoSeeder::class);
});

it('sends every prop each page declares required', function () {
    $manifest = propsManifest();

    if ($manifest === []) {
        $this->markTestSkipped('Node ist auf diesem System nicht verfügbar.');
    }

    $admin = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
    $admin->assignRole('super_admin');

    $partner = Assessor::query()
        ->where('approval_status', Assessor::STATUS_APPROVED)
        ->whereHas('user')
        ->with('user')
        ->first();

    expect($partner)->not->toBeNull('Der Demo-Seeder muss einen freigegebenen Partner anlegen.');

    $problems = [];
    $checked = 0;

    foreach (Route::getRoutes() as $route) {
        if (! in_array('GET', $route->methods(), true)) {
            continue;
        }

        $uri = $route->uri();

        // Routes taking parameters need fixtures of their own; the screens
        // behind them are covered by their dedicated feature tests.
        if (str_contains($uri, '{') || str_starts_with($uri, '_') || str_contains($uri, 'export')) {
            continue;
        }

        $actor = match (true) {
            str_starts_with($uri, 'admin') => $admin,
            str_starts_with($uri, 'portal') => $partner->user,
            default => null,
        };

        $response = $actor === null
            ? $this->get('/'.ltrim($uri, '/'))
            : $this->actingAs($actor)->get('/'.ltrim($uri, '/'));

        if ($response->status() !== 200) {
            continue;
        }

        // Inertia puts the page object in the root view's data; a plain
        // Blade response (the error pages) has none, and is skipped.
        try {
            $page = $response->viewData('page');
        } catch (Throwable) {
            continue;
        }

        if (! is_array($page) || ! isset($page['component'])) {
            continue;
        }

        $component = $page['component'];
        $required = $manifest[$component]['required'] ?? [];
        $props = $page['props'] ?? [];
        $checked++;

        foreach ($required as $prop) {
            $snake = str($prop)->snake()->value();

            if (! array_key_exists($prop, $props) && ! array_key_exists($snake, $props)) {
                $problems[] = "{$uri} → {$component}: Prop „{$prop}“ fehlt";

                continue;
            }

            $value = $props[$prop] ?? $props[$snake] ?? null;

            if ($value === null) {
                $problems[] = "{$uri} → {$component}: Prop „{$prop}“ ist null";
            }
        }
    }

    expect($checked)->toBeGreaterThan(20, 'Es wurden zu wenige Seiten geprüft.');
    expect($problems)->toBe([]);
});
