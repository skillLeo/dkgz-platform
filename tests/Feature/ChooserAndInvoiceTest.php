<?php

use App\Actions\CompleteAssignmentAction;
use App\Actions\DeclineAssignmentAction;
use App\Models\Assessor;
use App\Models\Assignment;
use App\Models\Commission;
use App\Models\ContentBlock;
use App\Models\ServiceRequest;
use App\Models\ServiceType;
use App\Models\User;
use App\Support\AttentionQueue;
use App\Support\Content;
use Database\Seeders\ContentBlockSeeder;
use Database\Seeders\EmailTemplateSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(SettingsSeeder::class);
    $this->seed(EmailTemplateSeeder::class);
    $this->seed(ContentBlockSeeder::class);
    Storage::fake('private');
});

function jobFor(string $status, int $fee = 7_900): Assignment
{
    $user = User::factory()->create(['is_active' => true]);
    $assessor = Assessor::factory()->create(['user_id' => $user->id]);
    $type = ServiceType::factory()->create(['is_active' => true]);

    $request = ServiceRequest::factory()->create([
        'service_type_id' => $type->id,
        'status' => ServiceRequest::STATUS_ASSIGNED,
        'customer_notified_at' => null,
    ]);

    return Assignment::factory()->create([
        'assessor_id' => $assessor->id,
        'service_request_id' => $request->id,
        'status' => $status,
        'dkgz_fee_snapshot_cents' => $fee,
        'confirmed_at' => null,
    ]);
}

describe('invoice numbers', function () {
    it('starts five digits in rather than at one', function () {
        // The old scheme told every partner exactly how many jobs DKGZ had ever
        // placed, and the first one to be sent 0001 learned they were the first.
        expect(Commission::nextInvoiceNumber())->toBe('DKGZRE-82191');
    });

    it('counts on from the last one it issued', function () {
        Commission::factory()->create(['invoice_number' => 'DKGZRE-82191']);
        Commission::factory()->create(['invoice_number' => 'DKGZRE-82192']);

        expect(Commission::nextInvoiceNumber())->toBe('DKGZRE-82193');
    });

    it('carries on counting once the number gains a digit', function () {
        // "DKGZRE-99999" sorts above "DKGZRE-100000" as text, so reading the
        // last one by string order alone would start the run again.
        Commission::factory()->create(['invoice_number' => 'DKGZRE-99999']);
        Commission::factory()->create(['invoice_number' => 'DKGZRE-100000']);

        expect(Commission::nextInvoiceNumber())->toBe('DKGZRE-100001');
    });

    it('leaves numbers issued under the old scheme alone', function () {
        // An invoice number is part of a document both sides have filed.
        $old = Commission::factory()->create(['invoice_number' => 'DKGZ-RE-2026-0001']);

        expect(Commission::nextInvoiceNumber())->toBe('DKGZRE-82191')
            ->and($old->fresh()->invoice_number)->toBe('DKGZ-RE-2026-0001');
    });
});

describe('a finished job', function () {
    it('has an invoice to download, even if it never passed through In Bearbeitung', function () {
        // It booked the fee and then waited for somebody to remember to press a
        // button, so a finished job had no invoice and nothing said why.
        $assignment = jobFor(Assignment::STATUS_ACCEPTED, 5_900);

        $commission = app(CompleteAssignmentAction::class)->execute($assignment);

        expect($commission->status)->toBe(Commission::STATUS_INVOICED)
            ->and($commission->invoice_number)->toBe('DKGZRE-82191')
            ->and($commission->invoice_path)->not->toBeNull();
    });

    it('is not billed twice when it was confirmed on the way', function () {
        $assignment = jobFor(Assignment::STATUS_ACCEPTED, 5_900);

        $first = app(\App\Actions\ConfirmAssignmentAction::class)->execute($assignment);
        app(CompleteAssignmentAction::class)->execute($assignment->fresh());

        expect(Commission::count())->toBe(1)
            ->and(Commission::first()->invoice_number)->toBe($first->invoice_number);
    });

    it('offers the pdf from the order it belongs to', function () {
        $assignment = jobFor(Assignment::STATUS_ACCEPTED, 5_900);
        app(CompleteAssignmentAction::class)->execute($assignment);

        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole('admin');

        // It only ever lived on the commission page, two clicks away, and an
        // office looking at a finished job could not find it.
        $this->actingAs($admin)
            ->get("/admin/auftraege/{$assignment->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('commission.has_invoice', true)
                ->where('commission.invoice_number', 'DKGZRE-82191'));
    });
});

