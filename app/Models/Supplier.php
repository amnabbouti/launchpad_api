<?php

namespace App\Models;

use App\Traits\HasOrganizationScope;
use App\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Supplier extends Model
{
    use HasFactory;
    use HasOrganizationScope;
    use HasPublicId;

    protected $fillable = [
        'org_id',
        'name',
        'code',
        'email',
        'phone',
        'address',
        'city',
        'state',
        'postal_code',
        'country',
        'website',
        'tax_id',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function getEntityType(): string
    {
        return 'supplier';
    }

    protected $appends = [
        'active',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public static function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
        ];
    }

    public function getActiveAttribute(): bool
    {
        return (bool) $this->is_active;
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'org_id');
    }

    public function items(): BelongsToMany
    {
        return $this->belongsToMany(Item::class, 'item_supplier', 'supplier_id', 'item_id')
            ->withPivot('supplier_part_number', 'price', 'lead_time', 'is_preferred')
            ->withTimestamps();
    }

    protected static function booted(): void
    {
        // Future events can be added here
    }
}
