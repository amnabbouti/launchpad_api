<?php

namespace App\Models;

use App\Traits\HasPublicId;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organization extends Model
{
    use HasPublicId; // Add public_id support
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'telephone',
        'address',
        'remarks',
        'website',
    ];

    protected static function getEntityType(): string
    {
        return 'organization';
    }

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'org_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(Item::class, 'org_id');
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class, 'org_id');
    }

    public function locations(): HasMany
    {
        return $this->hasMany(Location::class, 'org_id');
    }

    public function suppliers(): HasMany
    {
        return $this->hasMany(Supplier::class, 'org_id');
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class, 'org_id');
    }

    public function unitOfMeasures(): HasMany
    {
        return $this->hasMany(UnitOfMeasure::class, 'org_id');
    }

    public function statuses(): HasMany
    {
        return $this->hasMany(Status::class, 'org_id');
    }

    public function itemStatuses(): HasMany
    {
        return $this->hasMany(ItemStatus::class, 'org_id');
    }

    public function maintenanceCategories(): HasMany
    {
        return $this->hasMany(MaintenanceCategory::class, 'org_id');
    }

    public function maintenances(): HasMany
    {
        return $this->hasMany(Maintenance::class, 'org_id');
    }

    public function checkInOuts(): HasMany
    {
        return $this->hasMany(CheckInOut::class, 'org_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class, 'org_id');
    }
}
