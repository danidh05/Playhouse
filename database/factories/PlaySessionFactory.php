<?php

namespace Database\Factories;

use App\Models\Child;
use App\Models\PlaySession;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PlaySession>
 */
class PlaySessionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = PlaySession::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'child_id' => Child::factory(),
            'shift_id' => Shift::factory(),
            'user_id' => User::factory(),
            'start_time' => now()->subHours(rand(1, 5)),
            'end_time' => null,
            'duration_min' => null,
            'discount_pct' => $this->faker->numberBetween(0, 20),
            'total_cost' => 0.00,
        ];
    }

    /**
     * Indicate that the session has been completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'end_time' => now(),
            'duration_min' => $this->faker->numberBetween(30, 180),
            'total_cost' => $this->faker->randomFloat(2, 10, 50),
        ]);
    }

    /**
     * Configure the model factory to create a PlaySession for a specific child.
     */
    public function forChild($childId = null): static
    {
        return $this->state(function (array $attributes) use ($childId) {
            return [
                'child_id' => $childId ?? Child::factory()->create()->id,
            ];
        });
    }

    /**
     * Configure the model factory to create a PlaySession for a specific shift.
     */
    public function forShift($shiftId = null): static
    {
        return $this->state(function (array $attributes) use ($shiftId) {
            return [
                'shift_id' => $shiftId ?? Shift::factory()->create()->id,
            ];
        });
    }

    /**
     * Configure the model factory to create a PlaySession for a specific user.
     */
    public function forUser($userId = null): static
    {
        return $this->state(function (array $attributes) use ($userId) {
            return [
                'user_id' => $userId ?? User::factory()->create()->id,
            ];
        });
    }
} 