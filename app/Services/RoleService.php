<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\Permissions;
use App\Models\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

use function in_array;
use function is_array;

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
     * Add forbidden action to a role.
     */
    public function addForbiddenAction(string $roleId, string $action): Role
    {
        $role = $this->findById($roleId);
        $forbidden = $role->getForbidden();
        if (! in_array($action, $forbidden, true)) {
            $forbidden[] = $action;
            $role->update(['forbidden' => $forbidden]);
        }

        return $role->fresh();
    }

    /**
     * Create a new custom role.
     */
    public function createCustomRole(array $data): Role
    {
        $data = $this->applyCustomRoleBusinessRules($data);
        $this->validateCustomRolePermissions($data);
        return DB::transaction(fn() => $this->create($data));
    }

    /**
     * Delete a custom role.
     */
    public function deleteCustomRole(string $roleId): bool
    {
        $role = $this->findById($roleId);

        if ($role->isSystemRole()) {
            throw new InvalidArgumentException('Cannot delete system roles');
        }

        if ($role->users()->exists()) {
            throw new InvalidArgumentException('Cannot delete role that is assigned to users');
        }

        return DB::transaction(fn() => $this->delete($roleId));
    }

    /**
     * Get all roles.
     */
    public function getAllRoles(): Collection
    {
        return $this->all(['*'], [], [], ['title', 'asc'])->get();
    }

    /**
     * Get permissions managers can control.
     */
    public function getAvailablePermissions(): array
    {
        return Permissions::getAvailablePermissionsForManagers();
    }

    /**
     * Get roles with filters.
     */
    public function getFiltered(array $filters = []): Builder
    {
        $query = $this->getQuery();

        $query->when($filters['is_system'] ?? null, static fn($q, $value) => $q->where('is_system', $value))
            ->when($filters['slug'] ?? null, static fn($q, $value) => $q->where('slug', 'like', "%$value%"))
            ->when($filters['title'] ?? null, static fn($q, $value) => $q->where('title', 'like', "%$value%"))
            ->when($filters['q'] ?? null, static function ($q, $value) {
                return $q->where(static function ($query) use ($value): void {
                    $query->where('slug', 'like', "%$value%")
                        ->orWhere('title', 'like', "%$value%")
                        ->orWhere('description', 'like', "%$value%");
                });
            })
            ->when($filters['with'] ?? null, static fn($q, $relations) => $q->with($relations));

        return $query;
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
            'is_system' => $this->toBool($params['is_system'] ?? null),
            'slug'      => $this->toString($params['slug'] ?? null),
            'title'     => $this->toString($params['title'] ?? null),
            'q'         => $this->toString($params['q'] ?? null),
            'with'      => $this->processWithParameter($params['with'] ?? null),
        ];
    }

    /**
     * Remove forbidden action from role.
     */
    public function removeForbiddenAction(string $roleId, string $action): Role
    {
        $role = $this->findById($roleId);
        $forbidden = $role->getForbidden();
        $forbidden = array_filter($forbidden, static fn($item) => $item !== $action);
        $role->update(['forbidden' => array_values($forbidden)]);
        return $role->fresh();
    }

    /**
     * Update an existing custom role.
     */
    public function updateCustomRole(string $roleId, array $data): Role
    {
        $role = $this->findById($roleId);
        $data = $this->applyCustomRoleBusinessRules($data);
        $this->validateCustomRolePermissions($data);

        return DB::transaction(fn() => $this->update($roleId, $data));
    }

    /**
     * Get allowed query parameters.
     */
    protected function getAllowedParams(): array
    {
        return array_merge(parent::getAllowedParams(), [
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
     * Apply business rules for custom roles.
     */
    private function applyCustomRoleBusinessRules(array $data): array
    {
        $data['is_system'] = false;

        if (! isset($data['forbidden'])) {
            $data['forbidden'] = [];
        }

        // Enforce manager restrictions: automatically add required forbidden permissions
        $requiredForbidden = array_keys(Permissions::getRequiredForbiddenPermissions());
        $data['forbidden'] = array_unique(array_merge($data['forbidden'], $requiredForbidden));

        return $data;
    }

    /**
     * Validate custom role permissions.
     */
    private function validateCustomRolePermissions(array $data): void
    {
        if (! isset($data['forbidden']) || ! is_array($data['forbidden'])) {
            throw new InvalidArgumentException('Forbidden permissions must be provided as an array');
        }

        // Validate that all required forbidden permissions are present
        $requiredForbidden = array_keys(Permissions::getRequiredForbiddenPermissions());
        $missingRequired = array_diff($requiredForbidden, $data['forbidden']);

        if (! empty($missingRequired)) {
            throw new InvalidArgumentException(
                'Custom roles must include these forbidden permissions: ' . implode(', ', $missingRequired)
            );
        }

        // Validate that all forbidden permissions are valid
        $validPermissions = Permissions::getAvailablePermissionKeys();
        $invalidPermissions = array_diff($data['forbidden'], $validPermissions);

        if (! empty($invalidPermissions)) {
            throw new InvalidArgumentException(
                'Invalid forbidden permissions: ' . implode(', ', $invalidPermissions)
            );
        }
    }
}
