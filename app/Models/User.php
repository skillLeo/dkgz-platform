<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, LogsActivity, Notifiable, SoftDeletes;

    use HasRoles {
        hasPermissionTo as protected spatieHasPermissionTo;
    }

    protected $fillable = [
        'must_change_password',
        'name', 'first_name', 'last_name', 'email', 'password', 'phone',
        'avatar_path', 'locale', 'is_active',
    ];

    protected $hidden = [
        'password', 'remember_token', 'two_factor_secret',
    ];

    protected function casts(): array
    {
        return [
            'must_change_password' => 'boolean',
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'two_factor_secret' => 'encrypted',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'first_name', 'last_name', 'email', 'phone', 'is_active'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('benutzer');
    }

    public function assessor(): HasOne
    {
        return $this->hasOne(Assessor::class);
    }

    /** Full name, falling back to the single name column. */
    public function fullName(): string
    {
        $parts = trim(($this->first_name ?? '').' '.($this->last_name ?? ''));

        return $parts !== '' ? $parts : (string) $this->name;
    }

    /** Anyone who is not an assessor works inside the admin panel. */
    public function isStaff(): bool
    {
        return ! $this->hasRole('assessor');
    }

    /**
     * A deactivated account holds no permission, whatever its roles say.
     *
     * This override is the single choke point: Spatie registers its own
     * Gate::before that resolves through checkPermissionTo() → hasPermissionTo(),
     * and that callback runs before any application-level one, so gating here
     * is the only way to cover every path — Gate, middleware, Blade and policy.
     */
    public function hasPermissionTo($permission, ?string $guardName = null): bool
    {
        if (! $this->is_active) {
            return false;
        }

        return $this->spatieHasPermissionTo($permission, $guardName);
    }

    public function hasTwoFactorEnabled(): bool
    {
        return $this->two_factor_secret !== null && $this->two_factor_confirmed_at !== null;
    }
}
