<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product;
use App\Models\AddOn;

class SetupTestData extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'test:setup-data';

    /**
     * The console command description.
     */
    protected $description = 'Set up test products and add-ons for testing';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Setting up test products and add-ons...');

        $this->createTestProducts();
        $this->createTestAddOns();

        $this->newLine();
        $this->info('🎉 Test data setup complete!');
        $this->info('You can now create play sessions with add-ons and products.');
    }

    private function createTestProducts()
    {
        $products = [
            [
                'name' => 'Juice Box',
                'price' => 2.50,
                'lbp_price' => 225000,
                'stock_qty' => 50,
                'active' => true,
                'description' => 'Fresh apple juice box'
            ],
            [
                'name' => 'Cookies',
                'price' => 1.50,
                'lbp_price' => 135000,
                'stock_qty' => 30,
                'active' => true,
                'description' => 'Chocolate chip cookies'
            ],
            [
                'name' => 'Water Bottle',
                'price' => 1.00,
                'lbp_price' => 90000,
                'stock_qty' => 100,
                'active' => true,
                'description' => 'Small water bottle'
            ],
            [
                'name' => 'Toy Car',
                'price' => 5.00,
                'lbp_price' => 450000,
                'stock_qty' => 20,
                'active' => true,
                'description' => 'Small toy car'
            ]
        ];

        foreach ($products as $productData) {
            $existing = Product::where('name', $productData['name'])->first();
            if (!$existing) {
                Product::create($productData);
                $this->info("✅ Created product: {$productData['name']} - $" . number_format($productData['price'], 2));
            } else {
                $this->info("Product {$productData['name']} already exists");
            }
        }
    }

    private function createTestAddOns()
    {
        $addOns = [
            [
                'name' => 'Extra Hour',
                'price' => 3.00,
                'active' => true,
                'description' => 'Additional hour of play time'
            ],
            [
                'name' => 'Birthday Party Package',
                'price' => 15.00,
                'active' => true,
                'description' => 'Special birthday party setup with decorations'
            ],
            [
                'name' => 'Photo Package',
                'price' => 8.00,
                'active' => true,
                'description' => 'Professional photos of your child playing'
            ],
            [
                'name' => 'Snack Combo',
                'price' => 4.50,
                'active' => true,
                'description' => 'Juice box + cookies combo'
            ],
            [
                'name' => 'Premium Play Area',
                'price' => 2.00,
                'active' => true,
                'description' => 'Access to premium play equipment'
            ]
        ];

        foreach ($addOns as $addOnData) {
            $existing = AddOn::where('name', $addOnData['name'])->first();
            if (!$existing) {
                AddOn::create($addOnData);
                $this->info("✅ Created add-on: {$addOnData['name']} - $" . number_format($addOnData['price'], 2));
            } else {
                $this->info("Add-on {$addOnData['name']} already exists");
            }
        }
    }
} 