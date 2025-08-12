<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'preferred_language',
        'dashboard_layout',
        'role',
        'role_id',
        'is_active',
        'last_login_at',
        'two_factor_enabled',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_type',
        'two_factor_phone',
        'two_factor_confirmed_at',
        'two_factor_backup_codes',
        'session_timeout',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_backup_codes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'two_factor_confirmed_at' => 'datetime',
        'is_active' => 'boolean',
        'two_factor_enabled' => 'boolean',
        'two_factor_recovery_codes' => 'array',
        'two_factor_backup_codes' => 'array',
        'dashboard_layout' => 'array',
        'password' => 'hashed',
        'session_timeout' => 'integer',
    ];

    /**
     * The attributes with default values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'preferred_language' => 'en',
        'role' => 'owner',
        'is_active' => true,
        'two_factor_enabled' => false,
    ];

    /**
     * Get the default preferred language.
     *
     * @return string
     */
    public function getPreferredLanguageAttribute($value)
    {
        return $value ?? config('app.locale', 'en');
    }

    /**
     * Check if user has two-factor authentication enabled.
     *
     * @return bool
     */
    public function hasTwoFactorEnabled(): bool
    {
        return $this->two_factor_enabled && !empty($this->two_factor_secret);
    }

    /**
     * Get the role that belongs to the user
     */
    public function userRole(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    /**
     * Check if user has permission
     */
    public function hasPermission(string $permission): bool
    {
        if ($this->role === 'owner') {
            return true; // Owner has all permissions
        }

        return $this->userRole?->hasPermission($permission) ?? false;
    }

    /**
     * Check if user has any of the given permissions
     */
    public function hasAnyPermission(array $permissions): bool
    {
        if ($this->role === 'owner') {
            return true;
        }

        return $this->userRole?->hasAnyPermission($permissions) ?? false;
    }

    /**
     * Check if user has all of the given permissions
     */
    public function hasAllPermissions(array $permissions): bool
    {
        if ($this->role === 'owner') {
            return true;
        }

        return $this->userRole?->hasAllPermissions($permissions) ?? false;
    }

    /**
     * Get all user permissions
     */
    public function getPermissions(): array
    {
        if ($this->role === 'owner') {
            return \App\Models\Permission::pluck('name')->toArray();
        }

        return $this->userRole?->permissions()->pluck('name')->toArray() ?? [];
    }

    /**
     * Update the user's last login timestamp.
     *
     * @return void
     */
    public function updateLastLogin(): void
    {
        $this->update(['last_login_at' => now()]);
    }
}