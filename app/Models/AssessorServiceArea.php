<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssessorServiceArea extends Model
{
    use HasFactory;

    protected $fillable = [
        'assessor_id', 'postal_code_from', 'postal_code_to', 'label',
    ];

    public function assessor(): BelongsTo
    {
        return $this->belongsTo(Assessor::class);
    }

    /** "40000–40999 · Düsseldorf und Umgebung" */
    public function range(): string
    {
        $range = $this->postal_code_from === $this->postal_code_to
            ? $this->postal_code_from
            : "{$this->postal_code_from}–{$this->postal_code_to}";

        return $this->label ? "{$range} · {$this->label}" : $range;
    }

    public function covers(string $postalCode): bool
    {
        return (int) $postalCode >= (int) $this->postal_code_from
            && (int) $postalCode <= (int) $this->postal_code_to;
    }
}
