<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organization extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'telephone',
        'address',
        'remarks',
        'website',
    ];

    // An organization has many users
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    // An organization has many items
    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }
}
