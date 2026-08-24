<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * A city with its own landing pages.
 *
 * One page listing what is available there, and one per service offered — the
 * addresses are built from the names, so an operator who adds Düsseldorf and
 * ticks three services has four pages without touching a URL.
 */
class City extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'name', 'slug', 'state', 'postal_code',
        'headline', 'intro', 'meta_title', 'meta_description',
        'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'is_active'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('stadt');
    }

    /**
     * The address follows the name, with German umlauts spelled out.
     *
     * Str::slug turns "ü" into "u", and duesseldorf is what people type and
     * what search engines expect to see; dusseldorf is neither.
     */
    protected static function booted(): void
    {
        static::saving(function (self $city) {
            if (! $city->isDirty('name') && filled($city->slug)) {
                return;
            }

            $base = Str::slug($city->name, '-', 'de', [
                'ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue',
                'Ä' => 'ae', 'Ö' => 'oe', 'Ü' => 'ue', 'ß' => 'ss',
            ]) ?: 'stadt';

            $slug = $base;
            $suffix = 2;

            while (static::where('slug', $slug)
                ->when($city->exists, fn ($q) => $q->whereKeyNot($city->getKey()))
                ->exists()
            ) {
                $slug = $base.'-'.$suffix++;
            }

            $city->slug = $slug;
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function serviceTypes(): BelongsToMany
    {
        return $this->belongsToMany(ServiceType::class)->withTimestamps();
    }

    /** Only services that are both offered here and active in their own right. */
    public function publishedServiceTypes(): BelongsToMany
    {
        return $this->serviceTypes()->where('service_types.is_active', true);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /** "Düsseldorf, Nordrhein-Westfalen" where the state is known. */
    public function label(): string
    {
        return collect([$this->name, $this->state])->filter()->implode(', ');
    }
}
