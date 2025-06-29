<?php

namespace App\Http\Controllers\Api\Hierarchy;

use App\Constants\HttpStatus;
use App\Constants\SuccessMessages;
use App\Http\Controllers\Api\BaseController;
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
        $roles = $this->roleService->getFiltered($filters);

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
        $filters = $this->roleService->processRequestParams($request->query());
        $role = $this->roleService->findById($id, ['*'], $filters['with'] ?? []);

        return $this->successResponse([new RoleResource($role)]);
    }

    /**
     * Create a new role.
     */
    public function store(StoreRoleRequest $request): JsonResponse
    {
        $role = $this->roleService->createCustomRole($request->validated());

        return $this->successResponse([new RoleResource($role)], SuccessMessages::RESOURCE_CREATED, HttpStatus::HTTP_CREATED);
    }

    /**
     * Update the specified role.
     */
    public function update(UpdateRoleRequest $request, int $id): JsonResponse
    {
        $role = $this->roleService->updateCustomRole($id, $request->validated());

        return $this->successResponse([new RoleResource($role)], SuccessMessages::RESOURCE_UPDATED);
    }

    /**
     * Remove the specified role.
     */
    public function destroy(int $id): JsonResponse
    {
        $this->roleService->deleteCustomRole($id);

        return $this->successResponse([], SuccessMessages::RESOURCE_DELETED);
    }

    /**
     * Get all available permissions that can be forbidden.
     */
    public function availableActions(): JsonResponse
    {
        $permissions = $this->roleService->getAvailablePermissions();

        return $this->successResponse([$permissions]);
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

        return $this->successResponse([new RoleResource($role)], SuccessMessages::ACTION_FORBIDDEN);
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

        return $this->successResponse([new RoleResource($role)], SuccessMessages::ACTION_ALLOWED);
    }
}
