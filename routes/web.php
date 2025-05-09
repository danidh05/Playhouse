<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Cashier\ChildrenController;
use App\Http\Controllers\Cashier\ComplaintsController;
use App\Http\Controllers\Cashier\PlaySessionController;
use App\Http\Controllers\Cashier\SalesController;
use App\Http\Controllers\Cashier\ShiftController as CashierShiftController;
use App\Http\Controllers\Admin\ShiftController as AdminShiftController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

// Authentication routes
Route::get('/login', [AuthController::class, 'show'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
});

// Admin routes
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    // Dashboard
    Route::get('/', [App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard');
    
    // Visualizations
    Route::get('/visualizations', [App\Http\Controllers\Admin\DashboardController::class, 'visualizations'])->name('visualizations');
    
    // Settings routes
    Route::get('/settings', [App\Http\Controllers\Admin\SettingsController::class, 'index'])->name('settings.index');
    Route::patch('/settings', [App\Http\Controllers\Admin\SettingsController::class, 'update'])->name('settings.update');
    
    // Products routes
    Route::resource('products', App\Http\Controllers\Admin\ProductController::class);
    
    // Add-ons routes
    Route::resource('addons', App\Http\Controllers\Admin\AddOnController::class);
    
    // Complaints routes
    Route::get('/complaints', [App\Http\Controllers\Admin\ComplaintController::class, 'index'])->name('complaints.index');
    Route::patch('/complaints/{complaint}/toggle-resolved', [App\Http\Controllers\Admin\ComplaintController::class, 'toggleResolved'])->name('complaints.toggle-resolved');
    
    // Expenses routes
    Route::resource('expenses', App\Http\Controllers\Admin\ExpenseController::class)->except(['show', 'edit', 'update']);
    
    // Shifts routes
    Route::get('/shifts', [AdminShiftController::class, 'index'])->name('shifts.index');
    Route::get('/shifts/{shift}', [AdminShiftController::class, 'show'])->name('shifts.show');
});

// Cashier routes
Route::prefix('cashier')->name('cashier.')->middleware(['auth', 'role:cashier'])->group(function () {
    Route::get('/', [App\Http\Controllers\Cashier\DashboardController::class, 'index'])->name('dashboard');
    
    // Shift routes (no active shift check)
    Route::get('/shifts', [CashierShiftController::class, 'index'])->name('shifts.index');
    Route::get('/shifts/open', [CashierShiftController::class, 'showOpen'])->name('shifts.open');
    Route::post('/shifts', [CashierShiftController::class, 'store'])->name('shifts.store');
    Route::get('/shifts/{shift}/close', [CashierShiftController::class, 'showClose'])->name('shifts.close');
    Route::put('/shifts/{shift}', [CashierShiftController::class, 'update'])->name('shifts.update');
    Route::get('/shifts/{shift}/report', [CashierShiftController::class, 'report'])->name('shifts.report');
    
    // Routes that require an active shift
    Route::middleware(['active.shift'])->group(function () {
        // Children routes
        Route::resource('children', ChildrenController::class);
        
        // Complaints routes
        Route::resource('complaints', ComplaintsController::class);
        Route::patch('/complaints/{complaint}/resolve', [ComplaintsController::class, 'markAsResolved'])->name('complaints.resolve');
        
        // Play sessions routes
        Route::get('/sessions', [PlaySessionController::class, 'index'])->name('sessions.index');
        Route::get('/sessions/create', [PlaySessionController::class, 'create'])->name('sessions.create');
        Route::get('/sessions/start/{child}', [PlaySessionController::class, 'start'])->name('sessions.start');
        Route::post('/sessions', [PlaySessionController::class, 'store'])->name('sessions.store');
        Route::get('/sessions/{session}', [PlaySessionController::class, 'show'])->name('sessions.show');
        Route::get('/sessions/{session}/end', [PlaySessionController::class, 'showEnd'])->name('sessions.show-end');
        Route::put('/sessions/{session}/end', [PlaySessionController::class, 'end'])->name('sessions.end');
        Route::patch('/sessions/{session}/add-ons', [PlaySessionController::class, 'updateAddOns'])->name('sessions.update-addons');
        Route::get('/sessions/{session}/add-ons', [PlaySessionController::class, 'showAddOns'])->name('sessions.show-addons');
        Route::get('/sessions/{session}/add-products', [PlaySessionController::class, 'showAddProducts'])->name('sessions.add-products');
        Route::post('/sessions/{session}/add-products', [PlaySessionController::class, 'storeProducts'])->name('sessions.store-products');
        Route::delete('/sessions/{session}', [PlaySessionController::class, 'destroy'])->name('sessions.destroy');
        
        // Sales routes
        Route::get('/sales', [SalesController::class, 'index'])->name('sales.index');
        Route::get('/sales/list', [SalesController::class, 'list'])->name('sales.list');
        Route::get('/sales/create', [SalesController::class, 'create'])->name('sales.create');
        Route::get('/sales/product-price', [SalesController::class, 'getProductPrice'])->name('sales.product-price');
        Route::post('/sales', [SalesController::class, 'store'])->name('sales.store');
        
        // Add-on only sales routes - moved before the '/sales/{sale}' route
        Route::get('/sales/add-on-only', [SalesController::class, 'createAddOnOnly'])->name('sales.create-addon-only');
        Route::post('/sales/add-on-only', [SalesController::class, 'storeAddOnOnly'])->name('sales.store-addon-only');
        
        Route::get('/sales/{sale}', [SalesController::class, 'show'])->name('sales.show');
        Route::delete('/sales/{sale}', [SalesController::class, 'destroy'])->name('sales.destroy');
    });
});

// require __DIR__.'/auth.php';

// Temporary test route for session duration capping
Route::get('/test-session-capping', function() {
    // Only available in local environment
    if (!app()->environment('local')) {
        abort(404);
    }
    
    // Create a test child if none exists
    $child = \App\Models\Child::firstOrCreate(
        ['name' => 'Test Child'],
        [
            'guardian_name' => 'Test Guardian',
            'guardian_contact' => '123456789',
            'birth_date' => now()->subYears(5),
        ]
    );
    
    // Find active shift or create one
    $shift = \App\Models\Shift::where('cashier_id', auth()->id())
        ->whereNull('closed_at')
        ->first();
        
    if (!$shift) {
        $shift = \App\Models\Shift::create([
            'cashier_id' => auth()->id(),
            'date' => today(),
            'opened_at' => now(),
            'type' => 'morning'
        ]);
    }
    
    // Create a test session that started 2 hours ago but with planned_hours of 1
    $session = \App\Models\PlaySession::create([
        'child_id' => $child->id,
        'user_id' => auth()->id(),
        'shift_id' => $shift->id,
        'started_at' => now()->subHours(2), // Started 2 hours ago
        'planned_hours' => 1, // But only planned for 1 hour
        'total_cost' => 0,
    ]);
    
    // Redirect to end session screen
    return redirect()->route('cashier.sessions.show-end', $session)
        ->with('success', 'Test session created with start time 2 hours ago and planned duration of 1 hour.');
})->middleware(['auth', 'role:cashier'])->name('test.session-capping');