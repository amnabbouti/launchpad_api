<?php

namespace App\Services;

use App\Models\Organization;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

use App\Constants\ErrorMessages;

class OrganizationService extends BaseService
{
    public function __construct(Organization $organization)
    {
        parent::__construct($organization);
    }

    /**
     * Super admins see all organizations
     */
    protected function getQuery()
    {
        $query = parent::getQuery();
        $user = auth()->user();

        // Super admin can see all organizations
        if ($user && $user->isSuperAdmin()) {
            return $query;
        }

        // users can only see their own organization
        if ($user && $user->org_id) {
            return $query->where('id', $user->org_id);
        }

        // No user or no org_id means no access
        return $query->whereRaw('1 = 0');
    }

    /**
     * Update organization - restricted to super admins only
     */
    public function update($id, array $data): Model
    {
        $user = auth()->user();

        if (! $user || ! $user->isSuperAdmin()) {
            throw new AuthorizationException(ErrorMessages::FORBIDDEN);
        }

        return parent::update($id, $data);
    }

    /**
     * Delete organization - restricted to super admins only
     */
    public function delete($id): bool
    {
        $user = auth()->user();

        if (! $user || ! $user->isSuperAdmin()) {
            throw new AuthorizationException(ErrorMessages::FORBIDDEN);
        }

        return parent::delete($id);
    }

    /**
     * Get active organizations
     */
    public function getActive(): Collection
    {
        return $this->getQuery()->where('is_active', true)->get();
    }

    /**
     * 'with' parameter for relationship loading
     */
    public function parseRelationships($withParam): array
    {
        if (empty($withParam)) {
            return [];
        }

        // all available relationships
        if ($withParam === 'all') {
            return [
                'users',
                'items',
                'categories',
                'locations',
                'suppliers',
                'stocks',
                'unitOfMeasures',
                'statuses',
                'itemStatuses',
                'maintenanceCategories',
                'maintenances',
                'checkInOuts',
                'attachments',
            ];
        }

        // Convert string to array and validate relationships
        $relations = is_string($withParam)
            ? explode(',', $withParam)
            : (array) $withParam;

        // Define allowed relationships
        $allowedRelations = [
            'users',
            'items',
            'categories',
            'locations',
            'suppliers',
            'stocks',
            'unitOfMeasures',
            'statuses',
            'itemStatuses',
            'maintenanceCategories',
            'maintenances',
            'checkInOuts',
            'attachments',
        ];

        // Filter only allowed relationships
        return array_intersect(array_map('trim', $relations), $allowedRelations);
    }

    /**
     * Create organization - restricted to super admins only
     */
    public function create(array $data): Model
    {
        $user = auth()->user();
        if (! $user || ! $user->isSuperAdmin()) {
            throw new AuthorizationException('Forbidden');
        }
        return parent::create($data);
    }
}
