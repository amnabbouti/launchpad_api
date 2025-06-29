<?php

namespace App\Services;

use App\Models\Organization;
use App\Services\AuthorizationEngine;
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
     * Create organization with business rules.
     */
    public function createOrganization(array $data): Organization
    {
        $data = $this->applyOrganizationBusinessRules($data);
        $this->validateOrganizationBusinessRules($data);

        return $this->create($data);
    }

    /**
     * Update organization with business rules.
     */
    public function updateOrganization(int $organizationId, array $data): Organization
    {
        $data = $this->applyOrganizationBusinessRules($data, $organizationId);
        $this->validateOrganizationBusinessRules($data, $organizationId);

        return $this->update($organizationId, $data);
    }

    /**
     * Super admins see all organizations
     */
    protected function getQuery()
    {
        $query = $this->model->newQuery();
        $user = AuthorizationEngine::getCurrentUser();

        // Super admin can see all organizations
        if ($user && AuthorizationEngine::isSuperAdmin($user)) {
            return $query;
        }

        // Regular users can only see their own organization
        if ($user && $user->org_id) {
            return $query->where('id', $user->org_id);
        }

        // No user or no org_id means no access
        return $query->whereRaw('1 = 0');
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
     * Apply business rules for organization operations.
     */
    private function applyOrganizationBusinessRules(array $data, $organizationId = null): array
    {
        // Set created_by if not provided and it's a new organization
        if (!$organizationId && !isset($data['created_by'])) {
            $data['created_by'] = AuthorizationEngine::getCurrentUser()?->id;
        }

        // Set default status if not provided
        if (!isset($data['status'])) {
            $data['status'] = 'active';
        }

        return $data;
    }

    /**
     * Validate business rules for organization operations.
     */
    private function validateOrganizationBusinessRules(array $data, $organizationId = null): void
    {
        // Validate required fields
        $requiredFields = ['name', 'email', 'country', 'billing_address', 'tax_id', 'plan_id'];
        
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                throw new \InvalidArgumentException("The {$field} field is required");
            }
        }

        // Validate email format
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('The email must be a valid email address');
        }

        // Validate website URL if provided
        if (isset($data['website']) && !empty($data['website'])) {
            if (!filter_var($data['website'], FILTER_VALIDATE_URL)) {
                throw new \InvalidArgumentException('The website must be a valid URL');
            }
        }

        // Validate subscription date relationship
        if (isset($data['subscription_starts_at']) && isset($data['subscription_ends_at'])) {
            $startDate = \Carbon\Carbon::parse($data['subscription_starts_at']);
            $endDate = \Carbon\Carbon::parse($data['subscription_ends_at']);
            
            if ($endDate->isBefore($startDate)) {
                throw new \InvalidArgumentException('The subscription end date must be after or equal to the subscription start date');
            }
        }

        // Validate plan exists
        if (isset($data['plan_id'])) {
            $plan = \App\Models\Plan::find($data['plan_id']);
            if (!$plan) {
                throw new \InvalidArgumentException('The selected plan ID is invalid');
            }
        }

        // Validate created_by user exists if provided
        if (isset($data['created_by'])) {
            $user = \App\Models\User::find($data['created_by']);
            if (!$user) {
                throw new \InvalidArgumentException('The selected creator is invalid');
            }
        }
    }
}
