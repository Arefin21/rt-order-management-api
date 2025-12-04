<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Stock;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class StockSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing stocks to avoid duplicate errors
        Stock::truncate();

        // Get all products and create stocks for them
        $products = Product::all();

        if ($products->isEmpty()) {
            $this->command->warn('No products found. Please run ProductSeeder first.');
            return;
        }

        // Create stocks for each product
        foreach ($products as $product) {
            Stock::factory()->create([
                'product_id' => $product->id,
            ]);
        }
    }
}
