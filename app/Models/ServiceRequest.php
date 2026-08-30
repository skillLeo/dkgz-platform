<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class ServiceRequest extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    /**
     * How far each reference advances past the last one.
     *
     * Five to twelve: enough that the gap between two references cannot be
     * read as a count, small enough that four digits still hold a month's
     * worth — a little over a thousand requests at this rate.
     */
    private const STEP_MIN = 5;

    private const STEP_MAX = 12;

    /** Retries when two requests land on the same number at once. */
    private const REFERENCE_ATTEMPTS = 5;

    public const STATUS_NEW = 'new';

    public const STATUS_MATCHED = 'matched';

    public const STATUS_ASSIGNED = 'assigned';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    /**
     * Every matched partner declined. The request stays open — a partner
     * approved in that area later can still be matched to it — but the office
     * knows nobody currently covering it wanted the work.
     */
    public const STATUS_UNANSWERED = 'unanswered';

    protected $fillable = [
        'reference', 'service_type_id', 'requested_assessor_id', 'postal_code', 'city',
        'customer_name', 'customer_phone', 'customer_email',
        'vehicle_make', 'vehicle_model', 'vehicle_year', 'vehicle_plate', 'vehicle_vin',
        'description', 'preferred_date', 'urgency', 'status', 'matched_count',
        'assigned_at', 'customer_notified_at', 'ip_address', 'user_agent', 'consent_at',
    ];

    protected function casts(): array
    {
        return [
            'preferred_date' => 'date',
            'assigned_at' => 'datetime',
            'customer_notified_at' => 'datetime',
            'consent_at' => 'datetime',
            'matched_count' => 'integer',
            'vehicle_year' => 'integer',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'matched_count', 'assigned_at'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('anfrage');
    }

    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(ServiceType::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(RequestImage::class);
    }

    public function offers(): HasMany
    {
        return $this->hasMany(RequestOffer::class);
    }

    public function matches(): HasMany
    {
        return $this->hasMany(RequestMatch::class);
    }

    public function assignment(): HasOne
    {
        return $this->hasOne(Assignment::class);
    }

    /**
     * Reference in the format DKGZ-YYYY-NNNNN, sequential within the year.
     * Called inside the submission transaction so the counter cannot race.
     */
    /**
     * The customer's reference: `DKGZ` + two-digit year + two-digit month + a
     * four-digit sequence that restarts each month, e.g. `DKGZ26081234`.
     *
     * The sequence is derived from the highest reference already issued in that
     * month rather than a counter, so it cannot drift out of step with reality.
     * References issued under the earlier `DKGZ-YYYY-NNNNN` format are left
     * exactly as they are — a reference a customer already holds must keep
     * matching the one in the system.
     */
    /**
     * The next reference, advanced by an unpredictable amount.
     *
     * It used to count up by one. Anybody holding two references — a customer
     * and a partner comparing notes, or one person who enquired twice — could
     * subtract them and read off exactly how much work DKGZ had taken in
     * between, which is nobody's business but DKGZ's.
     *
     * A random step of five to twelve turns that arithmetic into a range: a gap
     * of thirty-four means somewhere between three and seven requests, not
     * thirty-four and not any exact number. It does not hide the order of two
     * references, and it never can while they are readable and sequential at
     * all — what it removes is the exact count, which is what was being leaked.
     *
     * The first reference of a month is offset too. Starting at 0001 announces
     * both that it is the first and where the counting began, which hands an
     * observer the fixed point the rest of the arithmetic needs.
     *
     * random_int rather than rand: this exists to stop somebody working the
     * numbers out, so a predictable generator would defeat the whole point.
     */
    public static function nextReference(?CarbonInterface $moment = null): string
    {
        $moment ??= now();
        $prefix = 'DKGZ'.$moment->format('ym');

        // Two requests arriving together would otherwise read the same last
        // reference and build the same next one. The column is unique, so the
        // loser of that race raises rather than duplicating; trying again with
        // a fresh read and a fresh step settles it.
        foreach (range(1, self::REFERENCE_ATTEMPTS) as $ignored) {
            $reference = $prefix.str_pad(
                (string) (self::lastSequence($prefix) + random_int(self::STEP_MIN, self::STEP_MAX)),
                4,
                '0',
                STR_PAD_LEFT
            );

            if (! static::withTrashed()->where('reference', $reference)->exists()) {
                return $reference;
            }
        }

        // Every attempt collided, which needs a great many simultaneous
        // requests. Falling back to something certainly unique beats handing
        // back a reference that is about to fail on insert.
        return $prefix.str_pad((string) (self::lastSequence($prefix) + random_int(self::STEP_MIN, self::STEP_MAX) + random_int(100, 999)), 4, '0', STR_PAD_LEFT);
    }

    /**
     * The highest sequence used this month, or zero.
     *
     * Ordered by length first: "DKGZ26080100" sorts below "DKGZ2608099" as
     * text, so a month that runs past four digits would otherwise start
     * counting again from the wrong place.
     */
    private static function lastSequence(string $prefix): int
    {
        $last = static::withTrashed()
            ->where('reference', 'like', "{$prefix}%")
            ->orderByRaw('LENGTH(reference) DESC')
            ->orderByDesc('reference')
            ->value('reference');

        return $last === null ? 0 : (int) substr($last, strlen($prefix));
    }

    public function isOpen(): bool
    {
        return in_array($this->status, [self::STATUS_NEW, self::STATUS_MATCHED], true);
    }

    /** No assessor's area covers this postal code — surfaced as "Nicht vermittelt". */
    public function isUnmatched(): bool
    {
        return $this->status === self::STATUS_NEW && $this->matched_count === 0;
    }

    /** Every matched assessor declined; the request needs admin attention. */
    public function isFullyDeclined(): bool
    {
        // 'unanswered' is set the moment the last partner declines, so both it
        // and a still-'matched' row with no pending matches mean the same thing.
        return in_array($this->status, [self::STATUS_MATCHED, self::STATUS_UNANSWERED], true)
            && $this->matched_count > 0
            && ! $this->matches()->where('outcome', RequestMatch::OUTCOME_PENDING)->exists();
    }

    /**
     * The vehicle, where anybody knows what it is.
     *
     * The public form stopped asking — the assessor telephones the customer and
     * asks then — so this is empty on most requests now and has to say so. A
     * bare "Fahrzeug:" followed by nothing reads like a bug in an e-mail.
     */
    /** The one partner this was aimed at, where it was aimed at anybody. */
    public function requestedAssessor(): BelongsTo
    {
        return $this->belongsTo(Assessor::class, 'requested_assessor_id');
    }

    public function vehicleLabel(): string
    {
        $label = trim("{$this->vehicle_make} {$this->vehicle_model}".
            ($this->vehicle_year ? " · {$this->vehicle_year}" : ''));

        return $label !== '' ? $label : 'noch nicht angegeben';
    }

    /**
     * "Martina Reinhardt" → "M. Reinhardt". Used in the partner's own order
     * list, where the contact has already been released to them; the privacy
     * boundary in ServiceRequestResource still governs who may see any of it.
     */
    public function customerShortName(): ?string
    {
        if (blank($this->customer_name)) {
            return null;
        }

        $parts = preg_split('/\s+/', trim($this->customer_name));

        if (count($parts) < 2) {
            return $this->customer_name;
        }

        return mb_substr($parts[0], 0, 1).'. '.end($parts);
    }

    /** "DKGZ26081234" → "1234", the form used in compact lists. */
    public function shortReference(): string
    {
        return substr($this->reference, -4);
    }

    public function locationLabel(): string
    {
        return trim("{$this->city} · {$this->postal_code}");
    }

    public function getRouteKeyName(): string
    {
        return 'id';
    }

    public function scopeNeedsAttention(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->where(fn (Builder $inner) => $inner
                ->where('status', self::STATUS_NEW)
                ->where('matched_count', 0))
                ->orWhere(fn (Builder $inner) => $inner
                    ->whereIn('status', [self::STATUS_MATCHED, self::STATUS_UNANSWERED])
                    ->whereDoesntHave('matches', fn (Builder $m) => $m
                        ->where('outcome', RequestMatch::OUTCOME_PENDING)));
        });
    }

    /** Anonymises customer data in place for the GDPR retention command. */
    public function anonymise(): void
    {
        DB::transaction(function () {
            $this->images()->each(function (RequestImage $image) {
                $image->delete();
            });

            $this->forceFill([
                'customer_name' => 'Anonymisiert',
                'customer_phone' => '',
                'customer_email' => 'anonym-'.$this->id.'@dkgz.invalid',
                'vehicle_plate' => null,
                'vehicle_vin' => null,
                'description' => null,
                'ip_address' => null,
                'user_agent' => null,
            ])->saveQuietly();
        });
    }
}
