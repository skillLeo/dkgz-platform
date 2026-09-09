<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Support\Settings;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Commission extends Model
{
    use HasFactory, LogsActivity;

    /** How this row was calculated. Historical rows keep 'percentage'. */
    public const TYPE_FIXED = 'fixed';

    public const TYPE_PERCENTAGE = 'percentage';

    public const STATUS_OPEN = 'open';

    public const STATUS_INVOICED = 'invoiced';

    public const STATUS_SETTLED = 'settled';

    public const STATUS_WAIVED = 'waived';

    protected $fillable = [
        'assignment_id', 'assessor_id', 'fee_type', 'dkgz_fee_cents',
        'fee_cents', 'rate_percent', 'commission_cents',
        'vat_percent', 'vat_cents', 'gross_cents',
        'status', 'invoice_number', 'invoice_path', 'invoiced_at',
        'settled_at', 'settled_by', 'notes',
    ];

    protected $hidden = ['invoice_path'];

    protected function casts(): array
    {
        return [
            'fee_cents' => MoneyCast::class,
            'dkgz_fee_cents' => MoneyCast::class,
            'commission_cents' => MoneyCast::class,
            'vat_cents' => MoneyCast::class,
            'gross_cents' => MoneyCast::class,
            'vat_percent' => 'decimal:2',
            'rate_percent' => 'decimal:2',
            'invoiced_at' => 'datetime',
            'settled_at' => 'datetime',
        ];
    }

    /**
     * The rate to charge on a fee invoiced now.
     *
     * Only ever read when an invoice is being issued. Once it is, the rate that
     * applied is written onto the row and read from there for ever after — a
     * rate is a fact about a date, and recomputing it later would rewrite
     * invoices both sides already have in their books.
     */
    public static function currentVatPercent(): float
    {
        return (float) Settings::get('business.vat_percent', 19);
    }

    /**
     * Net, tax and gross for this commission.
     *
     * Falls back to the current rate for a commission not yet invoiced, so the
     * portal can show a partner what an outstanding fee will come to before the
     * invoice exists.
     *
     * @return array{net: int, percent: float, vat: int, gross: int}
     */
    public function amounts(): array
    {
        $net = (int) $this->commission_cents;
        $percent = $this->vat_percent !== null
            ? (float) $this->vat_percent
            : self::currentVatPercent();

        $vat = $this->vat_cents !== null
            ? (int) $this->vat_cents
            : (int) round($net * $percent / 100);

        return [
            'net' => $net,
            'percent' => $percent,
            'vat' => $vat,
            'gross' => $this->gross_cents !== null ? (int) $this->gross_cents : $net + $vat,
        ];
    }

    /** What the partner is actually asked to pay. */
    public function grossCents(): int
    {
        return $this->amounts()['gross'];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'commission_cents', 'rate_percent', 'vat_percent', 'gross_cents', 'invoice_number', 'notes'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('provision');
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    public function assessor(): BelongsTo
    {
        return $this->belongsTo(Assessor::class);
    }

    public function settledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'settled_by');
    }

    /**
     * The one place commission arithmetic happens. The rate is passed in as a
     * snapshot by CompleteAssignmentAction and never read live from settings
     * here — editing the rate must not rewrite historical records.
     */
    public static function calculateCents(int $feeCents, float $ratePercent): int
    {
        return (int) round($feeCents * $ratePercent / 100);
    }

    /** The assessor keeps this much of the fee. */
    public function assessorShareCents(): int
    {
        return (int) $this->fee_cents - (int) $this->commission_cents;
    }

    /** The first number issued under the current scheme. */
    public const INVOICE_START = 82191;

    public const INVOICE_PREFIX = 'DKGZRE-';

    /**
     * DKGZRE-82191, and one more for each after it.
     *
     * Consecutive, as an invoice number has to be, but it does not start at one.
     * The old DKGZ-RE-2026-0001 announced to every partner exactly how many jobs
     * DKGZ had ever placed, and the first partner to be sent number 0001 learns
     * they are the first — which is not a thing a young platform wants to say.
     * Beginning five digits in tells nobody anything and stays five digits for
     * seventeen thousand invoices.
     *
     * Numbers already issued keep theirs. An invoice number is part of a
     * document both sides have filed, and renumbering one afterwards is not a
     * tidy-up, it is a different invoice.
     */
    public static function nextInvoiceNumber(): string
    {
        $last = static::where('invoice_number', 'like', self::INVOICE_PREFIX.'%')
            // Longest first, so 100000 sorts above 99999 rather than below it —
            // the same trap the request references fell into.
            ->orderByRaw('LENGTH(invoice_number) DESC')
            ->orderByDesc('invoice_number')
            ->value('invoice_number');

        $sequence = $last === null
            ? self::INVOICE_START
            : ((int) substr($last, strlen(self::INVOICE_PREFIX))) + 1;

        return self::INVOICE_PREFIX.$sequence;
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_OPEN => 'Offen',
            self::STATUS_INVOICED => 'Abgerechnet',
            self::STATUS_SETTLED => 'Bezahlt',
            self::STATUS_WAIVED => 'Erlassen',
            default => $this->status,
        };
    }

    /** Flagged for admin review — an unusually large fee. */
    public function needsReview(): bool
    {
        return (int) $this->fee_cents > Assignment::FEE_REVIEW_THRESHOLD_CENTS;
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_OPEN);
    }

    public function scopeBillable(Builder $query): Builder
    {
        return $query->whereIn('status', [self::STATUS_OPEN, self::STATUS_INVOICED]);
    }
}
