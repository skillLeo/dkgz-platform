<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Append-only trail behind the vertical status timeline. Never updated.
 */
class AssignmentStatusEvent extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'assignment_id', 'from_status', 'to_status', 'actor_type', 'actor_id', 'note',
    ];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    /**
     * How the step reads in the partner's "Verlauf": what happened, in the
     * past tense, rather than the name of the state that was entered.
     */
    public function label(): string
    {
        return match ($this->to_status) {
            Assignment::STATUS_ACCEPTED => 'Auftrag angenommen',
            Assignment::STATUS_IN_PROGRESS => 'Bearbeitung begonnen',
            Assignment::STATUS_DOCUMENTS_UPLOADED => 'Gutachten hochgeladen',
            Assignment::STATUS_COMPLETED => 'Auftrag abgeschlossen',
            Assignment::STATUS_CANCELLED => 'Auftrag storniert',
            default => $this->to_status,
        };
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
