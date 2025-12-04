<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderProduct extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'order_id',
        'product_id',
        'stock_id',
        'sale_price',
        'sub_total',
        'profit',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sale_price' => 'decimal:2',
            'sub_total' => 'decimal:2',
            'profit' => 'decimal:2',
        ];
    }

    /**
     * Get the order that owns the order product.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the product that owns the order product.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the stock that owns the order product.
     */
    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }
}
