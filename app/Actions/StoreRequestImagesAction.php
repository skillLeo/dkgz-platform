<?php

namespace App\Actions;

use App\Models\RequestImage;
use App\Models\ServiceRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;

/**
 * Re-encodes every uploaded photo through Intervention rather than storing what
 * was sent. That normalises the format, caps the dimensions for a 128M host,
 * and drops EXIF — which on a phone photo carries GPS coordinates.
 */
class StoreRequestImagesAction
{
    private const MAX_EDGE = 2000;

    /** @param array<int, UploadedFile> $files */
    public function execute(ServiceRequest $request, array $files): int
    {
        $manager = ImageManager::usingDriver(new GdDriver);
        $stored = 0;

        foreach ($files as $file) {
            if (! $file instanceof UploadedFile || ! $file->isValid()) {
                continue;
            }

            // Trust the file's actual content, never its extension.
            $mime = $file->getMimeType();

            if (! in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
                continue;
            }

            $image = $manager->decodePath($file->getRealPath());

            if ($image->width() > self::MAX_EDGE || $image->height() > self::MAX_EDGE) {
                $image->scaleDown(self::MAX_EDGE, self::MAX_EDGE);
            }

            $encoded = $image->encode(new WebpEncoder(quality: 82));
            $path = 'anfragen/'.$request->id.'/'.bin2hex(random_bytes(12)).'.webp';

            Storage::disk('private')->put($path, (string) $encoded);

            RequestImage::create([
                'service_request_id' => $request->id,
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime' => 'image/webp',
                'size_bytes' => strlen((string) $encoded),
            ]);

            $stored++;
        }

        return $stored;
    }
}
