<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Status extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'stock_statuses';

    protected $fillable = [
        'name',
        'code',
        'description',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    // Stocks with this status
    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class);
    }

    // Maintenances where this is the status out
    public function maintenancesOut(): HasMany
    {
        return $this->hasMany(Maintenance::class, 'status_out_id');
    }

    // Maintenances where this is the status in
    public function maintenancesIn(): HasMany
    {
        return $this->hasMany(Maintenance::class, 'status_in_id');
    }

    // Check-ins/outs where this is the status out
    public function checkInOutsOut(): HasMany
    {
        return $this->hasMany(CheckInOut::class, 'status_out_id');
    }

    // Check-ins/outs where this is the status in
    public function checkInOutsIn(): HasMany
    {
        return $this->hasMany(CheckInOut::class, 'status_in_id');
    }

    // Maintenance conditions where this is the status when returned
    public function maintenanceConditionsReturned(): HasMany
    {
        return $this->hasMany(MaintenanceCondition::class, 'status_when_returned_id');
    }

    // Maintenance conditions where this is the status when exceeded
    public function maintenanceConditionsExceeded(): HasMany
    {
        return $this->hasMany(MaintenanceCondition::class, 'status_when_exceeded_id');
    }
}
