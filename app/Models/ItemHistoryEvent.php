<?php

declare(strict_types = 1);

namespace App\Models;

use App\Traits\HasUuidv7;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemHistoryEvent extends Model {
    use HasUuidv7;

    public const EVENT_CREATED = 'created';

    public const EVENT_MAINTENANCE_IN = 'maintenance_in';

    public const EVENT_MAINTENANCE_OUT = 'maintenance_out';

    public const EVENT_MOVED = 'moved';

    public const EVENT_TRACKING_CHANGED = 'tracking_changed';

    public const EVENT_UPDATED = 'updated';

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    protected $fillable = [
        'org_id',
        'item_id',
        'event_type',
        'old_values',
        'new_values',
        'user_id',
        'reason',
    ];

    public function item(): BelongsTo {
        return $this->belongsTo(Item::class);
    }

    public function organization(): BelongsTo {
        return $this->belongsTo(Organization::class, 'org_id');
    }

    public function user(): BelongsTo {
        return $this->belongsTo(User::class);
    }
}
