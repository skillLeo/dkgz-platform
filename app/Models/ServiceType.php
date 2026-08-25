<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Support\GermanNoun;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class ServiceType extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'dkgz_fee_cents',
        'includes_de', 'target_audience_de', 'typical_situations_de',
        'differences_de', 'additional_info_de', 'faqs', 'content_is_placeholder',
        'slug', 'name_de', 'gender', 'description_de', 'icon', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            // Question and answer pairs belonging to this service.
            'faqs' => 'array',
            'content_is_placeholder' => 'boolean',
            'dkgz_fee_cents' => MoneyCast::class,
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * The gender the articles in front of this service's name are bent to.
     *
     * Stored only when the operator has overruled the guess, so renaming a
     * service moves its gender with it unless somebody has said otherwise.
     */
    public function genus(): string
    {
        return in_array($this->gender, GermanNoun::GENDERS, true)
            ? $this->gender
            : GermanNoun::genderOf($this->name_de);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['slug', 'name_de', 'description_de', 'is_active', 'sort_order'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('leistungsart');
    }

    public function assessors(): BelongsToMany
    {
        return $this->belongsToMany(Assessor::class);
    }

    public function serviceRequests(): HasMany
    {
        return $this->hasMany(ServiceRequest::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name_de');
    }

    /**
     * Keeps the public URL matching the name, whoever changes it.
     *
     * This lived in the admin controller, which meant a rename through the
     * seeder, a console command or a future screen left the address describing
     * something that is no longer there. It belongs to the model, so there is
     * no way to rename a service and forget.
     *
     * A name that collides with another service gets a numeric suffix rather
     * than silently taking over the other one's address.
     */
    protected static function booted(): void
    {
        static::saving(function (self $type) {
            if (! $type->isDirty('name_de') && filled($type->slug)) {
                return;
            }

            // German transliteration: Str::slug turns "ü" into "u", which
            // gives ausfuhrliches rather than ausfuehrliches. On a German site
            // the umlaut spelling is the one people expect to see in a URL.
            $base = Str::slug($type->name_de, '-', 'de', [
                'ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue',
                'Ä' => 'ae', 'Ö' => 'oe', 'Ü' => 'ue',
                'ß' => 'ss',
            ]) ?: 'leistung';
            $slug = $base;
            $suffix = 2;

            while (static::where('slug', $slug)
                ->when($type->exists, fn ($q) => $q->whereKeyNot($type->getKey()))
                ->exists()
            ) {
                $slug = $base.'-'.$suffix++;
            }

            $type->slug = $slug;
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
