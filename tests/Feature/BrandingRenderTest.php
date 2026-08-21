<?php

/**
 * The uploaded logo has to appear on every shell that carries the wordmark.
 *
 * Upload worked, storage worked, the URL resolved — and the logo appeared
 * nowhere, because the public site, the admin panel and the portal each drew
 * their own DKGZ lockup and none of them read the branding settings. No test
 * that stops at "the page renders" would have noticed, so this one searches the
 * rendered markup for the file itself.
 */
it('shows the uploaded logo on every shell that carries the wordmark', function () {
    exec('node --version 2>/dev/null', $out, $code);

    if ($code !== 0) {
        $this->markTestSkipped('Node ist auf diesem System nicht verfügbar.');
    }

    exec(sprintf(
        'cd %s && npx vite build --config vite.ssr.config.js 2>&1',
        escapeshellarg(base_path())
    ), $buildOutput, $buildCode);

    expect($buildCode)->toBe(0, "SSR-Build fehlgeschlagen:\n".implode("\n", $buildOutput));

    exec(sprintf(
        'cd %s && node tests/Js/branding-runner.mjs 2>&1',
        escapeshellarg(base_path())
    ), $output, $exitCode);

    $report = implode("\n", $output);

    expect($exitCode)->toBe(0, $report);
    expect($report)->toContain('0 ohne Logo');
})->group('js');
