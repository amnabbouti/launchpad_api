<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Category;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

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
     * Get filtered categories with optional relationships.
     */
    public function getFiltered(array $filters = []): Builder
    {
        $query = $this->getQuery();

        $query->when($filters['name'] ?? null, fn($q, $value) => $q->where('name', 'like', "%$value%"))
            ->when($filters['parent_id'] ?? null, fn($q, $value) => $q->where('parent_id', $value))
            ->when(isset($filters['is_active']), fn($q) => $q->where('is_active', $filters['is_active']))
            ->when($filters['with'] ?? null, fn($q, $relations) => $q->with($relations));

        return $query;
    }

    /**
     * Get root categories with full nested hierarchy.
     */
    public function getRootCategoriesWithHierarchy(): Collection
    {
        return $this->getQuery()
            ->whereNull('parent_id')
            ->with('childrenRecursive')
            ->get();
    }

    /**
     * Count all categories recursively
     */
    public function countAllCategoriesRecursively(Collection $categories): int
    {
        $count = $categories->count();

        foreach ($categories as $category) {
            if ($category->relationLoaded('childrenRecursive') && $category->childrenRecursive) {
                $count += $this->countAllCategoriesRecursively($category->childrenRecursive);
            }
        }

        return $count;
    }

    /**
     * Create a new category with validated data.
     */
    public function create(array $data): Model
    {
        $data = $this->applyCategoryBusinessRules($data);
        if (! empty($data['parent_id'])) {
            $parent = $this->findById($data['parent_id']);
            $data['path'] = $parent->path ? $parent->path . '/' . $parent->id : $parent->id;
        }

        return parent::create($data);
    }

    /**
     * Update a category with validated data.
     */
    public function update($id, array $data): Model
    {
        $data = $this->applyCategoryBusinessRules($data, $id);
        if (isset($data['parent_id'])) {
            if (! empty($data['parent_id'])) {
                $parent = $this->findById($data['parent_id']);
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
    protected function getAllowedParams(): array
    {
        return array_merge(parent::getAllowedParams(), [
            'org_id',
            'name',
            'parent_id',
            'is_active',
            'hierarchy',
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
     * Process request parameters
     */
    public function processRequestParams(array $params): array
    {
        $this->validateParams($params);

        return [
            'org_id' => $this->toInt($params['org_id'] ?? null),
            'name' => $this->toString($params['name'] ?? null),
            'parent_id' => $this->toInt($params['parent_id'] ?? null),
            'is_active' => $this->toBool($params['is_active'] ?? null),
            'hierarchy' => $this->toBool($params['hierarchy'] ?? null),
            'with' => $this->processWithParameter($params['with'] ?? null),
        ];
    }

    /**
     * Apply business rules for category operations.
     */
    private function applyCategoryBusinessRules(array $data, $categoryId = null): array
    {
        if (! isset($data['is_active'])) {
            $data['is_active'] = true;
        }

        if (! empty($data['parent_id'])) {
            if ($categoryId && $data['parent_id'] == $categoryId) {
                throw new InvalidArgumentException('A category cannot be its own parent');
            }
        }

        return $data;
    }
}
