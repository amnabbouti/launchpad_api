<?php

declare(strict_types = 1);

namespace App\Models;

use App\Traits\HasUuidv7;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrintJob extends Model {
    use HasFactory;
    use HasUuidv7;

    public $incrementing = false;

    protected $casts = [
        'entity_ids'  => 'array',
        'options'     => 'array',
        'copies'      => 'integer',
        'created_at'  => 'datetime',
        'updated_at'  => 'datetime',
        'started_at'  => 'datetime',
        'finished_at' => 'datetime',
    ];

    protected $fillable = [
        'org_id',
        'user_id',
        'entity_type',
        'entity_ids',
        'format',
        'preset',
        'options',
        'printer_id',
        'copies',
        'status',
        'error_code',
        'error_message',
        'artifact_path',
        'started_at',
        'finished_at',
    ];

    protected $keyType = 'string';

    protected $table = 'printjobs';

    public function organization(): BelongsTo {
        return $this->belongsTo(Organization::class, 'org_id');
    }

    public function printer(): BelongsTo {
        return $this->belongsTo(Printer::class, 'printer_id');
    }

    public function user(): BelongsTo {
        return $this->belongsTo(User::class, 'user_id');
    }
}
