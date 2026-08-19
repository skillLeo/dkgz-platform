<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/**
 * Public asset URLs that survive a host with symlinks disabled.
 *
 * storage:link is attempted during deployment, but on some shared hosting it
 * silently does nothing. When public/storage is not a working link, URLs fall
 * back to a controller route that streams the file instead.
 */
class SafeStorage
{
    private static ?bool $linkWorks = null;

    public static function url(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        // An absolute URL was stored (or a seeded default) — pass it through.
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '/')) {
            return $path;
        }

        return self::symlinkWorks()
            ? Storage::disk('public')->url($path)
            : route('media.show', ['path' => $path]);
    }

    public static function symlinkWorks(): bool
    {
        if (self::$linkWorks !== null) {
            return self::$linkWorks;
        }

        $link = public_path('storage');

        return self::$linkWorks = (is_link($link) || is_dir($link)) && is_readable($link);
    }

    /** Test seam — lets a test force either branch. */
    public static function fakeSymlinkState(?bool $works): void
    {
        self::$linkWorks = $works;
    }
}
