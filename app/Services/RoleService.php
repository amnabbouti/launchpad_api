<?php

namespace App\Services;

use App\Models\Role;
use Illuminate\Database\Eloquent\Collection;

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
     * Get roles filtered by search.
     */
    public function getFiltered(array $filters = []): Collection
    {
        $query = $this->getQuery();

        $query->when($filters['search'] ?? null, function ($q, $search) {
            $q->where(function ($subQuery) use ($search) {
                $subQuery->where('slug', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%");
            });
        })
            ->when($filters['with'] ?? null, fn ($q, $relations) => $q->with($relations));

        return $query->orderBy('title', 'asc')->get();
    }

    /**
     * Get all roles.
     */
    public function getAllRoles(): Collection
    {
        return $this->all(['*'], [], [], ['title', 'asc']);
    }

    /**
     * Get organization roles - excludes super_admin.
     */
    public function getOrganizationRoles(): Collection
    {
        return $this->getQuery()
            ->whereIn('slug', ['manager', 'employee'])
            ->orderBy('title', 'asc')
            ->get();
    }

    /**
     * Process request parameters with validation and type conversion.
     */
    public function processRequestParams(array $params): array
    {
        // Validate parameters against whitelist
        $this->validateParams($params);

        return [
            'search' => $this->toString($params['search'] ?? null),
            'slug' => $this->toString($params['slug'] ?? null),
            'title' => $this->toString($params['title'] ?? null),
            'is_active' => $this->toBool($params['is_active'] ?? null),
            'with' => $this->processWithParameter($params['with'] ?? null),
        ];
    }

    /**
     * Get allowed query parameters.
     */
    protected function getAllowedParams(): array
    {
        return array_merge(parent::getAllowedParams(), [
            'search', 'slug', 'title', 'is_active',
        ]);
    }

    /**
     * Get valid relations for the model.
     */
    protected function getValidRelations(): array
    {
        return ['users'];
    }

    /**
     * Get all available actions that can be forbidden.
     */
    public function getAvailableActions(): array
    {
        return [
            'users.create',
            'users.edit',
            'users.delete',
            'users.view',
            'users.edit.role_self',
            'items.create',
            'items.update',
            'items.delete',
            'items.view',
            'categories.create',
            'categories.update',
            'categories.delete',
            'categories.view',
            'maintenance.create',
            'maintenance.update',
            'maintenance.delete',
            'maintenance.view',
            'locations.create',
            'locations.update',
            'locations.delete',
            'locations.view',
            'suppliers.create',
            'suppliers.update',
            'suppliers.delete',
            'suppliers.view',
            'roles.create',
            'roles.update',
            'roles.delete',
            'roles.view',
            'organizations.create',
            'organizations.update',
            'organizations.delete',
            'organizations.view',
        ];
    }

    /**
     * Add a forbidden action to role.
     */
    public function addForbiddenAction(int $roleId, string $action): Role
    {
        $role = $this->findById($roleId);
        $forbidden = $role->getForbidden();
        
        if (!in_array($action, $forbidden)) {
            $forbidden[] = $action;
            $role->update(['forbidden' => $forbidden]);
        }
        
        return $role->fresh();
    }

    /**
     * Remove a forbidden action from role (allow the action).
     */
    public function removeForbiddenAction(int $roleId, string $action): Role
    {
        $role = $this->findById($roleId);
        $forbidden = $role->getForbidden();
        
        $forbidden = array_filter($forbidden, fn($item) => $item !== $action);
        $role->update(['forbidden' => array_values($forbidden)]);
        
        return $role->fresh();
    }
}
