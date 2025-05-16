<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use HasFactory;
    use SoftDeletes;

    // Fields that can be mass assigned
    protected $fillable = [
        'name',
        'parent_id',
    ];

    // Event messages
    public const DELETING_WITH_CHILDREN_MESSAGE = 'Warning: Deleting a category with subcategories. Child categories will become top-level categories.';

    // Parent
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    // Children
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    // Recursive children
    public function childrenRecursive()
    {
        return $this->children()->with('childrenRecursive');
    }

    // Items
    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }

    // Boot
    protected static function booted(): void
    {
        static::deleting(function ($category) {
            $childrenCount = $category->children()->count();
            if ($childrenCount > 0) {
                \Illuminate\Support\Facades\Log::warning(Category::DELETING_WITH_CHILDREN_MESSAGE, [
                    'category_id' => $category->id,
                    'category_name' => $category->name,
                    'children_count' => $childrenCount,
                ]);

                event('category.deleting.with.children', $category);
            }
        });
    }
}
