<?php

use App\Jobs\NotifyMatchedAssessorsJob;
use App\Models\Assessor;
use App\Models\AssessorDocument;
use App\Models\AssessorServiceArea;
use App\Models\RequestMatch;
use App\Models\ServiceRequest;
use App\Models\ServiceType;
use App\Models\User;
use App\Support\AttentionQueue;
use Database\Seeders\ContentBlockSeeder;
use Database\Seeders\EmailTemplateSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Support\Facades\Queue;

/**
 * A request made from one partner's own profile.
 *
 * It carries no postal code — the form does not ask for one, because the
 * assessor is already chosen. And it ignores the partner's availability switch:
 * that switch turns off the next job in their area, not the customer who went
 * looking for this particular firm by name.
 */
beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(SettingsSeeder::class);
    $this->seed(EmailTemplateSeeder::class);
    $this->seed(ContentBlockSeeder::class);

    $this->type = ServiceType::factory()->create(['is_active' => true, 'dkgz_fee_cents' => 7900]);

    $this->admin = User::factory()->create(['is_active' => true]);
    $this->admin->assignRole('admin');
});

function listedPartner(bool $available = true, bool $withService = true): Assessor
{
    $user = User::factory()->create(['is_active' => true]);

    $assessor = Assessor::factory()->create([
        'user_id' => $user->id,
        'approval_status' => Assessor::STATUS_APPROVED,
        'is_listed' => true,
        'is_available' => $available,
        'company_name' => 'Sachverständigenbüro Dahab',
    ]);

    if ($withService) {
        $assessor->serviceTypes()->attach(test()->type->id);
    }

    AssessorServiceArea::factory()->create([
        'assessor_id' => $assessor->id,
        'postal_code_from' => 10000,
        'postal_code_to' => 19999,
    ]);

    return $assessor;
}

/** Nothing sent to this partner can arrive: their cover has run out. */
function lapseCover(Assessor $assessor): void
{
    AssessorDocument::create([
        'assessor_id' => $assessor->id,
        'type' => AssessorDocument::TYPE_LIABILITY,
        'path' => 'nachweise/abgelaufen.pdf',
        'original_name' => 'abgelaufen.pdf',
        'size_bytes' => 1024,
        'mime_type' => 'application/pdf',
        'uploaded_at' => now()->subYear(),
        'valid_until' => now()->subDay(),
    ]);
}

function sendFromProfile(Assessor $assessor): ServiceRequest
{
    test()->post('/anfrage', [
        'service_type_id' => test()->type->id,
        'requested_assessor_id' => $assessor->id,
        'customer_name' => 'Martina Reinhardt',
        'customer_email' => 'martina@beispiel.de',
        'customer_phone' => '+49 179 4480169',
    ])->assertSessionHasNoErrors();

    return ServiceRequest::latest('id')->firstOrFail();
}

describe('a partner who has paused', function () {
    it('still receives the request a customer sent them by name', function () {
        Queue::fake();
        $assessor = listedPartner(available: false);

        $request = sendFromProfile($assessor);

        expect($request->fresh()->matched_count)->toBe(1)
            ->and(RequestMatch::where('service_request_id', $request->id)->pluck('assessor_id')->all())
            ->toBe([$assessor->id]);

        // And is sent the ordinary offer e-mail for it.
        Queue::assertPushed(NotifyMatchedAssessorsJob::class);
    });

    it('is still left out of ordinary matching by area', function () {
        // The switch keeps its meaning for everything else: a paused partner
        // is not sent the next job in their postal region.
        $paused = listedPartner(available: false);

        expect(Assessor::query()->matchable()->whereKey($paused->id)->exists())->toBeFalse()
            ->and(Assessor::query()->reachable()->whereKey($paused->id)->exists())->toBeTrue();
    });

    it('keeps the contact form on their profile', function () {
        $assessor = listedPartner(available: false);

        $this->get("/sachverstaendige/{$assessor->slug}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('assessor.accepting', true));
    });
});

it('reaches a partner who has named no services', function () {
    // Their profile offers every assessment, so a customer can pick one — and
    // filtering on a list that is empty would silently drop that request.
    Queue::fake();
    $assessor = listedPartner(withService: false);

    $request = sendFromProfile($assessor);

    expect($request->fresh()->matched_count)->toBe(1);
});

describe('a partner nothing can reach', function () {
    it('does not offer a form that would reach nobody', function () {
        $assessor = listedPartner();
        lapseCover($assessor);

        $this->get("/sachverstaendige/{$assessor->slug}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('assessor.accepting', false));

        expect(file_get_contents(resource_path('js/Pages/Public/Sachverstaendiger.vue')))
            ->toContain('v-if="! assessor.accepting"');
    });

    it('keeps the partner in the directory', function () {
        // Pausing, or a lapsed cover, is not delisting.
        $assessor = listedPartner(available: false);

        $this->get('/sachverstaendige')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('assessors.data', fn ($rows) => collect($rows)
                ->contains('slug', $assessor->slug)));
    });
});

describe('the admin page for such a request', function () {
    it('opens even with no postal code', function () {
        // It asked which partners cover "no postal code" and took a TypeError
        // doing it, so the office could not open the case at all.
        Queue::fake();
        $request = sendFromProfile(listedPartner());

        $this->actingAs($this->admin)
            ->get("/admin/anfragen/{$request->id}")
            ->assertOk();
    });

    it('asks nobody to cover a postal code that does not exist', function () {
        expect(Assessor::covering(null)->count())->toBe(0)
            ->and(Assessor::covering('')->count())->toBe(0);
    });

    it('names the partner the customer chose, instead of an area', function () {
        Queue::fake();
        $assessor = listedPartner(available: false);
        $request = sendFromProfile($assessor);

        $this->actingAs($this->admin)
            ->get("/admin/anfragen/{$request->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('matching', fn ($m) => $m['requested']['id'] === $assessor->id
                // Paused, and reached anyway — so no fault is claimed.
                && $m['requested']['reachable'] === true
                && $m['requested']['reasons'] === []
                && $m['excluded'] === []));
    });

    it('says why, when the chosen partner genuinely could not be reached', function () {
        Queue::fake();
        $assessor = listedPartner();
        lapseCover($assessor);
        $request = sendFromProfile($assessor);

        $this->actingAs($this->admin)
            ->get("/admin/anfragen/{$request->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('matching', fn ($m) => $m['requested']['reachable'] === false
                // Not "unavailable": the switch is not what stopped it.
                && ! in_array('Als nicht verfügbar markiert', $m['requested']['reasons'], true)
                && in_array('Nachweis der Haftpflicht fehlt oder ist abgelaufen', $m['requested']['reasons'], true)));
    });

    it('tells the office the wish partner could not take it, not that an area is empty', function () {
        Queue::fake();
        $assessor = listedPartner();
        lapseCover($assessor);
        sendFromProfile($assessor);

        $matters = collect(AttentionQueue::items(PHP_INT_MAX))->pluck('matter')->implode(' | ');

        expect($matters)->toContain('Sachverständigenbüro Dahab')
            ->and($matters)->not->toContain('PLZ-Gebiet ');
    });
});
