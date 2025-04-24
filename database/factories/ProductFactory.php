<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Product::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->randomElement([
                'Juice Box', 'Snack Pack', 'Toy Car', 'Stuffed Animal',
                'Coloring Book', 'Crayons', 'Stickers', 'Puzzle',
                'T-shirt', 'Water Bottle', 'Candy Bag', 'Hair Accessory'
            ]),
            'price' => $this->faker->randomFloat(2, 2, 25),
            'stock_qty' => $this->faker->numberBetween(5, 100),
        ];
    }

    /**
     * Indicate that the product is low in stock.
     */
    public function lowStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock_qty' => $this->faker->numberBetween(1, 5),
        ]);
    }

    /**
     * Indicate that the product is out of stock.
     */
    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock_qty' => 0,
        ]);
    }
} 