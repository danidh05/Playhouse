<?php

namespace Database\Factories;

use App\Models\Child;
use App\Models\Complaint;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Complaint>
 */
class ComplaintFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Complaint::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $complaintTypes = config('play.complaint_types', []);
        
        // Default complaint types if config is empty
        if (empty($complaintTypes)) {
            $complaintTypes = ['Facility', 'Staff', 'Safety', 'Cleanliness', 'Service', 'Other'];
        }
        
        return [
            'shift_id' => Shift::factory(),
            'user_id' => User::factory(),
            'child_id' => $this->faker->boolean(70) ? Child::factory() : null,
            'type' => $this->faker->randomElement($complaintTypes),
            'description' => $this->faker->paragraph(),
            'resolved' => false,
        ];
    }

    /**
     * Indicate that the complaint is resolved.
     *
     * @return static
     */
    public function resolved(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'resolved' => true,
            ];
        });
    }
} 