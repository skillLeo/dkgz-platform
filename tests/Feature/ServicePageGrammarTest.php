<?php

use App\Models\City;
use App\Models\ContentBlock;
use App\Models\ServiceType;
use App\Models\User;
use App\Support\GermanNoun;
use Database\Seeders\ContentBlockSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingsSeeder;

/**
 * The word in front of the service name.
 *
 * One set of editable copy serves every service page, so a sentence written
 * around "Unfallgutachten" was being handed word for word to "Beweissicherung":
 * "Sie benötigen ein Beweissicherung", "Häufige Fragen zum Beweissicherung".
 * German decides that word by the noun's gender, so the gender travels with the
 * service and the article is bent to it where the copy is filled in.
 */
beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(SettingsSeeder::class);
    $this->seed(ContentBlockSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
});

describe('the gender of a service name', function () {
    it('reads it off the ending', function () {
        $expected = [
            // Everything DKGZ arranges today.
            'Unfallgutachten' => 'n',
            'Haftpflichtgutachten' => 'n',
            'Kaskogutachten' => 'n',
            'Fahrzeugschadengutachten' => 'n',
            'Wertgutachten' => 'n',
            'Oldtimergutachten' => 'n',
            'Gebrauchtwagen-Check' => 'm',
            'Beweissicherung' => 'f',

            // And the shapes the next ones are likely to take.
            'Fahrzeugbewertung' => 'f',
            'Schadenaufnahme' => 'f',
            'Reparaturbestätigung' => 'f',
            'Oldtimer-Service' => 'm',
            'Leasingrückgabe' => 'f',
            'Kurzgutachten' => 'n',
        ];

        foreach ($expected as $name => $gender) {
            expect(GermanNoun::genderOf($name))
                ->toBe($gender, "„{$name}“ sollte ".GermanNoun::LABELS[$gender].' sein');
        }
    });

    it('takes the gender of the last noun in a compound', function () {
        // "Gebrauchtwagen-Check" is masculine like "Check", not neuter like
        // the "Wagen" in the middle of it.
        expect(GermanNoun::genderOf('Gebrauchtwagen-Check'))->toBe('m');
        expect(GermanNoun::genderOf('Kfz Wertermittlung'))->toBe('f');
    });

    it('lets the operator overrule the guess', function () {
        $type = ServiceType::factory()->create(['name_de' => 'Oldtimer-Expertise']);

        expect($type->genus())->toBe('f');

        $type->update(['gender' => 'n']);

        expect($type->fresh()->genus())->toBe('n');
    });

    it('moves the gender with the name when nobody has overruled it', function () {
        $type = ServiceType::factory()->create(['name_de' => 'Unfallgutachten']);
        expect($type->genus())->toBe('n');

        $type->update(['name_de' => 'Beweissicherung']);
        expect($type->fresh()->genus())->toBe('f');
    });
});

