<?php

namespace Database\Factories;

use App\Models\Permission;
use Illuminate\Database\Eloquent\Factories\Factory;

class PermissionFactory extends Factory
{
    protected $model = Permission::class;

    public function definition(): array
    {
        $modules = ['dashboard', 'invoicing', 'inventory', 'customers', 'accounting', 'configuration'];
        $actions = ['view', 'create', 'edit', 'delete', 'export'];
        
        $module = $this->faker->randomElement($modules);
        $action = $this->faker->randomElement($actions);
        
        return [
            'name' => "{$module}.{$action}",
            'display_name' => ucfirst($action) . ' ' . ucfirst($module),
            'description' => "Permission to {$action} {$module}",
            'module' => $module,
            'action' => $action,
        ];
    }

    public function forModule(string $module): static
    {
        return $this->state(fn (array $attributes) => [
            'module' => $module,
            'name' => "{$module}.{$attributes['action']}",
            'display_name' => ucfirst($attributes['action']) . ' ' . ucfirst($module),
            'description' => "Permission to {$attributes['action']} {$module}",
        ]);
    }

    public function forAction(string $action): static
    {
        return $this->state(fn (array $attributes) => [
            'action' => $action,
            'name' => "{$attributes['module']}.{$action}",
            'display_name' => ucfirst($action) . ' ' . ucfirst($attributes['module']),
            'description' => "Permission to {$action} {$attributes['module']}",
        ]);
    }
}