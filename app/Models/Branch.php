<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends Model
{
    protected $fillable = [
        'code',
        'name',
        'margin_rate',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'margin_rate' => 'float',
            'is_active' => 'boolean',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function fuelRedemptions(): HasMany
    {
        return $this->hasMany(FuelRedemption::class);
    }

    public function creditSales(): HasMany
    {
        return $this->hasMany(CreditSale::class);
    }

    public function cashEntries(): HasMany
    {
        return $this->hasMany(CashEntry::class);
    }
}
