<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class DiscountCoupon extends Model
{
    protected $fillable = [
        'code',
        'discount_percent',
        'usage_limit',
        'usage_count',
        'expires_at',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'discount_percent' => 'decimal:2',
            'usage_limit' => 'integer',
            'usage_count' => 'integer',
            'expires_at' => 'datetime',
            'active' => 'boolean',
        ];
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Prece::class, 'discount_coupon_products', 'discount_coupon_id', 'Prece_ID', 'id', 'Prece_ID');
    }
}
