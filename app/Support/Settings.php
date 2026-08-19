<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Cached read/write access to the settings table.
 *
 * The whole table is loaded once per request cycle and cached for an hour —
 * on a 128M shared host that is far cheaper than a query per lookup, and the
 * table is small by design. Any write busts the cache.
 */
class Settings
{
    public const CACHE_KEY = 'dkgz.settings';

    public const CACHE_TTL_MINUTES = 60;

    /** @var array<string, mixed>|null */
    private static ?array $memo = null;

    /** False before the table has been migrated, so boot code can bail out. */
    public static function isAvailable(): bool
    {
        try {
            return Schema::hasTable('settings');
        } catch (Throwable) {
            return false;
        }
    }

    /** @return array<string, mixed> */
    public static function all(): array
    {
        if (self::$memo !== null) {
            return self::$memo;
        }

        if (! self::isAvailable()) {
            return self::$memo = [];
        }

        return self::$memo = Cache::remember(
            self::CACHE_KEY,
            now()->addMinutes(self::CACHE_TTL_MINUTES),
            fn () => Setting::all()
                ->mapWithKeys(fn (Setting $s) => [$s->key => $s->typedValue()])
                ->all()
        );
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $value = self::all()[$key] ?? null;

        return $value === null || $value === '' ? $default : $value;
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $value = self::all()[$key] ?? null;

        return $value === null ? $default : (bool) $value;
    }

    public static function int(string $key, int $default = 0): int
    {
        $value = self::all()[$key] ?? null;

        return $value === null || $value === '' ? $default : (int) $value;
    }

    public static function float(string $key, float $default = 0.0): float
    {
        $value = self::all()[$key] ?? null;

        return $value === null || $value === '' ? $default : (float) $value;
    }

    /** @return array<string, mixed> */
    public static function group(string $group): array
    {
        if (! self::isAvailable()) {
            return [];
        }

        return Setting::where('group', $group)
            ->orderBy('sort_order')
            ->get()
            ->mapWithKeys(fn (Setting $s) => [$s->key => $s->typedValue()])
            ->all();
    }

    public static function set(string $key, mixed $value): void
    {
        $setting = Setting::where('key', $key)->first();

        if ($setting === null) {
            return;
        }

        $setting->writeValue($value);
        $setting->save();

        self::flush();
    }

    /** @param array<string, mixed> $values */
    public static function setMany(array $values): void
    {
        $settings = Setting::whereIn('key', array_keys($values))->get()->keyBy('key');

        foreach ($values as $key => $value) {
            $setting = $settings->get($key);

            if ($setting === null) {
                continue;
            }

            // A blank submission never wipes a stored secret: the UI shows a
            // mask, so an empty field means "leave it alone".
            if ($setting->isSecret() && ($value === null || $value === '')) {
                continue;
            }

            $setting->writeValue($value);
            $setting->save();
        }

        self::flush();
    }

    public static function flush(): void
    {
        self::$memo = null;
        Cache::forget(self::CACHE_KEY);
    }

    /** The commission rate, never hardcoded anywhere else. */
    public static function commissionRate(): float
    {
        return self::float('business.commission_rate', 15.00);
    }
}
