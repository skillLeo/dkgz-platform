<?php

namespace App\Actions;

use App\Models\Assessor;
use App\Support\ImagePipeline;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

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

    public function execute(Assessor $assessor, UploadedFile $file): string
    {
        // Centre-cropped to a square: a portrait shown in a circle looks wrong
        // at any other aspect ratio, and cropping here beats cropping in CSS on
        // every surface that displays it. The pipeline handles the colour
        // profile, the rotation flag and the EXIF block.
        $binary = ImagePipeline::encode($file, square: self::EDGE);

        $path = 'sachverstaendige/'.$assessor->id.'/'.bin2hex(random_bytes(12)).'.webp';

        Storage::disk('public')->put($path, $binary);

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
