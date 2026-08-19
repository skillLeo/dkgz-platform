<?php

use App\Actions\MatchRequestAction;
use App\Models\Assessor;
use App\Models\AssessorServiceArea;
use App\Models\PostalCode;
use App\Models\ServiceRequest;
use App\Models\ServiceType;
use App\Models\User;
use App\Notifications\NewRequestNotification;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\ServiceTypeSeeder;
use Database\Seeders\SettingsSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(SettingsSeeder::class);
    $this->seed(ServiceTypeSeeder::class);
    PostalCode::create(['code' => '40589', 'city' => 'Düsseldorf', 'state' => 'Nordrhein-Westfalen']);

    $this->type = ServiceType::first();

    $user = User::factory()->create(['is_active' => true]);
    $user->assignRole('assessor');

    $this->assessor = Assessor::factory()->create([
        'user_id' => $user->id,
        'approval_status' => Assessor::STATUS_APPROVED,
        'is_available' => true,
    ]);

    AssessorServiceArea::factory()->covering('40589')->create(['assessor_id' => $this->assessor->id]);
    $this->assessor->serviceTypes()->attach($this->type->id);
    $this->user = $user->fresh();
});

it('renders every portal screen', function (string $path, string $component) {
    $this->actingAs($this->user)
        ->get($path)
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component($component));
})->with([
    ['/portal', 'Portal/Dashboard'],
    ['/portal/anfragen', 'Portal/Anfragen'],
    ['/portal/auftraege', 'Portal/Auftraege'],
    ['/portal/abgelehnt', 'Portal/Abgelehnt'],
    ['/portal/provisionen', 'Portal/Provisionen'],
    ['/portal/einsatzgebiet', 'Portal/Einsatzgebiet'],
    ['/portal/leistungen', 'Portal/Leistungen'],
    ['/portal/profil', 'Portal/Profil'],
    ['/portal/einstellungen', 'Portal/Einstellungen'],
    ['/portal/benachrichtigungen', 'Portal/Benachrichtigungen'],
]);

describe('availability', function () {
    it('stops matching once the partner switches off', function () {
        $this->actingAs($this->user)
            ->post('/portal/verfuegbarkeit', ['is_available' => false])
            ->assertRedirect();

        expect($this->assessor->fresh()->is_available)->toBeFalse();

        $request = ServiceRequest::factory()->inPostalCode('40589')->create([
            'service_type_id' => $this->type->id,
            'reference' => ServiceRequest::nextReference(),
        ]);

        expect(app(MatchRequestAction::class)->execute($request))->toBe(0);
    });
});

describe('service areas', function () {
    it('adds and removes a postal code range', function () {
        $this->actingAs($this->user)->post('/portal/einsatzgebiet', [
            'postal_code_from' => '50000',
            'postal_code_to' => '50999',
            'label' => 'Köln und Umgebung',
        ])->assertRedirect();

        $area = $this->assessor->serviceAreas()->where('postal_code_from', '50000')->firstOrFail();

        expect($area->label)->toBe('Köln und Umgebung');

        $this->actingAs($this->user)->delete("/portal/einsatzgebiet/{$area->id}")->assertRedirect();

        expect(AssessorServiceArea::find($area->id))->toBeNull();
    });

    it('refuses a range whose end is below its start', function () {
        $this->actingAs($this->user)->post('/portal/einsatzgebiet', [
            'postal_code_from' => '50999',
            'postal_code_to' => '50000',
        ])->assertSessionHasErrors('postal_code_to');
    });

    it('refuses to delete another partner\'s area', function () {
        $other = Assessor::factory()->create();
        $area = AssessorServiceArea::factory()->create(['assessor_id' => $other->id]);

        $this->actingAs($this->user)->delete("/portal/einsatzgebiet/{$area->id}")->assertForbidden();
    });
});

describe('services', function () {
    it('requires at least one service', function () {
        $this->actingAs($this->user)
            ->post('/portal/leistungen', ['service_type_ids' => []])
            ->assertSessionHasErrors('service_type_ids');
    });

    it('syncs the selection', function () {
        $ids = ServiceType::limit(3)->pluck('id')->all();

        $this->actingAs($this->user)->post('/portal/leistungen', ['service_type_ids' => $ids])->assertRedirect();

        expect($this->assessor->fresh()->serviceTypes->pluck('id')->sort()->values()->all())
            ->toBe(collect($ids)->sort()->values()->all());
    });
});

describe('the notification poll', function () {
    it('returns a count and at most five items', function () {
        $request = ServiceRequest::factory()->inPostalCode('40589')->create([
            'service_type_id' => $this->type->id,
            'reference' => ServiceRequest::nextReference(),
        ]);

        foreach (range(1, 7) as $ignored) {
            $this->user->notify(new NewRequestNotification($request));
        }

        $response = $this->actingAs($this->user)->getJson('/api/notifications/poll');

        $response->assertOk();

        expect($response->json('count'))->toBe(7)
            ->and($response->json('items'))->toHaveCount(5);
    });

    it('is closed to guests', function () {
        $this->getJson('/api/notifications/poll')->assertUnauthorized();
    });
});

describe('the password change', function () {
    it('requires the current password', function () {
        $this->actingAs($this->user)->post('/portal/einstellungen/passwort', [
            'current_password' => 'falsch',
            'password' => 'NeuesPasswort2026!',
            'password_confirmation' => 'NeuesPasswort2026!',
        ])->assertSessionHasErrors('current_password');
    });
});
