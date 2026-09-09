<?php

use App\Models\ContentBlock;
use App\Models\ServiceType;
use App\Support\Content;
use Database\Seeders\ContentBlockSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\ServiceTypeSeeder;
use Database\Seeders\SettingsSeeder;

/**
 * The first step: two choices instead of a dropdown and a button.
 *
 * Which assessment leads is not written into the template — it is whichever the
 * operator has put first under Leistungsarten. These guard that, and that the
 * page somebody lands on afterwards tells them what they asked for.
 */
beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(SettingsSeeder::class);
    $this->seed(ContentBlockSeeder::class);
    $this->seed(ServiceTypeSeeder::class);
});

it('leads with whichever service the operator put first, not a name in the code', function () {
    $starter = file_get_contents(resource_path('js/Components/Domain/RequestStarter.vue'));

    // Keyed by position in the operator's own order, so renaming the service
    // cannot silently drop it — the icon map was keyed by slug once and did
    // exactly that.
    expect($starter)->toContain('props.serviceTypes[0]')
        ->and($starter)->toContain('props.serviceTypes.slice(1)')
        ->and($starter)->not->toContain('Unfall- & Schadengutachten');
});

it('sends both pages the same services, in the operator order, with their icons', function () {
    foreach (['/', '/anfrage'] as $url) {
        $this->get($url)
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('serviceTypes', function ($types) {
                $types = collect($types);

                return $types->isNotEmpty()
                    && $types->every(fn ($type) => array_key_exists('icon', (array) $type))
                    && $types->every(fn ($type) => array_key_exists('description_de', (array) $type))
                    && $types->every(fn ($type) => array_key_exists('slug', (array) $type));
            }));
    }
});

it('reorders the leading choice when the operator reorders the services', function () {
    $second = ServiceType::active()->ordered()->skip(1)->first();

    // Whatever the office puts at the top is what the hero offers first.
    $second->update(['sort_order' => -1]);

    $this->get('/')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where(
            'serviceTypes.0.name_de',
            $second->name_de,
        ));
});

