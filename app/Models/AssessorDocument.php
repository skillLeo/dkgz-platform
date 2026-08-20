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
        'assessor_id', 'type', 'path', 'original_name', 'size_bytes', 'mime_type', 'uploaded_at',
    ];

    protected function casts(): array
    {
        return [
            'uploaded_at' => 'datetime',
            'size_bytes' => 'integer',
        ];
    }

    public function assessor(): BelongsTo
    {
        return $this->belongsTo(Assessor::class);
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
