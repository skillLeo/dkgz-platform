<?php

use App\Models\Assessor;
use App\Models\Assignment;
use App\Models\CustomerReview;
use App\Models\ServiceType;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Database\Seeders\EmailTemplateSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingsSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(SettingsSeeder::class);
    $this->seed(EmailTemplateSeeder::class);
});

it('accepts every rating a customer can give and lands somewhere usable', function (int $rating) {
    $assignment = Assignment::factory()->create(['status' => Assignment::STATUS_COMPLETED]);

    $review = CustomerReview::create([
        'assignment_id' => $assignment->id,
        'token' => CustomerReview::generateToken(),
        'expires_at' => now()->addDays(30),
    ]);

    $response = $this->post("/bewertung/{$review->token}", [
        'rating' => $rating,
        'feedback' => 'Alles in Ordnung.',
    ]);

    $response->assertSessionHasNoErrors()->assertRedirect();

    expect($review->fresh()->rating)->toBe($rating);

    // Wherever it sends them, that page has to work — a low rating used to
    // bounce back to a screen that could not tell it was mid-flow.
    $this->get($response->headers->get('Location'))->assertSuccessful();
})->with([1, 2, 3, 4, 5, 6, 7, 8, 9, 10]);

it('walks a low rating all the way to the thank-you page', function () {
    $assignment = Assignment::factory()->create(['status' => Assignment::STATUS_COMPLETED]);

    $review = CustomerReview::create([
        'assignment_id' => $assignment->id,
        'token' => CustomerReview::generateToken(),
        'expires_at' => now()->addDays(30),
    ]);

    $this->post("/bewertung/{$review->token}", ['rating' => 4])->assertRedirect();

    // The page it lands on has to know it is mid-flow, not offering the rating
    // form again to somebody who has already rated.
    $this->get("/bewertung/{$review->token}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('review.needs_feedback', true));

    $this->post("/bewertung/{$review->token}/feedback", [
        'feedback_category' => 'Terminfindung',
        'feedback' => 'Der Termin kam spät zustande.',
    ])->assertRedirect(route('review.thanks', $review->token));

    expect($review->fresh()->feedback_category)->toBe('Terminfindung');
});

it('saves a service description without complaining', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $type = ServiceType::factory()->create(['is_active' => true, 'dkgz_fee_cents' => 7_900]);

    $this->actingAs($admin)
        ->post("/admin/leistungsarten/{$type->id}", [
            'name_de' => $type->name_de,
            'description_de' => 'Eine neue, längere Beschreibung dieser Leistung.',
            'icon' => $type->icon,
            'is_active' => true,
            'dkgz_fee_cents' => 7_900,
            'includes_de' => 'Besichtigung, Fotodokumentation, Gutachten.',
            'target_audience_de' => 'Privatpersonen und Flottenbetreiber.',
            'typical_situations_de' => 'Nach einem Unfall.',
            'differences_de' => 'Abgrenzung zum Kurzgutachten.',
            'additional_info_de' => 'Weitere Hinweise.',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    expect($type->fresh()->description_de)->toBe('Eine neue, längere Beschreibung dieser Leistung.');
});

it('lets an admin open a full detail view for an order', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $assignment = Assignment::factory()->create();

    $this->actingAs($admin)
        ->get("/admin/auftraege/{$assignment->id}")
        ->assertOk();
});

describe('the assessor portrait', function () {
    // Reported three times. Every server-side path works in isolation, so these
    // walk the exact request the browser makes: an Inertia visit to the settings
    // screen, then a multipart POST with the Inertia headers attached.
    it('survives the round trip the browser actually makes', function () {
        Storage::fake('public');

        $assessor = Assessor::factory()->create(['approval_status' => Assessor::STATUS_APPROVED]);
        $assessor->user->assignRole('assessor');

        $response = $this->actingAs($assessor->user)
            ->withHeaders([
                'X-Inertia' => 'true',
                'X-Inertia-Version' => (string) app(\Inertia\ResponseFactory::class)->getVersion(),
                'X-Requested-With' => 'XMLHttpRequest',
            ])
            ->post('/portal/profil/bild', [
                'photo' => UploadedFile::fake()->image('portrait.jpg', 1200, 1600),
            ]);

        $response->assertSessionHasNoErrors();

        // Inertia treats anything but a 303 on a redirect-after-POST as a page
        // it cannot follow, which is what "nothing happens" looks like.
        expect($response->status())->toBeIn([302, 303]);
        expect($assessor->fresh()->photo_path)->not->toBeNull();

        // The Inertia headers above persist on the test client; a browser sends
        // them per request, so they are cleared before the next visit.
        $this->flushHeaders();

        // And the settings screen must hand the new URL straight back.
        $settings = $this->actingAs($assessor->user)->get('/portal/einstellungen');

        expect($settings->status())->toBe(200, 'Einstellungen antworteten mit '.$settings->status());

        $settings->assertInertia(fn ($page) => $page->where('photo.url', $assessor->fresh()->photoUrl()));
    });

    it('rejects a HEIC photograph with an explanation rather than silence', function () {
        Storage::fake('public');

        $assessor = Assessor::factory()->create(['approval_status' => Assessor::STATUS_APPROVED]);
        $assessor->user->assignRole('assessor');

        $this->actingAs($assessor->user)
            ->post('/portal/profil/bild', [
                'photo' => UploadedFile::fake()->create('IMG_4821.HEIC', 2400, 'image/heic'),
            ])
            ->assertSessionHasErrors('photo');

        expect(session('errors')->first('photo'))->toContain('HEIC');
    });
});
