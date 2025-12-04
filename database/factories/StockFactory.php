<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Stock>
 */
class StockFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $purchasePrice = fake()->randomFloat(2, 10, 500);
        $salePrice = $purchasePrice * (1 + fake()->randomFloat(2, 0.1, 0.5)); // 10-50% markup

        return [
            'product_id' => Product::factory(),
            'sku' => fake()->unique()->bothify('SKU-####-????'),
            'sale_price' => $salePrice,
            'purchase_price' => $purchasePrice,
            'quantity' => fake()->numberBetween(0, 1000),
            'last_update_at' => fake()->dateTimeBetween('-1 month', 'now'),
        ];
    }
}
