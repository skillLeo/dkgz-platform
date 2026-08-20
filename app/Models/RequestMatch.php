<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The forensic trail the admin panel displays: who was notified, when they
 * looked, and how the request resolved for them.
 */
class RequestMatch extends Model
{
    use HasFactory;

    public const OUTCOME_PENDING = 'pending';

    public const OUTCOME_ACCEPTED = 'accepted';

    public const OUTCOME_DECLINED = 'declined';

    public const OUTCOME_CLOSED = 'closed';

    public const OUTCOME_EXPIRED = 'expired';

    protected $fillable = [
        'service_request_id', 'assessor_id', 'outcome',
        'notified_at', 'viewed_at', 'responded_at', 'decline_reason',
    ];

    protected function casts(): array
    {
        return [
            'notified_at' => 'datetime',
            'viewed_at' => 'datetime',
            'responded_at' => 'datetime',
        ];
    }

    public function serviceRequest(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class);
    }

    public function assessor(): BelongsTo
    {
        return $this->belongsTo(Assessor::class);
    }

    public function isPending(): bool
    {
        return $this->outcome === self::OUTCOME_PENDING;
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('outcome', self::OUTCOME_PENDING);
    }

    /**
     * Written from the partner's side: what happened to them, rather than the
     * bare state name. "Geschlossen" told a partner nothing about why a
     * request left their queue; "Von anderem Partner angenommen" does.
     */
    public function outcomeLabel(): string
    {
        return match ($this->outcome) {
            self::OUTCOME_PENDING => 'Annahme offen',
            self::OUTCOME_ACCEPTED => 'Von mir angenommen',
            self::OUTCOME_DECLINED => 'Von mir abgelehnt',
            self::OUTCOME_CLOSED => 'Von anderem Partner angenommen',
            self::OUTCOME_EXPIRED => 'Frist abgelaufen',
            default => $this->outcome,
        };
    }
}
