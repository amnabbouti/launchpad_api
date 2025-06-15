<?php

namespace App\Models;

use App\Traits\HasAttachments;
use App\Traits\HasOrganizationScope;
use App\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasAttachments;
    use HasFactory;
    use HasOrganizationScope;
    use HasPublicId;

    public const DELETING_WITH_CHILDREN_MESSAGE = 'Warning: Deleting a category with subcategories. Child categories will become top-level categories.';

    protected $fillable = [
        'org_id',
        'name',
        'parent_id',
        'path',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function getEntityType(): string
    {
        return 'category';
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'org_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function childrenRecursive()
    {
        return $this->children()->with('childrenRecursive');
    }

    public function items(): HasMany
    {
        return $this->hasMany(Item::class, 'category_id');
    }

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
