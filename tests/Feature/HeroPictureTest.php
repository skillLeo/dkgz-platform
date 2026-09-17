<?php

use App\Models\City;
use App\Models\ContentBlock;
use App\Models\ServiceType;
use App\Models\User;
use App\Support\Content;
use App\Support\SafeStorage;
use Database\Seeders\ContentBlockSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * The picture beside the headline on the service and city pages.
 *
 * Those pages had an empty half next to their headline. Each now takes the most
 * specific picture there is — the service's or the city's own, then the default
 * for that kind of page, then the homepage's — so none of them is ever empty and
 * pictures can be added one at a time.
 */
beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(ContentBlockSeeder::class);
    Storage::fake('public');
    SafeStorage::fakeSymlinkState(true);

    $this->admin = User::factory()->create(['is_active' => true]);
    $this->admin->assignRole('admin');

    $this->service = ServiceType::factory()->create(['name_de' => 'Unfallgutachten', 'is_active' => true]);
    $this->city = City::create(['name' => 'Düsseldorf', 'is_active' => true]);
    $this->city->serviceTypes()->attach($this->service->id);
});

afterEach(fn () => SafeStorage::fakeSymlinkState(null));

/** Sets a content block's picture as the upload endpoint would. */
function setDefaultPicture(string $page, string $section, string $path): void
{
    setBlock($page, $section, 'bild', $path);
}

function setBlock(string $page, string $section, string $field, string $value): void
{
    ContentBlock::where(['page_key' => $page, 'section_key' => $section, 'field_key' => $field])
        ->firstOrFail()
        ->update(['value' => $value]);

    Content::flush();
}

function homepagePicture(): string
{
    return Content::get('startseite.hero.bild');
}

function cityServiceUrl(): string
{
    return '/kfz-gutachter/'.test()->city->slug.'/'.test()->service->slug;
}

