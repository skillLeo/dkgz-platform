<?php

namespace App\Support;

use Carbon\CarbonInterface;
use DateTimeInterface;
use Illuminate\Support\Carbon;

/**
 * German output formatting. Must stay byte-identical to the useGermanFormat
 * composable on the Vue side — the two are covered by a shared test fixture.
 */
class Formatter
{
    /** 127550 → "1.275,50 €" */
    public static function money(?int $cents, bool $withSymbol = true): string
    {
        $cents ??= 0;

        $sign = $cents < 0 ? '-' : '';
        $abs = abs($cents);

        $formatted = number_format($abs / 100, 2, ',', '.');

        return $withSymbol ? "{$sign}{$formatted} €" : "{$sign}{$formatted}";
    }

    /** 127550 → "1.275,50" (for input fields) */
    public static function amount(?int $cents): string
    {
        return self::money($cents, withSymbol: false);
    }

    /** "1.275,50" | "1275,50" | "1275.50" → 127550 */
    public static function parseMoney(string|int|float|null $input): ?int
    {
        if ($input === null || $input === '') {
            return null;
        }

        if (is_int($input)) {
            return $input * 100;
        }

        $raw = trim((string) $input);
        $raw = str_replace(['€', ' ', "\u{00A0}", "\u{202F}"], '', $raw);

        // German notation: dot groups thousands, comma separates decimals.
        if (str_contains($raw, ',')) {
            $raw = str_replace('.', '', $raw);
            $raw = str_replace(',', '.', $raw);
        }

        if (! is_numeric($raw)) {
            return null;
        }

        return (int) round(((float) $raw) * 100);
    }

    /** → "17.08.2026" */
    public static function date(DateTimeInterface|CarbonInterface|string|null $value): string
    {
        if ($value === null) {
            return '';
        }

        return Carbon::parse($value)->format('d.m.Y');
    }

    /** → "17.08.2026, 14:32 Uhr" */
    public static function dateTime(DateTimeInterface|CarbonInterface|string|null $value): string
    {
        if ($value === null) {
            return '';
        }

        return Carbon::parse($value)->format('d.m.Y, H:i').' Uhr';
    }

    /** → "August" — named here so the output cannot vary with host locale data. */
    public static function monthName(DateTimeInterface|CarbonInterface|string|null $value): string
    {
        if ($value === null) {
            return '';
        }

        return [
            1 => 'Januar', 2 => 'Februar', 3 => 'März', 4 => 'April',
            5 => 'Mai', 6 => 'Juni', 7 => 'Juli', 8 => 'August',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Dezember',
        ][(int) Carbon::parse($value)->format('n')];
    }

    /**
     * → "vor 12 Min." / "vor 3 Std." / "vor 2 Tagen"
     *
     * Kept deliberately in step with relativeTime() in useGermanFormat.js;
     * FormatParityTest runs both against the same fixtures.
     */
    public static function relativeTime(DateTimeInterface|CarbonInterface|string|null $value, ?CarbonInterface $now = null): string
    {
        if ($value === null) {
            return '';
        }

        $moment = Carbon::parse($value);
        $now ??= Carbon::now();

        $seconds = (int) floor($now->getTimestamp() - $moment->getTimestamp());

        if ($seconds < 60) {
            return 'gerade eben';
        }

        $minutes = intdiv($seconds, 60);

        if ($minutes < 60) {
            return "vor {$minutes} Min.";
        }

        $hours = intdiv($minutes, 60);

        if ($hours < 24) {
            return "vor {$hours} Std.";
        }

        $days = intdiv($hours, 24);

        if ($days <= 7) {
            return $days === 1 ? 'vor 1 Tag' : "vor {$days} Tagen";
        }

        return self::date($moment);
    }

    /** → "heute 18:00" / "18.08. 12:00" — the acceptance deadline in a list. */
    public static function deadline(DateTimeInterface|CarbonInterface|string|null $value, ?CarbonInterface $now = null): string
    {
        if ($value === null) {
            return '';
        }

        $moment = Carbon::parse($value);
        $now ??= Carbon::now();
        $clock = $moment->format('H:i');

        if ($moment->isSameDay($now)) {
            return "heute {$clock}";
        }

        if ($moment->isSameDay($now->copy()->addDay())) {
            return "morgen {$clock}";
        }

        return $moment->format('d.m.').' '.$clock;
    }

    /** → "14:32 Uhr" */
    public static function time(DateTimeInterface|CarbonInterface|string|null $value): string
    {
        if ($value === null) {
            return '';
        }

        return Carbon::parse($value)->format('H:i').' Uhr';
    }

    /** "01794480169" → "+49 179 4480169" */
    public static function phone(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $digits = preg_replace('/[^0-9+]/', '', $value) ?? '';

        if (str_starts_with($digits, '+49')) {
            $national = substr($digits, 3);
        } elseif (str_starts_with($digits, '0049')) {
            $national = substr($digits, 4);
        } elseif (str_starts_with($digits, '0')) {
            $national = substr($digits, 1);
        } else {
            return $value;
        }

        if ($national === '') {
            return $value;
        }

        // The design renders every number as "+49 179 4480169" / "+49 211 4470012"
        // / "+49 421 5580120" — a three-digit prefix, then the subscriber number.
        // Two-digit metropolitan codes (30 Berlin, 40 Hamburg, 89 Munich, 69
        // Frankfurt, 21 …) keep their real length so the grouping stays truthful.
        $twoDigitAreaCodes = ['30', '40', '69', '89'];
        $prefixLength = in_array(substr($national, 0, 2), $twoDigitAreaCodes, true) ? 2 : 3;

        if (strlen($national) <= $prefixLength) {
            return '+49 '.$national;
        }

        $prefix = substr($national, 0, $prefixLength);
        $rest = substr($national, $prefixLength);

        return trim("+49 {$prefix} {$rest}");
    }

    /** 15.0 → "15 %" · 12.5 → "12,5 %" */
    public static function percent(float|string|null $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $number = (float) $value;
        $decimals = fmod($number, 1.0) === 0.0 ? 0 : 2;
        $formatted = number_format($number, $decimals, ',', '.');

        if ($decimals === 2) {
            $formatted = rtrim(rtrim($formatted, '0'), ',');
        }

        return "{$formatted} %";
    }

    /**
     * 2411724 → "2,4 MB" · 318000 → "318 KB"
     *
     * Decimal (SI) units, not binary. The design shows a 2411724-byte report as
     * "2,4 MB" and a 318000-byte invoice as "318 KB", which only holds at ÷1000.
     */
    public static function fileSize(?int $bytes): string
    {
        if ($bytes === null) {
            return '';
        }

        if ($bytes < 1000) {
            return "{$bytes} B";
        }

        $units = ['KB', 'MB', 'GB'];
        $value = $bytes / 1000;
        $unit = 0;

        while ($value >= 1000 && $unit < count($units) - 1) {
            $value /= 1000;
            $unit++;
        }

        // One decimal below 100, none above — "2,4 MB" but "318 KB".
        $decimals = $value >= 100 ? 0 : 1;
        $formatted = number_format($value, $decimals, ',', '.');

        if ($decimals === 1 && str_ends_with($formatted, ',0')) {
            $formatted = substr($formatted, 0, -2);
        }

        return $formatted.' '.$units[$unit];
    }
}
