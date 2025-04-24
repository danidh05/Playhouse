<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Sale;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Database\Seeder;

class SaleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get payment methods from config
        $paymentMethods = config('play.payment_methods', []);
        
        // Skip if payment methods aren't configured
        if (empty($paymentMethods)) {
            echo "Skipping sales seeding - no payment methods configured in config/play.php\n";
            return;
        }
        
        // Get cashiers and products
        $cashiers = User::whereHas('role', function ($query) {
            $query->where('name', 'cashier');
        })->get();
        
        $products = Product::where('stock_qty', '>', 0)->get();
        
        // Get shifts
        $shifts = Shift::all();

        // Skip seeding if we don't have the required data
        if ($cashiers->isEmpty() || $shifts->isEmpty() || $products->isEmpty()) {
            echo "Skipping sales seeding - no cashiers, shifts, or products found\n";
            return;
        }

        // Create 30 random sales
        foreach (range(1, 30) as $index) {
            $product = $products->random();
            $quantity = rand(1, 3);
            
            Sale::factory()->create([
                'shift_id' => $shifts->random()->id,
                'product_id' => $product->id,
                'user_id' => $cashiers->random()->id,
                'quantity' => $quantity,
                'unit_price' => $product->price,
                'total_price' => $product->price * $quantity,
                'payment_method' => $paymentMethods[array_rand($paymentMethods)],
            ]);
            
            // Update product stock
            $product->update([
                'stock_qty' => max(0, $product->stock_qty - $quantity)
            ]);
        }
    }
} 