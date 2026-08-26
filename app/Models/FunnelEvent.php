<?php

namespace App\Models;

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

    protected $fillable = ['step', 'day', 'count'];

    protected function casts(): array
    {
        return ['day' => 'date', 'count' => 'integer'];
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
        $totals = static::whereBetween('day', [$from->toDateString(), $to->toDateString()])
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
