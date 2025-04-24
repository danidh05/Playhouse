<?php

namespace Database\Seeders;

use App\Models\Child;
use App\Models\Complaint;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Database\Seeder;

class ComplaintSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get complaint types from config
        $complaintTypes = config('play.complaint_types', []);
        
        // Skip if complaint types aren't configured
        if (empty($complaintTypes)) {
            echo "Skipping complaint seeding - no complaint types configured in config/play.php\n";
            return;
        }
        
        // Get users with cashier role
        $cashiers = User::whereHas('role', function ($query) {
            $query->where('name', 'cashier');
        })->get();
        
        // Get shifts and children
        $shifts = Shift::all();
        $children = Child::all();

        // Skip seeding if we don't have the required data
        if ($cashiers->isEmpty() || $shifts->isEmpty()) {
            echo "Skipping complaint seeding - no cashiers or shifts found\n";
            return;
        }

        // Create 15 random unresolved complaints
        foreach (range(1, 15) as $index) {
            Complaint::factory()->create([
                'shift_id' => $shifts->random()->id,
                'user_id' => $cashiers->random()->id,
                'child_id' => $index % 3 === 0 ? null : ($children->isEmpty() ? null : $children->random()->id),
                'type' => $complaintTypes[array_rand($complaintTypes)],
                'resolved' => false,
            ]);
        }

        // Create 10 resolved complaints
        foreach (range(1, 10) as $index) {
            Complaint::factory()->resolved()->create([
                'shift_id' => $shifts->random()->id,
                'user_id' => $cashiers->random()->id,
                'child_id' => $index % 2 === 0 ? ($children->isEmpty() ? null : $children->random()->id) : null,
                'type' => $complaintTypes[array_rand($complaintTypes)],
            ]);
        }
    }
} 