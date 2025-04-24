<?php

namespace Database\Factories;

use App\Models\Shift;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Shift>
 */
class ShiftFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Shift::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $isNightShift = $this->faker->boolean;
        
        return [
            'cashier_id' => User::factory()->create(['role_id' => 2]), // Assuming role_id 2 is for cashiers
            'date' => $this->faker->date,
            'type' => $isNightShift ? 'night' : 'morning',
            'opened_at' => $isNightShift ? now()->setTime(15, 0) : now()->setTime(8, 0),
            'closed_at' => null,
        ];
    }

    /**
     * Indicate that the shift is closed.
     */
    public function closed(): static
    {
        return $this->state(function (array $attributes) {
            $isNightShift = $attributes['type'] === 'night';
            $openedAt = $attributes['opened_at'];
            
            return [
                'closed_at' => $isNightShift ? 
                    $openedAt->copy()->addHours(8) : 
                    $openedAt->copy()->addHours(7),
            ];
        });
    }
} 