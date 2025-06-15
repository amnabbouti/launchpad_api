<?php

namespace App\Models;

use App\Traits\HasOrganizationScope;
use App\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemMovement extends Model
{
    use HasFactory;
    use HasOrganizationScope;
    use HasPublicId;

    /**
     * ID prefix for ItemMovements
     */
    protected string $publicIdPrefix = 'MOV';

    protected static function getEntityType(): string
    {
        return 'item_movement';
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'org_id',
        'item_id',
        'from_location_id',
        'to_location_id',
        'quantity',
        'user_id',
        'moved_at',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'moved_at' => 'datetime',
        'quantity' => 'float',
    ];

    /**
     * Get the item that was moved
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * Get the source location
     */
    public function fromLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'from_location_id');
    }

    /**
     * Get the destination location
     */
    public function toLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'to_location_id');
    }

    /**
     * Get the user who moved the item
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
