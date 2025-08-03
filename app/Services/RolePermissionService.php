<?php

namespace App\Services;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class RolePermissionService
{
    private const CACHE_TTL = 3600; // 1 hour

    /**
     * Get all roles with permissions
     */
    public function getAllRoles(): array
    {
        return Cache::remember('all_roles', self::CACHE_TTL, function () {
            return Role::with('permissions')->active()->get()->toArray();
        });
    }

    /**
     * Get all permissions grouped by module
     */
    public function getAllPermissions(): array
    {
        return Cache::remember('all_permissions', self::CACHE_TTL, function () {
            return Permission::getGroupedByModule();
        });
    }

    /**
     * Create a new role
     */
    public function createRole(array $data): Role
    {
        $role = Role::create([
            'name' => $data['name'],
            'display_name' => $data['display_name'],
            'description' => $data['description'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);

        if (isset($data['permissions'])) {
            $role->syncPermissions($data['permissions']);
        }

        $this->clearCache();

        return $role;
    }

    /**
     * Update role
     */
    public function updateRole(Role $role, array $data): Role
    {
        $role->update([
            'display_name' => $data['display_name'],
            'description' => $data['description'] ?? $role->description,
            'is_active' => $data['is_active'] ?? $role->is_active,
        ]);

        if (isset($data['permissions'])) {
            $role->syncPermissions($data['permissions']);
        }

        $this->clearCache();

        return $role;
    }

    /**
     * Delete role
     */
    public function deleteRole(Role $role): bool
    {
        // Check if any users have this role
        if ($role->users()->count() > 0) {
            throw new \Exception('Cannot delete role that is assigned to users');
        }

        $deleted = $role->delete();
        
        if ($deleted) {
            $this->clearCache();
        }

        return $deleted;
    }

    /**
     * Assign role to user
     */
    public function assignRoleToUser(User $user, Role $role): void
    {
        $user->update(['role_id' => $role->id]);
        $this->clearUserPermissionsCache($user->id);
    }

    /**
     * Remove role from user
     */
    public function removeRoleFromUser(User $user): void
    {
        $user->update(['role_id' => null]);
        $this->clearUserPermissionsCache($user->id);
    }

    /**
     * Get user permissions with caching
     */
    public function getUserPermissions(User $user): array
    {
        $cacheKey = "user_permissions_{$user->id}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($user) {
            return $user->getPermissions();
        });
    }

    /**
     * Check if user has permission with caching
     */
    public function userHasPermission(User $user, string $permission): bool
    {
        $permissions = $this->getUserPermissions($user);
        return in_array($permission, $permissions);
    }

