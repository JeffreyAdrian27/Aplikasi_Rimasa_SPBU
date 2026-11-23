<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FuelRedemption extends Model
{
    protected $fillable = [
        'branch_id',
        'transaction_date',
        'do_number',
        'product_name',
        'is_non_bbm',
        'volume_liters',
        'price_per_liter',
        'total_amount',
        'payment_status',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'transaction_date' => 'date:Y-m-d',
            'is_non_bbm' => 'boolean',
            'volume_liters' => 'float',
            'price_per_liter' => 'float',
            'total_amount' => 'float',
        ];
    }

    public static function detectNonBbm(string $productName): bool
    {
        $upper = strtoupper($productName);
        foreach (['LPG', 'ELPIJI', 'OLI', 'PELUMAS'] as $keyword) {
            if (str_contains($upper, $keyword)) {
                return true;
            }
        }

        return false;
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
