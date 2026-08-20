<?php

use App\Models\Invitation;
use App\Models\User;
use App\Support\InvitationImport;
use Database\Seeders\EmailTemplateSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Http\UploadedFile;

function csv(string $contents): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'liste').'.csv';
    file_put_contents($path, $contents);

    return new UploadedFile($path, 'partner.csv', 'text/csv', null, true);
}

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(SettingsSeeder::class);
    $this->seed(EmailTemplateSeeder::class);

    $this->admin = User::factory()->create(['is_active' => true]);
    $this->admin->assignRole('admin');
});

describe('reading the file', function () {
    it('reads a bare list with no heading row', function () {
        $preview = InvitationImport::preview(csv("erste@beispiel.de\nzweite@beispiel.de\n"));

        expect($preview['counts']['neu'])->toBe(2)
            ->and(collect($preview['rows'])->pluck('email')->all())
            ->toBe(['erste@beispiel.de', 'zweite@beispiel.de']);
    });

    it('reads semicolons and headings, as German Excel writes them', function () {
        $preview = InvitationImport::preview(csv(
            "Firma;E-Mail;Ort\nGutachten Nord;nord@beispiel.de;Kiel\nKfz Süd;sued@beispiel.de;München\n"
        ));

        expect($preview['counts']['neu'])->toBe(2)
            ->and($preview['rows'][0]['name'])->toBe('Gutachten Nord')
            ->and($preview['rows'][0]['email'])->toBe('nord@beispiel.de');
    });

    it('survives a UTF-8 byte order mark, which Excel adds', function () {
        $preview = InvitationImport::preview(csv("\xEF\xBB\xBFE-Mail\nmitbom@beispiel.de\n"));

        expect($preview['counts']['neu'])->toBe(1)
            ->and($preview['rows'][0]['email'])->toBe('mitbom@beispiel.de');
    });

    it('finds the address wherever it sits when headings do not match', function () {
        $preview = InvitationImport::preview(csv("Spalte A,Spalte B\nGutachten West,west@beispiel.de\n"));

        expect($preview['rows'][0]['email'])->toBe('west@beispiel.de');
    });
});

describe('the preview', function () {
    it('separates new addresses from ones that would be skipped', function () {
        User::factory()->create(['email' => 'schon@beispiel.de']);
        Invitation::create([
            'email' => 'offen@beispiel.de', 'role' => 'assessor',
            'token' => Invitation::generateToken(), 'expires_at' => now()->addDays(14),
        ]);

        $preview = InvitationImport::preview(csv(
            "neu@beispiel.de\nschon@beispiel.de\noffen@beispiel.de\nneu@beispiel.de\nkaputt@\n"
        ));

        expect($preview['counts'])
            ->toMatchArray(['neu' => 1, 'vorhanden' => 1, 'eingeladen' => 1, 'doppelt' => 1, 'ungueltig' => 1]);
    });

    it('ignores a line that is not an attempt at an address', function () {
        // A stray line with no @ is not a malformed address, it is not a row.
        $preview = InvitationImport::preview(csv("gut@beispiel.de\nÜberschrift ohne Adresse\n"));

        expect($preview['counts']['total'])->toBe(1)
            ->and($preview['counts']['ungueltig'])->toBe(0);
    });

    it('sends nothing at all', function () {
        InvitationImport::preview(csv("niemand@beispiel.de\n"));

        expect(Invitation::count())->toBe(0);
    });
});

describe('sending', function () {
    it('creates one invitation per address with the shared message', function () {
        $this->actingAs($this->admin)->post('/admin/einladungen/import', [
            'emails' => ['eins@beispiel.de', 'zwei@beispiel.de'],
            'message' => 'Carspector erweitert sein Angebot.',
        ])->assertSessionHasNoErrors();

        expect(Invitation::count())->toBe(2)
            ->and(Invitation::first()->message)->toBe('Carspector erweitert sein Angebot.')
            ->and(Invitation::first()->role)->toBe('assessor');
    });

    it('re-checks addresses at send time rather than trusting the preview', function () {
        User::factory()->create(['email' => 'zwischenzeitlich@beispiel.de']);

        $this->actingAs($this->admin)->post('/admin/einladungen/import', [
            'emails' => ['frisch@beispiel.de', 'zwischenzeitlich@beispiel.de'],
        ]);

        expect(Invitation::count())->toBe(1)
            ->and(Invitation::first()->email)->toBe('frisch@beispiel.de');
    });

    it('refuses a role without permission to invite', function () {
        $support = User::factory()->create(['is_active' => true]);
        $support->assignRole('support');

        $this->actingAs($support)->post('/admin/einladungen/import', [
            'emails' => ['jemand@beispiel.de'],
        ])->assertForbidden();

        expect(Invitation::count())->toBe(0);
    });
});
