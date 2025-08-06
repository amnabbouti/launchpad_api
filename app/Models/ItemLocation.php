<?php

namespace App\Models;

use App\Traits\HasAttachments;
use App\Traits\HasOrganizationScope;
use App\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemLocation extends Model
{
    use HasAttachments;
    use HasFactory;
    use HasOrganizationScope;
    use HasPublicId;

    protected $fillable = [
        'org_id',
        'item_id',
        'location_id',
        'quantity',
        'moved_date',
        'notes',
    ];

    protected static function getEntityType(): string
    {
        return 'item_location';
    }

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

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'location_id');
    }
}