describe('a job the partner hands back', function () {
    it('stops nagging that the customer was never told', function () {
        $assignment = jobFor(Assignment::STATUS_ACCEPTED);

        app(DeclineAssignmentAction::class)->execute($assignment, 'kunde_nicht_erreichbar');

        // The mail goes out on the queue, which runs inline under the sync
        // driver the tests use.
        expect($assignment->serviceRequest->fresh()->customer_notified_at)->not->toBeNull()
            ->and(collect(AttentionQueue::items(PHP_INT_MAX))->pluck('matter')->implode(' '))
            ->not->toContain('benachrichtigt');
    });

    it('shows the office why', function () {
        $assignment = jobFor(Assignment::STATUS_ACCEPTED);
        app(DeclineAssignmentAction::class)->execute($assignment, 'kunde_nicht_erreichbar');

        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole('admin');

        // Recorded from the start, passed to the page from the start, and then
        // never drawn.
        $this->actingAs($admin)
            ->get("/admin/auftraege/{$assignment->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('assignment.cancellation_reason', 'Kunde war nicht erreichbar'));

        expect(file_get_contents(resource_path('js/Pages/Admin/Auftrag.vue')))
            ->toContain('assignment.cancellation_reason');
    });
});

describe('the first step of the request', function () {
    it('lists every assessment, including the one the homepage leads with', function () {
        ServiceType::factory()->count(3)->create(['is_active' => true]);

        $this->get('/anfrage')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('serviceTypes', fn ($types) => count($types) >= 3)
                ->where('selected', []));
    });

    it('is chosen and then confirmed, not chosen by being pressed', function () {
        $source = file_get_contents(resource_path('js/Components/Domain/ServiceChooser.vue'));

        // A list of seven where somebody is comparing punishes reading if it
        // acts the instant a row is touched.
        expect($source)->toContain('type="radio"')
            ->and($source)->toContain(':disabled="! modelValue"')
            ->and($source)->toContain("emit('confirm')");
    });

    it('explains what each assessment is for, in its own words', function () {
        // The description says what the assessment covers, which is the right
        // answer on the services page and the wrong one to somebody deciding
        // which of seven they need.
        $type = ServiceType::factory()->create([
            'is_active' => true,
            'description_de' => 'Bewertung und Dokumentation von Fahrzeugschäden.',
            'info_de' => 'Brauchen Sie nach einem Unfall, an dem ein anderer schuld ist.',
        ]);

        $this->get('/anfrage')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('serviceTypes', fn ($types) => collect($types)
                ->firstWhere('id', $type->id)['info_de'] === 'Brauchen Sie nach einem Unfall, an dem ein anderer schuld ist.'));

        expect(file_get_contents(resource_path('js/Components/Domain/ServiceChooser.vue')))
            ->toContain('type.info_de ?? type.description_de');
    });

    it('falls back to the description until somebody writes the longer one', function () {
        // Nothing is blank while the office works through seven of them.
        $type = ServiceType::factory()->create([
            'is_active' => true,
            'description_de' => 'Bewertung und Dokumentation von Fahrzeugschäden.',
            'info_de' => null,
        ]);

        expect($type->infoText())->toBe('Bewertung und Dokumentation von Fahrzeugschäden.');

        $this->get('/anfrage')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('serviceTypes', fn ($types) => collect($types)
                ->firstWhere('id', $type->id)['info_de'] === 'Bewertung und Dokumentation von Fahrzeugschäden.'));
    });

    it('lets the office write it per assessment', function () {
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole('admin');
        $type = ServiceType::factory()->create(['is_active' => true, 'dkgz_fee_cents' => 7900]);

        $this->actingAs($admin)
            ->post("/admin/leistungsarten/{$type->id}", [
                'name_de' => $type->name_de,
                'description_de' => 'Kurz.',
                'info_de' => 'Wann Sie das brauchen: nach einem Unfall.',
                'is_active' => true,
                'dkgz_fee_cents' => 7900,
            ])
            ->assertSessionHasNoErrors();

        expect($type->fresh()->info_de)->toBe('Wann Sie das brauchen: nach einem Unfall.');
    });

    it('keeps the line breaks the office typed into the info text', function () {
        // An explanation written as two paragraphs arrived as one run of text,
        // so the example ran into the sentence before it. Interpolated, never
        // rendered as markup — a text field that quietly accepts tags is a text
        // field somebody can put a script in.
        $dialog = file_get_contents(resource_path('js/Components/Feedback/ConfirmDialog.vue'));

        expect($dialog)->toContain('whitespace-pre-line pt-2 text-base')
            ->and($dialog)->toContain('{{ state.message }}')
            ->and($dialog)->not->toContain('v-html');
    });

    it('stores a line break rather than flattening it', function () {
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole('admin');
        $type = ServiceType::factory()->create(['is_active' => true, 'dkgz_fee_cents' => 7900]);

        $this->actingAs($admin)
            ->post("/admin/leistungsarten/{$type->id}", [
                'name_de' => $type->name_de,
                'info_de' => "Nach einem Unfall.\n\nBeispiel: Auffahrunfall auf der Autobahn.",
                'is_active' => true,
                'dkgz_fee_cents' => 7900,
            ])
            ->assertSessionHasNoErrors();

        expect($type->fresh()->info_de)->toContain("\n\nBeispiel:");
    });

    it('opens each description in the dialog rather than inside its row', function () {
        $source = file_get_contents(resource_path('js/Components/Domain/ServiceChooser.vue'));

        // Opened in place it pushed its own box taller, and a grid stretches
        // every box in a row to match — so asking what one assessment meant left
        // an empty tall box beside it.
        expect($source)->toContain('@click="explain(type)"')
            ->and($source)->toContain('dismissOnly: true')
            ->and($source)->not->toContain('open === type.id');
    });

    it('centres a one-line name against a two-line one beside it', function () {
        $source = file_get_contents(resource_path('js/Components/Domain/ServiceChooser.vue'));

        // The box is the flex row itself, so the label stretches to the height
        // the grid gives it and centres its own content.
        expect($source)->toContain('class="flex rounded-card border bg-white')
            ->and($source)->toContain('flex min-w-0 flex-1 cursor-pointer items-center gap-4 p-4')
            ->and($source)->toContain('self-center');
    });

    it('sends the second homepage choice here rather than opening a list in the hero', function () {
        $source = file_get_contents(resource_path('js/Components/Domain/RequestStarter.vue'));

        expect($source)->toContain('const browseAll')
            ->and($source)->toContain("router.get(props.action, {}, { preserveScroll: false })")
            // Nothing is listed or confirmed in the hero any more.
            ->and($source)->not->toContain('BaseSelect')
            ->and($source)->not->toContain('v-for');
    });

    it('carries the progress band, and can drop its eyebrow', function () {
        $source = file_get_contents(resource_path('js/Pages/Public/Anfrage.vue'));

        expect($source)->toContain('<template #progress>')
            ->and($source)->toContain("isOn(props.content, 'kopf', 'eyebrow_anzeigen')")
            ->and(ContentBlock::where('page_key', 'anfrage')
                ->where('field_key', 'eyebrow_anzeigen')->value('type'))->toBe('boolean');
    });
});

