<?php

declare(strict_types = 1);

namespace App\Models;

use App\Traits\HasUuidv7;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemMovement extends Model {
    use HasFactory;
    use HasUuidv7;

    public const MOVEMENT_ADJUSTMENT = 'adjustment';

    public const MOVEMENT_INITIAL_PLACEMENT = 'initial_placement';

    /**
     * Movement type constants
     */
    public const MOVEMENT_TRANSFER = 'transfer';

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'moved_at' => 'datetime',
        'quantity' => 'float',
    ];

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
        'movement_type',
        'reason',
        'reference_id',
        'reference_type',
        'notes',
    ];

    /**
     * Get the source location
     */
    public function fromLocation(): BelongsTo {
        return $this->belongsTo(Location::class, 'from_location_id');
    }

    /**
     * Get the item that was moved
     */
    public function item(): BelongsTo {
        return $this->belongsTo(Item::class);
    }

    /**
     * Get the destination location
     */
    public function toLocation(): BelongsTo {
        return $this->belongsTo(Location::class, 'to_location_id');
    }

    /**
     * Get the user who moved the item
     */
    public function user(): BelongsTo {
        return $this->belongsTo(User::class);
    }
}
