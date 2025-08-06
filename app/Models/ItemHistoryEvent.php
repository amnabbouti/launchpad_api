<?php

namespace App\Models;

use App\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemHistoryEvent extends Model
{
    use HasPublicId;

    protected string $publicIdPrefix = 'IHE';

    protected static function getEntityType(): string
    {
        return 'item_history_event';
    }

    const EVENT_CREATED = 'created';

    const EVENT_UPDATED = 'updated';

    const EVENT_MOVED = 'moved';

    const EVENT_TRACKING_CHANGED = 'tracking_changed';

    const EVENT_MAINTENANCE_IN = 'maintenance_in';

    const EVENT_MAINTENANCE_OUT = 'maintenance_out';

    protected $fillable = [
        'org_id',
        'item_id',
        'event_type',
        'old_values',
        'new_values',
        'user_id',
        'reason',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'org_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
