<?php

declare(strict_types = 1);

namespace App\Models;

use App\Constants\AppConstants;
use App\Traits\HasAttachments;
use App\Traits\HasUuidv7;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

use function in_array;

class Item extends Model {
    use HasAttachments;
    use HasFactory;
    use HasUuidv7;

    /**
     * Tracking mode constants
     */
    public const TRACKING_ABSTRACT = 'abstract';

    public const TRACKING_SERIALIZED = 'serialized';

    public const TRACKING_STANDARD = 'standard';

    protected $appends = [
        'type',
    ];

    protected $casts = [
        'price'               => 'decimal:2',
        'estimated_value'     => 'decimal:2',
        'is_active'           => 'boolean',
        'specifications'      => 'json',
        'tracking_mode'       => 'string',
        'tracking_changed_at' => 'datetime',
        'created_at'          => 'datetime',
        'updated_at'          => 'datetime',
    ];

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
        'batch_id',
        'estimated_value',
        'tracking_changed_at',
        'tracking_change_reason',
    ];

    protected $hidden = [];

    public static function rules(): array {
        return [
            'price' => 'nullable|numeric|min:0|max:' . AppConstants::ITEM_MAX_PRICE,
        ];
    }

    public function batch(): BelongsTo {
        return $this->belongsTo(Batch::class, 'batch_id');
    }

    public function category(): BelongsTo {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function checkInOuts(): MorphMany {
        return $this->morphMany(CheckInOut::class, 'trackable');
    }

    public function childItems(): HasMany {
        return $this->hasMany(self::class, 'parent_item_id');
    }

    /**
     * Get the type attribute
     */
    public function getTypeAttribute(): string {
        return $this->tracking_mode ?? 'unknown';
    }

    public function historyEvents(): HasMany {
        return $this->hasMany(ItemHistoryEvent::class);
    }

    /**
     * Helper methods for tracking modes
     */
    public function isAbstract(): bool {
        return $this->tracking_mode === self::TRACKING_ABSTRACT;
    }

    public function isPhysical(): bool {
        return in_array($this->tracking_mode, [self::TRACKING_STANDARD, self::TRACKING_SERIALIZED], true);
    }

    public function isSerialized(): bool {
        return $this->tracking_mode === self::TRACKING_SERIALIZED;
    }

    public function isStandard(): bool {
        return $this->tracking_mode === self::TRACKING_STANDARD;
    }

    // Direct relationships for bulk/serialized items
    public function itemLocations(): HasMany {
        return $this->hasMany(ItemLocation::class, 'item_id');
    }

    public function itemRelations(): HasMany {
        return $this->hasMany(self::class, 'item_relation_id');
    }

    // Many-to-many relationship with locations through item_locations
    public function locations(): BelongsToMany {
        return $this->belongsToMany(Location::class, 'item_locations', 'item_id', 'location_id')
            ->withPivot('quantity')
            ->withTimestamps();
    }

    public function maintenanceConditions(): HasMany {
        return $this->hasMany(MaintenanceCondition::class, 'item_id');
    }

    public function maintenances(): MorphMany {
        return $this->morphMany(Maintenance::class, 'maintainable');
    }

    public function movements(): HasMany {
        return $this->hasMany(ItemMovement::class, 'item_id');
    }

    public function organization(): BelongsTo {
        return $this->belongsTo(Organization::class, 'org_id');
    }

    public function parentItem(): BelongsTo {
        return $this->belongsTo(self::class, 'parent_item_id');
    }

    public function relatedItem(): BelongsTo {
        return $this->belongsTo(self::class, 'item_relation_id');
    }

    /**
     * Scopes for different tracking modes
     */
    public function scopeAbstract($query) {
        return $query->where('tracking_mode', self::TRACKING_ABSTRACT);
    }

    /**
     * Scope for active items
     */
    public function scopeActive($query) {
        return $query->where('is_active', true);
    }

    /**
     * Scope for items by location (via ItemLocation relationship)
     */
    public function scopeByLocation($query, $locationId) {
        return $query->whereHas('itemLocations', static function ($q) use ($locationId): void {
            $q->where('location_id', $locationId);
        });
    }

    public function scopePhysical($query) {
        return $query->whereIn('tracking_mode', [self::TRACKING_STANDARD, self::TRACKING_SERIALIZED]);
    }

    /**
     * Scope for scanning items by barcode, serial number, or code
     */
    public function scopeScannable($query, string $code) {
        return $query->where(static function ($q) use ($code): void {
            $q->where('barcode', $code)
                ->orWhere('serial_number', $code)
                ->orWhere('code', $code);
        });
    }

    /**
     * Scope for searching items by name, code, description, or serial
     */
    public function scopeSearch($query, $term) {
        return $query->where(static function ($q) use ($term): void {
            $q->where('name', 'like', "%{$term}%")
                ->orWhere('code', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%")
                ->orWhere('serial_number', 'like', "%{$term}%");
        });
    }

    public function scopeSerialized($query) {
        return $query->where('tracking_mode', self::TRACKING_SERIALIZED);
    }

    public function scopeStandard($query) {
        return $query->where('tracking_mode', self::TRACKING_STANDARD);
    }

    public function status(): BelongsTo {
        return $this->belongsTo(Status::class, 'status_id');
    }

    public function suppliers(): BelongsToMany {
        return $this->belongsToMany(Supplier::class, 'item_supplier', 'item_id', 'supplier_id')
            ->withPivot('supplier_part_number', 'price', 'lead_time_days', 'is_preferred')
            ->withTimestamps();
    }

    public function unitOfMeasure(): BelongsTo {
        return $this->belongsTo(UnitOfMeasure::class, 'unit_id');
    }

    public function user(): BelongsTo {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Model event handlers
     */
    protected static function booted(): void {
        self::creating(static function ($item): void {
            $item->applyTrackingModeConstraints();
        });

        self::updating(static function ($item): void {
            if ($item->isDirty('tracking_mode')) {
                $item->applyTrackingModeConstraints();
            }
        });
    }

    /**
     * Apply tracking mode constraints
     */
    private function applyTrackingModeConstraints(): void {
        match ($this->tracking_mode) {
            self::TRACKING_ABSTRACT   => $this->setAbstractConstraints(),
            self::TRACKING_STANDARD   => $this->setStandardConstraints(),
            self::TRACKING_SERIALIZED => $this->setSerializedConstraints(),
            default                   => null,
        };
    }

    /**
     * Set constraints for abstract items
     */
    private function setAbstractConstraints(): void {
        $this->serial_number = null;
        $this->status_id     = null;
        $this->notes         = null;
    }

    private function setSerializedConstraints(): void {
        // Serialized items keep all their data
        // Validation ensures serial_number is provided
    }

    private function setStandardConstraints(): void {
        $this->serial_number = null;
    }
}
