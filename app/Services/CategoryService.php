<?php

declare(strict_types = 1);

namespace App\Services;

use App\Models\Category;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class CategoryService extends BaseService {
    /**
     * Create a new service instance.
     */
    public function __construct(Category $category) {
        parent::__construct($category);
    }

    /**
     * Create a new category with validated data.
     */
    public function create(array $data): Model {
        $data = $this->applyCategoryBusinessRules($data);
        if (! empty($data['parent_id'])) {
            $parent       = $this->findById($data['parent_id']);
            $data['path'] = $parent->path ? $parent->path . '/' . $parent->id : $parent->id;
        }

        return parent::create($data);
    }

    /**
     * Get filtered categories with optional relationships.
     */
    public function getFiltered(array $filters = []): Builder {
        $query = $this->getQuery();

        // Apply filters
        $this->applyFilters($query, $filters);

        return $query;
    }

    /**
     * Process request parameters
     */
    public function processRequestParams(array $params): array {
        $this->validateParams($params);

        return [
            'name'      => $this->toString($params['name'] ?? null),
            'parent_id' => $this->toInt($params['parent_id'] ?? null),
            'is_active' => $this->toBool($params['is_active'] ?? null),
            'hierarchy' => $this->toBool($params['hierarchy'] ?? null),
            'with'      => $this->processWithParameter($params['with'] ?? null),
        ];
    }

    /**
     * Update a category with validated data.
     */
    public function update($id, array $data): Model {
        $data = $this->applyCategoryBusinessRules($data, $id);
        if (isset($data['parent_id'])) {
            if (! empty($data['parent_id'])) {
                $parent       = $this->findById($data['parent_id']);
                $data['path'] = $parent->path ? $parent->path . '/' . $parent->id : $parent->id;
            } else {
                $data['path'] = null;
            }
        }

        return parent::update($id, $data);
    }

    /**
     * Get allowed query parameters
     */
    protected function getAllowedParams(): array {
        return array_merge(parent::getAllowedParams(), [
            'name',
            'parent_id',
            'is_active',
            'hierarchy',
            'org_id',
        ]);
    }

    /**
     * Get valid relations for the model.
     */
    protected function getValidRelations(): array {
        return ['parent', 'children'];
    }

    /**
     * Apply business rules for category operations.
     */
    private function applyCategoryBusinessRules(array $data, $categoryId = null): array {
        if (! isset($data['is_active'])) {
            $data['is_active'] = true;
        }

        if (! empty($data['parent_id'])) {
            if ($categoryId && $data['parent_id'] === $categoryId) {
                throw new InvalidArgumentException('A category cannot be its own parent');
            }
        }

        return $data;
    }

    /**
     * Apply filters to the query
     */
    private function applyFilters(Builder $query, array $filters): void {
        $query->when($filters['name'] ?? null, static fn ($q, $value) => $q->where('name', 'like', "%{$value}%"))
            ->when($filters['parent_id'] ?? null, static fn ($q, $value) => $q->where('parent_id', $value))
            ->when(isset($filters['is_active']), static fn ($q) => $q->where('is_active', $filters['is_active']))
            ->when($filters['with'] ?? null, static fn ($q, $relations) => $q->with($relations));
    }
}