describe('a service page', function () {
    it('shows the homepage picture while nothing else is set', function () {
        $this->get("/leistungen/{$this->service->slug}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('picture.src', homepagePicture()));
    });

    it('shows the default for service pages once there is one', function () {
        setDefaultPicture('leistungen', 'detail', 'inhalte/leistungen-standard.webp');

        $this->get("/leistungen/{$this->service->slug}")
            ->assertInertia(fn ($page) => $page->where('picture.src', Storage::disk('public')->url('inhalte/leistungen-standard.webp')));
    });

    it('shows its own picture above everything else', function () {
        setDefaultPicture('leistungen', 'detail', 'inhalte/leistungen-standard.webp');
        $this->service->update(['image_path' => 'leistungen/eigenes.webp']);

        $this->get("/leistungen/{$this->service->slug}")
            ->assertInertia(fn ($page) => $page->where('picture.src', Storage::disk('public')->url('leistungen/eigenes.webp')));
    });

    it('takes its size and the seal from the homepage', function () {
        $this->get("/leistungen/{$this->service->slug}")
            ->assertInertia(fn ($page) => $page
                ->where('picture.seal_title', Content::get('startseite.hero.siegel_titel'))
                ->where('picture.on_mobile', false)
                ->has('picture.size'));
    });
});

describe('a city page', function () {
    it('shows the homepage picture while nothing else is set', function () {
        $this->get("/kfz-gutachter/{$this->city->slug}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('picture.src', homepagePicture()));
    });

    it('shows the default for city pages, then its own', function () {
        setDefaultPicture('staedte', 'stadt', 'inhalte/stadt-standard.webp');

        $this->get("/kfz-gutachter/{$this->city->slug}")
            ->assertInertia(fn ($page) => $page->where('picture.src', Storage::disk('public')->url('inhalte/stadt-standard.webp')));

        $this->city->update(['image_path' => 'staedte/duesseldorf.webp']);

        $this->get("/kfz-gutachter/{$this->city->slug}")
            ->assertInertia(fn ($page) => $page->where('picture.src', Storage::disk('public')->url('staedte/duesseldorf.webp')));
    });

    it('is not given the service picture', function () {
        $this->service->update(['image_path' => 'leistungen/eigenes.webp']);

        $this->get("/kfz-gutachter/{$this->city->slug}")
            ->assertInertia(fn ($page) => $page->where('picture.src', homepagePicture()));
    });
});

describe('a page for one service in one city', function () {
    it('shows the homepage picture while nothing else is set', function () {
        $this->get(cityServiceUrl())
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('picture.src', homepagePicture()));
    });

    it('shows its own default before the homepage picture', function () {
        setDefaultPicture('staedte', 'leistung', 'inhalte/stadt-leistung-standard.webp');

        $this->get(cityServiceUrl())
            ->assertInertia(fn ($page) => $page->where('picture.src', Storage::disk('public')->url('inhalte/stadt-leistung-standard.webp')));
    });

    it('shows the city picture before the default', function () {
        setDefaultPicture('staedte', 'leistung', 'inhalte/stadt-leistung-standard.webp');
        $this->city->update(['image_path' => 'staedte/duesseldorf.webp']);

        $this->get(cityServiceUrl())
            ->assertInertia(fn ($page) => $page->where('picture.src', Storage::disk('public')->url('staedte/duesseldorf.webp')));
    });

    it('shows the service picture before the city picture', function () {
        $this->city->update(['image_path' => 'staedte/duesseldorf.webp']);
        $this->service->update(['image_path' => 'leistungen/eigenes.webp']);

        $this->get(cityServiceUrl())
            ->assertInertia(fn ($page) => $page->where('picture.src', Storage::disk('public')->url('leistungen/eigenes.webp')));
    });
});

it('draws the picture on all three pages', function () {
    foreach (['Leistung', 'Stadt', 'StadtLeistung'] as $name) {
        expect(file_get_contents(resource_path("js/Pages/Public/{$name}.vue")))
            ->toContain('<HeroPicture')
            ->toContain('lg:grid-cols-[minmax(0,58fr)_minmax(0,42fr)]');
    }
});

describe('uploading a picture for a service', function () {
    it('stores it re-encoded and shows it in the panel', function () {
        $this->actingAs($this->admin)
            ->post("/admin/leistungsarten/{$this->service->id}/bild", [
                'image' => UploadedFile::fake()->image('unfall.jpg', 1200, 1500),
            ])
            ->assertSessionHasNoErrors();

        $path = $this->service->fresh()->image_path;

        expect($path)->toStartWith('leistungen/')->toEndWith('.webp');
        Storage::disk('public')->assertExists($path);

        $this->actingAs($this->admin)
            ->get('/admin/leistungsarten')
            ->assertInertia(fn ($page) => $page->where('serviceTypes.0.image_url', Storage::disk('public')->url($path)));
    });

    it('deletes the picture it replaces', function () {
        $this->actingAs($this->admin)->post("/admin/leistungsarten/{$this->service->id}/bild", [
            'image' => UploadedFile::fake()->image('erste.jpg'),
        ]);
        $first = $this->service->fresh()->image_path;

        $this->actingAs($this->admin)->post("/admin/leistungsarten/{$this->service->id}/bild", [
            'image' => UploadedFile::fake()->image('zweite.jpg'),
        ]);

        Storage::disk('public')->assertMissing($first);
        Storage::disk('public')->assertExists($this->service->fresh()->image_path);
    });

    it('removes it, and the page falls back again', function () {
        $this->actingAs($this->admin)->post("/admin/leistungsarten/{$this->service->id}/bild", [
            'image' => UploadedFile::fake()->image('unfall.jpg'),
        ]);
        $path = $this->service->fresh()->image_path;

        $this->actingAs($this->admin)
            ->delete("/admin/leistungsarten/{$this->service->id}/bild")
            ->assertSessionHasNoErrors();

        expect($this->service->fresh()->image_path)->toBeNull();
        Storage::disk('public')->assertMissing($path);

        $this->get("/leistungen/{$this->service->slug}")
            ->assertInertia(fn ($page) => $page->where('picture.src', homepagePicture()));
    });

    it('refuses a file that is not a picture', function () {
        $this->actingAs($this->admin)
            ->post("/admin/leistungsarten/{$this->service->id}/bild", [
                'image' => UploadedFile::fake()->create('schaden.pdf', 100, 'application/pdf'),
            ])
            ->assertSessionHasErrors('image');

        expect($this->service->fresh()->image_path)->toBeNull();
    });

    it('is not something the ordinary edit form can set', function () {
        $this->actingAs($this->admin)->post("/admin/leistungsarten/{$this->service->id}", [
            'name_de' => 'Unfallgutachten',
            'is_active' => true,
            'dkgz_fee_cents' => 7900,
            'image_path' => 'fremd/datei.webp',
        ]);

        expect($this->service->fresh()->image_path)->toBeNull();
    });

    it('takes its picture with it when the service is deleted', function () {
        $service = ServiceType::factory()->create(['is_active' => false]);

        $this->actingAs($this->admin)->post("/admin/leistungsarten/{$service->id}/bild", [
            'image' => UploadedFile::fake()->image('weg.jpg'),
        ]);
        $path = $service->fresh()->image_path;

        $service->fresh()->delete();

        Storage::disk('public')->assertMissing($path);
    });
});

describe('uploading a picture for a city', function () {
    it('stores it and shows it on the city page', function () {
        $this->actingAs($this->admin)
            ->post("/admin/staedte/{$this->city->id}/bild", [
                'image' => UploadedFile::fake()->image('duesseldorf.jpg', 1200, 1500),
            ])
            ->assertSessionHasNoErrors();

        $path = $this->city->fresh()->image_path;

        expect($path)->toStartWith('staedte/');
        Storage::disk('public')->assertExists($path);

        $this->get("/kfz-gutachter/{$this->city->slug}")
            ->assertInertia(fn ($page) => $page->where('picture.src', Storage::disk('public')->url($path)));
    });

    it('removes it again', function () {
        $this->actingAs($this->admin)->post("/admin/staedte/{$this->city->id}/bild", [
            'image' => UploadedFile::fake()->image('duesseldorf.jpg'),
        ]);
        $path = $this->city->fresh()->image_path;

        $this->actingAs($this->admin)
            ->delete("/admin/staedte/{$this->city->id}/bild")
            ->assertSessionHasNoErrors();

        expect($this->city->fresh()->image_path)->toBeNull();
        Storage::disk('public')->assertMissing($path);
    });

    it('refuses somebody without the permission', function () {
        $editor = User::factory()->create(['is_active' => true]);
        $editor->assignRole('content_editor');

        $this->actingAs($editor)
            ->post("/admin/staedte/{$this->city->id}/bild", ['image' => UploadedFile::fake()->image('x.jpg')])
            ->assertForbidden();

        $this->actingAs($editor)
            ->post("/admin/leistungsarten/{$this->service->id}/bild", ['image' => UploadedFile::fake()->image('x.jpg')])
            ->assertForbidden();
    });
});

it('offers a default picture and its size for each kind of page in Seiteninhalte', function () {
    foreach ([['leistungen', 'detail'], ['staedte', 'stadt'], ['staedte', 'leistung']] as [$page, $section]) {
        expect(ContentBlock::where([
            'page_key' => $page, 'section_key' => $section, 'field_key' => 'bild', 'type' => 'image',
        ])->exists())->toBeTrue()
            ->and(ContentBlock::where([
                'page_key' => $page, 'section_key' => $section, 'field_key' => 'bild_groesse',
            ])->value('value'))->toBe('');
    }
});

describe('the size of a picture', function () {
    it('follows the homepage while nothing else is set', function () {
        setBlock('startseite', 'hero', 'bild_groesse', '93');

        $this->get("/kfz-gutachter/{$this->city->slug}")
            ->assertInertia(fn ($page) => $page->where('picture.size', '93'));
    });

    it('takes the size set for its kind of page', function () {
        setBlock('staedte', 'stadt', 'bild_groesse', '80');

        $this->get("/kfz-gutachter/{$this->city->slug}")
            ->assertInertia(fn ($page) => $page->where('picture.size', '80'));

        // Only that kind of page.
        $this->get("/leistungen/{$this->service->slug}")
            ->assertInertia(fn ($page) => $page->where('picture.size', Content::get('startseite.hero.bild_groesse')));
    });

    it('keeps the size set next to its own picture', function () {
        setBlock('staedte', 'stadt', 'bild_groesse', '80');
        $this->city->update(['image_path' => 'staedte/duesseldorf.webp', 'image_size' => 120]);

        $this->get("/kfz-gutachter/{$this->city->slug}")
            ->assertInertia(fn ($page) => $page->where('picture.size', '120'));
    });

    it('lets an own picture without a size take the size for its kind of page', function () {
        setBlock('leistungen', 'detail', 'bild_groesse', '110');
        $this->service->update(['image_path' => 'leistungen/eigenes.webp']);

        $this->get("/leistungen/{$this->service->slug}")
            ->assertInertia(fn ($page) => $page->where('picture.size', '110'));
    });

    it('goes with the city picture onto a service page in that city', function () {
        setBlock('staedte', 'leistung', 'bild_groesse', '90');
        $this->city->update(['image_path' => 'staedte/duesseldorf.webp', 'image_size' => 125]);

        $this->get(cityServiceUrl())
            ->assertInertia(fn ($page) => $page->where('picture.size', '125'));
    });

    it('never reaches a page that is not showing its picture', function () {
        setBlock('staedte', 'leistung', 'bild_groesse', '90');
        $this->city->update(['image_path' => 'staedte/duesseldorf.webp', 'image_size' => 125]);
        $this->service->update(['image_path' => 'leistungen/eigenes.webp']);

        // The service picture wins here, and it has no size of its own.
        $this->get(cityServiceUrl())
            ->assertInertia(fn ($page) => $page->where('picture.size', '90'));
    });
});

describe('setting the size in the panel', function () {
    it('saves it with a city that has a picture', function () {
        $this->city->update(['image_path' => 'staedte/duesseldorf.webp']);

        $this->actingAs($this->admin)
            ->post("/admin/staedte/{$this->city->id}", ['name' => 'Düsseldorf', 'is_active' => true, 'image_size' => '115'])
            ->assertSessionHasNoErrors();

        expect($this->city->fresh()->image_size)->toBe(115);

        // Emptied again, it follows the page.
        $this->actingAs($this->admin)
            ->post("/admin/staedte/{$this->city->id}", ['name' => 'Düsseldorf', 'is_active' => true, 'image_size' => ''])
            ->assertSessionHasNoErrors();

        expect($this->city->fresh()->image_size)->toBeNull();
    });

    it('saves it with a service that has a picture', function () {
        $this->service->update(['image_path' => 'leistungen/eigenes.webp']);

        $this->actingAs($this->admin)
            ->post("/admin/leistungsarten/{$this->service->id}", [
                'name_de' => 'Unfallgutachten', 'is_active' => true, 'dkgz_fee_cents' => 7900, 'image_size' => '70',
            ])
            ->assertSessionHasNoErrors();

        expect($this->service->fresh()->image_size)->toBe(70);
    });

    it('refuses a size outside the range', function () {
        $this->city->update(['image_path' => 'staedte/duesseldorf.webp']);

        $this->actingAs($this->admin)
            ->post("/admin/staedte/{$this->city->id}", ['name' => 'Düsseldorf', 'is_active' => true, 'image_size' => '200'])
            ->assertSessionHasErrors('image_size');

        expect($this->city->fresh()->image_size)->toBeNull();
    });

    it('keeps no size for a city without a picture', function () {
        $this->actingAs($this->admin)
            ->post("/admin/staedte/{$this->city->id}", ['name' => 'Düsseldorf', 'is_active' => true, 'image_size' => '120'])
            ->assertSessionHasNoErrors();

        expect($this->city->fresh()->image_size)->toBeNull();
    });

    it('forgets the size along with the picture', function () {
        $this->actingAs($this->admin)->post("/admin/leistungsarten/{$this->service->id}/bild", [
            'image' => UploadedFile::fake()->image('unfall.jpg'),
        ]);
        $this->service->fresh()->update(['image_size' => 130]);

        $this->actingAs($this->admin)->delete("/admin/leistungsarten/{$this->service->id}/bild");

        expect($this->service->fresh()->image_size)->toBeNull();
    });

    it('sits inside the picture box on every screen that uploads one', function () {
        foreach (['Admin/Inhalte', 'Admin/Leistungsarten', 'Admin/Staedte'] as $screen) {
            expect(file_get_contents(resource_path("js/Pages/{$screen}.vue")))->toContain('<PictureSizeField');
        }

        $this->actingAs($this->admin)
            ->get('/admin/staedte')
            ->assertInertia(fn ($page) => $page->has('defaultImageSize')->has('cities.0.image_size'));
    });
});
