<?php

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Controller;
use App\Http\Requests\SaleRequest;
use App\Models\AddOn;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Shift;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class SalesController extends Controller
{
    /**
     * Display a listing of the sales.
     */
    public function index(Request $request)
    {
        $filter = $request->input('filter', 'today');
        
        $salesQuery = Sale::with([
                'items.product', 
                'child',
                'play_session.addOns',
                'child_sales',
                'child_sales.items.product'
            ])
            ->orderBy('created_at', 'desc');
        
        // Apply date filtering
        switch ($filter) {
            case 'today':
                $salesQuery->whereDate('created_at', today());
                break;
            case 'week':
                $salesQuery->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                break;
            case 'month':
                $salesQuery->whereMonth('created_at', now()->month)
                          ->whereYear('created_at', now()->year);
                break;
            // 'all' doesn't need any filter
        }
        
        $sales = $salesQuery->paginate(15);
        
        // Format the current date for display
        $currentDate = now()->format('D,d M Y');
        
        return view('cashier.sales.index', compact('sales', 'currentDate'));
    }

    /**
     * Show the form for creating a new sale.
     */
    public function create(Request $request)
    {
        $products = Product::where('stock_qty', '>', 0)
                          ->where('active', true)
                          ->get();
        
        // Find active shift
        $activeShift = Shift::where('cashier_id', Auth::id())
                           ->whereNull('closed_at')
                           ->first();
        
        // Get the list of children for customer selection
        $children = \App\Models\Child::orderBy('name')->get();
        
        // Add play session count for each child
        foreach ($children as $child) {
            $child->play_sessions_count = \App\Models\PlaySession::where('child_id', $child->id)->count();
        }
        
        // Check if a specific child was selected
        $selectedChild = null;
        if ($request->has('child_id') && $request->child_id) {
            $selectedChild = \App\Models\Child::find($request->child_id);
            if ($selectedChild) {
                $selectedChild->play_sessions_count = \App\Models\PlaySession::where('child_id', $selectedChild->id)->count();
            }
        }
        
        return view('cashier.sales.create', compact(
            'products', 
            'activeShift', 
            'children',
            'selectedChild'
        ));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:products,id',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
            'payment_method' => ['required', Rule::in(config('play.payment_methods', []))],
            'shift_id' => 'required|exists:shifts,id',
            'amount_paid' => 'required|numeric|min:0',
            'currency' => 'required|in:usd,lbp',
        ]);

        $items = $request->items;
        $totalAmount = 0;
        $currency = $request->currency;
        $paymentMethod = $request->payment_method;
        $lbpRate = config('play.lbp_exchange_rate', 90000);
        
        // Start database transaction
        DB::beginTransaction();
        
        try {
            // Process items and calculate total
            foreach ($items as $id => $item) {
                $product = Product::findOrFail($id);
                
                // If we don't have enough stock, abort the transaction
                if ($product->stock_qty < $item['qty']) {
                    DB::rollBack();
                    
                    if ($request->ajax()) {
                        return response()->json([
                            'success' => false,
                            'message' => "Not enough stock for {$product->name}"
                        ], 422);
                    }
                    
                    return redirect()->back()->with('error', "Not enough stock for {$product->name}");
                }
                
                // Keep item price in original currency (no conversion for storage)
                $itemPrice = $item['price'];
                $itemTotal = $itemPrice * $item['qty'];
                $totalAmount += $itemTotal;
                
                // Reduce stock
                $product->decrement('stock_qty', $item['qty']);
            }
            
            // Keep amount_paid in original currency (no conversion for storage)
            $amountPaid = $request->amount_paid;
            
            // Create the sale record in original currency (no conversion)
            $sale = new Sale([
                'user_id' => Auth::id(),
                'shift_id' => $request->shift_id,
                'payment_method' => $paymentMethod,
                'amount_paid' => $amountPaid,
                'total_amount' => $totalAmount,
                'status' => 'completed',
                'currency' => $paymentMethod === 'LBP' ? 'LBP' : 'USD',
            ]);
            
            if ($request->has('child_id') && !empty($request->child_id)) {
                $sale->child_id = $request->child_id;
            }
            
            $sale->save();
            
            // Create sale items in original currency (no conversion)
            foreach ($items as $id => $item) {
                $itemPrice = $item['price'];
                
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $id,
                    'quantity' => $item['qty'],
                    'unit_price' => $itemPrice,
                    'subtotal' => $itemPrice * $item['qty'],
                ]);
            }
            
            DB::commit();
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Sale completed successfully',
                    'sale_id' => $sale->id,
                    'redirect' => route('cashier.sales.show', $sale->id)
                ]);
            }
            
            return redirect()->route('cashier.sales.show', $sale->id)
                ->with('success', 'Sale completed successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to process the sale: ' . $e->getMessage()
                ], 500);
            }
            
            return redirect()->back()->with('error', 'Failed to process the sale: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified sale.
     */
    public function show(Sale $sale)
    {
        $sale->load([
          'items.product',
          'user',
          'shift',
          'child',
          'play_session',
          'child_sales.items.product'
        ]);
        
        // Get play sessions count if child exists
        $playSessionsCount = null;
        if ($sale->child_id) {
            $playSessionsCount = \App\Models\PlaySession::where('child_id', $sale->child_id)->count();
        }
        
        // DISABLED: No longer recalculating totals - we use ONLY cashier's custom amounts
        // If there's a play session with zero actual_hours but it has ended, just update the hours for record keeping
        if ($sale->play_session && $sale->play_session->ended_at && $sale->play_session->actual_hours == 0) {
            $startTime = $sale->play_session->started_at;
            $endTime = $sale->play_session->ended_at;
            $durationMinutes = $startTime->diffInMinutes($endTime);
            $actualHours = $durationMinutes / 60;
            
            // Update ONLY actual hours for record keeping - DO NOT touch amounts
            $sale->play_session->update(['actual_hours' => $actualHours]);
        }
        
        // IMPORTANT: We NO LONGER recalculate or override amounts set by the cashier
        // The cashier's custom amounts in total_amount and total_cost are FINAL
        
        return view('cashier.sales.show', compact('sale', 'playSessionsCount'));
    }
    

    /**
     * Get product price for AJAX request
     */
    public function getProductPrice(Request $request)
    {
        $product = Product::find($request->product_id);
        
        if (!$product) {
            return response()->json(['error' => 'Product not found'], 404);
        }
        
        return response()->json([
            'price' => $product->price,
            'price_lbp' => $product->price_lbp,
            'stock' => $product->stock_qty
        ]);
    }

    /**
     * Display a listing of the sales with detailed filtering.
     */
    public function list(Request $request)
    {
        $filter = $request->input('filter', 'today');
        $paymentMethod = $request->input('payment_method');
        
        $salesQuery = Sale::with([
                'items.product', 
                'child',
                'play_session.addOns',
                'child_sales',
                'child_sales.items.product',
                'user',
                'shift'
            ])
            ->orderBy('created_at', 'desc');
        
        // Apply date filtering
        switch ($filter) {
            case 'today':
                $salesQuery->whereDate('created_at', today());
                break;
            case 'week':
                $salesQuery->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                break;
            case 'month':
                $salesQuery->whereMonth('created_at', now()->month)
                          ->whereYear('created_at', now()->year);
                break;
            // 'all' doesn't need any filter
        }
        
        // Apply payment method filter if specified
        if ($paymentMethod) {
            $salesQuery->where('payment_method', $paymentMethod);
        }
        
        $sales = $salesQuery->paginate(15)->withQueryString();
        
        // Get statistical data
        $todaySalesCount = Sale::whereDate('created_at', today())->count();
        
        // Calculate today's revenue including child sales
        $todaySales = Sale::with('child_sales')
            ->whereDate('created_at', today())
            ->get();
        
        $todayRevenue = 0;
        foreach ($todaySales as $sale) {
            $baseAmount = $sale->total_amount;
            $childSalesAmount = $sale->child_sales->sum('total_amount');
            $todayRevenue += ($baseAmount + $childSalesAmount);
        }
        
        $productsSoldCount = SaleItem::whereHas('sale', function($query) {
            $query->whereDate('created_at', today());
        })->sum('quantity');
        
        // Format the current date for display
        $currentDate = now()->format('D, d M Y');
        
        return view('cashier.sales.list', compact(
            'sales', 
            'currentDate', 
            'todaySalesCount', 
            'todayRevenue',
            'productsSoldCount'
        ));
    }
    
    /**
     * Remove the specified sale from storage and optionally its associated play session.
     */
    public function destroy(Sale $sale)
    {
        // Use a transaction to ensure data integrity
        DB::transaction(function() use ($sale) {
            // Store relevant information before deleting
            $playSessionId = $sale->play_session_id;
            $hasPlaySession = !empty($playSessionId);
            
            // Restore product stock for each sale item (only if product exists)
            foreach ($sale->items as $item) {
                if ($item->product_id && $item->product) {
                    $item->product->increment('stock_qty', $item->quantity);
                }
            }
            
            // Check for child sales that should be deleted
            if ($sale->child_sales && $sale->child_sales->count() > 0) {
                foreach ($sale->child_sales as $childSale) {
                    // Restore product stock for each child sale item (only if product exists)
                    foreach ($childSale->items as $item) {
                        if ($item->product_id && $item->product) {
                            $item->product->increment('stock_qty', $item->quantity);
                        }
                    }
                    
                    // Delete child sale items
                    $childSale->items()->delete();
                    
                    // Delete the child sale
                    $childSale->delete();
                }
            }
            
            // Delete sale items
            $sale->items()->delete();
            
            // Delete the sale
            $sale->delete();
            
            // If there was an associated play session and this is not a child sale,
            // we need to delete the play session manually (if cascade hasn't done it already)
            if ($hasPlaySession && empty($sale->parent_sale_id)) {
                $playSession = \App\Models\PlaySession::find($playSessionId);
                
                if ($playSession) {
                    // If there are add-ons associated with the play session, detach them
                    if ($playSession->addOns()->count() > 0) {
                        $playSession->addOns()->detach();
                    }
                    
                    // Delete the play session
                    $playSession->delete();
                }
            }
        });
        
        return redirect()->route('cashier.sales.index')
            ->with('success', 'Sale and associated data have been successfully deleted.');
    }

    /**
     * Show form for creating an add-on-only sale (without play session).
     */
    public function createAddOnOnly(Request $request)
    {
        // Get all available add-ons
        $addOns = \App\Models\AddOn::where('active', true)->orderBy('name')->get();
        
        // Find active shift
        $activeShift = Shift::where('cashier_id', Auth::id())
                           ->whereNull('closed_at')
                           ->first();
        
        if (!$activeShift) {
            return redirect()->route('cashier.dashboard')
                ->with('error', 'You must have an active shift to create sales.');
        }
        
        // Get the list of children for customer selection
        $children = \App\Models\Child::orderBy('name')->get();
        
        // Check if a specific child was selected
        $selectedChild = null;
        if ($request->has('child_id') && $request->child_id) {
            $selectedChild = \App\Models\Child::find($request->child_id);
        }
        
        return view('cashier.sales.create-addon-only', compact(
            'addOns', 
            'activeShift', 
            'children',
            'selectedChild'
        ));
    }
    
    /**
     * Store an add-on-only sale (without play session).
     */
    public function storeAddOnOnly(Request $request)
    {
        $request->validate([
            'child_id' => 'required|exists:children,id',
            'payment_method' => 'required|in:LBP,USD',
            'custom_total' => 'required|numeric|min:0.01',
            'amount_paid' => 'required|numeric|min:0',
            'add_ons' => 'required|array|min:1',
            'add_ons.*.qty' => 'numeric|min:0.01'
        ]);

                // Find active shift
                $shift = Shift::where('cashier_id', Auth::id())
                            ->whereNull('closed_at')
                            ->first();
                
                if (!$shift) {
            return redirect()->back()
                ->with('error', 'No active shift found.')
                ->withInput();
        }

        return DB::transaction(function () use ($request, $shift) {
                $paymentMethod = $request->payment_method;
            
            // BETTER APPROACH: Always store amounts in their native currency
            // LBP payments: store large LBP numbers (e.g., 180000)
            // USD payments: store small USD numbers (e.g., 2.00)
            if ($paymentMethod === 'LBP') {
                // For LBP payments, always ensure we store meaningful LBP amounts
                $lbpRate = config('play.lbp_exchange_rate', 90000);
                
                if ($request->custom_total < 1000) {
                    // User likely entered USD amount, convert to LBP for storage
                    $totalAmountToStore = round($request->custom_total * $lbpRate, 0);
                    $amountPaidToStore = round($request->amount_paid * $lbpRate, 0);
                    
                    $conversionNote = "Amounts entered in USD (${$request->custom_total} total, ${$request->amount_paid} paid) were converted to LBP (" . number_format($totalAmountToStore) . " total, " . number_format($amountPaidToStore) . " paid) for storage.";
                } else {
                    // User entered LBP amounts directly - store as-is
                    $totalAmountToStore = round($request->custom_total, 0);
                    $amountPaidToStore = round($request->amount_paid, 0);
                    $conversionNote = null;
                }
            } else {
                // For USD payments, store USD amounts directly
                $totalAmountToStore = round($request->custom_total, 2);
                $amountPaidToStore = round($request->amount_paid, 2);
                $conversionNote = null;
            }
            
            // Validate amount paid is sufficient
            if ($amountPaidToStore < $totalAmountToStore) {
                return redirect()->back()
                    ->with('error', 'Amount paid must be at least the total cost.')
                    ->withInput();
            }

            // Create the sale - CRITICAL: store in payment currency without conversion
            $sale = Sale::create([
                'shift_id' => $shift->id,
                'user_id' => Auth::id(),
                'child_id' => $request->child_id,
                'total_amount' => $totalAmountToStore, // Same currency as payment_method
                'amount_paid' => $amountPaidToStore,   // Same currency as payment_method
                'payment_method' => $paymentMethod,
                'currency' => $paymentMethod,
                'status' => 'completed',
                'notes' => $conversionNote ? 'Add-on only sale (no play session). ' . $conversionNote : 'Add-on only sale (no play session)'
            ]);
                
                // Create sale items for each add-on
            foreach ($request->add_ons as $addOnId => $data) {
                if (isset($data['qty']) && (float)$data['qty'] > 0) {
                    $addOn = \App\Models\AddOn::find($addOnId);
                    if ($addOn) {
                        $qty = (float)$data['qty'];
                        
                        // Store add-on prices in USD for consistency with session add-ons
                        // The custom total handles the final pricing in the selected currency
                        $itemPrice = $addOn->price; // Always USD base price
                        $itemSubtotal = $itemPrice * $qty; // USD subtotal
                        
                        // For display purposes, we'll convert during display, not storage
                        SaleItem::create([
                            'sale_id' => $sale->id,
                            'product_id' => null,
                            'add_on_id' => $addOn->id,
                            'quantity' => $qty,
                            'unit_price' => $itemPrice, // USD price for consistency
                            'subtotal' => $itemSubtotal, // USD subtotal for consistency
                            'description' => $addOn->name . ' (add-on)'
                        ]);
                    }
                }
                }
                
                return redirect()->route('cashier.sales.show', $sale)
                    ->with('success', 'Add-on sale completed successfully');
            });
    }
} 