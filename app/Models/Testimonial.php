<?php

namespace App\Models;

use App\Support\SafeStorage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * One customer's words about DKGZ.
 *
 * Real people only. A fabricated review is illegal in Germany under the UWG,
 * and beyond that it is the one thing on the page that stops working the moment
 * it stops being true.
 */
class Testimonial extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'name', 'location', 'quote', 'photo_path', 'rating', 'is_published', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'rating' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'is_published'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('kundenstimme');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public function photoUrl(): ?string
    {
        return blank($this->photo_path) ? null : SafeStorage::url($this->photo_path);
    }

    /** Fallback when there is no photograph: never a broken image or empty box. */
    public function initials(): string
    {
        return collect(preg_split('/\s+/', trim((string) $this->name)))
            ->filter()
            ->take(2)
            ->map(fn (string $part) => mb_strtoupper(mb_substr($part, 0, 1)))
            ->implode('');
    }
}
