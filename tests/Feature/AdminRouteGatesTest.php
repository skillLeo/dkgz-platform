<?php

use App\Models\Assessor;
use App\Models\RequestMatch;
use App\Models\ServiceRequest;
use App\Models\ServiceType;
use App\Models\User;
use App\Support\AttentionQueue;
use App\Support\Settings;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingsSeeder;
use Spatie\Permission\Models\Role;

/**
 * Every admin route, hit by every role. This is the acceptance requirement
 * "every admin route permission-gated, tested per role" — a route that quietly
 * loses its gate fails here rather than in production.
 */
beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(SettingsSeeder::class);
});

function actingAsRole(string $role): User
{
    $user = User::factory()->create(['is_active' => true]);
    $user->assignRole($role);

    return $user->fresh();
}

/** @return array<string, array{0: string, 1: array<int, string>}> */
dataset('gatedRoutes', [
    // route path            => roles that MAY reach it
    '/admin/anfragen' => ['/admin/anfragen', ['super_admin', 'admin', 'manager', 'support']],
    '/admin/auftraege' => ['/admin/auftraege', ['super_admin', 'admin', 'manager', 'support']],
    '/admin/sachverstaendige' => ['/admin/sachverstaendige', ['super_admin', 'admin', 'manager', 'support']],
    '/admin/einladungen' => ['/admin/einladungen', ['super_admin', 'admin', 'manager', 'support']],
    '/admin/provisionen' => ['/admin/provisionen', ['super_admin', 'admin', 'manager', 'support']],
    '/admin/leistungsarten' => ['/admin/leistungsarten', ['super_admin', 'admin']],
    '/admin/inhalte' => ['/admin/inhalte', ['super_admin', 'admin', 'content_editor']],
    '/admin/seiten' => ['/admin/seiten', ['super_admin', 'admin', 'content_editor']],
    '/admin/faq' => ['/admin/faq', ['super_admin', 'admin', 'content_editor']],
    '/admin/email-vorlagen' => ['/admin/email-vorlagen', ['super_admin', 'admin', 'content_editor']],
    '/admin/branding' => ['/admin/branding', ['super_admin', 'admin', 'content_editor']],
    '/admin/integrationen' => ['/admin/integrationen', ['super_admin']],
    '/admin/einstellungen' => ['/admin/einstellungen', ['super_admin', 'admin']],
    '/admin/benutzer' => ['/admin/benutzer', ['super_admin', 'admin']],
    '/admin/rollen' => ['/admin/rollen', ['super_admin', 'admin']],
    '/admin/protokoll' => ['/admin/protokoll', ['super_admin', 'admin', 'manager', 'support']],
    '/admin/system' => ['/admin/system', ['super_admin', 'admin']],
]);

it('admits exactly the roles that hold the permission', function (string $path, array $allowed) {
    $everyRole = ['super_admin', 'admin', 'manager', 'support', 'content_editor'];

    foreach ($everyRole as $role) {
        $response = $this->actingAs(actingAsRole($role))->get($path);

        if (in_array($role, $allowed, true)) {
            expect($response->status())->toBe(200, "{$role} sollte {$path} sehen dürfen, bekam {$response->status()}.");
        } else {
            expect($response->status())->toBe(403, "{$role} sollte {$path} nicht sehen dürfen, bekam {$response->status()}.");
        }
    }
})->with('gatedRoutes');

it('keeps an assessor out of the whole admin panel', function (string $path) {
    $user = User::factory()->create();
    $user->assignRole('assessor');

    $this->actingAs($user)->get($path)->assertForbidden();
})->with([
    '/admin/anfragen',
    '/admin/auftraege',
    '/admin/sachverstaendige',
    '/admin/provisionen',
    '/admin/benutzer',
    '/admin/einstellungen',
]);

it('sends a guest to the login screen', function (string $path) {
    $this->get($path)->assertRedirect(route('login'));
})->with([
    '/admin',
    '/admin/anfragen',
    '/admin/provisionen',
]);

describe('write routes', function () {
    it('refuses a settings change to anyone without settings.edit', function () {
        $this->actingAs(actingAsRole('support'))
            ->post('/admin/einstellungen/business', ['business__commission_rate' => '99'])
            ->assertForbidden();
    });

    it('refuses the SMTP test to anyone without integrations.manage', function () {
        $this->actingAs(actingAsRole('admin'))
            ->post('/admin/integrationen/smtp-test', ['email' => 'test@example.test'])
            ->assertForbidden();
    });

    it('allows super_admin to change a setting', function () {
        $this->actingAs(actingAsRole('super_admin'))
            ->post('/admin/einstellungen/business', ['business__commission_rate' => '12.5'])
            ->assertRedirect();

        expect(Settings::commissionRate())->toBe(12.5);
    });

    it('refuses role management to admin, who may not recompose roles', function () {
        $role = Role::findByName('manager');

        $this->actingAs(actingAsRole('admin'))
            ->post("/admin/rollen/{$role->id}", ['permissions' => []])
            ->assertForbidden();
    });
});

describe('the attention queue', function () {
    it('lists a request every matched partner declined', function () {
        $type = ServiceType::factory()->create();
        $request = ServiceRequest::factory()->create([
            'service_type_id' => $type->id,
            'reference' => ServiceRequest::nextReference(),
            'status' => ServiceRequest::STATUS_MATCHED,
            'matched_count' => 2,
        ]);

        $assessor = Assessor::factory()->create(['approval_status' => Assessor::STATUS_APPROVED]);
        RequestMatch::create([
            'service_request_id' => $request->id,
            'assessor_id' => $assessor->id,
            'outcome' => RequestMatch::OUTCOME_DECLINED,
            'notified_at' => now(),
            'responded_at' => now(),
        ]);

        $items = collect(AttentionQueue::items());

        expect($items->pluck('reference'))->toContain($request->reference)
            ->and($items->firstWhere('reference', $request->reference)['matter'])
            ->toBe('Von allen 2 Partnern abgelehnt');
    });

    it('lists a request no partner covered', function () {
        $request = ServiceRequest::factory()->create([
            'service_type_id' => ServiceType::factory()->create()->id,
            'reference' => ServiceRequest::nextReference(),
            'status' => ServiceRequest::STATUS_NEW,
            'matched_count' => 0,
            'postal_code' => '17033',
        ]);

        expect(collect(AttentionQueue::items())->firstWhere('reference', $request->reference)['matter'])
            ->toBe('Kein Partner im PLZ-Gebiet 17033');
    });

    it('lists a partner whose liability cover is about to lapse', function () {
        $assessor = Assessor::factory()->create(['approval_status' => Assessor::STATUS_APPROVED]);
        $assessor->documents()->create([
            'type' => 'liability',
            'path' => 'nachweise/h.pdf',
            'original_name' => 'h.pdf',
            'size_bytes' => 100,
            'mime_type' => 'application/pdf',
            'uploaded_at' => now(),
            'valid_until' => now()->addDays(5),
        ]);

        expect(collect(AttentionQueue::items())->pluck('reference'))
            ->toContain($assessor->partnerId());
    });

    it('is empty when nothing needs a human', function () {
        expect(AttentionQueue::items())->toBe([]);
    });
});
