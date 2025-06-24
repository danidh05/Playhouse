<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SetupLocalTesting extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'test:setup 
                            {--fresh : Run fresh migrations and seeders}
                            {--serve : Start the development server after setup}';

    /**
     * The console command description.
     */
    protected $description = 'Complete setup for local testing environment';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Setting up local testing environment...');
        $this->newLine();

        // Check if we should run fresh migrations
        if ($this->option('fresh')) {
            $this->info('Running fresh migrations...');
            $this->call('migrate:fresh', ['--seed' => true]);
            $this->newLine();
        } else {
            // Just run migrations
            $this->info('Running migrations...');
            $this->call('migrate');
            $this->newLine();
        }

        // Create test users
        $this->info('Creating test users...');
        $this->call('test:create-users');
        $this->newLine();

        // Setup test data
        $this->info('Setting up test data...');
        $this->call('test:setup-data');
        $this->newLine();

        // Clear caches
        $this->info('Clearing caches...');
        $this->call('route:clear');
        $this->call('config:clear');
        $this->call('view:clear');
        $this->newLine();

        $this->info('✅ Setup complete!');
        $this->newLine();
        
        $this->displayLoginInfo();
        
        // Start server if requested
        if ($this->option('serve')) {
            $this->newLine();
            $this->info('🌐 Starting development server...');
            $this->call('serve');
        } else {
            $this->newLine();
            $this->info('🚀 To start the server run: php artisan serve');
            $this->info('🌐 Then visit: http://localhost:8000');
        }
    }

    private function displayLoginInfo()
    {
        $this->info('📋 LOGIN CREDENTIALS:');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('👤 Admin User:');
        $this->line('   Email: admin@test.com');
        $this->line('   Password: password');
        $this->newLine();
        $this->info('💰 Cashier Users:');
        $this->line('   Email: cashier@test.com  | Password: password');
        $this->line('   Email: john@test.com     | Password: password');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->newLine();
        
        $this->info('🧪 TEST SCENARIO FOR CURRENCY BUG:');
        $this->line('1. Login as cashier (cashier@test.com / password)');
        $this->line('2. Start a new shift if needed');
        $this->line('3. Create a play session for "Test Child Pricing"');
        $this->line('4. Add some add-ons (like "Premium Play Area" - $2.00)');
        $this->line('5. Wait a minute or manually end the session');
        $this->line('6. Select LBP as payment method');
        $this->line('7. Check if total shows 720,000 L.L (not 8 L.L)');
        $this->newLine();
    }
} 