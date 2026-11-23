<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditSale extends Model
{
    protected $fillable = [
        'branch_id',
        'sale_date',
        'client_name',
        'product_name',
        'is_non_bbm',
        'volume_liters',
        'price_per_liter',
        'total_amount',
        'credit_term_days',
        'due_date',
        'payment_status',
        'paid_date',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'sale_date' => 'date:Y-m-d',
            'due_date' => 'date:Y-m-d',
            'paid_date' => 'date:Y-m-d',
            'is_non_bbm' => 'boolean',
            'volume_liters' => 'float',
            'price_per_liter' => 'float',
            'total_amount' => 'float',
            'credit_term_days' => 'integer',
        ];
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
