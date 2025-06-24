<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Role;
use App\Models\Child;
use Illuminate\Support\Facades\Hash;

class CreateTestUsers extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'test:create-users';

    /**
     * The console command description.
     */
    protected $description = 'Create test users for local development and testing';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Creating test users for local development...');

        // Ensure roles exist
        $this->ensureRolesExist();

        // Create admin user
        $admin = $this->createUser([
            'name' => 'Admin Test',
            'email' => 'admin@test.com',
            'password' => 'password',
            'role_id' => Role::where('name', 'admin')->first()->id,
        ]);
        $this->info("✅ Created Admin: {$admin->email} / password");

        // Create cashier user
        $cashier = $this->createUser([
            'name' => 'Cashier Test',
            'email' => 'cashier@test.com',
            'password' => 'password',
            'role_id' => Role::where('name', 'cashier')->first()->id,
        ]);
        $this->info("✅ Created Cashier: {$cashier->email} / password");

        // Create another cashier for testing
        $cashier2 = $this->createUser([
            'name' => 'John Cashier',
            'email' => 'john@test.com',
            'password' => 'password',
            'role_id' => Role::where('name', 'cashier')->first()->id,
        ]);
        $this->info("✅ Created Cashier 2: {$cashier2->email} / password");

        // Create some test children for sessions
        $this->createTestChildren();

        $this->newLine();
        $this->info('🎉 Test users created successfully!');
        $this->newLine();
        $this->info('You can now login with:');
        $this->info('Admin: admin@test.com / password');
        $this->info('Cashier: cashier@test.com / password');
        $this->info('Cashier 2: john@test.com / password');
        $this->newLine();
        $this->info('🚀 Start your local server: php artisan serve');
        $this->info('🌐 Then visit: http://localhost:8000');
    }

    private function ensureRolesExist()
    {
        $roles = ['admin', 'cashier'];

        foreach ($roles as $roleName) {
            $role = Role::firstOrCreate(
                ['name' => $roleName],
                ['description' => ucfirst($roleName) . ' role']
            );
            $this->info("Role '{$roleName}' ready");
        }
    }

    private function createUser($data)
    {
        // Check if user already exists
        $existingUser = User::where('email', $data['email'])->first();
        
        if ($existingUser) {
            $this->warn("User {$data['email']} already exists, updating...");
            $existingUser->update([
                'name' => $data['name'],
                'password' => Hash::make($data['password']),
                'role_id' => $data['role_id'],
            ]);
            return $existingUser;
        }

        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role_id' => $data['role_id'],
        ]);
    }

    private function createTestChildren()
    {
        $children = [
            [
                'name' => 'Alice Smith',
                'guardian_name' => 'Mary Smith',
                'guardian_phone' => '+1234567890',
                'age' => 8,
                'marketing_source' => 'social_media'
            ],
            [
                'name' => 'Bob Johnson',
                'guardian_name' => 'John Johnson',
                'guardian_phone' => '+1234567891',
                'age' => 6,
                'marketing_source' => 'referral'
            ],
            [
                'name' => 'Charlie Brown',
                'guardian_name' => 'Sarah Brown',
                'guardian_phone' => '+1234567892',
                'age' => 10,
                'marketing_source' => 'walk_in'
            ],
            [
                'name' => 'Diana Wilson',
                'guardian_name' => 'Mike Wilson',
                'guardian_phone' => '+1234567893',
                'age' => 7,
                'marketing_source' => 'google'
            ],
            [
                'name' => 'Test Child Pricing',
                'guardian_name' => 'Test Parent',
                'guardian_phone' => '+1234567894',
                'age' => 5,
                'marketing_source' => 'other'
            ]
        ];

        foreach ($children as $childData) {
            $existing = Child::where('name', $childData['name'])->first();
            if (!$existing) {
                Child::create($childData);
                $this->info("✅ Created child: {$childData['name']}");
            } else {
                $this->info("Child {$childData['name']} already exists");
            }
        }
    }
} 