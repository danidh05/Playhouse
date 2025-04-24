<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Models\Product;
use App\Models\AddOn;
use App\Models\Shift;
use App\Models\Child;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            TestUserSeeder::class,
            ComplaintSeeder::class,
            SaleSeeder::class,
        ]);

        // Create roles
        $adminRole = Role::create(['name' => 'admin']);
        $cashierRole = Role::create(['name' => 'cashier']);

        // Create users
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role_id' => $adminRole->id,
        ]);

        $cashier = User::create([
            'name' => 'Cashier User',
            'email' => 'cashier@example.com',
            'password' => Hash::make('password'),
            'role_id' => $cashierRole->id,
        ]);
        
        // Create sample shifts
        $shifts = [
            [
                'cashier_id' => $cashier->id,
                'date' => now()->subDays(3)->toDateString(),
                'type' => 'morning',
                'notes' => 'Morning shift',
                'opening_amount' => 100.00,
                'closing_amount' => 250.00,
                'opened_at' => now()->subDays(3)->setHour(9)->setMinute(0),
                'closed_at' => now()->subDays(3)->setHour(17)->setMinute(0),
            ],
            [
                'cashier_id' => $cashier->id,
                'date' => now()->subDays(2)->toDateString(),
                'type' => 'full',
                'notes' => 'Busy day',
                'opening_amount' => 100.00,
                'closing_amount' => 320.50,
                'opened_at' => now()->subDays(2)->setHour(10)->setMinute(0),
                'closed_at' => now()->subDays(2)->setHour(18)->setMinute(0),
            ],
            [
                'cashier_id' => $cashier->id,
                'date' => now()->subDays(1)->toDateString(),
                'type' => 'evening',
                'notes' => 'Weekend shift',
                'opening_amount' => 100.00,
                'closing_amount' => 210.75,
                'opened_at' => now()->subDays(1)->setHour(9)->setMinute(0),
                'closed_at' => now()->subDays(1)->setHour(17)->setMinute(0),
            ],
        ];
        
        foreach ($shifts as $shiftData) {
            Shift::create($shiftData);
        }
        
        // Create sample children
        $children = [
            [
                'name' => 'Emma Johnson',
                'age' => 6,
                'emergency_contact' => '555-1234',
                'medical_conditions' => 'None',
            ],
            [
                'name' => 'Noah Williams',
                'age' => 8,
                'emergency_contact' => '555-5678',
                'medical_conditions' => 'Mild asthma',
            ],
            [
                'name' => 'Olivia Brown',
                'age' => 5,
                'emergency_contact' => '555-9012',
                'medical_conditions' => 'Peanut allergy',
            ],
        ];
        
        foreach ($children as $childData) {
            Child::create($childData);
        }
        
        // Create sample products
        $products = [
            [
                'name' => 'Juice Box',
                'price' => 2.50,
                'stock_qty' => 50,
            ],
            [
                'name' => 'Kids Snack Pack',
                'price' => 4.99,
                'stock_qty' => 30,
            ],
            [
                'name' => 'Playhouse T-Shirt',
                'price' => 15.99,
                'stock_qty' => 25,
            ],
        ];
        
        foreach ($products as $product) {
            Product::create($product);
        }
        
        // Create sample add-ons
        $addOns = [
            [
                'name' => 'Face Painting',
                'price' => 7.99,
            ],
            [
                'name' => 'Party Room (1 hour)',
                'price' => 25.00,
            ],
            [
                'name' => 'Special Activity',
                'price' => 10.00,
            ],
        ];
        
        foreach ($addOns as $addOn) {
            AddOn::create($addOn);
        }
    }
}