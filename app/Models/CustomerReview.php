<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class CustomerReview extends Model
{
    use HasFactory;

    protected $fillable = [
        'assignment_id', 'token', 'rating', 'feedback_category', 'feedback',
        'submitted_at', 'redirected_at', 'expires_at',
    ];

    protected $hidden = ['token'];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'submitted_at' => 'datetime',
            'redirected_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    public static function generateToken(): string
    {
        return Str::random(64);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isSubmitted(): bool
    {
        return $this->submitted_at !== null;
    }

    public function isUsable(): bool
    {
        return ! $this->isSubmitted() && ! $this->isExpired();
    }

    /**
     * Ratings at or above the configured threshold are sent on to the public
     * review page; anything lower is routed into the internal feedback step.
     */
    public function qualifiesForPublicRedirect(int $minimumRating): bool
    {
        return $this->rating !== null && $this->rating >= $minimumRating;
    }

    public function getRouteKeyName(): string
    {
        return 'token';
    }
}