    /**
     * Seed default permissions
     */
    public function seedDefaultPermissions(): void
    {
        $permissions = [
            // Dashboard
            ['name' => 'dashboard.view', 'display_name' => 'View Dashboard', 'module' => 'dashboard', 'action' => 'view'],
            ['name' => 'dashboard.customize', 'display_name' => 'Customize Dashboard', 'module' => 'dashboard', 'action' => 'customize'],

            // Invoicing
            ['name' => 'invoices.view', 'display_name' => 'View Invoices', 'module' => 'invoicing', 'action' => 'view'],
            ['name' => 'invoices.create', 'display_name' => 'Create Invoices', 'module' => 'invoicing', 'action' => 'create'],
            ['name' => 'invoices.edit', 'display_name' => 'Edit Invoices', 'module' => 'invoicing', 'action' => 'edit'],
            ['name' => 'invoices.delete', 'display_name' => 'Delete Invoices', 'module' => 'invoicing', 'action' => 'delete'],
            ['name' => 'invoices.export', 'display_name' => 'Export Invoices', 'module' => 'invoicing', 'action' => 'export'],

            // Inventory
            ['name' => 'inventory.view', 'display_name' => 'View Inventory', 'module' => 'inventory', 'action' => 'view'],
            ['name' => 'inventory.create', 'display_name' => 'Create Inventory Items', 'module' => 'inventory', 'action' => 'create'],
            ['name' => 'inventory.edit', 'display_name' => 'Edit Inventory Items', 'module' => 'inventory', 'action' => 'edit'],
            ['name' => 'inventory.delete', 'display_name' => 'Delete Inventory Items', 'module' => 'inventory', 'action' => 'delete'],
            ['name' => 'inventory.audit', 'display_name' => 'Perform Stock Audits', 'module' => 'inventory', 'action' => 'audit'],

            // Customers
            ['name' => 'customers.view', 'display_name' => 'View Customers', 'module' => 'customers', 'action' => 'view'],
            ['name' => 'customers.create', 'display_name' => 'Create Customers', 'module' => 'customers', 'action' => 'create'],
            ['name' => 'customers.edit', 'display_name' => 'Edit Customers', 'module' => 'customers', 'action' => 'edit'],
            ['name' => 'customers.delete', 'display_name' => 'Delete Customers', 'module' => 'customers', 'action' => 'delete'],
            ['name' => 'customers.communicate', 'display_name' => 'Communicate with Customers', 'module' => 'customers', 'action' => 'communicate'],

            // Accounting
            ['name' => 'accounting.view', 'display_name' => 'View Accounting', 'module' => 'accounting', 'action' => 'view'],
            ['name' => 'accounting.transactions', 'display_name' => 'Manage Transactions', 'module' => 'accounting', 'action' => 'transactions'],
            ['name' => 'accounting.reports', 'display_name' => 'Generate Reports', 'module' => 'accounting', 'action' => 'reports'],
            ['name' => 'accounting.lock', 'display_name' => 'Lock Transactions', 'module' => 'accounting', 'action' => 'lock'],

            // Configuration
            ['name' => 'config.view', 'display_name' => 'View Configuration', 'module' => 'configuration', 'action' => 'view'],
            ['name' => 'config.edit', 'display_name' => 'Edit Configuration', 'module' => 'configuration', 'action' => 'edit'],
            ['name' => 'config.users', 'display_name' => 'Manage Users', 'module' => 'configuration', 'action' => 'users'],
            ['name' => 'config.roles', 'display_name' => 'Manage Roles', 'module' => 'configuration', 'action' => 'roles'],
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['name' => $permission['name']],
                $permission
            );
        }
    }

    /**
     * Seed default roles
     */
    public function seedDefaultRoles(): void
    {
        // Accountant role
        $accountantRole = Role::updateOrCreate(
            ['name' => 'accountant'],
            [
                'display_name' => 'Accountant',
                'description' => 'Can manage accounting, view reports, and handle financial data',
                'is_active' => true,
            ]
        );

        $accountantPermissions = Permission::whereIn('module', ['dashboard', 'accounting', 'invoicing'])
            ->whereNotIn('action', ['delete'])
            ->pluck('id')
            ->toArray();
        $accountantRole->syncPermissions($accountantPermissions);

        // Sales role
        $salesRole = Role::updateOrCreate(
            ['name' => 'sales'],
            [
                'display_name' => 'Sales Representative',
                'description' => 'Can manage customers, create invoices, and view inventory',
                'is_active' => true,
            ]
        );

        $salesPermissions = Permission::whereIn('module', ['dashboard', 'customers', 'invoicing', 'inventory'])
            ->whereNotIn('action', ['delete', 'audit'])
            ->pluck('id')
            ->toArray();
        $salesRole->syncPermissions($salesPermissions);

        // Inventory Manager role
        $inventoryRole = Role::updateOrCreate(
            ['name' => 'inventory_manager'],
            [
                'display_name' => 'Inventory Manager',
                'description' => 'Can manage inventory, perform audits, and view related reports',
                'is_active' => true,
            ]
        );

        $inventoryPermissions = Permission::whereIn('module', ['dashboard', 'inventory'])
            ->pluck('id')
            ->toArray();
        $inventoryRole->syncPermissions($inventoryPermissions);
    }

    /**
     * Clear all role and permission caches
     */
    public function clearCache(): void
    {
        Cache::forget('all_roles');
        Cache::forget('all_permissions');
        
        // Clear user permission caches
        $userIds = User::pluck('id');
        foreach ($userIds as $userId) {
            Cache::forget("user_permissions_{$userId}");
        }
    }

    /**
     * Clear user permissions cache
     */
    private function clearUserPermissionsCache(int $userId): void
    {
        Cache::forget("user_permissions_{$userId}");
    }
}