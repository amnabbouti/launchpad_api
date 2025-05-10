<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory, SoftDeletes;

    // Fields that can be mass assigned
    protected $fillable = [
        'name',
        'parent_id'
    ];

    // Event messages
    public const DELETING_WITH_CHILDREN_MESSAGE = 'Warning: Deleting a category with subcategories. Child categories will become top-level categories.';

    // Parent category relationship
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    // Direct child categories
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    // Load all nested subcategories for category trees
    public function childrenRecursive()
    {
        return $this->children()->with('childrenRecursive');
    }

    // Items belonging to this category
    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }

    // Boot the model
    protected static function booted(): void
    {
        // Check for children before deleting
        static::deleting(function ($category) {
            $childrenCount = $category->children()->count();
            if ($childrenCount > 0) {
                // Log a warning message
                \Illuminate\Support\Facades\Log::warning(Category::DELETING_WITH_CHILDREN_MESSAGE, [
                    'category_id' => $category->id,
                    'category_name' => $category->name,
                    'children_count' => $childrenCount
                ]);

                event('category.deleting.with.children', $category);
            }
        });
    }
}
