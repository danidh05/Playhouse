<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Sale;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Sale>
 */
class SaleFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Sale::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = $this->faker->numberBetween(1, 5);
        $unitPrice = $this->faker->randomFloat(2, 5, 50);
        $totalPrice = $quantity * $unitPrice;
        
        $paymentMethods = config('play.payment_methods', ['Cash', 'Credit Card', 'Debit Card']);
        
        return [
            'shift_id' => Shift::factory(),
            'product_id' => Product::factory(),
            'user_id' => User::factory(),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_price' => $totalPrice,
            'payment_method' => $this->faker->randomElement($paymentMethods),
        ];
    }
} 