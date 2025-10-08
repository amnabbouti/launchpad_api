<?php

declare(strict_types = 1);

namespace App\Models;

use App\Traits\HasAttachments;
use App\Traits\HasUuidv7;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model {
    use HasAttachments;
    use HasFactory;
    use HasUuidv7;

    public const DELETING_WITH_CHILDREN_MESSAGE = 'Warning: Deleting a location with child locations. Child locations will become top-level locations.';

    protected $casts = [
        'is_active'  => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $fillable = [
        'org_id',
        'name',
        'code',
        'parent_id',
        'path',
        'description',
        'is_active',
    ];

    public function children(): HasMany {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function childrenRecursive() {
        return $this->children()->with('childrenRecursive');
    }

    public function items(): BelongsToMany {
        return $this->belongsToMany(Item::class, 'item_locations', 'location_id', 'item_id')
            ->withPivot('quantity')
            ->withTimestamps();
    }

    public function organization(): BelongsTo {
        return $this->belongsTo(Organization::class, 'org_id');
    }

    public function parent(): BelongsTo {
        return $this->belongsTo(self::class, 'parent_id');
    }

    protected static function booted(): void {
        self::created(function ($location): void {
            $path           = ($location->parent->path ?? '/') . $location->id . '/';
            $location->path = $path;
            $location->saveQuietly();
        });

        self::deleting(static function ($location): void {
            $childrenCount = $location->children()->count();

            if ($childrenCount > 0) {
                \Illuminate\Support\Facades\Log::warning(Location::DELETING_WITH_CHILDREN_MESSAGE, [
                    'location_id'    => $location->id,
                    'location_name'  => $location->name,
                    'location_code'  => $location->code,
                    'children_count' => $childrenCount,
                ]);

                event('location.deleting.with.children', $location);
            }
        });
    }
}
