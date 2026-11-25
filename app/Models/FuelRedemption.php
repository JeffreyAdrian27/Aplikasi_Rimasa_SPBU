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
        'tax_per_8kl',
        'tax_amount',
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
            'tax_per_8kl' => 'float',
            'tax_amount' => 'float',
            'total_amount' => 'float',
        ];
    }

    public const PPH22_RATE = 0.0025; // 0,25%

    public const BASE_PRICES = [
        'Solar' => 5862.07,
        'Pertalite' => 8620.69,
        'Pertamax' => 13750.00,
        'Pertamina Dex' => 21724.14,
    ];

    public static function defaultBasePrice(string $productName): float
    {
        return ProductBasePrice::forProduct($productName);
    }

    public static function calculateTax(float $volumeLiters, float $basePrice): float
    {
        if ($volumeLiters <= 0 || $basePrice <= 0) {
            return 0.0;
        }

        return round(($volumeLiters * $basePrice) * self::PPH22_RATE);
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
