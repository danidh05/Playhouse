<?php

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Controller;
use App\Http\Requests\SaleRequest;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Shift;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
        $products = Product::where('stock_qty', '>', 0)->get();
        
        // Find active shift
        $activeShift = Shift::where('cashier_id', Auth::id())
                           ->whereNull('closed_at')
                           ->first();
        
        // Get the list of children for customer selection
        $children = \App\Models\Child::orderBy('name')->get();
        
        // Check if a specific child was selected
        $selectedChild = null;
        if ($request->has('child_id') && $request->child_id) {
            $selectedChild = \App\Models\Child::find($request->child_id);
        }
        
        return view('cashier.sales.create', compact(
            'products', 
            'activeShift', 
            'children',
            'selectedChild'
        ));
    }

    /**
     * Store a newly created sale in storage.
     */
    public function store(Request $request)
    {
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
            
            // Decode the products from the JSON string
            $productsData = json_decode($request->products, true);
            
            if (empty($productsData)) {
                return redirect()->back()->with('error', 'No products selected.');
            }
            
            // Get customer details
            $childId = $request->child_id ?? null;
            $playSessionId = $request->play_session_id ?? null;
            
            // Set payment method and handle amount calculations
            $paymentMethod = $request->payment_method;
            $lbpRate = config('play.lbp_exchange_rate', 90000);

            // Get the play session if it exists
            $playSession = null;
            if ($playSessionId) {
                $playSession = \App\Models\PlaySession::find($playSessionId);
            }
            
            // Calculate total amount in USD
            if ($paymentMethod === 'LBP') {
                // Get the raw LBP amounts
                $amountPaidLBP = $request->amount_paid;
                
                // If there's a play session, use its total
                if ($playSession) {
                    $totalAmountLBP = $playSession->total_cost * $lbpRate;
                } else {
                    $totalAmountLBP = $request->total_amount_lbp;
                }
                
                // Convert to USD for storage
                $totalAmount = round($totalAmountLBP / $lbpRate, 2);
                // Convert amount paid from LBP to USD
                $amountPaid = round($amountPaidLBP / $lbpRate, 2);
            } else {
                // For USD payments
                $amountPaid = round($request->amount_paid, 2);
                
                // If there's a play session, use its total
                if ($playSession) {
                    $totalAmount = round($playSession->total_cost, 2);
                } else {
                    $totalAmount = round($request->total_amount, 2);
                }
            }
            
            // Create the sale
            $sale = new Sale();
            $sale->shift_id = $shift->id;
            $sale->user_id = Auth::id();
            $sale->total_amount = $totalAmount;
            $sale->amount_paid = $amountPaid;
            $sale->payment_method = $paymentMethod;
            
            // Store LBP amount in metadata if needed
            if ($paymentMethod === 'LBP') {
                $sale->metadata = json_encode(['amount_paid_lbp' => $amountPaidLBP]);
            }
            
            $sale->child_id = $childId;
            $sale->play_session_id = $playSessionId;
            $sale->save();
            
            // Process each product
            foreach ($productsData as $productData) {
                // Lock the product row for update to prevent race conditions
                $product = Product::where('id', $productData['id'])->lockForUpdate()->firstOrFail();
                
                if ($product->stock_qty < $productData['quantity']) {
                    // If we don't have enough stock, rollback the transaction
                    DB::rollBack();
                    return redirect()->back()->with('error', "Not enough stock available for {$product->name}");
                }
                
                // Create the sale item
                $saleItem = new SaleItem();
                $saleItem->sale_id = $sale->id;
                $saleItem->product_id = $product->id;
                $saleItem->quantity = $productData['quantity'];
                $saleItem->unit_price = $product->price;
                $saleItem->subtotal = $product->price * $productData['quantity'];
                $saleItem->save();
                
                // Update the stock quantity
                $product->update([
                    'stock_qty' => $product->stock_qty - $productData['quantity']
                ]);
            }
            
            return redirect()->route('cashier.sales.show', $sale)->with('success', 'Sale completed successfully');
        });
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
          'play_session',
          'play_session.addOns'
        ]);
        
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
        
        return view('cashier.sales.show', compact('sale'));
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
} 