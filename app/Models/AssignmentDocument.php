<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Lives on the PRIVATE disk. The path is never exposed to the client; files are
 * served only through the download controller after an authorisation check.
 */
class AssignmentDocument extends Model
{
    use HasFactory;

    public const TYPE_REPORT = 'report';

    public const TYPE_CUSTOMER_INVOICE = 'customer_invoice';

    public const TYPE_OTHER = 'other';

    public const DISK = 'private';

    protected $fillable = [
        'assignment_id', 'type', 'path', 'original_name', 'mime', 'size_bytes', 'uploaded_at',
    ];

    protected $hidden = ['path'];

    protected function casts(): array
    {
        return [
            'uploaded_at' => 'datetime',
            'size_bytes' => 'integer',
        ];
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            self::TYPE_REPORT => 'Gutachten',
            self::TYPE_CUSTOMER_INVOICE => 'Rechnung an den Kunden',
            self::TYPE_OTHER => 'Weitere Unterlage',
            default => $this->type,
        };
    }
}
