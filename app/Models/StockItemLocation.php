<?php

namespace App\Models;

use App\Traits\HasOrganizationScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockItemLocation extends Model
{
    use HasFactory;
    use HasOrganizationScope;

    protected $fillable = [
        'org_id',
        'stock_item_id',
        'location_id',
        'quantity',
        'moved_date',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'moved_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'org_id');
    }

    public function stockItem(): BelongsTo
    {
        return $this->belongsTo(StockItem::class, 'stock_item_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'location_id');
    }
}
