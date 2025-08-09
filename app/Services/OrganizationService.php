<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Organization;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;

class OrganizationService extends BaseService
{
    public function __construct(Organization $organization)
    {
        parent::__construct($organization);
    }

    /**
     * Create an organization with business rules.
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
        $this->validateOrganizationBusinessRules($data);

        return $this->update($organizationId, $data);
    }

    /**
     * Super admins see all organizations
     */
    protected function getQuery(): Builder
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

        return $query->whereRaw('1 = 0');
    }

    /**
     * Get active organizations
     */
    public function getActive(): Builder
    {
        return $this->getQuery()->where('is_active', true);
    }

    /**
     * Get filtered organizations with optional relationships.
     */
    public function getFiltered(array $filters = []): Builder
    {
        $query = $this->getQuery();

        $query->when($filters['name'] ?? null, fn($q, $value) => $q->where('name', 'like', "%$value%"))
            ->when($filters['email'] ?? null, fn($q, $value) => $q->where('email', 'like', "%$value%"))
            ->when($filters['country'] ?? null, fn($q, $value) => $q->where('country', 'like', "%$value%"))
            ->when($filters['status'] ?? null, fn($q, $value) => $q->where('status', $value))
            ->when(isset($filters['is_active']), fn($q) => $q->where('is_active', $filters['is_active']))
            // plans removed
            ->when($filters['with'] ?? null, fn($q, $relations) => $q->with($relations));

        return $query;
    }

    /**
     * Process request parameters with validation and type conversion.
     */
    public function processRequestParams(array $params): array
    {
        $this->validateParams($params);

        return [
            'name' => $this->toString($params['name'] ?? null),
            'email' => $this->toString($params['email'] ?? null),
            'country' => $this->toString($params['country'] ?? null),
            'status' => $this->toString($params['status'] ?? null),
            'is_active' => $this->toBool($params['is_active'] ?? null),
            // plans removed
            'with' => $this->processWithParameter($params['with'] ?? null),
        ];
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
                'licenses',
                'users',
                'items',
                'categories',
                'locations',
                'suppliers',
                'batches',
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
            'licenses',
            'users',
            'items',
            'categories',
            'locations',
            'suppliers',
            'batches',
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
     * Get allowed query parameters.
     */
    protected function getAllowedParams(): array
    {
        return array_merge(parent::getAllowedParams(), [
            'name',
            'email',
            'country',
            'status',
            'is_active',
            // plans removed
        ]);
    }

    /**
     * Get valid relations for the model.
     */
    protected function getValidRelations(): array
    {
        return [
            'users',
            'items',
            'categories',
            'locations',
            'suppliers',
            'batches',
            'unitOfMeasures',
            'statuses',
            'itemStatuses',
            'maintenanceCategories',
            'maintenances',
            'checkInOuts',
            'attachments',
            'licenses',
        ];
    }

    /**
     * Apply business rules for organization operations.
     */
    private function applyOrganizationBusinessRules(array $data, $organizationId = null): array
    {
        if (! $organizationId && ! isset($data['created_by'])) {
            $data['created_by'] = AuthorizationEngine::getCurrentUser()?->id;
        }

        if (! isset($data['status'])) {
            $data['status'] = 'active';
        }

        return $data;
    }

    /**
     * Validate business rules for organization operations.
     */
    private function validateOrganizationBusinessRules(array $data): void
    {
        // Validate required fields
        $requiredFields = ['name', 'email', 'country', 'billing_address', 'tax_id'];

        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                throw new InvalidArgumentException("The $field field is required");
            }
        }

        // Validate email format
        if (! filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('The email must be a valid email address');
        }

        // Validate website URL if provided
        if (! empty($data['website'])) {
            if (! filter_var($data['website'], FILTER_VALIDATE_URL)) {
                throw new InvalidArgumentException('The website must be a valid URL');
            }
        }

        // Validate created_by user exists if provided
        if (isset($data['created_by'])) {
            $user = User::find($data['created_by']);
            if (! $user) {
                throw new InvalidArgumentException('The selected creator is invalid');
            }
        }
    }
}
