<?php

declare(strict_types = 1);

namespace App\Models;

use App\Traits\HasUuidv7;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Printer extends Model {
    use HasFactory;
    use HasUuidv7;

    public $incrementing = false;

    protected $casts = [
        'config'       => 'array',
        'capabilities' => 'array',
        'is_active'    => 'boolean',
        'is_default'   => 'boolean',
        'created_at'   => 'datetime',
        'updated_at'   => 'datetime',
    ];

    protected $fillable = [
        'org_id',
        'name',
        'driver',
        'host',
        'port',
        'config',
        'capabilities',
        'is_active',
        'is_default',
    ];

    protected $keyType = 'string';

    protected $table = 'printers';

    public function organization(): BelongsTo {
        return $this->belongsTo(Organization::class, 'org_id');
    }

    public function printJobs(): HasMany {
        return $this->hasMany(PrintJob::class, 'printer_id');
    }
}