describe('the availability line on the second step', function () {
    it('names the assessment that was chosen', function () {
        $source = file_get_contents(resource_path('js/Pages/Public/Anfrage.vue'));

        expect($source)->toContain("t('formular', 'verfuegbarkeit'")
            ->and($source)->toContain('showAvailability');

        expect(Content::get('anfrage.formular.verfuegbarkeit'))->toContain('{Ihren leistung}');
    });

    it('is a switch, on by default', function () {
        expect(Content::bool('anfrage.formular.verfuegbarkeit_anzeigen'))->toBeTrue()
            ->and(ContentBlock::where('page_key', 'anfrage')
                ->where('field_key', 'verfuegbarkeit_anzeigen')->value('type'))->toBe('boolean');
    });
});

it('shows the reassurance in the request header on a phone as well', function () {
    $source = file_get_contents(resource_path('js/Layouts/RequestFlowLayout.vue'));

    // It was hidden below sm, which removed the one reassurance on screen
    // exactly where the screen is smallest and the doubt is largest.
    expect($source)->not->toContain('hidden sm:inline')
        ->and($source)->toContain('max-w-24 text-xs leading-tight');
});

it('draws every mark at the size it was asked for', function () {
    // The crash mark was given a quarter more for a while, on the theory that a
    // thinner drawing needs the room to read the same. A row where one icon is
    // visibly larger reads as a mistake long before it reads as balance.
    $icons = file_get_contents(resource_path('js/Support/serviceIcons.js'));
    $component = file_get_contents(resource_path('js/Components/Domain/ServiceIcon.vue'));

    // Not "scale" on its own: one of the icons is the pair of justice scales.
    expect($icons)->not->toContain('scale:')
        ->and($icons)->not->toContain('scaleFor')
        ->and($component)->not->toContain('scaleFor')
        ->and($component)->toContain(':size="size"');
});

