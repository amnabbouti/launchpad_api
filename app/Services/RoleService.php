<?php

namespace App\Services;

use App\Models\Role;
use App\Services\AuthorizationEngine;
use App\Constants\Permissions;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class RoleService extends BaseService
{
    /**
     * Create a new service instance.
     */
    public function __construct(Role $role)
    {
        parent::__construct($role);
    }

    /**
     * Create a new custom role.
     */
    public function createCustomRole(array $data): Role
    {
        if (!AuthorizationEngine::canCreateCustomRole()) {
            throw new InvalidArgumentException('You do not have permission to create custom roles');
        }

        $data = $this->applyCustomRoleBusinessRules($data);
        $this->validateCustomRolePermissions($data);

        return DB::transaction(fn() => $this->create($data));
    }

    /**
     * Update an existing custom role.
     */
    public function updateCustomRole(int $roleId, array $data): Role
    {
        $role = $this->findById($roleId);

        if (!AuthorizationEngine::canModifyCustomRole($role)) {
            throw new InvalidArgumentException('You do not have permission to modify this role');
        }

        $data = $this->applyCustomRoleBusinessRules($data);
        $this->validateCustomRolePermissions($data);

        return DB::transaction(fn() => $this->update($roleId, $data));
    }

    /**
     * Delete a custom role.
     */
    public function deleteCustomRole(int $roleId): bool
    {
        $role = $this->findById($roleId);

        if ($role->isSystemRole()) {
            throw new InvalidArgumentException('Cannot delete system roles');
        }

        if (!AuthorizationEngine::canModifyCustomRole($role)) {
            throw new InvalidArgumentException('You do not have permission to delete this role');
        }

        if ($role->users()->exists()) {
            throw new InvalidArgumentException('Cannot delete role that is assigned to users');
        }

        return DB::transaction(fn() => $this->delete($roleId));
    }

    /**
     * Get roles that current user can assign.
     */
    public function getAssignableRoles(): array
    {
        return AuthorizationEngine::getAssignableRoles();
    }

    /**
     * Get roles with filters.
     */
    public function getFiltered(array $filters = []): Collection
    {
        $query = $this->getQuery();

        $query->when($filters['org_id'] ?? null, fn($q, $value) => $q->where('org_id', $value))
            ->when($filters['is_system'] ?? null, fn($q, $value) => $q->where('is_system', $value))
            ->when($filters['slug'] ?? null, fn($q, $value) => $q->where('slug', 'like', "%{$value}%"))
            ->when($filters['title'] ?? null, fn($q, $value) => $q->where('title', 'like', "%{$value}%"))
            ->when($filters['q'] ?? null, function ($q, $value) {
                return $q->where(function ($query) use ($value) {
                    $query->where('slug', 'like', "%{$value}%")
                        ->orWhere('title', 'like', "%{$value}%")
                        ->orWhere('description', 'like', "%{$value}%");
                });
            })
            ->when($filters['with'] ?? null, fn($q, $relations) => $q->with($relations));

        return $query->get();
    }

    /**
     * Get all roles.
     */
    public function getAllRoles(): Collection
    {
        return $this->all(['*'], [], [], ['title', 'asc']);
    }

    /**
     * Get organization roles (excludes super_admin).
     */
    public function getOrganizationRoles(): Collection
    {
        return $this->getQuery()
            ->whereIn('slug', ['manager', 'employee'])
            ->orderBy('title', 'asc')
            ->get();
    }

    /**
     * Process request parameters.
     */
    public function processRequestParams(array $params): array
    {
        $this->validateParams($params);
        return [
            'org_id' => $this->toInt($params['org_id'] ?? null),
            'is_system' => $this->toBool($params['is_system'] ?? null),
            'slug' => $this->toString($params['slug'] ?? null),
            'title' => $this->toString($params['title'] ?? null),
            'q' => $this->toString($params['q'] ?? null),
            'with' => $this->processWithParameter($params['with'] ?? null),
        ];
    }

    /**
     * Get allowed query parameters.
     */
    protected function getAllowedParams(): array
    {
        return array_merge(parent::getAllowedParams(), [
            'org_id',
            'is_system',
            'slug',
            'title',
            'q',
        ]);
    }

    /**
     * Get valid relations for the model.
     */
    protected function getValidRelations(): array
    {
        return [
            'organization',
            'users',
        ];
    }

    /**
     * Get permissions managers can control.
     */
    public function getAvailablePermissions(): array
    {
        return Permissions::getAvailablePermissionsForManagers();
    }

    /**
     * Get permission keys only.
     */
    public function getAvailablePermissionKeys(): array
    {
        return Permissions::getAvailablePermissionKeys();
    }

    /**
     * Add forbidden action to role.
     */
    public function addForbiddenAction(int $roleId, string $action): Role
    {
        $role = $this->findById($roleId);

        if (!AuthorizationEngine::canModifyCustomRole($role)) {
            throw new InvalidArgumentException('You do not have permission to modify this role');
        }

        $forbidden = $role->getForbidden();

        if (!in_array($action, $forbidden)) {
            $forbidden[] = $action;
            $role->update(['forbidden' => $forbidden]);
        }

        return $role->fresh();
    }

    /**
     * Remove forbidden action from role.
     */
    public function removeForbiddenAction(int $roleId, string $action): Role
    {
        $role = $this->findById($roleId);

        if (!AuthorizationEngine::canModifyCustomRole($role)) {
            throw new InvalidArgumentException('You do not have permission to modify this role');
        }

        $currentUser = AuthorizationEngine::getCurrentUser();

        if ($currentUser && $currentUser->isManager()) {
            $requiredForbidden = AuthorizationEngine::getRequiredForbiddenActionsForManagers();
            if (in_array($action, $requiredForbidden)) {
                throw new InvalidArgumentException("Cannot remove security-required permission: {$action}");
            }
        }

        $forbidden = $role->getForbidden();
        $forbidden = array_filter($forbidden, fn($item) => $item !== $action);
        $role->update(['forbidden' => array_values($forbidden)]);

        return $role->fresh();
    }

    /**
     * Apply business rules for custom roles.
     */
    private function applyCustomRoleBusinessRules(array $data): array
    {
        $currentUser = AuthorizationEngine::getCurrentUser();

        // Auto-assign organization for managers
        if ($currentUser && $currentUser->isManager() && !isset($data['org_id'])) {
            $data['org_id'] = $currentUser->org_id;
        }

        $data['is_system'] = false;

        if (!isset($data['forbidden'])) {
            $data['forbidden'] = [];
        }

        // Auto-enforce security restrictions for managers
        if ($currentUser && $currentUser->isManager()) {
            $requiredForbidden = AuthorizationEngine::getRequiredForbiddenActionsForManagers();
            $data['forbidden'] = array_unique(array_merge($data['forbidden'], $requiredForbidden));
        }

        return $data;
    }

    /**
     * Validate custom role permissions.
     */
    private function validateCustomRolePermissions(array $data): void
    {
        if (!isset($data['forbidden']) || !is_array($data['forbidden'])) {
            throw new InvalidArgumentException('Forbidden permissions must be provided as an array');
        }

        $errors = AuthorizationEngine::validateCustomRolePermissions($data['forbidden']);

        if (!empty($errors)) {
            throw new InvalidArgumentException('Security validation failed: ' . implode(', ', $errors));
        }
    }
}
