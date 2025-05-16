<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Item extends Model
{
    public function maintenances(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Maintenance::class);
    }

    use HasFactory;
    use SoftDeletes;

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
        'specifications',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
        'specifications' => 'json',
    ];

    protected $appends = [
        'active',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function getActiveAttribute(): bool
    {
        return (bool) $this->is_active;
    }

    // Category
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    // User
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Stock
    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }

    // Check-ins/outs via stock
    public function checkInOuts()
    {
        return $this->hasManyThrough(
            \App\Models\CheckInOut::class,
            \App\Models\Stock::class,
            'item_id',
            'stock_id',
            'id',
            'id'
        );
    }

    // Locations
    public function locations(): BelongsToMany
    {
        return $this->belongsToMany(Location::class)
            ->withPivot('quantity')
            ->withTimestamps()
            ->withTrashed();
    }

    // Suppliers
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
