<?php

namespace App\Models;

use App\Support\Settings;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Assessor extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_SUSPENDED = 'suspended';

    protected $fillable = [
        'user_id', 'company_name', 'legal_form', 'street', 'house_number',
        'postal_code', 'city', 'country', 'vat_id', 'website',
        'bank_account_holder', 'bank_iban', 'bank_bic',
        'notify_new_request', 'notify_commission_statement',
        'certification_body', 'certification_number', 'certification_valid_until',
        'years_experience', 'qualification_document_path', 'is_available',
        'approval_status', 'approved_at', 'approved_by', 'rejection_reason',
        'suspension_reason', 'suspended_at', 'internal_notes',
    ];

    protected function casts(): array
    {
        return [
            'is_available' => 'boolean',
            'notify_new_request' => 'boolean',
            'notify_commission_statement' => 'boolean',
            'certification_valid_until' => 'date',
            'approved_at' => 'datetime',
            'suspended_at' => 'datetime',
            'years_experience' => 'integer',
        ];
    }

    /**
     * The identifier partners see and quote in correspondence. Derived from the
     * key rather than stored, so it can never drift out of step with the row.
     */
    public function partnerId(): string
    {
        return sprintf('DKGZ-SV-%04d', $this->id);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'company_name', 'approval_status', 'is_available', 'postal_code',
                'city', 'vat_id', 'certification_body', 'certification_number',
                'rejection_reason', 'suspension_reason',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('sachverstaendiger');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * The covered ranges as the portal filter chip writes them: "405–409, 414".
     * Ranges collapse to their shared leading digits so a partner covering a
     * whole region does not get a chip listing forty codes.
     */
    public function serviceAreaLabel(): string
    {
        return $this->serviceAreas
            ->map(function (AssessorServiceArea $area) {
                $from = (string) $area->postal_code_from;
                $to = (string) $area->postal_code_to;

                if ($from === $to) {
                    return $from;
                }

                // Trim the digits the two ends share nothing about: keep three.
                $shortFrom = substr($from, 0, 3);
                $shortTo = substr($to, 0, 3);

                return $shortFrom === $shortTo ? $shortFrom : "{$shortFrom}–{$shortTo}";
            })
            ->unique()
            ->sort()
            ->implode(', ');
    }

    public function documents(): HasMany
    {
        // Qualification first, then liability: the order a reviewer works in.
        return $this->hasMany(AssessorDocument::class)
            ->orderByRaw("CASE type WHEN 'qualification' THEN 0 WHEN 'liability' THEN 1 ELSE 2 END")
            ->orderBy('id');
    }

    public function serviceAreas(): HasMany
    {
        return $this->hasMany(AssessorServiceArea::class);
    }

    public function serviceTypes(): BelongsToMany
    {
        return $this->belongsToMany(ServiceType::class);
    }

    public function requestMatches(): HasMany
    {
        return $this->hasMany(RequestMatch::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(Commission::class);
    }

    public function isApproved(): bool
    {
        return $this->approval_status === self::STATUS_APPROVED;
    }

    /**
     * The full eligibility gate used by the matching engine. Kept here so the
     * rule lives in exactly one place — see also scopeMatchable().
     */
    public function isMatchable(): bool
    {
        return $this->isApproved()
            && $this->is_available
            && (bool) $this->user?->is_active
            && (! Settings::bool('business.require_valid_liability_cover', true)
                || ! $this->liabilityCoverHasLapsed());
    }

    /**
     * A partner whose professional liability cover has run out must not be sent
     * new work. Cover with no recorded end date is treated as current: the date
     * is optional on older rows, and refusing to match everyone who registered
     * before the field existed would empty the network overnight.
     */
    /** The latest end date on file, or null when no dated cover exists. */
    public function liabilityCoverValidUntil(): ?Carbon
    {
        return $this->documents
            ->where('type', AssessorDocument::TYPE_LIABILITY)
            ->pluck('valid_until')
            ->filter()
            ->max();
    }

    /**
     * True when this date is the partner's effective cover end — so a reminder
     * about an old certificate is not sent to someone who already filed a newer
     * one.
     */
    public function liabilityCoverExpiresOn(\DateTimeInterface $date): bool
    {
        $effective = $this->liabilityCoverValidUntil();

        return $effective !== null && $effective->isSameDay($date);
    }

    public function liabilityCoverExpiresSoon(int $days = 30): bool
    {
        $until = $this->liabilityCoverValidUntil();

        return $until !== null
            && ! $until->isPast()
            && $until->lessThanOrEqualTo(now()->addDays($days));
    }

    public function liabilityCoverHasLapsed(): bool
    {
        $dated = $this->documents
            ->where('type', AssessorDocument::TYPE_LIABILITY)
            ->filter(fn (AssessorDocument $document) => $document->valid_until !== null);

        if ($dated->isEmpty()) {
            return false;
        }

        return $dated->every(fn (AssessorDocument $document) => $document->hasLapsed());
    }

    /**
     * Share of notified requests this partner accepted, as a whole percent.
     * Null while they have never been notified — 0 % would read as a judgement
     * on someone who has simply not been offered anything yet.
     */
    public function acceptanceRate(): ?int
    {
        $notified = $this->requestMatches()->count();

        if ($notified === 0) {
            return null;
        }

        $accepted = $this->requestMatches()->where('outcome', RequestMatch::OUTCOME_ACCEPTED)->count();

        return (int) round(($accepted / $notified) * 100);
    }

    public function displayAddress(): string
    {
        $street = trim("{$this->street} {$this->house_number}");
        $place = trim("{$this->postal_code} {$this->city}");

        return trim($street.($street && $place ? ', ' : '').$place);
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('approval_status', self::STATUS_APPROVED);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('approval_status', self::STATUS_PENDING);
    }

    /**
     * Approved + available + the user account still active. This is the first
     * half of the matching rule; the postal-code and service-type halves are
     * applied by MatchRequestAction.
     */
    public function scopeMatchable(Builder $query): Builder
    {
        return $query->approved()
            ->where('is_available', true)
            ->whereHas('user', fn (Builder $q) => $q->where('is_active', true))
            // Lapsed liability cover removes a partner from matching — but only
            // while the platform is configured to require it. This is a fifth
            // criterion beyond the four the client specified, so it is a switch
            // rather than a hard rule. See DECISIONS.md D-11.
            ->when(
                Settings::bool('business.require_valid_liability_cover', true),
                fn (Builder $query) => $query->where(fn (Builder $q) => $q
                    ->whereDoesntHave('documents', fn (Builder $d) => $d
                        ->where('type', AssessorDocument::TYPE_LIABILITY)
                        ->whereNotNull('valid_until'))
                    ->orWhereHas('documents', fn (Builder $d) => $d
                        ->where('type', AssessorDocument::TYPE_LIABILITY)
                        ->whereNotNull('valid_until')
                        ->whereDate('valid_until', '>=', now())))
            );
    }

    /** Assessors whose service areas numerically span the given postal code. */
    public function scopeCovering(Builder $query, string $postalCode): Builder
    {
        $numeric = (int) $postalCode;

        return $query->whereHas('serviceAreas', function (Builder $q) use ($numeric) {
            $q->whereRaw('CAST(postal_code_from AS UNSIGNED) <= ?', [$numeric])
                ->whereRaw('CAST(postal_code_to AS UNSIGNED) >= ?', [$numeric]);
        });
    }

    public function scopeOffering(Builder $query, int $serviceTypeId): Builder
    {
        return $query->whereHas(
            'serviceTypes',
            fn (Builder $q) => $q->where('service_types.id', $serviceTypeId)
        );
    }
}
