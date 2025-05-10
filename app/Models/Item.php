<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Item represents a specific instance of a Stock.
 * While Stock represents a type or category of product (e.g., "Dell XPS 15 Laptop"),
 * Item represents a specific instance with a serial number (e.g., "Dell XPS 15 #123456").
 * Multiple Item records can belong to a single Stock.
 */
class Item extends Model
{
    use HasFactory, SoftDeletes;

    // Fields that can be mass assigned
    protected $fillable = [
        'name',
        'code',
        'description',
        'quantity',
        'price',
        'unit',
        'category_id',
        'user_id',
        'stock_id',
        'is_active',
        'specifications'
    ];

    // Type casting for attributes
    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
        'specifications' => 'json',
    ];

    // Virtual attributes to add to the model
    protected $appends = [
        'active',
    ];

    // to hide from API responses
    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    // simpler 'active' attribute from is_active
    public function getActiveAttribute(): bool
    {
        return (bool)$this->is_active;
    }

    // Item belongs to a category
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    // Item belongs to a user
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Item belongs to a stock
    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }

    public function locations(): BelongsToMany
    {
        return $this->belongsToMany(Location::class)
            ->withPivot('quantity')
            ->withTimestamps()
            ->withTrashed();
    }

    public function suppliers(): BelongsToMany
    {
        return $this->belongsToMany(Supplier::class, 'item_supplier')
            ->withPivot('supplier_part_number', 'price', 'lead_time', 'is_preferred')
            ->withTimestamps();
    }

    // Validation rules for the model
    public static function rules(): array
    {
        return [
            'quantity' => 'numeric|min:0',
            'price' => 'nullable|numeric|min:0',
        ];
    }

    // Boot method to register model events
    protected static function booted(): void
    {
        // Validate before saving
        static::saving(function ($item) {
            if ($item->quantity < 0) {
                throw new \InvalidArgumentException('Item quantity cannot be negative.');
            }

            if ($item->price !== null && $item->price < 0) {
                throw new \InvalidArgumentException('Item price cannot be negative.');
            }
        });
    }
}