describe('bending the article in editable copy', function () {
    it('produces the right German for each gender', function () {
        if (! function_exists('exec')) {
            $this->markTestSkipped('exec ist nicht verfügbar.');
        }

        exec('node --version 2>/dev/null', $out, $code);

        if ($code !== 0) {
            $this->markTestSkipped('Node ist auf diesem System nicht verfügbar.');
        }

        // Template, service name, gender => the sentence it has to produce.
        $cases = [
            ['Sie benötigen {einen leistung} in {stadt}?', 'Unfallgutachten', 'n', 'Sie benötigen ein Unfallgutachten in Köln?'],
            ['Sie benötigen {einen leistung} in {stadt}?', 'Beweissicherung', 'f', 'Sie benötigen eine Beweissicherung in Köln?'],
            ['Sie benötigen {einen leistung} in {stadt}?', 'Gebrauchtwagen-Check', 'm', 'Sie benötigen einen Gebrauchtwagen-Check in Köln?'],
            ['Häufige Fragen {zum leistung}', 'Unfallgutachten', 'n', 'Häufige Fragen zum Unfallgutachten'],
            ['Häufige Fragen {zum leistung}', 'Beweissicherung', 'f', 'Häufige Fragen zur Beweissicherung'],
            ['So kommen Sie {zum leistung} in {stadt}', 'Gebrauchtwagen-Check', 'm', 'So kommen Sie zum Gebrauchtwagen-Check in Köln'],
            ['übernimmt {den leistung} und meldet sich', 'Gebrauchtwagen-Check', 'm', 'übernimmt den Gebrauchtwagen-Check und meldet sich'],
            ['übernimmt {den leistung} und meldet sich', 'Beweissicherung', 'f', 'übernimmt die Beweissicherung und meldet sich'],
            // An article opening a sentence keeps its capital.
            ['{Der leistung} ist kostenlos.', 'Beweissicherung', 'f', 'Die Beweissicherung ist kostenlos.'],
            ['{Ihr leistung} ist unterwegs.', 'Beweissicherung', 'f', 'Ihre Beweissicherung ist unterwegs.'],
            // No article, as before.
            ['{leistung} in {stadt}', 'Wertgutachten', 'n', 'Wertgutachten in Köln'],
            // A word that is not an article is left standing rather than
            // guessed at, so the operator sees their mistake.
            ['{unbekannt leistung}', 'Wertgutachten', 'n', '{unbekannt leistung}'],
            // As is a placeholder nobody supplies a value for.
            ['{leistung} in {ort}', 'Wertgutachten', 'n', 'Wertgutachten in {ort}'],
        ];

        $module = base_path('resources/js/Support/placeholders.js');
        $script = <<<JS
        import { fill } from '{$module}'
        const cases = JSON.parse(process.argv[2])
        console.log(JSON.stringify(cases.map(([text, leistung, genus]) =>
            fill(text, { stadt: 'Köln', leistung, leistung_genus: genus }))))
        JS;

        $path = base_path('storage/framework/testing/placeholder-grammar.mjs');
        @mkdir(dirname($path), 0777, true);
        file_put_contents($path, $script);

        $output = shell_exec(sprintf(
            'cd %s && node %s %s 2>&1',
            escapeshellarg(base_path()),
            escapeshellarg($path),
            escapeshellarg(json_encode($cases))
        ));

        @unlink($path);

        $produced = json_decode(trim((string) $output), true);

        expect($produced)->not->toBeNull("Node lieferte keine verwertbare Ausgabe: {$output}");
        expect($produced)->toBe(array_column($cases, 3));
    });

    it('sends the gender to the page that fills the copy in', function () {
        $type = ServiceType::factory()->create(['name_de' => 'Beweissicherung', 'is_active' => true]);

        $this->get("/leistungen/{$type->slug}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('serviceType.genus', 'f'));

        $city = City::create(['name' => 'Köln', 'is_active' => true]);
        $city->serviceTypes()->attach($type);

        $this->get("/kfz-gutachter/{$city->slug}/{$type->slug}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('serviceType.genus', 'f'));
    });

    it('leaves no seeded copy with the article stranded outside the braces', function () {
        // "zum {leistung}" is the shape of the bug: an article frozen to one
        // service's gender in text every service reuses.
        $stranded = ContentBlock::query()
            ->whereIn('page_key', ['staedte', 'leistungen'])
            ->get()
            ->filter(fn (ContentBlock $block) => preg_match(
                '/\b(zum|zur|ein|eine|einen|das|der|die|den|dem|Ihr|Ihre)\s+\{leistung\}/u',
                (string) $block->value
            ))
            ->map(fn (ContentBlock $block) => "{$block->page_key}.{$block->section_key}.{$block->field_key}")
            ->values()
            ->all();

        expect($stranded)->toBe([]);
    });
});

describe('what the service pages carry', function () {
    it('offers the other services here with their icons', function () {
        $city = City::create(['name' => 'Köln', 'is_active' => true]);
        $shown = ServiceType::factory()->create(['name_de' => 'Unfallgutachten', 'is_active' => true, 'icon' => 'car-front']);
        $other = ServiceType::factory()->create(['name_de' => 'Wertgutachten', 'is_active' => true, 'icon' => 'gauge']);
        $city->serviceTypes()->attach([$shown->id, $other->id]);

        $this->get("/kfz-gutachter/{$city->slug}/{$shown->slug}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('otherServices.0.name', 'Wertgutachten')
                ->where('otherServices.0.icon', 'gauge'));
    });

    it('no longer lists the same service in other cities', function () {
        $type = ServiceType::factory()->create(['name_de' => 'Unfallgutachten', 'is_active' => true]);
        $here = City::create(['name' => 'Köln', 'is_active' => true]);
        $there = City::create(['name' => 'Bonn', 'is_active' => true]);
        $here->serviceTypes()->attach($type);
        $there->serviceTypes()->attach($type);

        $this->get("/kfz-gutachter/{$here->slug}/{$type->slug}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page->missing('otherCities'));
    });

    it('titles the nationwide route to the service after the service', function () {
        $block = ContentBlock::query()
            ->where('page_key', 'leistungen')
            ->where('section_key', 'detail')
            ->where('field_key', 'ablauf_ueberschrift')
            ->first();

        expect($block?->value)->toBe('So kommen Sie {zum leistung}');
    });
});

describe('how it works on the homepage', function () {
    it('is three steps', function () {
        $steps = ContentBlock::query()
            ->where('page_key', 'startseite')
            ->where('section_key', 'ablauf')
            ->where('field_key', 'like', 'schritt_%_titel')
            ->pluck('field_key')
            ->sort()
            ->values()
            ->all();

        expect($steps)->toBe(['schritt_1_titel', 'schritt_2_titel', 'schritt_3_titel']);
    });

    it('says three in the sentence above them', function () {
        $intro = ContentBlock::query()
            ->where('page_key', 'startseite')
            ->where('section_key', 'ablauf')
            ->where('field_key', 'text')
            ->value('value');

        expect($intro)->toStartWith('Drei Schritte');
    });

    it('keeps the last step saying the assessor makes contact', function () {
        // That was the fourth step's whole content; dropping it without
        // moving the sentence would have lost the reassurance.
        $third = ContentBlock::query()
            ->where('page_key', 'startseite')
            ->where('section_key', 'ablauf')
            ->where('field_key', 'schritt_3_text')
            ->value('value');

        expect($third)->toContain('meldet sich direkt bei Ihnen');
    });

    it('renumbers rather than leaving a gap when a step is emptied', function () {
        $source = file_get_contents(resource_path('js/Pages/Public/Startseite.vue'));

        expect($source)->toContain('.filter((step) => step.title || step.text)');
        expect($source)->toContain('String(index + 1).padStart(2, \'0\')');
    });
});

describe('saying the article in the admin panel', function () {
    it('shows the article each service is using', function () {
        ServiceType::factory()->create(['name_de' => 'Beweissicherung']);

        $this->actingAs($this->admin)
            ->get('/admin/leistungsarten')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('serviceTypes.0.artikel', 'die')
                ->where('serviceTypes.0.gender', null));
    });

    it('stores an override and takes it back again', function () {
        $type = ServiceType::factory()->create([
            'name_de' => 'Beweissicherung',
            'is_active' => true,
            'dkgz_fee_cents' => 8500,
        ]);

        $payload = [
            'name_de' => 'Beweissicherung',
            'is_active' => true,
            'dkgz_fee_cents' => 8500,
        ];

        $this->actingAs($this->admin)
            ->post("/admin/leistungsarten/{$type->id}", $payload + ['gender' => 'n'])
            ->assertSessionHasNoErrors();

        expect($type->fresh()->genus())->toBe('n');

        // Back to being worked out from the name.
        $this->actingAs($this->admin)
            ->post("/admin/leistungsarten/{$type->id}", $payload + ['gender' => ''])
            ->assertSessionHasNoErrors();

        expect($type->fresh()->genus())->toBe('f');
    });

    it('refuses a gender that is not one of the three', function () {
        $type = ServiceType::factory()->create(['name_de' => 'Wertgutachten', 'dkgz_fee_cents' => 8500]);

        $this->actingAs($this->admin)
            ->post("/admin/leistungsarten/{$type->id}", [
                'name_de' => 'Wertgutachten',
                'gender' => 'x',
                'dkgz_fee_cents' => 8500,
            ])
            ->assertSessionHasErrors('gender');
    });
});
