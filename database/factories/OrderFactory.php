<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $statuses = ['Pending', 'Processing', 'Delivered', 'Cancelled'];
        
        return [
            'invoice_number' => 'INV-' . fake()->unique()->numerify('########'),
            'date_time' => fake()->dateTimeBetween('-1 month', 'now'),
            'total_amount' => fake()->randomFloat(2, 100, 10000),
            'customer_name' => fake()->name(),
            'status' => fake()->randomElement($statuses),
        ];
    }
}
