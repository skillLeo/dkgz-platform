<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LiabilityReminder extends Model
{
    protected $fillable = ['assessor_id', 'valid_until', 'days_before', 'sent_at'];

    protected function casts(): array
    {
        return ['valid_until' => 'date', 'sent_at' => 'datetime'];
    }

    public function assessor(): BelongsTo
    {
        return $this->belongsTo(Assessor::class);
    }
}
