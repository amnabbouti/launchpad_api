<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Location extends Model
{
    use HasFactory, SoftDeletes;

    // Fields that can be mass assigned
    protected $fillable = [
        'name',
        'code',
        'parent_id',
        'path'
    ];

    // Event messages
    public const DELETING_WITH_CHILDREN_MESSAGE = 'Warning: Deleting a location with child locations. Child locations will become top-level locations.';

    // Type casting for attributes
    protected $casts = [
        'is_active' => 'boolean'
    ];

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

    // Loads all nested children recursively for hierarchy navigation
    public function childrenRecursive()
    {
        return $this->children()->with('childrenRecursive');
    }

    // Items stored at this location with quantities
    public function items(): BelongsToMany
    {
        return $this->belongsToMany(Item::class)
            ->withPivot('quantity')
            ->withTimestamps()
            ->withTrashed(); // Include soft deleted pivot records
    }

    // Automatically build hierarchical path after creation
    protected static function booted(): void
    {
        static::created(function ($location) {
            // Format: /parent_id/current_id/
            $path = ($location->parent->path ?? '/') . $location->id . '/';
            $location->path = $path;
            $location->saveQuietly();
        });

        // Check for children before deleting
        static::deleting(function ($location) {
            $childrenCount = $location->children()->count();
            if ($childrenCount > 0) {
                // Log a warning message
                \Illuminate\Support\Facades\Log::warning(Location::DELETING_WITH_CHILDREN_MESSAGE, [
                    'location_id' => $location->id,
                    'location_name' => $location->name,
                    'location_code' => $location->code,
                    'children_count' => $childrenCount
                ]);

                // dispatching an event here that controllers can listen for
                // to show a confirmation dialog to the user
                event('location.deleting.with.children', $location);
            }
        });
    }
}
