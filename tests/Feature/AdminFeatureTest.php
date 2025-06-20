<?php

namespace Tests\Feature;

use App\Models\AddOn;
use App\Models\Complaint;
use App\Models\Expense;
use App\Models\Product;
use App\Models\Role;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AdminFeatureTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $admin;
    protected $adminRole;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        $this->adminRole = Role::create(['name' => 'admin']);
        Role::create(['name' => 'cashier']);

        // Create admin user
        $this->admin = User::create([
            'name' => 'Test Admin',
            'email' => 'testadmin@example.com',
            'password' => bcrypt('password'),
            'role_id' => $this->adminRole->id,
        ]);
    }

    /** @test */
    public function admin_can_access_dashboard()
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));
        $response->assertStatus(200);
        $response->assertViewIs('admin.dashboard');
    }

    /** @test */
    public function admin_can_view_products_list()
    {
        $response = $this->actingAs($this->admin)->get(route('admin.products.index'));
        $response->assertStatus(200);
        $response->assertViewIs('admin.products.index');
    }

    /** @test */
    public function admin_can_create_product()
    {
        $productData = [
            'name' => 'Test Product',
            'price' => 19.99,
            'price_lbp' => 1799100,
            'stock_qty' => 10,
            'active' => true,
        ];

        $response = $this->actingAs($this->admin)
            ->withoutMiddleware()
            ->post(route('admin.products.store'), $productData);

        $response->assertRedirect(route('admin.products.index'));
        $this->assertDatabaseHas('products', $productData);
    }

    /** @test */
    public function admin_can_update_product()
    {
        $product = Product::create([
            'name' => 'Original Product',
            'price' => 15.00,
            'price_lbp' => 1350000,
            'stock_qty' => 5,
            'active' => true,
        ]);

        $updatedData = [
            'name' => 'Updated Product',
            'price' => 20.00,
            'price_lbp' => 1800000,
            'stock_qty' => 8,
            'active' => true,
        ];

        $response = $this->actingAs($this->admin)
            ->withoutMiddleware()
            ->put(route('admin.products.update', $product), $updatedData);

        $response->assertRedirect(route('admin.products.index'));
        $this->assertDatabaseHas('products', $updatedData);
    }

    /** @test */
    public function admin_can_delete_product()
    {
        $product = Product::create([
            'name' => 'Product to Delete',
            'price' => 10.00,
            'price_lbp' => 900000,
            'stock_qty' => 3,
            'active' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->withoutMiddleware()
            ->delete(route('admin.products.destroy', $product));

        $response->assertRedirect(route('admin.products.index'));
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    /** @test */
    public function admin_can_view_addons_list()
    {
        $response = $this->actingAs($this->admin)->get(route('admin.addons.index'));
        $response->assertStatus(200);
        $response->assertViewIs('admin.addons.index');
    }

    /** @test */
    public function admin_can_create_addon()
    {
        $addonData = [
            'name' => 'Test Add-on',
            'price' => 5.99,
            'active' => true,
        ];

        $response = $this->actingAs($this->admin)
            ->withoutMiddleware()
            ->post(route('admin.addons.store'), $addonData);

        $response->assertRedirect(route('admin.addons.index'));
        $this->assertDatabaseHas('add_ons', $addonData);
    }

    /** @test */
    public function admin_can_update_addon()
    {
        $addon = AddOn::create([
            'name' => 'Original Add-on',
            'price' => 3.99,
            'active' => true,
        ]);

        $updatedData = [
            'name' => 'Updated Add-on',
            'price' => 7.99,
            'active' => true,
        ];

        $response = $this->actingAs($this->admin)
            ->withoutMiddleware()
            ->put(route('admin.addons.update', $addon), $updatedData);

        $response->assertRedirect(route('admin.addons.index'));
        $this->assertDatabaseHas('add_ons', $updatedData);
    }

    /** @test */
    public function admin_can_delete_addon()
    {
        $addon = AddOn::create([
            'name' => 'Add-on to Delete',
            'price' => 2.99,
            'active' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->withoutMiddleware()
            ->delete(route('admin.addons.destroy', $addon));

        $response->assertRedirect(route('admin.addons.index'));
        $this->assertDatabaseMissing('add_ons', ['id' => $addon->id]);
    }

    /** @test */
    public function admin_can_view_complaints_list()
    {
        $response = $this->actingAs($this->admin)->get(route('admin.complaints.index'));
        $response->assertStatus(200);
        $response->assertViewIs('admin.complaints.index');
    }

    /** @test */
    public function admin_can_toggle_complaint_resolved_status()
    {
        // Create a shift first
        $shift = Shift::create([
            'cashier_id' => $this->admin->id,
            'date' => today(),
            'type' => 'morning',
            'opening_amount' => 100.00,
            'opened_at' => now(),
        ]);

        $complaint = Complaint::create([
            'type' => 'Service',
            'description' => 'Test complaint',
            'resolved' => false,
            'user_id' => $this->admin->id,
            'shift_id' => $shift->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->withoutMiddleware()
            ->patch(route('admin.complaints.toggle-resolved', $complaint));

        $response->assertRedirect(route('admin.complaints.index'));
        $this->assertDatabaseHas('complaints', [
            'id' => $complaint->id,
            'resolved' => true,
        ]);
    }

    /** @test */
    public function admin_can_view_expenses_list()
    {
        $response = $this->actingAs($this->admin)->get(route('admin.expenses.index'));
        $response->assertStatus(200);
        $response->assertViewIs('admin.expenses.index');
    }

    /** @test */
    public function admin_can_create_expense()
    {
        $expenseData = [
            'item' => 'Office Supplies',
            'amount' => 25.50,
            'description' => 'Pens and paper',
        ];

        $response = $this->actingAs($this->admin)
            ->withoutMiddleware()
            ->post(route('admin.expenses.store'), $expenseData);

        $response->assertRedirect(route('admin.expenses.index'));
        $this->assertDatabaseHas('expenses', [
            'item' => $expenseData['item'],
            'amount' => $expenseData['amount'],
            'user_id' => $this->admin->id,
        ]);
    }

    /** @test */
    public function admin_can_delete_expense()
    {
        $expense = Expense::create([
            'item' => 'Deletable Expense',
            'amount' => 15.00,
            'user_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->withoutMiddleware()
            ->delete(route('admin.expenses.destroy', $expense));

        $response->assertRedirect(route('admin.expenses.index'));
        $this->assertDatabaseMissing('expenses', ['id' => $expense->id]);
    }
} 