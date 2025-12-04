<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockLog extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'type',
        'stock_id',
        'product_id',
        'previous_quantity',
        'change_quantity',
        'current_quantity',
    ];

    /**
     * Get the stock that owns the stock log.
     */
    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }

    /**
     * Get the product that owns the stock log.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
