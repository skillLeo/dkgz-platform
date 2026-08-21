<?php

namespace App\Models;

use App\Casts\MoneyCast;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Assignment extends Model
{
    use HasFactory, LogsActivity;

    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_DOCUMENTS_UPLOADED = 'documents_uploaded';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    /** Fee bounds in cents. Enforced server-side, see BUILD_SPEC Part D. */
    public const FEE_MIN_CENTS = 5_000;

    public const FEE_MAX_CENTS = 5_000_000;

    public const FEE_REVIEW_THRESHOLD_CENTS = 1_000_000;

    protected $fillable = [
        'dkgz_fee_snapshot_cents',
        'service_request_id', 'assessor_id', 'status',
        'accepted_at', 'started_at', 'completed_at', 'cancelled_at',
        'cancellation_reason', 'fee_cents', 'fee_entered_at', 'assessor_notes',
        'confirmed_at', 'customer_invoice_cents', 'customer_invoice_recipient',
        'customer_invoice_number',
    ];

    protected function casts(): array
    {
        return [
            'dkgz_fee_snapshot_cents' => MoneyCast::class,
            'accepted_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'fee_entered_at' => 'datetime',
            'fee_cents' => MoneyCast::class,
            'customer_invoice_cents' => MoneyCast::class,
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'fee_cents', 'completed_at', 'cancellation_reason'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('auftrag');
    }

    public function serviceRequest(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class);
    }

    public function assessor(): BelongsTo
    {
        return $this->belongsTo(Assessor::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(AssignmentDocument::class);
    }

    public function statusEvents(): HasMany
    {
        return $this->hasMany(AssignmentStatusEvent::class)->orderBy('created_at');
    }

    public function commission(): HasOne
    {
        return $this->hasOne(Commission::class);
    }

    public function review(): HasOne
    {
        return $this->hasOne(CustomerReview::class);
    }

    public function reportDocument(): ?AssignmentDocument
    {
        return $this->documents->firstWhere('type', AssignmentDocument::TYPE_REPORT);
    }

    public function invoiceDocument(): ?AssignmentDocument
    {
        return $this->documents->firstWhere('type', AssignmentDocument::TYPE_CUSTOMER_INVOICE);
    }

    /**
     * The client's fraud control: an assignment cannot be completed until both
     * the report and the invoice the assessor issued are on file.
     */
    public function hasRequiredDocuments(): bool
    {
        $types = $this->documents()
            ->whereIn('type', [
                AssignmentDocument::TYPE_REPORT,
                AssignmentDocument::TYPE_CUSTOMER_INVOICE,
            ])
            ->distinct()
            ->pluck('type');

        return $types->contains(AssignmentDocument::TYPE_REPORT)
            && $types->contains(AssignmentDocument::TYPE_CUSTOMER_INVOICE);
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isOpen(): bool
    {
        return ! $this->isCompleted() && ! $this->isCancelled();
    }

    /** Records a transition on the append-only timeline. */
    public function recordStatusEvent(
        ?string $from,
        string $to,
        string $actorType,
        ?int $actorId = null,
        ?string $note = null,
    ): AssignmentStatusEvent {
        return $this->statusEvents()->create([
            'from_status' => $from,
            'to_status' => $to,
            'actor_type' => $actorType,
            'actor_id' => $actorId,
            'note' => $note,
            'created_at' => now(),
        ]);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_ACCEPTED => 'Angenommen',
            self::STATUS_IN_PROGRESS => 'In Bearbeitung',
            self::STATUS_DOCUMENTS_UPLOADED => 'Unterlagen hochgeladen',
            self::STATUS_COMPLETED => 'Abgeschlossen',
            self::STATUS_CANCELLED => 'Storniert',
            default => $this->status,
        };
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNotIn('status', [self::STATUS_COMPLETED, self::STATUS_CANCELLED]);
    }
}
