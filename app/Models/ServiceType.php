<?php

namespace App\Models;

use App\Casts\MoneyCast;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class ServiceType extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'dkgz_fee_cents',
        'slug', 'name_de', 'description_de', 'icon', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'dkgz_fee_cents' => MoneyCast::class,
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
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

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
