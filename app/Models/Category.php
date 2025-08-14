<?php

declare(strict_types = 1);

namespace App\Models;

use App\Traits\HasAttachments;
use App\Traits\HasUuidv7;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model {
    use HasAttachments;
    use HasFactory;
    use HasUuidv7;

    public const DELETING_WITH_CHILDREN_MESSAGE = 'Warning: Deleting a category with subcategories. Child categories will become top-level categories.';

    protected $casts = [
        'allow_tracking_transitions' => 'boolean',
        'min_value_for_serialized'   => 'decimal:2',
        'created_at'                 => 'datetime',
        'updated_at'                 => 'datetime',
    ];

    protected $fillable = [
        'org_id',
        'name',
        'parent_id',
        'path',
        'default_tracking_mode',
        'allow_tracking_transitions',
        'min_value_for_serialized',
    ];

    public function children(): HasMany {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function childrenRecursive() {
        return $this->children()->with('childrenRecursive');
    }

    public function items(): HasMany {
        return $this->hasMany(Item::class, 'category_id');
    }

    public function organization(): BelongsTo {
        return $this->belongsTo(Organization::class, 'org_id');
    }

    public function parent(): BelongsTo {
        return $this->belongsTo(self::class, 'parent_id');
    }

    protected static function booted(): void {
        self::deleting(static function ($category): void {
            $childrenCount = $category->children()->count();

            if ($childrenCount > 0) {
                \Illuminate\Support\Facades\Log::warning(Category::DELETING_WITH_CHILDREN_MESSAGE, [
                    'category_id'    => $category->id,
                    'category_name'  => $category->name,
                    'children_count' => $childrenCount,
                ]);

                event('category.deleting.with.children', $category);
            }
        });
    }
}
