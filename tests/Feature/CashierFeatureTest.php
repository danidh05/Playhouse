<?php

namespace Tests\Feature;

use App\Models\AddOn;
use App\Models\Child;
use App\Models\PlaySession;
use App\Models\Product;
use App\Models\Role;
use App\Models\Shift;
use App\Models\User;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class CashierFeatureTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $cashier;
    protected $cashierRole;
    protected $shift;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::create(['name' => 'admin']);
        $this->cashierRole = Role::create(['name' => 'cashier']);

        // Create cashier user
        $this->cashier = User::create([
            'name' => 'Test Cashier',
            'email' => 'testcashier@example.com',
            'password' => bcrypt('password'),
            'role_id' => $this->cashierRole->id,
        ]);
        
        // Create active shift for testing
        $this->shift = Shift::create([
            'cashier_id' => $this->cashier->id,
            'date' => now(),
            'type' => 'morning',
            'opening_amount' => 100.00,
            'opened_at' => now(),
        ]);
    }

    /** @test */
    public function cashier_can_access_dashboard()
    {
        $response = $this->actingAs($this->cashier)->get(route('cashier.dashboard'));
        $response->assertStatus(200);
        $response->assertViewIs('cashier.dashboard');
    }

    /** @test */
    public function cashier_can_view_children_list()
    {
        $response = $this->actingAs($this->cashier)->get(route('cashier.children.index'));
        $response->assertStatus(200);
    }

    /** @test */
    public function cashier_can_create_child()
    {
        $childData = [
            'name' => 'Test Child',
            'birth_date' => now()->subYears(6)->format('Y-m-d'),
            'guardian_name' => 'Parent Name',
            'guardian_phone' => '123-456-7890',
            'notes' => 'Some notes',
        ];

        $response = $this->actingAs($this->cashier)
            ->withoutMiddleware()
            ->post(route('cashier.children.store'), $childData);
        
        $response->assertRedirect();
        $this->assertDatabaseHas('children', [
            'name' => $childData['name'],
            'guardian_name' => $childData['guardian_name'],
            'guardian_phone' => $childData['guardian_phone'],
        ]);
    }

    /** @test */
    public function cashier_can_update_child()
    {
        $child = Child::create([
            'name' => 'Original Child',
            'birth_date' => now()->subYears(5)->format('Y-m-d'),
            'guardian_name' => 'Original Parent',
            'guardian_phone' => '123-456-7890',
        ]);

        $updatedData = [
            'name' => 'Updated Child',
            'birth_date' => now()->subYears(6)->format('Y-m-d'),
            'guardian_name' => 'Updated Parent',
            'guardian_phone' => '987-654-3210',
            'notes' => 'Updated notes',
        ];

        $response = $this->actingAs($this->cashier)
            ->withoutMiddleware()
            ->put(route('cashier.children.update', $child), $updatedData);
        
        $response->assertRedirect();
        $this->assertDatabaseHas('children', [
            'id' => $child->id,
            'name' => $updatedData['name'],
            'guardian_name' => $updatedData['guardian_name'],
            'guardian_phone' => $updatedData['guardian_phone'],
        ]);
    }

    /** @test */
    public function cashier_can_delete_child()
    {
        $child = Child::create([
            'name' => 'Deletable Child',
            'birth_date' => now()->subYears(5)->format('Y-m-d'),
            'guardian_name' => 'Parent',
            'guardian_phone' => '123-456-7890',
        ]);

        $response = $this->actingAs($this->cashier)
            ->withoutMiddleware()
            ->delete(route('cashier.children.destroy', $child));
        
        $response->assertRedirect();
        $this->assertDatabaseMissing('children', ['id' => $child->id]);
    }

    /** @test */
    public function cashier_can_start_play_session()
    {
        $child = Child::create([
            'name' => 'Play Child',
            'birth_date' => now()->subYears(5)->format('Y-m-d'),
            'guardian_name' => 'Parent',
            'guardian_phone' => '123-456-7890',
        ]);

        $response = $this->actingAs($this->cashier)
            ->get(route('cashier.sessions.start', $child));
        
        $response->assertStatus(200);
    }

    /** @test */
    public function cashier_can_store_play_session()
    {
        $child = Child::create([
            'name' => 'Play Child',
            'birth_date' => now()->subYears(5)->format('Y-m-d'),
            'guardian_name' => 'Parent',
            'guardian_phone' => '123-456-7890',
        ]);

        $sessionData = [
            'child_id' => $child->id,
            'planned_hours' => 2,
            'notes' => 'Test session',
        ];

        $response = $this->actingAs($this->cashier)
            ->withoutMiddleware()
            ->post(route('cashier.sessions.store'), $sessionData);
        
        $response->assertRedirect();
        $this->assertDatabaseHas('play_sessions', [
            'child_id' => $child->id,
            'planned_hours' => 2,
        ]);
    }

    /** @test */
    public function cashier_can_show_end_play_session()
    {
        $child = Child::create([
            'name' => 'Play Child',
            'birth_date' => now()->subYears(5)->format('Y-m-d'),
            'guardian_name' => 'Parent',
            'guardian_phone' => '123-456-7890',
        ]);

        $session = PlaySession::create([
            'child_id' => $child->id,
            'shift_id' => $this->shift->id,
            'user_id' => $this->cashier->id,
            'planned_hours' => 2,
            'started_at' => now(),
        ]);

        $response = $this->actingAs($this->cashier)
            ->get(route('cashier.sessions.show-end', $session));
        
        $response->assertStatus(200);
    }

    /** @test */
    public function cashier_can_end_play_session()
    {
        $child = Child::create([
            'name' => 'Play Child',
            'birth_date' => now()->subYears(5)->format('Y-m-d'),
            'guardian_name' => 'Parent',
            'guardian_phone' => '123-456-7890',
        ]);

        $session = PlaySession::create([
            'child_id' => $child->id,
            'shift_id' => $this->shift->id,
            'user_id' => $this->cashier->id,
            'planned_hours' => 2,
            'started_at' => now()->subHours(2),
        ]);

        $endData = [
            'payment_method' => 'cash',
            'amount_paid' => 20.00,
            'custom_total' => 20.00,
            'total_amount' => 20.00,
        ];

        $response = $this->actingAs($this->cashier)
            ->withoutMiddleware()
            ->put(route('cashier.sessions.end', $session), $endData);
        
        $response->assertRedirect();
        $this->assertDatabaseHas('play_sessions', [
            'id' => $session->id,
            'amount_paid' => 20.00,
            'payment_method' => 'cash',
        ]);
        $this->assertNotNull($session->fresh()->ended_at);
    }

    /** @test */
    public function cashier_can_update_play_session_addons()
    {
        $child = Child::create([
            'name' => 'Play Child',
            'age' => 5,
            'birth_date' => now()->subYears(5)->format('Y-m-d'),
            'guardian_name' => 'Parent',
            'guardian_contact' => '123-456-7890',
        ]);

        $session = PlaySession::create([
            'child_id' => $child->id,
            'shift_id' => $this->shift->id,
            'user_id' => $this->cashier->id,
            'planned_hours' => 2,
            'started_at' => now(),
        ]);

        $addon = AddOn::create([
            'name' => 'Test Add-On',
            'price' => 9.99,
            'active' => true,
        ]);

        $response = $this->actingAs($this->cashier)
            ->withoutMiddleware()
            ->patch(route('cashier.sessions.update-addons', $session), [
                'add_ons' => [$addon->id => ['qty' => 1]]
            ]);
        
        $response->assertRedirect();
        $this->assertTrue($session->fresh()->addOns->contains($addon->id));
    }

    /** @test */
    public function cashier_can_create_sale()
    {
        $product = Product::create([
            'name' => 'Test Product',
            'price' => 19.99,
            'price_lbp' => 1799100,
            'stock_qty' => 10,
            'active' => true,
        ]);

        $response = $this->actingAs($this->cashier)->get(route('cashier.sales.create'));
        $response->assertStatus(200);
    }

    /** @test */
    public function cashier_can_store_sale()
    {
        $product = Product::create([
            'name' => 'Test Product',
            'price' => 10.00,
            'price_lbp' => 900000,
            'stock_qty' => 5,
            'active' => true,
        ]);

        $saleData = [
            'items' => [
                $product->id => [
                    'id' => $product->id,
                    'qty' => 2,
                    'price' => $product->price,
                ]
            ],
            'payment_method' => 'cash',
            'shift_id' => $this->shift->id,
            'amount_paid' => 20.00,
            'currency' => 'usd',
        ];

        $response = $this->actingAs($this->cashier)
            ->withoutMiddleware()
            ->post(route('cashier.sales.store'), $saleData);

        $response->assertRedirect();

        // Check if the sale was created
        $sale = Sale::first();
        $this->assertNotNull($sale);
        $this->assertEquals(20.00, $sale->total_amount);
        $this->assertEquals('cash', $sale->payment_method);
    }

    /** @test */
    public function cashier_can_get_product_price()
    {
        $product = Product::create([
            'name' => 'Test Product',
            'price' => 19.99,
            'price_lbp' => 1799100,
            'stock_qty' => 10,
            'active' => true,
        ]);

        $response = $this->actingAs($this->cashier)
            ->get(route('cashier.sales.product-price', ['product_id' => $product->id]));
        
        $response->assertStatus(200);
        $response->assertJson(['price' => 19.99]);
    }

    /** @test */
    public function cashier_can_view_sales_list()
    {
        // Create a product for testing
        $product = Product::create([
            'name' => 'List Test Product',
            'price' => 29.99,
            'price_lbp' => 2699100,
            'stock_qty' => 5,
            'active' => true,
        ]);
        
        // Create a sale
        $sale = Sale::create([
            'shift_id' => $this->shift->id,
            'user_id' => $this->cashier->id,
            'total_amount' => 29.99,
            'payment_method' => 'cash',
        ]);
        
        // Add sale item
        SaleItem::create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 29.99,
            'subtotal' => 29.99
        ]);
        
        $response = $this->actingAs($this->cashier)
            ->get(route('cashier.sales.list'));
        
        $response->assertStatus(200);
        $response->assertViewIs('cashier.sales.list');
        $response->assertViewHas('sales');
        $response->assertViewHas('todaySalesCount');
        $response->assertViewHas('todayRevenue');
    }

    /** @test */
    public function cashier_can_create_complaint()
    {
        $response = $this->actingAs($this->cashier)->get(route('cashier.complaints.create'));
        $response->assertStatus(200);
    }

    /** @test */
    public function cashier_can_submit_complaint()
    {
        $child = Child::create([
            'name' => 'Complaint Child',
            'birth_date' => now()->subYears(6)->format('Y-m-d'),
            'guardian_name' => 'Complaint Parent',
            'guardian_phone' => '555-0123',
        ]);

        $complaintData = [
            'child_id' => $child->id,
            'description' => 'Test complaint description',
            'priority' => 'high',
        ];

        $response = $this->actingAs($this->cashier)
            ->withoutMiddleware()
            ->post(route('cashier.complaints.store'), $complaintData);

        $response->assertRedirect();
        $this->assertDatabaseHas('complaints', [
            'child_id' => $child->id,
            'description' => 'Test complaint description',
            'priority' => 'high',
        ]);
    }

    /** @test */
    public function cashier_can_add_products_to_session_and_charge_customer()
    {
        // Create a child and product
        $child = Child::create([
            'name' => 'Session Child',
            'birth_date' => now()->subYears(7)->format('Y-m-d'),
            'guardian_name' => 'Session Parent',
            'guardian_phone' => '555-0124',
        ]);

        $product = Product::create([
            'name' => 'Session Product',
            'price' => 15.00,
            'price_lbp' => 1350000,
            'stock_qty' => 10,
            'active' => true,
        ]);

        // Create a play session
        $session = PlaySession::create([
            'child_id' => $child->id,
            'shift_id' => $this->shift->id,
            'user_id' => $this->cashier->id,
            'planned_hours' => 2,
            'started_at' => now()->subHours(1),
        ]);

        // Add products to session
        $productData = json_encode([
            [
                'id' => $product->id,
                'name' => $product->name,
                'price' => $product->price,
                'quantity' => 2
            ]
        ]);

        $response = $this->actingAs($this->cashier)
            ->withoutMiddleware()
            ->post(route('cashier.sessions.store-products', $session), [
                'products' => $productData
            ]);

        $response->assertRedirect();

        // Verify pending sale was created
        $pendingSale = \App\Models\Sale::where('play_session_id', $session->id)
            ->where('status', 'pending')
            ->first();

        $this->assertNotNull($pendingSale);
        $this->assertEquals(30.00, $pendingSale->total_amount); // 2 * $15.00
        $this->assertEquals('pending', $pendingSale->payment_method);

        // Verify sale items were created
        $this->assertEquals(1, $pendingSale->items->count());
        $this->assertEquals($product->id, $pendingSale->items->first()->product_id);
        $this->assertEquals(2, $pendingSale->items->first()->quantity);
        $this->assertEquals(15.00, $pendingSale->items->first()->unit_price);

        // Verify stock was reduced
        $product->refresh();
        $this->assertEquals(8, $product->stock_qty); // 10 - 2

        // Now end the session with products included in payment
        $endData = [
            'payment_method' => 'cash',
            'amount_paid' => 50.00, // Includes session cost + products
            'custom_total' => 50.00,
            'total_amount' => 50.00,
        ];

        $response = $this->actingAs($this->cashier)
            ->withoutMiddleware()
            ->put(route('cashier.sessions.end', $session), $endData);

        $response->assertRedirect();

        // Verify session was ended
        $session->refresh();
        $this->assertNotNull($session->ended_at);
        $this->assertEquals('cash', $session->payment_method);

        // Verify pending sale became completed
        $pendingSale->refresh();
        $this->assertEquals('completed', $pendingSale->status);
        $this->assertEquals('cash', $pendingSale->payment_method);

        // Verify main sale was created
        $mainSale = \App\Models\Sale::where('play_session_id', $session->id)
            ->where('status', 'completed')
            ->whereNull('parent_sale_id')
            ->first();

        $this->assertNotNull($mainSale);
        $this->assertEquals(50.00, $mainSale->total_amount);
        $this->assertEquals(50.00, $mainSale->amount_paid);

        // Verify pending sale is linked to main sale
        $this->assertEquals($mainSale->id, $pendingSale->parent_sale_id);
    }
} 