it('lands on the second step with the service already chosen', function () {
    $type = ServiceType::active()->ordered()->first();

    // This is what pressing the first card does: it names the service in the
    // address, and the request page opens past the question.
    $this->get("/anfrage?leistung={$type->slug}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('selected.service_type_id', $type->id));
});

it('names the chosen assessment above the form, in one line', function () {
    $source = file_get_contents(resource_path('js/Pages/Public/Anfrage.vue'));

    // The description explained an assessment to somebody who had already
    // chosen it, which is a paragraph answering a question nobody still has.
    expect($source)->not->toContain('service.description_de');

    // Above the white box, not inside it.
    $form = strpos($source, '@submit.prevent="submit"');
    $line = strpos($source, "t('formular', 'gewaehlte_leistung'");

    expect($line)->toBeLessThan($form);
});

it('keeps the only way back to the first step', function () {
    // The progress band above is deliberately not clickable, so "ändern" beside
    // the answer is the only route back.
    $source = file_get_contents(resource_path('js/Pages/Public/Anfrage.vue'));

    expect($source)->toContain('@click="back"')
        ->and($source)->toContain('>ändern</button>');
});

it('offers the wording of the hero choices to the operator', function () {
    expect(ContentBlock::where('page_key', 'startseite')
        ->where('section_key', 'hero')
        ->where('field_key', 'option_weitere')
        ->exists())->toBeTrue('startseite.hero.option_weitere fehlt');

    // And nothing left over for screens that no longer exist: the hero does not
    // expand any more, so there is nothing to go back from.
    foreach ([['startseite', 'zurueck'], ['anfrage', 'zurueck'], ['anfrage', 'cta_schritt_1']] as [$page, $field]) {
        expect(ContentBlock::where('page_key', $page)->where('field_key', $field)->exists())
            ->toBeFalse("{$page}.{$field} sollte entfernt sein");
    }
});

it('carries a mark and a name and nothing else', function () {
    // Two choices with a sentence under each read as a paragraph to be worked
    // through, in a hero already carrying a headline, a strapline and a row of
    // faces. The description moved to the step where it is actually read.
    $source = file_get_contents(resource_path('js/Components/Domain/RequestStarter.vue'));

    expect($source)->not->toContain('description_de')
        ->and($source)->toContain('<ServiceIcon')
        ->and($source)->toContain('<ChevronRight');

    // And no subtitle left behind in the admin panel for a line nothing draws.
    expect(ContentBlock::where('field_key', 'option_weitere_text')->exists())->toBeFalse();
});

it('dresses both hero choices from one class list', function () {
    // The two have to be indistinguishable. Written once and bound twice,
    // because two copies of a long class list drift apart the first time
    // somebody edits one of them.
    $source = file_get_contents(resource_path('js/Components/Domain/RequestStarter.vue'));

    expect(substr_count($source, ':class="ROW"'))->toBe(2)
        ->and(substr_count($source, ':class="NAME"'))->toBe(2)
        // The tint arrives on hover and never sits on a resting row.
        ->and($source)->toContain('hover:bg-navy-100/60')
        ->and($source)->not->toContain(' bg-navy-100/60 ');
});

it('hands the rest to the request page instead of listing them in the hero', function () {
    $source = file_get_contents(resource_path('js/Components/Domain/RequestStarter.vue'));

    // Seven names with descriptions beside a headline, a strapline and a row of
    // faces is a page of reading in a hero.
    expect($source)->toContain('const browseAll')
        ->and($source)->not->toContain('v-for')
        ->and($source)->not->toContain('BaseSelect')
        ->and($source)->not->toContain('BaseButton');

    // And no wording left in the admin panel for a hero button that is gone.
    expect(ContentBlock::where('page_key', 'startseite')->where('field_key', 'cta_button')->exists())->toBeFalse();
});

it('offers the word before the chosen assessment to the operator', function () {
    $source = file_get_contents(resource_path('js/Pages/Public/Anfrage.vue'));

    expect($source)->toContain("t('formular', 'gewaehlte_leistung', 'Gutachten')")
        ->and(ContentBlock::where('page_key', 'anfrage')
            ->where('field_key', 'gewaehlte_leistung')
            ->value('value'))->toBe('Gutachten');
});

it('still lets the operator word the question and the line under it', function () {
    // Both survived the dropdown they used to belong to: the question now heads
    // the request page and the line sits under it, above the list.
    $source = file_get_contents(resource_path('js/Pages/Public/Anfrage.vue'));

    expect($source)->toContain("t('formular', 'frage_leistung'")
        ->and($source)->toContain("t('formular', 'frage_hinweis'");

    foreach (['frage_leistung', 'frage_hinweis'] as $field) {
        expect(ContentBlock::where('page_key', 'anfrage')
            ->where('section_key', 'formular')
            ->where('field_key', $field)
            ->exists())->toBeTrue("anfrage.formular.{$field} fehlt");
    }
});

it('has a mark drawn for the assessment after a crash', function () {
    $icons = file_get_contents(resource_path('js/Support/serviceIcons.js'));

    // Nothing in the borrowed set said "after a crash" at a glance, which is the
    // whole job of the first choice.
    expect($icons)->toContain("'crash'")
        ->and(file_exists(resource_path('js/Components/Icons/CrashIcon.vue')))->toBeTrue();

    // Same grid and the same joins as everything around it, or it reads as
    // pasted in from somewhere else.
    $crash = file_get_contents(resource_path('js/Components/Icons/CrashIcon.vue'));

    expect($crash)->toContain('viewBox="0 0 24 24"')
        ->and($crash)->toContain('stroke="currentColor"')
        ->and($crash)->toContain('stroke-linejoin="round"');
});

it('spaces the hero the way it was actually settled on', function () {
    // The gap over the row of faces is the same on every screen: it was made
    // responsive once, to bring them above the fold on a phone, and the hero
    // read worse for it. The room above the headline is untouched for the same
    // reason, and there is deliberately less below it than above, so the band of
    // four figures sits closer to what it belongs to.
    $source = file_get_contents(resource_path('js/Pages/Public/Startseite.vue'));

    expect($source)->toContain('gap-y-2 pt-7')
        ->and($source)->toContain('pb-12 pt-16 md:px-6')
        ->and($source)->toContain('lg:pb-16 lg:pt-24');
});

it('sets the photograph beside the text on a phone, not adrift in the middle', function () {
    $source = file_get_contents(resource_path('js/Pages/Public/Startseite.vue'));

    // Centred under a column of left-aligned text it read as something that had
    // fallen there. Centring is for the wide screen, where the column is wider
    // than the picture.
    expect($source)->toContain('relative max-w-[220px] lg:mx-auto lg:max-w-(--hero-w)');
});

describe('the hero photograph on a phone', function () {
    it('stays hidden until somebody switches it on', function () {
        // It has always been desktop-only. A switch that turned it on the moment
        // this shipped would be a change nobody asked for, so an unset switch
        // has to mean off here rather than the usual on.
        $source = file_get_contents(resource_path('js/Pages/Public/Startseite.vue'));

        expect($source)->toContain("isOn(props.content, 'hero', 'bild_mobil', false)")
            ->and(Content::bool('startseite.hero.bild_mobil', true))->toBeFalse();
    });

    it('is a switch the operator can turn on', function () {
        $block = ContentBlock::where('page_key', 'startseite')
            ->where('section_key', 'hero')
            ->where('field_key', 'bild_mobil')
            ->first();

        expect($block)->not->toBeNull()
            ->and($block->type)->toBe('boolean');

        $block->update(['value' => '1']);
        Content::flush('startseite');

        expect(Content::page('startseite')['hero']['bild_mobil'])->toBeTrue();
    });

    it('is drawn small, and never in front of the request box', function () {
        $source = file_get_contents(resource_path('js/Pages/Public/Startseite.vue'));

        // Capped on a phone and only sized by the operator from lg up: an
        // inline max-width would beat the class and there would be no way to
        // hold it smaller on a phone.
        expect($source)->toContain('max-w-[220px]')
            ->and($source)->toContain('lg:max-w-(--hero-w)')
            ->and($source)->toContain("'--hero-w'");

        // The image column comes after the text column in the markup, so on one
        // column it lands under the request box rather than above it.
        expect(strpos($source, 'RequestStarter'))->toBeLessThan(strpos($source, 'heroOnMobile'));
    });
});
