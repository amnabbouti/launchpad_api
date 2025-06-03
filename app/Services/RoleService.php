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
}
