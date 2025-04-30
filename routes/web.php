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
        
        // Sales routes
        Route::get('/sales', [SalesController::class, 'index'])->name('sales.index');
        Route::get('/sales/list', [SalesController::class, 'list'])->name('sales.list');
        Route::get('/sales/create', [SalesController::class, 'create'])->name('sales.create');
        Route::get('/sales/product-price', [SalesController::class, 'getProductPrice'])->name('sales.product-price');
        Route::post('/sales', [SalesController::class, 'store'])->name('sales.store');
        Route::get('/sales/{sale}', [SalesController::class, 'show'])->name('sales.show');
    });
});

// require __DIR__.'/auth.php';