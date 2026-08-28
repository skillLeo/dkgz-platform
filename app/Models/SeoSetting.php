<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Whether one page may be indexed.
 *
 * A row exists only for a page somebody has switched off, so the absence of a
 * row means yes — which keeps the table small and makes "indexed" the state
 * nobody has to maintain.
 */
class SeoSetting extends Model
{
    private const CACHE_KEY = 'dkgz.seo.noindex';

    protected $fillable = ['path', 'is_indexed'];

    protected function casts(): array
    {
        return ['is_indexed' => 'boolean'];
    }

    /** Every path the operator has switched off, read once per request. */
    public static function excluded(): array
    {
        return Cache::remember(
            self::CACHE_KEY,
            now()->addMinutes(30),
            fn () => static::where('is_indexed', false)->pluck('path')->all()
        );
    }

    public static function indexes(string $path): bool
    {
        return ! in_array($path, self::excluded(), true);
    }

    public static function set(string $path, bool $indexed): void
    {
        static::updateOrCreate(['path' => $path], ['is_indexed' => $indexed]);

        Cache::forget(self::CACHE_KEY);
    }
}
