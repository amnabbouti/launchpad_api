<?php

declare(strict_types = 1);

namespace App\Models;

use App\Traits\HasAttachments;
use App\Traits\HasUuidv7;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemLocation extends Model {
    use HasAttachments;
    use HasFactory;
    use HasUuidv7;

    protected $casts = [
        'quantity'   => 'decimal:2',
        'moved_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $fillable = [
        'org_id',
        'item_id',
        'location_id',
        'quantity',
        'moved_date',
        'notes',
    ];

    public function item(): BelongsTo {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function location(): BelongsTo {
        return $this->belongsTo(Location::class, 'location_id');
    }

    public function organization(): BelongsTo {
        return $this->belongsTo(Organization::class, 'org_id');
    }
}
