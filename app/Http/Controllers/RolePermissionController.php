<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Services\RolePermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RolePermissionController extends Controller
{
    private RolePermissionService $rolePermissionService;

    public function __construct(RolePermissionService $rolePermissionService)
    {
        $this->rolePermissionService = $rolePermissionService;
    }

    /**
     * Get all roles
     */
    public function getRoles(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->rolePermissionService->getAllRoles()
        ]);
    }

    /**
     * Get all permissions
     */
    public function getPermissions(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->rolePermissionService->getAllPermissions()
        ]);
    }

    /**
     * Create new role
     */
    public function createRole(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:roles,name',
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'is_active' => 'boolean',
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $role = $this->rolePermissionService->createRole($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Role created successfully',
            'data' => $role
        ], 201);
    }

    /**
     * Update role
     */
    public function updateRole(Request $request, Role $role): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'is_active' => 'boolean',
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $updatedRole = $this->rolePermissionService->updateRole($role, $validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Role updated successfully',
            'data' => $updatedRole
        ]);
    }

    /**
     * Delete role
     */
    public function deleteRole(Role $role): JsonResponse
    {
        try {
            $this->rolePermissionService->deleteRole($role);

            return response()->json([
                'success' => true,
                'message' => 'Role deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get user permissions
     */
    public function getUserPermissions(Request $request): JsonResponse
    {
        $user = $request->user();
        $permissions = $this->rolePermissionService->getUserPermissions($user);

        return response()->json([
            'success' => true,
            'data' => $permissions
        ]);
    }

    /**
     * Check user permission
     */
    public function checkPermission(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'permission' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();
        $hasPermission = $this->rolePermissionService->userHasPermission(
            $user,
            $request->permission
        );

        return response()->json([
            'success' => true,
            'data' => ['has_permission' => $hasPermission]
        ]);
    }

    /**
     * Assign role to user
     */
    public function assignRole(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'role_id' => 'required|exists:roles,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = \App\Models\User::findOrFail($request->user_id);
        $role = Role::findOrFail($request->role_id);

        $this->rolePermissionService->assignRoleToUser($user, $role);

        return response()->json([
            'success' => true,
            'message' => 'Role assigned successfully'
        ]);
    }

    /**
     * Remove role from user
     */
    public function removeRole(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = \App\Models\User::findOrFail($request->user_id);
        $this->rolePermissionService->removeRoleFromUser($user);

        return response()->json([
            'success' => true,
            'message' => 'Role removed successfully'
        ]);
    }
}