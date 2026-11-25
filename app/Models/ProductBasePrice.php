<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class ProductBasePrice extends Model
{
    public const DEFAULTS = [
        'Solar' => 5862.07,
        'Pertalite' => 8620.69,
        'Pertamax' => 13750.00,
        'Pertamina Dex' => 21724.14,
    ];

    protected $fillable = [
        'product_name',
        'base_price',
    ];

    protected function casts(): array
    {
        return [
            'base_price' => 'float',
        ];
    }

    public static function getMap(): array
    {
        $map = self::DEFAULTS;

        if (! Schema::hasTable('product_base_prices')) {
            return $map;
        }

        $rows = self::query()->pluck('base_price', 'product_name');

        foreach ($rows as $name => $price) {
            $map[$name] = (float) $price;
        }

        return $map;
    }

    public static function forProduct(string $productName): float
    {
        $map = self::getMap();

        return (float) ($map[$productName] ?? 0);
    }
}
