<?php

namespace App\Http\Controllers\Api\Hierarchy;

use App\Http\Controllers\Api\BaseController;
use App\Http\Resources\RoleResource;
use App\Services\RoleService;
use Illuminate\Http\JsonResponse;

class RoleController extends BaseController
{
    /**
     * Create a new controller instance with the role service.
     */
    public function __construct(
        private RoleService $roleService,
    ) {}

    /**
     * Get all roles.
     */
    public function index(): JsonResponse
    {
        // Collect raw query parameters
        $rawParams = [
            'name' => request()->query('name'),
            'slug' => request()->query('slug'),
            'type' => request()->query('type'),
            'with' => request()->query('with'),
        ];

        $roles = $this->roleService->getFiltered($rawParams);

        return $this->successResponse(RoleResource::collection($roles));
    }

    /**
     * Get all available roles for dropdowns/selects.
     */
    public function all(): JsonResponse
    {
        $roles = $this->roleService->getAllRoles();

        return $this->successResponse(RoleResource::collection($roles));
    }

    /**
     * Get organization-specific roles.
     */
    public function organizationRoles(): JsonResponse
    {
        $roles = $this->roleService->getOrganizationRoles();

        return $this->successResponse(RoleResource::collection($roles));
    }

    /**
     * Display the specified role.
     */
    public function show(int $id, Request $request): JsonResponse
    {
        $with = $request->query('with') ? explode(',', $request->query('with')) : [];
        $role = $this->roleService->findById($id, ['*'], $with);

        return response()->json([
            'data' => new RoleResource($role),
        ]);
    }

    /**
     * Update the specified role.
     */
    public function update(UpdateRoleRequest $request, int $id): JsonResponse
    {
        $role = $this->roleService->update($id, $request->validated());

        return response()->json([
            'message' => 'Role updated successfully',
            'data' => new RoleResource($role),
        ]);
    }

    /**
     * Remove the specified role.
     */
    public function destroy(int $id): JsonResponse
    {
        $this->roleService->delete($id);

        return response()->json([
            'message' => 'Role deleted successfully',
        ]);
    }

    /**
     * Get all available permissions.
     */
    public function permissions(): JsonResponse
    {
        $permissions = $this->roleService->getAvailablePermissions();

        return response()->json([
            'data' => $permissions,
        ]);
    }

    /**
     * Add permission to role.
     */
    public function addPermission(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'permission' => 'required|string',
        ]);

        $role = $this->roleService->addPermission($id, $request->permission);

        return response()->json([
            'message' => 'Permission added successfully',
            'data' => new RoleResource($role),
        ]);
    }

    /**
     * Remove permission from role.
     */
    public function removePermission(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'permission' => 'required|string',
        ]);

        $role = $this->roleService->removePermission($id, $request->permission);

        return response()->json([
            'message' => 'Permission removed successfully',
            'data' => new RoleResource($role),
        ]);
    }
}
