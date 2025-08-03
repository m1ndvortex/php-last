<?php

namespace Tests\Unit;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\RolePermissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class RolePermissionServiceTest extends TestCase
{
    use RefreshDatabase;

    private RolePermissionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new RolePermissionService();
    }

    public function test_can_create_role()
    {
        $data = [
            'name' => 'test_role',
            'display_name' => 'Test Role',
            'description' => 'A test role',
            'is_active' => true
        ];

        $role = $this->service->createRole($data);

        $this->assertInstanceOf(Role::class, $role);
        $this->assertEquals('test_role', $role->name);
        $this->assertEquals('Test Role', $role->display_name);
        $this->assertTrue($role->is_active);
    }

    public function test_can_create_role_with_permissions()
    {
        $permission1 = Permission::factory()->create();
        $permission2 = Permission::factory()->create();

        $data = [
            'name' => 'test_role',
            'display_name' => 'Test Role',
            'permissions' => [$permission1->id, $permission2->id]
        ];

        $role = $this->service->createRole($data);

        $this->assertTrue($role->hasPermission($permission1->name));
        $this->assertTrue($role->hasPermission($permission2->name));
    }

    public function test_can_update_role()
    {
        $role = Role::factory()->create(['display_name' => 'Old Name']);
        $permission = Permission::factory()->create();

        $data = [
            'display_name' => 'New Name',
            'permissions' => [$permission->id]
        ];

        $updatedRole = $this->service->updateRole($role, $data);

        $this->assertEquals('New Name', $updatedRole->display_name);
        $this->assertTrue($updatedRole->hasPermission($permission->name));
    }

    public function test_can_delete_role_without_users()
    {
        $role = Role::factory()->create();

        $result = $this->service->deleteRole($role);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
    }

    public function test_cannot_delete_role_with_users()
    {
        $role = Role::factory()->create();
        User::factory()->create(['role_id' => $role->id]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cannot delete role that is assigned to users');

        $this->service->deleteRole($role);
    }

    public function test_can_assign_role_to_user()
    {
        $user = User::factory()->create();
        $role = Role::factory()->create();

        $this->service->assignRoleToUser($user, $role);

        $user->refresh();
        $this->assertEquals($role->id, $user->role_id);
    }

    public function test_can_remove_role_from_user()
    {
        $role = Role::factory()->create();
        $user = User::factory()->create(['role_id' => $role->id]);

        $this->service->removeRoleFromUser($user);

        $user->refresh();
        $this->assertNull($user->role_id);
    }

    public function test_owner_user_has_all_permissions()
    {
        $user = User::factory()->create(['role' => 'owner']);
        $permission = Permission::factory()->create();

        $hasPermission = $this->service->userHasPermission($user, $permission->name);

        $this->assertTrue($hasPermission);
    }

    public function test_user_with_role_has_role_permissions()
    {
        $permission = Permission::factory()->create();
        $role = Role::factory()->create();
        $role->permissions()->attach($permission);
        
        $user = User::factory()->create(['role_id' => $role->id]);

        $hasPermission = $this->service->userHasPermission($user, $permission->name);

        $this->assertTrue($hasPermission);
    }

    public function test_user_without_role_has_no_permissions()
    {
        $user = User::factory()->create(['role' => 'staff', 'role_id' => null]);
        $permission = Permission::factory()->create();

        $hasPermission = $this->service->userHasPermission($user, $permission->name);

        $this->assertFalse($hasPermission);
    }

    public function test_can_seed_default_permissions()
    {
        $this->service->seedDefaultPermissions();

        $this->assertDatabaseHas('permissions', ['name' => 'dashboard.view']);
        $this->assertDatabaseHas('permissions', ['name' => 'invoices.create']);
        $this->assertDatabaseHas('permissions', ['name' => 'customers.view']);
    }

    public function test_can_seed_default_roles()
    {
        $this->service->seedDefaultPermissions();
        $this->service->seedDefaultRoles();

        $this->assertDatabaseHas('roles', ['name' => 'accountant']);
        $this->assertDatabaseHas('roles', ['name' => 'sales']);
        $this->assertDatabaseHas('roles', ['name' => 'inventory_manager']);
    }

    public function test_clears_cache_when_creating_role()
    {
        Cache::shouldReceive('forget')->with('all_roles')->once();
        Cache::shouldReceive('forget')->with('all_permissions')->once();

        $data = [
            'name' => 'test_role',
            'display_name' => 'Test Role'
        ];

        $role = $this->service->createRole($data);
        
        $this->assertInstanceOf(Role::class, $role);
        $this->assertEquals('test_role', $role->name);
    }
}