<?php

namespace App\Actions;

use App\Models\Assessor;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;
use RuntimeException;

/**
 * Stores an assessor's portrait.
 *
 * Re-encoded rather than stored as uploaded: that strips EXIF — which on a
 * phone photograph carries the GPS coordinates of wherever it was taken — and
 * guarantees the customer's mail client receives a format it can display.
 *
 * The file lands on the public disk because it is shown to customers in the
 * confirmation e-mail, where a signed private URL would not survive.
 */
class StoreAssessorPhotoAction
{
    /** Square, and large enough for a retina display at the size it is shown. */
    private const EDGE = 512;

    private const ALLOWED = ['image/jpeg', 'image/png', 'image/webp'];

    public function execute(Assessor $assessor, UploadedFile $file): string
    {
        if (! $file->isValid()) {
            throw new RuntimeException('Die Datei konnte nicht gelesen werden.');
        }

        // Trust the content, never the extension.
        if (! in_array($file->getMimeType(), self::ALLOWED, true)) {
            throw new RuntimeException('Bitte laden Sie ein Bild im Format JPG, PNG oder WebP hoch.');
        }

        $image = ImageManager::usingDriver(new GdDriver)->decodePath($file->getRealPath());

        // Centre-cropped to a square: a portrait shown in a circle looks wrong
        // at any other aspect ratio, and cropping here beats cropping in CSS
        // on every surface that displays it.
        $image->cover(self::EDGE, self::EDGE);

        $path = 'sachverstaendige/'.$assessor->id.'/'.bin2hex(random_bytes(12)).'.webp';

        Storage::disk('public')->put($path, (string) $image->encode(new WebpEncoder(quality: 85)));

        $this->forget($assessor->photo_path);

        $assessor->update(['photo_path' => $path]);

        return $path;
    }

    public function remove(Assessor $assessor): void
    {
        $this->forget($assessor->photo_path);

        $assessor->update(['photo_path' => null]);
    }

    /** Replacing or removing deletes the file it replaced; no orphans. */
    private function forget(?string $path): void
    {
        if (filled($path) && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
