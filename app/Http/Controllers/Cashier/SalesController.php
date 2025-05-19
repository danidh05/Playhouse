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
                'play_session'
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
            'payment_method' => 'required|in:cash,card',
            'shift_id' => 'required|exists:shifts,id',
            'amount_paid' => 'required|numeric|min:0',
            'currency' => 'required|in:usd,lbp',
        ]);

        $items = $request->items;
        $totalAmount = 0;
        $currency = $request->currency;
        
        // Start database transaction
        DB::beginTransaction();
        
        try {
            // Create the sale record
            $sale = new Sale([
                'user_id' => Auth::id(),
                'shift_id' => $request->shift_id,
                'payment_method' => $request->payment_method,
                'amount_paid' => $request->amount_paid,
                'status' => 'completed',
                'currency' => $currency,
            ]);
            
            if ($request->has('child_id') && !empty($request->child_id)) {
                $sale->child_id = $request->child_id;
            }
            
            // Process items
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
                
                $itemTotal = $item['price'] * $item['qty'];
                $totalAmount += $itemTotal;
                
                // Reduce stock
                $product->decrement('stock_qty', $item['qty']);
            }
            
            // Set total amount and save the sale
            $sale->total_amount = $totalAmount;
            $sale->save();
            
            // Create sale items
            foreach ($items as $id => $item) {
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $id,
                    'quantity' => $item['qty'],
                    'unit_price' => $item['price'],
                    'subtotal' => $item['price'] * $item['qty'],
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
        
        // If there's a play session with zero actual_hours but it has ended, calculate the hours
        if ($sale->play_session && $sale->play_session->ended_at) {
            // Calculate actual hours if not set
            if ($sale->play_session->actual_hours == 0) {
                $startTime = $sale->play_session->started_at;
                $endTime = $sale->play_session->ended_at;
                $durationMinutes = $startTime->diffInMinutes($endTime);
                $actualHours = $durationMinutes / 60;
                
                // Update play session actual hours
                $sale->play_session->update(['actual_hours' => $actualHours]);
            } else {
                $actualHours = $sale->play_session->actual_hours;
            }
            
            // Recalculate total cost to ensure it's correct
            $hourlyRate = config('play.hourly_rate', 10.00);
            $baseTotal = $actualHours * $hourlyRate;
            
            // Make sure add-ons are loaded
            $sale->load('play_session.addOns');
            
            // Get add-ons total
            $addOnsTotal = $sale->play_session->addOns->sum(function ($addOn) {
                return $addOn->pivot->subtotal;
            });
            
            // Apply discount only to time cost
            $discountMultiplier = (100 - ($sale->play_session->discount_pct ?? 0)) / 100;
            $timeCost = $baseTotal * $discountMultiplier;
            $totalCost = round($timeCost + $addOnsTotal, 2);
            
            // Only update if the total cost has changed
            if (abs($sale->play_session->total_cost - $totalCost) > 0.01) {
                $sale->play_session->update(['total_cost' => $totalCost]);
                $sale->update(['total_amount' => $totalCost]);
                
                // Refresh the model
                $sale->load(['play_session', 'play_session.addOns']);
            }
        }
        
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
                'play_session',
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
        $todayRevenue = Sale::whereDate('created_at', today())->sum('total_amount');
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
            
            // Restore product stock for each sale item
            foreach ($sale->items as $item) {
                $item->product->increment('stock_qty', $item->quantity);
            }
            
            // Check for child sales that should be deleted
            if ($sale->child_sales && $sale->child_sales->count() > 0) {
                foreach ($sale->child_sales as $childSale) {
                    // Restore product stock for each child sale item
                    foreach ($childSale->items as $item) {
                        $item->product->increment('stock_qty', $item->quantity);
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
        $addOns = \App\Models\AddOn::where('active', true)->get();
        
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
        
        // Get payment methods
        $paymentMethods = config('play.payment_methods', []);
        
        return view('cashier.sales.create-addon-only', compact(
            'addOns', 
            'activeShift', 
            'children',
            'selectedChild',
            'paymentMethods'
        ));
    }
    
    /**
     * Store an add-on-only sale (without play session).
     */
    public function storeAddOnOnly(Request $request)
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'child_id' => 'required|exists:children,id',
            'add_ons' => 'required|array',
            'payment_method' => 'required|string',
            'amount_paid' => 'required|numeric|min:0',
        ]);
        
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }
        
        try {
            return DB::transaction(function () use ($request) {
                // Find active shift
                $shift = Shift::where('cashier_id', Auth::id())
                            ->whereNull('closed_at')
                            ->first();
                
                if (!$shift) {
                    // Create a new shift for the cashier if none exists
                    $shift = new Shift();
                    $shift->cashier_id = Auth::id();
                    $shift->opened_at = now();
                    $shift->save();
                }
                
                // Calculate total from add-ons
                $totalAmount = 0;
                $addOnsWithQty = [];
                
                foreach ($request->add_ons as $addOnId => $data) {
                    if (isset($data['qty']) && (float)$data['qty'] > 0) {
                        $addOn = \App\Models\AddOn::find($addOnId);
                        if ($addOn) {
                            $qty = (float)$data['qty'];
                            $subtotal = $addOn->price * $qty;
                            $totalAmount += $subtotal;
                            
                            // Store for later
                            $addOnsWithQty[$addOnId] = [
                                'qty' => $qty,
                                'subtotal' => $subtotal
                            ];
                        }
                    }
                }
                
                // If no add-ons were selected with quantity, redirect back
                if (empty($addOnsWithQty)) {
                    return redirect()->back()
                        ->with('error', 'No add-ons selected with quantity.')
                        ->withInput();
                }
                
                // Set payment method and handle amount calculations
                $paymentMethod = $request->payment_method;
                $lbpRate = config('play.lbp_exchange_rate', 90000);
                
                // Calculate total amount in USD
                if ($paymentMethod === 'LBP') {
                    // Convert amount paid from LBP to USD
                    $amountPaid = round($request->amount_paid / $lbpRate, 2);
                } else {
                    // For USD payments
                    $amountPaid = round($request->amount_paid, 2);
                }
                
                // Create the sale
                $sale = new Sale();
                $sale->shift_id = $shift->id;
                $sale->user_id = Auth::id();
                $sale->total_amount = round($totalAmount, 2);
                $sale->amount_paid = $amountPaid;
                $sale->payment_method = $paymentMethod;
                $sale->child_id = $request->child_id;
                $sale->status = 'completed';
                $sale->notes = 'Add-on only sale (no play session)';
                $sale->save();
                
                // Create sale items for each add-on
                foreach ($addOnsWithQty as $addOnId => $data) {
                    $addOn = \App\Models\AddOn::find($addOnId);
                    
                    // Create the sale item
                    $saleItem = new SaleItem();
                    $saleItem->sale_id = $sale->id;
                    $saleItem->product_id = null;  // No product
                    $saleItem->add_on_id = $addOn->id;
                    $saleItem->quantity = $data['qty'];
                    $saleItem->unit_price = $addOn->price;
                    $saleItem->subtotal = $data['subtotal'];
                    $saleItem->save();
                }
                
                return redirect()->route('cashier.sales.show', $sale)
                    ->with('success', 'Add-on sale completed successfully');
            });
        } catch (\Exception $e) {
            // Log the error
            \Log::error('Error creating add-on sale: ' . $e->getMessage());
            
            // Provide a more informative error message
            return redirect()->back()
                ->with('error', 'An error occurred: ' . $e->getMessage())
                ->withInput();
        }
    }
} 