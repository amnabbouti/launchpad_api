<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Maintenance extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'is_first_offset_value',
        'remarks',
        'invoice_nbr',
        'cost',
        'date_expected_back_from_maintenance',
        'date_back_from_maintenance',
        'date_in_maintenance',
        'is_repair',
        'import_id',
        'import_source',
        'employee_id',
        'supplier_id',
        'stock_id',
        'status_out_id',
        'status_in_id'
    ];

    protected $casts = [
        'is_first_offset_value' => 'boolean',
        'cost' => 'decimal:2',
        'date_expected_back_from_maintenance' => 'datetime',
        'date_back_from_maintenance' => 'datetime',
        'date_in_maintenance' => 'datetime',
        'is_repair' => 'boolean'
    ];

    // Employee relationship
    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    // Supplier relationship
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    // Stock relationship
    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }

    // Status out relationship
    public function statusOut(): BelongsTo
    {
        return $this->belongsTo(Status::class, 'status_out_id');
    }

    // Status in relationship
    public function statusIn(): BelongsTo
    {
        return $this->belongsTo(Status::class, 'status_in_id');
    }

    // Maintenance details relationship
    public function maintenanceDetails(): HasMany
    {
        return $this->hasMany(MaintenanceDetail::class);
    }
}
