<?php

namespace App\Services;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class PlanService extends BaseService
{
    public function __construct(Plan $plan)
    {
        parent::__construct($plan);
    }

    /**
     * Create new plan with business rules.
     */
    public function createPlan(array $data): Plan
    {
        $data = $this->applyPlanBusinessRules($data);
        $this->validatePlanBusinessRules($data);

        return DB::transaction(fn() => $this->create($data));
    }

    /**
     * Update plan with business rules.
     */
    public function updatePlan(int $planId, array $data): Plan
    {
        $data = $this->applyPlanBusinessRules($data, $planId);
        $this->validatePlanBusinessRules($data, $planId);

        return DB::transaction(fn() => $this->update($planId, $data));
    }

    /**
     * Cannot delete plan with active licenses.
     */
    public function deletePlan(int $planId): bool
    {
        $plan = $this->findById($planId);
        
        // Check if plan has active licenses
        if ($plan->licenses()->where('status', 'active')->exists()) {
            throw new \InvalidArgumentException('Cannot delete plan with active licenses');
        }

        return DB::transaction(fn() => $this->delete($planId));
    }

    /**
     * Get filtered plans.
     */
    public function getFiltered(array $filters = []): Collection
    {
        $query = $this->getQuery();

        $query->when(isset($filters['is_active']), fn ($q) => $q->where('is_active', $filters['is_active']))
            ->when($filters['interval'] ?? null, fn ($q, $value) => $q->where('interval', $value))
            ->when($filters['price_min'] ?? null, fn ($q, $value) => $q->where('price', '>=', $value))
            ->when($filters['price_max'] ?? null, fn ($q, $value) => $q->where('price', '<=', $value))
            ->when($filters['q'] ?? null, fn ($q, $value) => $q->where('name', 'like', "%{$value}%"))
            ->when($filters['with'] ?? null, fn ($q, $relations) => $q->with($relations))
            ->when($filters['with_counts'] ?? null, fn ($q, $value) => $value ? $q->withCount(['licenses', 'organizations']) : $q);

        return $query->get();
    }

    protected function getAllowedParams(): array
    {
        return array_merge(parent::getAllowedParams(), [
            'is_active',
            'interval',
            'price_min',
            'price_max',
            'q',
            'with_counts',
        ]);
    }

    protected function getValidRelations(): array
    {
        return [
            'licenses',
            'organizations',
        ];
    }

    public function processRequestParams(array $params): array
    {
        $this->validateParams($params);
        return [
            'is_active' => $this->toBool($params['is_active'] ?? null),
            'interval' => $this->toString($params['interval'] ?? null),
            'price_min' => $this->toInt($params['price_min'] ?? null),
            'price_max' => $this->toInt($params['price_max'] ?? null),
            'q' => $this->toString($params['q'] ?? null),
            'with_counts' => $this->toBool($params['with_counts'] ?? null),
            'with' => $this->processWithParameter($params['with'] ?? null),
        ];
    }

    /**
     * Apply business rules for plan operations.
     */
    private function applyPlanBusinessRules(array $data, $planId = null): array
    {
        // Set default values
        if (!isset($data['interval'])) {
            $data['interval'] = 'monthly';
        }

        if (!isset($data['is_active'])) {
            $data['is_active'] = true;
        }

        if (!isset($data['price'])) {
            $data['price'] = 0;
        }

        return $data;
    }

    /**
     * Validate business rules for plan operations.
     */
    private function validatePlanBusinessRules(array $data, $planId = null): void
    {
        // Validate required fields
        if (empty($data['name'])) {
            throw new \InvalidArgumentException('The name field is required');
        }

        // Validate name uniqueness
        $query = Plan::where('name', $data['name']);
        if ($planId) {
            $query->where('id', '!=', $planId);
        }
        if ($query->exists()) {
            throw new \InvalidArgumentException('The plan name has already been taken');
        }

        // Validate price
        if (isset($data['price']) && $data['price'] < 0) {
            throw new \InvalidArgumentException('Price cannot be negative');
        }

        // Validate user_limit
        if (isset($data['user_limit']) && $data['user_limit'] < 1) {
            throw new \InvalidArgumentException('User limit must be at least 1');
        }

        // Validate interval
        if (isset($data['interval']) && !in_array($data['interval'], ['monthly', 'yearly', 'lifetime'])) {
            throw new \InvalidArgumentException('Invalid interval. Must be monthly, yearly, or lifetime');
        }
    }
} 