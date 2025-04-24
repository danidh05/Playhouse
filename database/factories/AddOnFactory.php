<?php

namespace Database\Factories;

use App\Models\AddOn;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AddOn>
 */
class AddOnFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = AddOn::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->randomElement([
                'Face Painting', 'Extra Snacks', 'Extended Time', 
                'Special Activity', 'Birthday Cake', 'Party Decoration',
                'Balloon Art', 'VIP Treatment', 'Private Room'
            ]),
            'price' => $this->faker->randomFloat(2, 5, 30),
        ];
    }
} 