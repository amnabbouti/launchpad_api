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
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Item extends Model
{
    use HasAttachments;
    use HasFactory;
    use HasOrganizationScope;
    use HasPublicId;

    /**
     * ID prefix for Items
     */
    protected string $publicIdPrefix = 'ITM';

    /**
     * Tracking mode constants
     */
    const TRACKING_ABSTRACT = 'abstract';     
    const TRACKING_BULK = 'bulk';             
    const TRACKING_SERIALIZED = 'serialized'; 

    protected static function getEntityType(): string
    {
        return 'item';
    }

    protected $fillable = [
        'org_id',
        'name',
        'code',
        'barcode',
        'description',
        'tracking_mode',
        'unit_id',
        'price',
        'serial_number',
        'status_id',
        'notes',
        'is_active',
        'specifications',
        'category_id',
        'user_id',
        'parent_item_id',
        'item_relation_id',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
        'specifications' => 'json',
        'tracking_mode' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $hidden = [];

    protected $appends = [
        'public_id', 
        'type'
    ];

    /**
     * Model event handlers
     */
    protected static function booted()
    {
        static::creating(function ($item) {
            $item->applyTrackingModeConstraints();
        });

        static::updating(function ($item) {
            if ($item->isDirty('tracking_mode')) {
                $item->applyTrackingModeConstraints();
            }
        });
    }

    public static function rules(): array
    {
        return [
            'price' => 'nullable|numeric|min:0',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'org_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function unitOfMeasure(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class, 'unit_id');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class, 'status_id');
    }

    public function parentItem(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'parent_item_id');
    }

    public function childItems(): HasMany
    {
        return $this->hasMany(Item::class, 'parent_item_id');
    }

    public function relatedItem(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_relation_id');
    }

    public function itemRelations(): HasMany
    {
        return $this->hasMany(Item::class, 'item_relation_id');
    }

    // Direct relationships for bulk/serialized items
    public function itemLocations(): HasMany
    {
        return $this->hasMany(ItemLocation::class, 'item_id');
    }
    
    // Many-to-many relationship with locations through item_locations
    public function locations(): BelongsToMany
    {
        return $this->belongsToMany(Location::class, 'item_locations', 'item_id', 'location_id')
            ->withPivot('quantity')
            ->withTimestamps();
    }

    public function movements(): HasMany
    {
        return $this->hasMany(ItemMovement::class, 'item_id');
    }

    public function checkInOuts(): MorphMany
    {
        return $this->morphMany(CheckInOut::class, 'trackable');
    }

    public function maintenances(): MorphMany
    {
        return $this->morphMany(Maintenance::class, 'maintainable');
    }

    public function maintenanceConditions(): HasMany
    {
        return $this->hasMany(MaintenanceCondition::class, 'item_id');
    }

    public function suppliers(): BelongsToMany
    {
        return $this->belongsToMany(Supplier::class, 'item_supplier', 'item_id', 'supplier_id')
            ->withPivot('supplier_part_number', 'price', 'lead_time_days', 'is_preferred')
            ->withTimestamps();
    }

    /**
     * Get the type attribute
     */
    public function getTypeAttribute(): string
    {
        return $this->tracking_mode;
    }

    /**
     * Scopes for different tracking modes
     */
    public function scopeAbstract($query)
    {
        return $query->where('tracking_mode', self::TRACKING_ABSTRACT);
    }

    public function scopeBulk($query)
    {
        return $query->where('tracking_mode', self::TRACKING_BULK);
    }

    public function scopeSerialized($query)
    {
        return $query->where('tracking_mode', self::TRACKING_SERIALIZED);
    }

    public function scopePhysical($query)
    {
        return $query->whereIn('tracking_mode', [self::TRACKING_BULK, self::TRACKING_SERIALIZED]);
    }

    /**
     * Scope for active items
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for items by location (via ItemLocation relationship)
     */
    public function scopeByLocation($query, $locationId)
    {
        return $query->whereHas('itemLocations', function ($q) use ($locationId) {
            $q->where('location_id', $locationId);
        });
    }

    /**
     * Scope for searching items by name, code, description, or serial
     */
    public function scopeSearch($query, $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('code', 'like', "%{$term}%")
              ->orWhere('description', 'like', "%{$term}%")
              ->orWhere('serial_number', 'like', "%{$term}%");
        });
    }

    /**
     * Scope for scanning items by barcode, serial number, or code
     */
    public function scopeScannable($query, string $code)
    {
        return $query->where(function ($q) use ($code) {
            $q->where('barcode', $code)
              ->orWhere('serial_number', $code)
              ->orWhere('code', $code);
        });
    }

    /**
     * Helper methods for tracking modes
     */
    public function isAbstract(): bool
    {
        return $this->tracking_mode === self::TRACKING_ABSTRACT;
    }

    public function isBulk(): bool
    {
        return $this->tracking_mode === self::TRACKING_BULK;
    }

    public function isSerialized(): bool
    {
        return $this->tracking_mode === self::TRACKING_SERIALIZED;
    }

    public function isPhysical(): bool
    {
        return in_array($this->tracking_mode, [self::TRACKING_BULK, self::TRACKING_SERIALIZED]);
    }

    /**
     * Apply tracking mode constraints
     */
    private function applyTrackingModeConstraints(): void
    {
        match ($this->tracking_mode) {
            self::TRACKING_ABSTRACT => $this->setAbstractConstraints(),
            self::TRACKING_BULK => $this->setBulkConstraints(),
            self::TRACKING_SERIALIZED => $this->setSerializedConstraints(),
            default => null,
        };
    }

    /**
     * Set constraints for abstract items
     */
    private function setAbstractConstraints(): void
    {
        $this->serial_number = null;
        $this->status_id = null;
        $this->notes = null;
    }

    /**
     * Set constraints for bulk items
     */
    private function setBulkConstraints(): void
    {
        $this->serial_number = null;
    }

    /**
     * Set constraints for serialized items (no automatic changes)
     */
    private function setSerializedConstraints(): void
    {
        // Serialized items keep all their data
        // Validation ensures serial_number is provided
    }

}
