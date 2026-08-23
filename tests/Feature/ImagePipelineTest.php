<?php

use App\Support\ImagePipeline;
use Illuminate\Http\UploadedFile;

/**
 * Uploaded pictures came out with their colours shifted. A photograph off a
 * modern phone carries an ICC profile — Display P3, Adobe RGB, sometimes CMYK
 * from a design tool — and GD neither reads nor writes those: it passes the raw
 * numbers through for the browser to read as sRGB, which is exactly how a red
 * turns orange.
 */
it('produces a WebP a browser can read', function () {
    $binary = ImagePipeline::encode(UploadedFile::fake()->image('foto.jpg', 1200, 900));

    expect(substr($binary, 0, 4))->toBe('RIFF')
        ->and(substr($binary, 8, 4))->toBe('WEBP');
});

it('crops to a square when one is asked for', function () {
    $binary = ImagePipeline::encode(
        UploadedFile::fake()->image('portrait.jpg', 1200, 1600),
        square: 512,
    );

    $size = getimagesizefromstring($binary);

    expect($size[0])->toBe(512)->and($size[1])->toBe(512);
});

it('bounds a very large picture instead of storing it whole', function () {
    $binary = ImagePipeline::encode(UploadedFile::fake()->image('riesig.jpg', 5000, 3000));

    $size = getimagesizefromstring($binary);

    expect(max($size[0], $size[1]))->toBeLessThanOrEqual(ImagePipeline::MAX_EDGE);
});

it('keeps the aspect ratio when not cropping', function () {
    $binary = ImagePipeline::encode(UploadedFile::fake()->image('quer.jpg', 3000, 1500));

    $size = getimagesizefromstring($binary);

    expect(round($size[0] / $size[1], 2))->toBe(2.0);
});

it('refuses a format the web cannot display, and says why', function () {
    expect(fn () => ImagePipeline::encode(
        UploadedFile::fake()->create('IMG_4821.HEIC', 2400, 'image/heic')
    ))->toThrow(RuntimeException::class, 'HEIC');
});

it('carries no EXIF into the stored file', function () {
    $binary = ImagePipeline::encode(UploadedFile::fake()->image('foto.jpg', 800, 600));

    // A phone writes GPS coordinates into that block; none of it survives.
    expect($binary)->not->toContain('Exif')
        ->and($binary)->not->toContain('GPS');
});
