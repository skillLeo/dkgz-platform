<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssessorDocument extends Model
{
    use HasFactory;

    public const TYPE_QUALIFICATION = 'qualification';

    public const TYPE_LIABILITY = 'liability';

    public const TYPE_OTHER = 'other';

    protected $fillable = [
        'assessor_id', 'type', 'path', 'original_name', 'size_bytes', 'mime_type', 'uploaded_at', 'valid_until',
    ];

    protected function casts(): array
    {
        return [
            'uploaded_at' => 'datetime',
            'valid_until' => 'date',
            'size_bytes' => 'integer',
        ];
    }

    public function assessor(): BelongsTo
    {
        return $this->belongsTo(Assessor::class);
    }

    /** True when cover has lapsed — such a partner must not receive new work. */
    public function hasLapsed(): bool
    {
        return $this->valid_until !== null && $this->valid_until->isPast();
    }

    public function expiresSoon(int $days = 30): bool
    {
        return $this->valid_until !== null
            && ! $this->hasLapsed()
            && $this->valid_until->lessThanOrEqualTo(now()->addDays($days));
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            self::TYPE_QUALIFICATION => 'Qualifikationsnachweis',
            self::TYPE_LIABILITY => 'Haftpflichtnachweis',
            default => 'Weiterer Nachweis',
        };
    }
}