it('asks for one icon size everywhere it lists an assessment', function () {
    foreach (['RequestStarter', 'ServiceChooser'] as $file) {
        $source = file_get_contents(resource_path("js/Components/Domain/{$file}.vue"));

        expect($source)->toContain(':size="28"')
            ->and($source)->not->toContain(':size="34"')
            ->and($source)->not->toContain(':size="42"');
    }
});

it('lists the assessments two across where there is room', function () {
    // Seven names in one column pushed the button off a desktop screen, which
    // is the one place there is width to spare.
    expect(file_get_contents(resource_path('js/Components/Domain/ServiceChooser.vue')))
        ->toContain('grid grid-cols-1 gap-3 md:grid-cols-2');
});

it('heads both steps at the same size', function () {
    $source = file_get_contents(resource_path('js/Pages/Public/Anfrage.vue'));

    // The first step was an h1 on a desktop and the second an h2, so the flow
    // shrank as it went.
    expect(substr_count($source, 'text-h2 font-bold text-navy-700'))->toBe(2)
        ->and($source)->not->toContain('sm:text-h1');
});

it('names the first step after what it asks for', function () {
    expect(file_get_contents(resource_path('js/Pages/Public/Anfrage.vue')))
        ->toContain("label: 'Gutachten', short: 'Gutachten'");
});

describe('the availability line', function () {
    it('bends its article to the assessment', function () {
        // "für Ihr Unfallgutachten", but "für Ihre Fahrzeugbewertung". The
        // article rides inside the braces so it can be bent to the noun.
        expect(Content::get('anfrage.formular.verfuegbarkeit'))
            ->toContain('{Ihren leistung}');

        expect(file_get_contents(resource_path('js/Pages/Public/Anfrage.vue')))
            ->toContain('leistung_genus: service.value?.genus');
    });

    it('is handed the gender it needs to do it', function () {
        ServiceType::factory()->create(['name_de' => 'Fahrzeugbewertung', 'is_active' => true, 'gender' => null]);

        $this->get('/anfrage')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('serviceTypes', fn ($types) => collect($types)
                ->every(fn ($type) => in_array(($type['genus'] ?? null), ['m', 'f', 'n'], true))));
    });

    it('works out feminine from the name when nobody set it', function () {
        // "-ung" is feminine, so "Fahrzeugbewertung" takes "Ihre".
        $type = ServiceType::factory()->create(['name_de' => 'Fahrzeugbewertung', 'is_active' => true, 'gender' => null]);

        expect($type->genus())->toBe('f');
    });
});
