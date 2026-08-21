<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * An invitation to take one specific request, sent to somebody who has no
 * account yet.
 *
 * @see \App\Actions\OfferRequestExternallyAction for how one is created and
 *      \App\Http\Controllers\Public\RequestOfferController for the invitee's side.
 */
class RequestOffer extends Model
{
    use HasFactory;

    /** How long the link stays usable. Long enough to survive a weekend. */
    public const VALID_DAYS = 7;

    /** How long an acceptance holds the request while they register. */
    public const HOLD_HOURS = 48;

    protected $fillable = [
        'service_request_id', 'email', 'name', 'token', 'invited_by',
        'assessor_id', 'message', 'sent_at', 'viewed_at', 'accepted_at',
        'declined_at', 'decline_reason', 'hold_until', 'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'viewed_at' => 'datetime',
            'accepted_at' => 'datetime',
            'declined_at' => 'datetime',
            'hold_until' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function serviceRequest(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class);
    }

    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function assessor(): BelongsTo
    {
        return $this->belongsTo(Assessor::class);
    }

    public static function generateToken(): string
    {
        return Str::random(64);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /** Still open: nobody has answered and the link has not run out. */
    public function isOpen(): bool
    {
        return $this->accepted_at === null
            && $this->declined_at === null
            && ! $this->isExpired();
    }

    /**
     * Accepted, not yet turned into an assignment, and still inside the window.
     *
     * This is what stops anybody else taking the request out from under an
     * invitee who is halfway through the registration form.
     */
    public function holdsRequest(): bool
    {
        return $this->accepted_at !== null
            && $this->assessor_id === null
            && $this->hold_until !== null
            && $this->hold_until->isFuture();
    }

    /** @param Builder<RequestOffer> $query */
    public function scopeHolding(Builder $query): Builder
    {
        return $query->whereNotNull('accepted_at')
            ->whereNull('assessor_id')
            ->where('hold_until', '>', now());
    }

    public function statusLabel(): string
    {
        return match (true) {
            $this->assessor_id !== null => 'Registriert und übernommen',
            $this->declined_at !== null => 'Abgelehnt',
            $this->holdsRequest() => 'Angenommen, Registrierung offen',
            $this->accepted_at !== null => 'Angenommen, Frist abgelaufen',
            $this->isExpired() => 'Abgelaufen',
            $this->viewed_at !== null => 'Geöffnet',
            default => 'Gesendet',
        };
    }

    /** Maps onto the shared StatusDot tones so the admin table reads uniformly. */
    public function statusTone(): string
    {
        return match (true) {
            $this->assessor_id !== null => 'accepted',
            $this->declined_at !== null => 'declined',
            $this->holdsRequest() => 'pending',
            $this->accepted_at !== null, $this->isExpired() => 'closed',
            default => 'pending',
        };
    }
}
