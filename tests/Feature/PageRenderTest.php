<?php

/**
 * Renders every Inertia page through Vue's server renderer.
 *
 * The rest of the suite asserts which component a route returns and what props
 * it carries, but never mounts Vue. A template that references a prop a child
 * no longer accepts passes every other test here and breaks in the browser —
 * this is the only check that would catch it.
 */
it('renders every page component without throwing', function () {
    exec('node --version 2>/dev/null', $out, $code);

    if ($code !== 0) {
        $this->markTestSkipped('Node ist auf diesem System nicht verfügbar.');
    }

    $build = base_path('storage/framework/testing/render-entry.mjs');

    // Rebuilt every run: a stale bundle would report on yesterday's templates.
    exec(sprintf(
        'cd %s && npx vite build --config vite.ssr.config.js 2>&1',
        escapeshellarg(base_path())
    ), $buildOutput, $buildCode);

    expect($buildCode)->toBe(0, "SSR-Build fehlgeschlagen:\n".implode("\n", $buildOutput));
    expect(file_exists($build))->toBeTrue();

    exec(sprintf(
        'cd %s && node tests/Js/render-runner.mjs 2>&1',
        escapeshellarg(base_path())
    ), $output, $exitCode);

    expect($exitCode)->toBe(0, implode("\n", $output));
    expect(implode("\n", $output))->toContain('0 fehlgeschlagen');
})->group('js');
