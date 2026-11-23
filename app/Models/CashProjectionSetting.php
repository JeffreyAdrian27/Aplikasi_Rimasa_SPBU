<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashProjectionSetting extends Model
{
    protected $fillable = [
        'year',
        'month',
        'opening_balance_rek1',
        'opening_balance_rek2',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'month' => 'integer',
            'opening_balance_rek1' => 'float',
            'opening_balance_rek2' => 'float',
        ];
    }
}
