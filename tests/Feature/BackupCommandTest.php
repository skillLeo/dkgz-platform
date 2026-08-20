<?php

use Illuminate\Support\Facades\Storage;

it('writes a compressed backup to private storage', function () {
    Storage::fake('private');

    $this->artisan('dkgz:backup-database')->assertSuccessful();

    $files = Storage::disk('private')->files('backups');

    expect($files)->toHaveCount(1)
        ->and($files[0])->toEndWith('.gz')
        ->and(Storage::disk('private')->size($files[0]))->toBeGreaterThan(0);
});

it('keeps only the requested number of backups', function () {
    Storage::fake('private');

    foreach (range(1, 5) as $n) {
        Storage::disk('private')->put("backups/dkgz-2026-01-0{$n}-000000.sql.gz", 'alt');
    }

    $this->artisan('dkgz:backup-database --keep=3')->assertSuccessful();

    expect(Storage::disk('private')->files('backups'))->toHaveCount(3);
});

it('never exposes a backup over the web', function () {
    expect(config('filesystems.disks.private.visibility', 'private'))->not->toBe('public');
});
