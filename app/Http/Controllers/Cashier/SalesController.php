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
            $totalAmount = 0;
            
            // If payment method is LBP, we need to calculate the equivalent USD amount
            if ($paymentMethod === 'LBP') {
                $totalAmount = $request->total_amount_lbp;
            } else {
                // Default to USD 
                $totalAmount = $request->total_amount;
            }
            
            // Create the sale
            $sale = new Sale();
            $sale->shift_id = $shift->id;
            $sale->user_id = Auth::id();
            $sale->total_amount = $totalAmount;
            $sale->payment_method = $paymentMethod;
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
            
            return redirect()->route('cashier.sales.create')->with('success', 'Sale completed successfully');
        });
    }

    /**
     * Display the specified sale.
     */
    public function show(Sale $sale)
    {
        $sale->load(['items.product', 'user', 'shift']);
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