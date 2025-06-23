<?php

namespace App\Models;

use App\Traits\HasAttachments;
use App\Traits\HasOrganizationScope;
use App\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    use HasAttachments;
    use HasFactory;
    use HasOrganizationScope;
    use HasPublicId;

    public const DELETING_WITH_CHILDREN_MESSAGE = 'Warning: Deleting a location with child locations. Child locations will become top-level locations.';

    protected $fillable = [
        'org_id',
        'name',
        'code',
        'parent_id',
        'path',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function getEntityType(): string
    {
        return 'location';
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'org_id');
    }

    // Parent location relationship
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'parent_id');
    }

    // Direct child locations
    public function children(): HasMany
    {
        return $this->hasMany(Location::class, 'parent_id');
    }

    // nested children recursively for hierarchy
    public function childrenRecursive()
    {
        return $this->children()->with('childrenRecursive');
    }

    // Items stored with quantities
    public function items(): BelongsToMany
    {
        return $this->belongsToMany(Item::class, 'item_locations', 'location_id', 'item_id')
            ->withPivot('quantity')
            ->withTimestamps();
    }

    protected static function booted(): void
    {
        static::created(function ($location) {
            $path = ($location->parent->path ?? '/').$location->id.'/';
            $location->path = $path;
            $location->saveQuietly();
        });

        static::deleting(function ($location) {
            $childrenCount = $location->children()->count();

            if ($childrenCount > 0) {
                \Illuminate\Support\Facades\Log::warning(Location::DELETING_WITH_CHILDREN_MESSAGE, [
                    'location_id' => $location->id,
                    'location_name' => $location->name,
                    'location_code' => $location->code,
                    'children_count' => $childrenCount,
                ]);

                event('location.deleting.with.children', $location);
            }
        });
    }
}
