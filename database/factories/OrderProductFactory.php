<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Product;
use App\Models\Stock;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrderProduct>
 */
class OrderProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $stock = Stock::inRandomOrder()->first();

        if (!$stock) {
            // If no stock exists, create a temporary one
            $product = Product::inRandomOrder()->first() ?? Product::factory()->create();
            $stock = Stock::factory()->create(['product_id' => $product->id]);
        }

        $salePrice = $stock->sale_price;
        $purchasePrice = $stock->purchase_price;
        $profitPercentage = $purchasePrice > 0
            ? (($salePrice - $purchasePrice) / $purchasePrice) * 100
            : 0;

        return [
            'order_id' => Order::factory(),
            'product_id' => $stock->product_id,
            'stock_id' => $stock->id,
            'sale_price' => $salePrice,
            'sub_total' => $salePrice, // Assuming quantity 1, or this could be calculated elsewhere
            'profit' => round($profitPercentage, 2),
        ];
    }
}
