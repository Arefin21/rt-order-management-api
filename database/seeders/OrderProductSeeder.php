<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\Stock;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class OrderProductSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing order products to avoid duplicate errors
        OrderProduct::truncate();

        $orders = Order::all();
        $stocks = Stock::where('quantity', '>', 0)->get();

        if ($orders->isEmpty()) {
            $this->command->warn('No orders found. Please run OrderSeeder first.');
            return;
        }

        if ($stocks->isEmpty()) {
            $this->command->warn('No stocks with quantity > 0 found.');
            return;
        }

        // Create order products for each order (1-3 products per order)
        foreach ($orders as $order) {
            $productCount = rand(1, 3);
            $selectedStocks = $stocks->random(min($productCount, $stocks->count()));

            foreach ($selectedStocks as $stock) {
                $salePrice = $stock->sale_price;
                $purchasePrice = $stock->purchase_price;
                $profitPercentage = $purchasePrice > 0
                    ? (($salePrice - $purchasePrice) / $purchasePrice) * 100
                    : 0;

                OrderProduct::create([
                    'order_id' => $order->id,
                    'product_id' => $stock->product_id,
                    'stock_id' => $stock->id,
                    'sale_price' => $salePrice,
                    'sub_total' => $salePrice,
                    'profit' => round($profitPercentage, 2),
                ]);
            }
        }
    }
}
