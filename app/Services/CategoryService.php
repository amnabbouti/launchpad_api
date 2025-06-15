<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class CategoryService extends BaseService
{
    /**
     * Create a new service instance.
     */
    public function __construct(Category $category)
    {
        parent::__construct($category);
    }

    /**
     * Get categories with their relationships.
     */
    public function getWithRelations(array $relations = []): Collection
    {
        return $this->getQuery()->with($relations)->get();
    }

    /**
     * Get categories by parent ID.
     */
    public function getByParentId(int $parentId): Collection
    {
        return $this->getQuery()->where('parent_id', $parentId)->get();
    }

    /**
     * Get active categories.
     */
    public function getActive(): Collection
    {
        return $this->getQuery()->where('is_active', true)->get();
    }

    /**
     * Get filtered categories with optional relationships.
     */
    public function getFiltered(array $filters = []): Collection
    {
        $query = $this->getQuery();

        // Apply filters
        $query->when($filters['org_id'] ?? null, fn ($q, $value) => $q->where('org_id', $value))
            ->when($filters['name'] ?? null, fn ($q, $value) => $q->where('name', 'like', "%{$value}%"))
            ->when($filters['parent_id'] ?? null, fn ($q, $value) => $q->where('parent_id', $value))
            ->when(isset($filters['is_active']), fn ($q) => $q->where('is_active', $filters['is_active']))
            ->when($filters['with'] ?? null, fn ($q, $relations) => $q->with($relations));

        return $query->get();
    }

    /**
     * Create a new category with validated data.
     */
    public function create(array $data): Model
    {
        // Handle path for hierarchical categories
        if (! empty($data['parent_id'])) {
            $parent = $this->findById($data['parent_id']);
            $data['path'] = $parent->path ? $parent->path.'/'.$parent->id : $parent->id;
        }

        return parent::create($data);
    }

    /**
     * Update a category with validated data.
     */
    public function update($id, array $data): Model
    {
        if (isset($data['parent_id'])) {
            if (! empty($data['parent_id'])) {
                $parent = $this->findById($data['parent_id']);
                $data['path'] = $parent->path ? $parent->path.'/'.$parent->id : $parent->id;
            } else {
                $data['path'] = null;
            }
        }

        return parent::update($id, $data);
    }

    /**
     * Get allowed query parameters
     */
    protected function getAllowedParams(): array
    {
        return array_merge(parent::getAllowedParams(), [
            'org_id', 'name', 'parent_id', 'is_active',
        ]);
    }

    /**
     * Get valid relations for the model.
     */
    protected function getValidRelations(): array
    {
        return ['parent', 'children'];
    }

    /**
     * Process request parameters with explicit validation and type conversion.
     */
    public function processRequestParams(array $params): array
    {
        // Validate parameters against whitelist for security
        $this->validateParams($params);

        return [
            'org_id' => $this->toInt($params['org_id'] ?? null),
            'name' => $this->toString($params['name'] ?? null),
            'parent_id' => $this->toInt($params['parent_id'] ?? null),
            'is_active' => $this->toBool($params['is_active'] ?? null),
            'with' => $this->processWithParameter($params['with'] ?? null),
        ];
    }
}
