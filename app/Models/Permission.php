<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'display_name',
        'description',
        'module',
        'action'
    ];

    /**
     * Get the roles that have this permission
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permissions');
    }

    /**
     * Scope for permissions by module
     */
    public function scopeModule($query, string $module)
    {
        return $query->where('module', $module);
    }

    /**
     * Scope for permissions by action
     */
    public function scopeAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Get permissions grouped by module
     */
    public static function getGroupedByModule(): array
    {
        return static::all()
            ->groupBy('module')
            ->map(function ($permissions) {
                return $permissions->map(function ($permission) {
                    return [
                        'id' => $permission->id,
                        'name' => $permission->name,
                        'display_name' => $permission->display_name,
                        'description' => $permission->description,
                        'action' => $permission->action,
                    ];
                });
            })
            ->toArray();
    }
}