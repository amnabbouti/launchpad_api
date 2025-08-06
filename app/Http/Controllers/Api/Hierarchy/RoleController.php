<?php

namespace App\Http\Controllers\Api\Hierarchy;

use App\Http\Controllers\Api\BaseController;
use App\Http\Middleware\ApiResponseMiddleware;
use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Http\Resources\RoleResource;
use App\Services\RoleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoleController extends BaseController
{
    /**
     * Create a new controller instance with the role service.
     */
    public function __construct(
        private RoleService $roleService,
    ) {}

    /**
     * Get all roles with filtering.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $this->roleService->processRequestParams($request->query());
        $rolesQuery = $this->roleService->getFiltered($filters);
        $totalCount = $rolesQuery->count();

        $roles = $this->paginated($rolesQuery, $request);

        return ApiResponseMiddleware::listResponse(
            RoleResource::collection($roles),
            'role',
            $totalCount
        );
    }

    /**
     * Get all available roles for dropdowns/selects.
     */
    public function all(): JsonResponse
    {
        $roles = $this->roleService->getAllRoles();
        $totalCount = $roles->count();

        return ApiResponseMiddleware::listResponse(
            RoleResource::collection($roles),
            'role',
            $totalCount
        );
    }

    /**
     * Get organization-specific roles.
     */
    public function organizationRoles(): JsonResponse
    {
        $roles = $this->roleService->getOrganizationRoles();
        $totalCount = $roles->count();

        return ApiResponseMiddleware::listResponse(
            RoleResource::collection($roles),
            'role',
            $totalCount
        );
    }

    /**
     * Display the specified role.
     */
    public function show(int $id, Request $request): JsonResponse
    {
        $filters = $this->roleService->processRequestParams($request->query());
        $role = $this->roleService->findById($id, ['*'], $filters['with'] ?? []);

        return ApiResponseMiddleware::showResponse(
            new RoleResource($role),
            'role',
            $role->toArray()
        );
    }

    /**
     * Create a new role.
     */
    public function store(StoreRoleRequest $request): JsonResponse
    {
        $role = $this->roleService->createCustomRole($request->validated());

        return ApiResponseMiddleware::createResponse(
            new RoleResource($role),
            'role',
            $role->toArray()
        );
    }

    /**
     * Update the specified role.
     */
    public function update(UpdateRoleRequest $request, int $id): JsonResponse
    {
        $role = $this->roleService->updateCustomRole($id, $request->validated());

        return ApiResponseMiddleware::updateResponse(
            new RoleResource($role),
            'role',
            $role->toArray()
        );
    }

    /**
     * Remove the specified role.
     */
    public function destroy(int $id): JsonResponse
    {
        $this->roleService->deleteCustomRole($id);

        return ApiResponseMiddleware::deleteResponse('role');
    }

    /**
     * Get all available permissions that can be forbidden.
     */
    public function availableActions(): JsonResponse
    {
        $permissions = $this->roleService->getAvailablePermissions();

        return ApiResponseMiddleware::showResponse(
            ['permissions' => $permissions],
            'role',
            []
        );
    }

    /**
     * Add forbidden action to role.
     */
    public function addForbiddenAction(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'action' => 'required|string',
        ]);

        $role = $this->roleService->addForbiddenAction($id, $request->action);

        return ApiResponseMiddleware::updateResponse(
            new RoleResource($role),
            'role',
            $role->toArray()
        );
    }

    /**
     * Remove forbidden action from role (allow the action).
     */
    public function removeForbiddenAction(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'action' => 'required|string',
        ]);

        $role = $this->roleService->removeForbiddenAction($id, $request->action);

        return ApiResponseMiddleware::updateResponse(
            new RoleResource($role),
            'role',
            $role->toArray()
        );
    }
}
