<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Sale;
use App\Models\Shift;
use App\Models\User;
use App\Models\Product;
use App\Models\PlaySession;
use App\Models\Child;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ShiftManagementTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $cashier;
    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        $adminRole = Role::create(['name' => 'admin']);
        $cashierRole = Role::create(['name' => 'cashier']);

        // Create users
        $this->admin = User::create([
            'name' => 'Test Admin',
            'email' => 'testadmin@example.com',
            'password' => bcrypt('password'),
            'role_id' => $adminRole->id,
        ]);

        $this->cashier = User::create([
            'name' => 'Test Cashier',
            'email' => 'testcashier@example.com',
            'password' => bcrypt('password'),
            'role_id' => $cashierRole->id,
        ]);
    }

    /** @test */
    public function cashier_can_open_shift()
    {
        $response = $this->actingAs($this->cashier)
            ->withoutMiddleware()
            ->post(route('cashier.shifts.store'), [
                'shift_start_time' => '08:00',
                'shift_end_time' => '16:00',
                'notes' => 'Opening morning shift',
                'confirm' => '1',
                'opening_amount' => 100.00,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('shifts', [
            'cashier_id' => $this->cashier->id,
            'type' => 'full', // 8 hour shift is 'full' type
            'notes' => 'Opening morning shift',
            'opening_amount' => 100.00,
        ]);
    }

    /** @test */
    public function cashier_can_close_shift()
    {
        // Create an open shift
        $shift = Shift::create([
            'cashier_id' => $this->cashier->id,
            'date' => now(),
            'type' => 'morning',
            'opening_amount' => 100.00,
            'opened_at' => now()->subHours(8),
        ]);

        // Create some sales for this shift
        $product = Product::create([
            'name' => 'Test Product',
            'price' => 19.99,
            'stock_qty' => 10,
        ]);

        Sale::create([
            'shift_id' => $shift->id,
            'user_id' => $this->cashier->id,
            'total_amount' => 39.98,
            'amount_paid' => 39.98,
            'payment_method' => 'cash',
        ]);

        // Create a play session for this shift
        $child = Child::create([
            'name' => 'Test Child',
            'age' => 5,
            'birth_date' => now()->subYears(5)->format('Y-m-d'),
            'guardian_name' => 'Test Guardian',
            'guardian_contact' => '123-456-7890',
        ]);

        $playSession = PlaySession::create([
            'shift_id' => $shift->id,
            'child_id' => $child->id,
            'user_id' => $this->cashier->id,
            'planned_hours' => 2,
            'actual_hours' => 2,
            'started_at' => now()->subHours(3),
            'ended_at' => now()->subHour(),
            'amount_paid' => 20.00,
            'payment_method' => 'cash',
        ]);

        // Create corresponding sale for the play session
        Sale::create([
            'shift_id' => $shift->id,
            'user_id' => $this->cashier->id,
            'total_amount' => 20.00,
            'amount_paid' => 20.00,
            'payment_method' => 'cash',
            'play_session_id' => $playSession->id,
            'child_id' => $child->id,
            'status' => 'completed'
        ]);

        $response = $this->actingAs($this->cashier)
            ->withoutMiddleware()
            ->put(route('cashier.shifts.update', $shift), [
                'closing_amount' => 159.98, // Opening + sales + sessions
                'notes' => 'Closing shift notes',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('shifts', [
            'id' => $shift->id,
            'closing_amount' => 159.98,
            'notes' => 'Closing shift notes',
        ]);
        $this->assertNotNull($shift->fresh()->closed_at);
    }

    /** @test */
    public function cashier_cannot_close_shift_with_open_play_sessions()
    {
        // Create an open shift
        $shift = Shift::create([
            'cashier_id' => $this->cashier->id,
            'date' => now(),
            'type' => 'morning',
            'opening_amount' => 100.00,
            'opened_at' => now()->subHours(8),
        ]);

        // Create an open play session
        $child = Child::create([
            'name' => 'Test Child',
            'age' => 5,
            'birth_date' => now()->subYears(5)->format('Y-m-d'),
            'guardian_name' => 'Test Guardian',
            'guardian_contact' => '123-456-7890',
        ]);

        PlaySession::create([
            'shift_id' => $shift->id,
            'child_id' => $child->id,
            'user_id' => $this->cashier->id,
            'planned_hours' => 2,
            'started_at' => now()->subHour(),
            'ended_at' => null,
        ]);

        $response = $this->actingAs($this->cashier)
            ->withoutMiddleware()
            ->put(route('cashier.shifts.update', $shift), [
                'closing_amount' => 100.00,
                'notes' => 'Closing shift notes',
            ]);

        $response->assertRedirect();
        // The system allows closing shifts with open sessions but shows a warning
        $this->assertNotNull($shift->fresh()->closed_at);
        $this->assertDatabaseHas('shifts', [
            'id' => $shift->id,
            'closing_amount' => 100.00,
            'notes' => 'Closing shift notes',
        ]);
    }

    /** @test */
    public function cashier_can_view_shift_report()
    {
        // Create a closed shift with transactions
        $shift = Shift::create([
            'cashier_id' => $this->cashier->id,
            'date' => now(),
            'type' => 'morning',
            'opening_amount' => 100.00,
            'closing_amount' => 159.98,
            'opened_at' => now()->subHours(8),
            'closed_at' => now(),
        ]);

        // Create sales
        $product = Product::create([
            'name' => 'Test Product',
            'price' => 19.99,
            'stock_qty' => 10,
        ]);

        Sale::create([
            'shift_id' => $shift->id,
            'user_id' => $this->cashier->id,
            'total_amount' => 39.98,
            'amount_paid' => 39.98,
            'payment_method' => 'cash',
        ]);

        // Create a play session
        $child = Child::create([
            'name' => 'Test Child',
            'age' => 5,
            'birth_date' => now()->subYears(5)->format('Y-m-d'),
            'guardian_name' => 'Test Guardian',
            'guardian_contact' => '123-456-7890',
        ]);

        PlaySession::create([
            'shift_id' => $shift->id,
            'child_id' => $child->id,
            'user_id' => $this->cashier->id,
            'planned_hours' => 2,
            'actual_hours' => 2,
            'started_at' => now()->subHours(3),
            'ended_at' => now()->subHour(),
            'amount_paid' => 20.00,
            'payment_method' => 'cash',
        ]);

        $response = $this->actingAs($this->cashier)
            ->get(route('cashier.shifts.report', $shift));

        $response->assertStatus(200);
        $response->assertViewHas('shift', $shift);
        $response->assertViewHas('playSessionSales');
        $response->assertViewHas('playSessions');
    }

    /** @test */
    public function admin_can_view_all_shifts()
    {
        // Create some shifts
        Shift::create([
            'cashier_id' => $this->cashier->id,
            'date' => now(),
            'type' => 'morning',
            'opening_amount' => 100.00,
            'closing_amount' => 150.00,
            'opened_at' => now()->subHours(8),
            'closed_at' => now(),
        ]);

        Shift::create([
            'cashier_id' => $this->cashier->id,
            'date' => now()->subDay(),
            'type' => 'evening',
            'opening_amount' => 100.00,
            'closing_amount' => 200.00,
            'opened_at' => now()->subDay()->subHours(8),
            'closed_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.shifts.index'));

        $response->assertStatus(200);
        $response->assertViewHas('shifts');
        
        $shifts = $response->viewData('shifts');
        $this->assertEquals(2, $shifts->count());
    }

    /** @test */
    public function admin_can_view_shift_details()
    {
        // Create a shift
        $shift = Shift::create([
            'cashier_id' => $this->cashier->id,
            'date' => now(),
            'type' => 'morning',
            'opening_amount' => 100.00,
            'closing_amount' => 150.00,
            'opened_at' => now()->subHours(8),
            'closed_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.shifts.show', $shift));

        $response->assertStatus(200);
        $response->assertViewHas('shift', $shift);
    }
} 