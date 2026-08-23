<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Imagick;
use ImagickPixel;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;
use RuntimeException;
use Throwable;

/**
 * Turns whatever somebody uploaded into something a browser renders correctly.
 *
 * The colours were the problem. A photograph off a modern phone or camera
 * carries an ICC profile — Display P3, Adobe RGB, sometimes CMYK from a design
 * tool — and GD neither reads nor writes those. It hands the raw numbers
 * through and the browser interprets them as sRGB, which is why an upload came
 * out flat, oversaturated, or with the reds gone orange.
 *
 * Where ImageMagick is available the profile is converted to sRGB properly
 * before anything else happens, which is the only way to preserve how the
 * picture actually looked. GD remains the fallback so the feature still works
 * on a host without it; the result is merely no worse than before.
 */
class ImagePipeline
{
    /** Anything larger is a waste of everybody's bandwidth. */
    public const MAX_EDGE = 2000;

    private const ALLOWED = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

    /**
     * Re-encodes to sRGB WebP, optionally cropped to a square.
     *
     * @param  int|null  $square  Centre-crop to this edge length, or null to
     *   keep the aspect ratio and merely bound the longest edge.
     */
    public static function encode(UploadedFile $file, ?int $square = null, int $quality = 85): string
    {
        if (! $file->isValid()) {
            throw new RuntimeException('Die Datei konnte nicht gelesen werden.');
        }

        if (! in_array($file->getMimeType(), self::ALLOWED, true)) {
            throw new RuntimeException(
                'Bitte laden Sie ein Bild im Format JPG, PNG oder WebP hoch. '
                .'Fotos vom iPhone sind oft HEIC — stellen Sie in den Kameraeinstellungen '
                .'auf „Maximale Kompatibilität" um oder speichern Sie das Bild vorher als JPG.'
            );
        }

        if (extension_loaded('imagick')) {
            try {
                return self::withImagick($file->getRealPath(), $square, $quality);
            } catch (Throwable) {
                // Fall through: a picture that ImageMagick chokes on is still
                // better served by GD than by an error page.
            }
        }

        return self::withGd($file->getRealPath(), $square, $quality);
    }

    private static function withImagick(string $path, ?int $square, int $quality): string
    {
        $image = new Imagick($path);

        // Orientation first: a phone photograph is usually stored sideways with
        // a flag saying which way up, and stripping the flag without applying
        // it turns the picture on its side.
        self::applyOrientation($image);

        // The colour fix. transformImageColorspace converts *through* whatever
        // profile the file carries, so a Display P3 or CMYK original arrives in
        // sRGB looking like itself. Simply stripping the profile — which is
        // what GD effectively does — leaves the original numbers to be read as
        // sRGB, and that is what moved the colours.
        $image->transformImageColorspace(Imagick::COLORSPACE_SRGB);

        // Flatten onto white: a transparent PNG saved as WebP keeps its alpha,
        // but a CMYK or palette image can otherwise come out with a black
        // background.
        if ($image->getImageAlphaChannel()) {
            $image->setImageBackgroundColor(new ImagickPixel('white'));
        }

        if ($square !== null) {
            $image->cropThumbnailImage($square, $square);
        } else {
            $image->thumbnailImage(self::MAX_EDGE, self::MAX_EDGE, true);
        }

        // Every remaining profile and EXIF block goes, including the GPS
        // coordinates a phone writes into a photograph.
        $image->stripImage();

        $image->setImageFormat('webp');
        $image->setImageCompressionQuality($quality);

        $blob = $image->getImageBlob();

        $image->clear();
        $image->destroy();

        return $blob;
    }

    /** Applies the EXIF orientation flag, then forgets it. */
    private static function applyOrientation(Imagick $image): void
    {
        match ($image->getImageOrientation()) {
            Imagick::ORIENTATION_BOTTOMRIGHT => $image->rotateImage(new ImagickPixel, 180),
            Imagick::ORIENTATION_RIGHTTOP => $image->rotateImage(new ImagickPixel, 90),
            Imagick::ORIENTATION_LEFTBOTTOM => $image->rotateImage(new ImagickPixel, -90),
            default => null,
        };

        $image->setImageOrientation(Imagick::ORIENTATION_TOPLEFT);
    }

    private static function withGd(string $path, ?int $square, int $quality): string
    {
        $image = ImageManager::usingDriver(new GdDriver)->decodePath($path);

        $square !== null
            ? $image->cover($square, $square)
            : $image->scaleDown(self::MAX_EDGE, self::MAX_EDGE);

        return (string) $image->encode(new WebpEncoder(quality: $quality));
    }
}
