<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * A picture an operator uploaded, kept on the public disk.
 *
 * The content editor, the service types and the cities all store pictures the
 * same way: re-encoded, under a name nobody can guess, and deleted when they are
 * replaced. One place for that, so a fourth kind of picture cannot quietly skip
 * the part that strips a phone's GPS coordinates.
 */
class StoredImage
{
    /** The rules every picture upload is validated against. */
    public const RULES = ['required', 'file', 'mimes:png,jpg,jpeg,webp', 'max:12288'];

    /**
     * Re-encodes the upload and stores it, returning the stored path.
     *
     * Re-encoded rather than stored as uploaded: converts the colour profile to
     * sRGB so the picture on the page looks like the picture the operator chose,
     * applies the phone's rotation flag, bounds the size, and drops the EXIF
     * block including its GPS coordinates.
     *
     * @throws RuntimeException when the file cannot be read as a picture
     */
    public static function store(UploadedFile $file, string $directory): string
    {
        $binary = ImagePipeline::encode($file);

        $path = trim($directory, '/').'/'.bin2hex(random_bytes(12)).'.webp';

        Storage::disk('public')->put($path, $binary);

        return $path;
    }

    /**
     * Size and dimensions of the stored file, so the editor can see what is
     * actually there rather than only that something is.
     *
     * @return array<string, mixed>|null
     */
    public static function meta(?string $path): ?array
    {
        if (blank($path) || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        $meta = [
            'name' => basename($path),
            'size_label' => Formatter::fileSize(Storage::disk('public')->size($path)),
            'dimensions' => null,
        ];

        $dimensions = @getimagesize(Storage::disk('public')->path($path));

        if ($dimensions !== false) {
            $meta['dimensions'] = $dimensions[0].' × '.$dimensions[1].' px';
        }

        return $meta;
    }

    /**
     * Removes a stored file, tolerating one that has already gone.
     *
     * Replacing a picture deletes the one it replaced. Without this every
     * correction leaves a file nobody can reach and nobody will ever clean.
     */
    public static function forget(?string $path): void
    {
        if (filled($path) && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
