<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * An anonymous tally of how far people get through the request form.
 *
 * Deliberately not a log of visits: there is no identifier of any kind, so two
 * people reaching step two are indistinguishable from one person reaching it
 * twice. That is the trade for needing no consent, and for a funnel that counts
 * everybody rather than only those who accepted a banner.
 */
class FunnelEvent extends Model
{
    /**
     * The steps of the request form, in order.
     *
     * Named after what somebody has actually done rather than numbered, because
     * the numbering has changed once already and a chart reading "Schritt 3"
     * against a form with two steps tells nobody anything. The third step is
     * gone with the vehicle questions; counts recorded against it while it
     * existed stay in the table and simply stop being charted.
     */
    public const STEPS = [
        'begonnen' => 'Formular geöffnet',
        'schritt_2' => 'Kontaktdaten erreicht',
        'abgesendet' => 'Anfrage abgesendet',
    ];

    /** Steps the form no longer has, kept so old rows are still recognised. */
    public const RETIRED_STEPS = ['schritt_3'];

    /**
     * The periods the dashboard offers, and how far back each one reaches.
     *
     * Yesterday is a closed day rather than "one day back": a funnel for a day
     * still in progress and a funnel for a finished one answer different
     * questions, and mixing them is how a quiet morning reads as a collapse.
     */
    public const PERIODS = [
        'heute' => 'Heute',
        'gestern' => 'Gestern',
        '7tage' => 'Letzte 7 Tage',
        '30tage' => 'Letzte 30 Tage',
    ];

    /**
     * The first and last day of a named period.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public static function period(string $key): array
    {
        return match ($key) {
            'heute' => [today(), today()],
            'gestern' => [today()->subDay(), today()->subDay()],
            '7tage' => [today()->subDays(6), today()],
            default => [today()->subDays(29), today()],
        };
    }

    protected $fillable = ['step', 'day', 'count'];

    protected function casts(): array
    {
        return ['day' => 'date', 'count' => 'integer'];
    }

    /**
     * The day, written the way the column holds it.
     *
     * A plain date cast writes "2026-08-28 00:00:00" through the model while
     * record() writes "2026-08-28" — two formats in one column. On SQLite the
     * funnel compared strings, so a row with a time component sorted past the
     * last day of any range that contained it and simply never appeared. The
     * cast alone does not fix this: it governs how the value is read back and
     * serialised, not how it is stored.
     */
    protected function day(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => $value === null ? null : Carbon::parse($value)->toDateString(),
        );
    }

    /** Adds one to today's tally for this step. */
    public static function record(string $step): void
    {
        if (! array_key_exists($step, self::STEPS)) {
            return;
        }

        // Upsert then increment: two requests arriving together must not lose
        // one of the two counts to a read-modify-write race.
        static::query()->upsert(
            [['step' => $step, 'day' => now()->toDateString(), 'count' => 0]],
            ['step', 'day'],
            [],
        );

        static::where('step', $step)
            ->whereDate('day', now()->toDateString())
            ->update(['count' => DB::raw('count + 1')]);
    }

    /**
     * Totals per step over a period, with the drop-off between them.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function funnel(Carbon $from, Carbon $to): array
    {
        // Compared as dates rather than as strings: a row holding a time
        // component sorts after the last day of the range and vanishes from
        // it, which is how a busy day could read as an empty one.
        $totals = static::whereDate('day', '>=', $from->toDateString())
            ->whereDate('day', '<=', $to->toDateString())
            ->groupBy('step')
            ->selectRaw('step, SUM(count) AS total')
            ->pluck('total', 'step');

        $started = (int) ($totals['begonnen'] ?? 0);
        $rows = [];

        foreach (self::STEPS as $step => $label) {
            $count = (int) ($totals[$step] ?? 0);

            $rows[] = [
                'step' => $step,
                'label' => $label,
                'count' => $count,
                // Against the top of the funnel, which is the number that says
                // whether the form is losing people.
                'share' => $started > 0 ? round($count / $started * 100) : null,
            ];
        }

        return $rows;
    }
}
