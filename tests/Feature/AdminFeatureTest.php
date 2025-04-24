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
            'stock_qty' => 50,
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.products.store'), $productData);
        
        $response->assertRedirect(route('admin.products.index'));
        $this->assertDatabaseHas('products', $productData);
    }

    /** @test */
    public function admin_can_update_product()
    {
        $product = Product::create([
            'name' => 'Original Product',
            'price' => 19.99,
            'stock_qty' => 50,
        ]);

        $updatedData = [
            'name' => 'Updated Product',
            'price' => 29.99,
            'stock_qty' => 100,
        ];

        $response = $this->actingAs($this->admin)->put(route('admin.products.update', $product), $updatedData);
        
        $response->assertRedirect(route('admin.products.index'));
        $this->assertDatabaseHas('products', $updatedData);
    }

    /** @test */
    public function admin_can_delete_product()
    {
        $product = Product::create([
            'name' => 'Deletable Product',
            'price' => 19.99,
            'stock_qty' => 50,
        ]);

        $response = $this->actingAs($this->admin)->delete(route('admin.products.destroy', $product));
        
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
            'name' => 'Test Add-On',
            'price' => 9.99,
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.addons.store'), $addonData);
        
        $response->assertRedirect(route('admin.addons.index'));
        $this->assertDatabaseHas('add_ons', $addonData);
    }

    /** @test */
    public function admin_can_update_addon()
    {
        $addon = AddOn::create([
            'name' => 'Original Add-On',
            'price' => 9.99,
        ]);

        $updatedData = [
            'name' => 'Updated Add-On',
            'price' => 14.99,
        ];

        $response = $this->actingAs($this->admin)->put(route('admin.addons.update', $addon), $updatedData);
        
        $response->assertRedirect(route('admin.addons.index'));
        $this->assertDatabaseHas('add_ons', $updatedData);
    }

    /** @test */
    public function admin_can_delete_addon()
    {
        $addon = AddOn::create([
            'name' => 'Deletable Add-On',
            'price' => 9.99,
        ]);

        $response = $this->actingAs($this->admin)->delete(route('admin.addons.destroy', $addon));
        
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
        // Create a shift
        $shift = Shift::create([
            'cashier_id' => $this->admin->id,
            'date' => now(),
            'type' => 'morning',
            'opening_amount' => 100.00,
            'opened_at' => now(),
        ]);

        $complaint = Complaint::create([
            'shift_id' => $shift->id,
            'user_id' => $this->admin->id,
            'type' => 'Service',
            'description' => 'Test complaint',
            'resolved' => false,
        ]);

        $response = $this->actingAs($this->admin)
            ->patch(route('admin.complaints.toggle-resolved', $complaint));
        
        $response->assertRedirect(route('admin.complaints.index'));
        $this->assertDatabaseHas('complaints', [
            'id' => $complaint->id,
            'resolved' => true,
        ]);

        // Toggle back to unresolved
        $response = $this->actingAs($this->admin)
            ->patch(route('admin.complaints.toggle-resolved', $complaint->fresh()));
        
        $this->assertDatabaseHas('complaints', [
            'id' => $complaint->id,
            'resolved' => false,
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
            'item' => 'Office supplies',
            'amount' => 49.99,
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.expenses.store'), $expenseData);
        
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
            'user_id' => $this->admin->id,
            'item' => 'Deletable expense',
            'amount' => 29.99,
            'created_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->delete(route('admin.expenses.destroy', $expense));
        
        $response->assertRedirect(route('admin.expenses.index'));
        $this->assertDatabaseMissing('expenses', ['id' => $expense->id]);
    }
} 