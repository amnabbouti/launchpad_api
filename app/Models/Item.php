<?php

namespace App\Models;

use App\Traits\HasAttachments;
use App\Traits\HasOrganizationScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Item extends Model
{
    use HasAttachments;
    use HasFactory;
    use HasOrganizationScope;

    protected $fillable = [
        'org_id',
        'name',
        'code',
        'barcode',
        'description',
        'quantity',
        'unit_id',
        'price',
        'is_active',
        'specifications',
        'category_id',
        'user_id',
        'status_id',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'price' => 'decimal:2',
        'is_active' => 'boolean',
        'specifications' => 'json',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $hidden = [];

    public static function rules(): array
    {
        return [
            'quantity' => 'numeric|min:0',
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

    public function stockItems(): HasMany
    {
        return $this->hasMany(StockItem::class, 'item_id');
    }

    public function maintenances(): HasManyThrough
    {
        return $this->hasManyThrough(Maintenance::class, StockItem::class, 'item_id', 'stock_item_id');
    }

    public function maintenanceConditions(): HasMany
    {
        return $this->hasMany(MaintenanceCondition::class, 'item_id');
    }

    public function suppliers(): BelongsToMany
    {
        return $this->belongsToMany(Supplier::class, 'item_supplier', 'item_id', 'supplier_id')
            ->withPivot('supplier_part_number', 'price', 'lead_time', 'is_preferred')
            ->withTimestamps();
    }

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
