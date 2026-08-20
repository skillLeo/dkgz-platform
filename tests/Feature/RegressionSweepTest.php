<?php

use Illuminate\Support\Facades\File;

/**
 * The acceptance deadline and the percentage commission were removed, and so
 * was the bottom tab bar. Removing an architecture leaves debris — a label, a
 * filter, a padding offset that no longer offsets anything — and that debris is
 * what a client notices first. This fails if any of it survives.
 */
function sourceFiles(array $roots, array $extensions): array
{
    $files = [];

    foreach ($roots as $root) {
        if (! File::isDirectory(base_path($root))) {
            continue;
        }

        foreach (File::allFiles(base_path($root)) as $file) {
            if (in_array($file->getExtension(), $extensions, true)) {
                $files[$file->getPathname()] = File::get($file->getPathname());
            }
        }
    }

    return $files;
}

it('has no acceptance-deadline wording left in any interface', function () {
    $offenders = [];

    foreach (sourceFiles(['resources/js', 'resources/views'], ['vue', 'js', 'php']) as $path => $contents) {
        foreach (['Frist zur Annahme', 'accept_deadline', 'Annahmefrist', 'Countdown'] as $needle) {
            if (str_contains($contents, $needle)) {
                $offenders[] = basename($path).' → '.$needle;
            }
        }
    }

    expect($offenders)->toBe([]);
});

it('shows no percentage commission rate anywhere it is not historical', function () {
    $offenders = [];

    foreach (sourceFiles(['resources/js'], ['vue']) as $path => $contents) {
        // Portal/Provisionen legitimately renders the rate on legacy rows.
        if (str_contains($path, 'Provisionen.vue')) {
            continue;
        }

        foreach (['Vermittlungsprovision 15', '15 % des', 'commissionRate'] as $needle) {
            if (str_contains($contents, $needle)) {
                $offenders[] = basename($path).' → '.$needle;
            }
        }
    }

    expect($offenders)->toBe([]);
});

it('no longer references the removed bottom tab bar', function () {
    $offenders = [];

    foreach (sourceFiles(['resources/js', 'resources/css'], ['vue', 'js', 'css']) as $path => $contents) {
        foreach (['MobileBottomNav', 'MobileMoreSheet', 'spacing-tabbar'] as $needle) {
            if (str_contains($contents, $needle)) {
                $offenders[] = basename($path).' → '.$needle;
            }
        }
    }

    expect($offenders)->toBe([]);
});

it('carries no hardcoded example in the old reference format', function () {
    $offenders = [];

    foreach (sourceFiles(['resources/js', 'resources/views', 'database/seeders', 'app'], ['vue', 'js', 'php']) as $path => $contents) {
        if (preg_match('/DKGZ-\d{4}-\d{4,5}/', $contents, $m)) {
            $offenders[] = basename($path).' → '.$m[0];
        }
    }

    expect($offenders)->toBe([]);
});
