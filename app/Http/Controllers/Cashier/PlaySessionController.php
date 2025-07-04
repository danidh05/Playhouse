<?php

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Controller;
use App\Http\Requests\PlaySessionRequest;
use App\Models\AddOn;
use App\Models\Child;
use App\Models\PlaySession;
use App\Models\Shift;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Models\Sale;

class PlaySessionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $activeSessions = PlaySession::with('child')->whereNull('ended_at')->latest()->get();
        
        // Get filter parameter from request
        $filter = $request->input('filter', 'today');
        
        // Base query for recent sessions - eager load related data
        $recentSessionsQuery = PlaySession::with(['child', 'sale'])
            ->whereNotNull('ended_at')
            ->latest();
        
        // Apply date filtering
        switch ($filter) {
            case 'today':
                $recentSessionsQuery->whereDate('created_at', today());
                break;
            case 'week':
                $recentSessionsQuery->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                break;
            case 'month':
                $recentSessionsQuery->whereMonth('created_at', now()->month)
                          ->whereYear('created_at', now()->year);
                break;
            // 'all' doesn't need any filter
        }
        
        $recentSessions = $recentSessionsQuery->paginate(10)->withQueryString();
        
        // Load play session count for each child in recent sessions
        foreach ($recentSessions as $session) {
            $session->child->play_sessions_count = PlaySession::where('child_id', $session->child_id)->count();
        }
        
        return view('cashier.sessions.index', compact('activeSessions', 'recentSessions', 'filter'));
    }

    /**
     * Show the form for starting a new session.
     * If child_id is provided in the query string, it will be pre-selected.
     */
    public function create(Child $child = null)
    {
        // Find an active shift for the current cashier
        $activeShift = Shift::where('cashier_id', Auth::id())
                           ->whereNull('closed_at')
                           ->latest()
                           ->first();

        if (!$activeShift) {
            // Create a new shift if none exists
            $activeShift = Shift::create([
                'cashier_id' => Auth::id(),
                'date' => now()->toDateString(),
                'type' => (now()->hour < 12) ? 'morning' : 'night',
                'opened_at' => now(),
            ]);
        }

        $hourlyRate = config('play.hourly_rate', 10.00);
        $children = Child::orderBy('name')->get();

        // Add play session count for each child
        foreach ($children as $childOption) {
            $childOption->play_sessions_count = PlaySession::where('child_id', $childOption->id)->count();
        }

        return view('cashier.sessions.start', compact('child', 'children', 'activeShift', 'hourlyRate'));
    }

    /**
     * Show the form for starting a session for a specific child.
     */
    public function start(Child $child)
    {
        // Find an active shift for the current cashier
        $activeShift = Shift::where('cashier_id', Auth::id())
                           ->whereNull('closed_at')
                           ->latest()
                           ->first();
        
        if (!$activeShift) {
            // Create a new shift if none exists
            $activeShift = Shift::create([
                'cashier_id' => Auth::id(),
                'date' => now()->toDateString(),
                'type' => (now()->hour < 12) ? 'morning' : 'night',
                'opened_at' => now(),
            ]);
        }
        
        $hourlyRate = config('play.hourly_rate', 10.00);
        $children = Child::orderBy('name')->get();
        
        // Add play session count for each child
        foreach ($children as $childOption) {
            $childOption->play_sessions_count = PlaySession::where('child_id', $childOption->id)->count();
        }
        
        return view('cashier.sessions.start', compact('child', 'children', 'activeShift', 'hourlyRate'));
    }

    /**
     * Store a newly created session in storage.
     */
    public function store(PlaySessionRequest $request)
    {
        $validated = $request->validated();
        
        // If start_time is present, set it as started_at
        if (isset($validated['start_time'])) {
            $validated['started_at'] = $validated['start_time'];
            unset($validated['start_time']);
        } else {
            // If no start_time provided, use current time
            $validated['started_at'] = now();
        }
        
        // Ensure shift_id is set if not provided
        if (!isset($validated['shift_id'])) {
            // Find active shift for current cashier
            $activeShift = Shift::where('cashier_id', Auth::id())
                ->whereNull('closed_at')
                ->latest()
                ->first();
                
            if (!$activeShift) {
                // Create a shift if none exists
                $activeShift = Shift::create([
                    'cashier_id' => Auth::id(),
                    'date' => now()->toDateString(),
                    'type' => (now()->hour < 12) ? 'morning' : 'night',
                    'opened_at' => now(),
                    'opening_amount' => 0, // Default value
                ]);
            }
            
            $validated['shift_id'] = $activeShift->id;
        }
        
        // Get hourly rate from config
        $hourlyRate = config('play.hourly_rate', 10.00);
        
        // Calculate initial estimated cost based on planned hours
        if (isset($validated['planned_hours']) && $validated['planned_hours'] > 0) {
            $discountMultiplier = isset($validated['discount_pct']) ? (100 - $validated['discount_pct']) / 100 : 1;
            $initialCost = $validated['planned_hours'] * $hourlyRate * $discountMultiplier;
            $validated['total_cost'] = $initialCost;
        }
        
        $playSession = new PlaySession($validated);
        $playSession->user_id = Auth::id();
        $playSession->save();
        
        return redirect()->route('cashier.sessions.index')
            ->with('success', 'Play session started successfully');
    }
    
    /**
     * Display the specified session.
     *
     * @param  \App\Models\PlaySession  $session
     * @return \Illuminate\View\View
     */
    public function show(PlaySession $session)
    {
        // Load relationships including sale
        $session->load(['child', 'user', 'shift', 'addOns', 'sale']);
        
        // Get total play sessions count for this child
        $playSessionsCount = PlaySession::where('child_id', $session->child_id)->count();
        
        // Get session duration
        if ($session->ended_at) {
            $endTime = $session->ended_at;
        } else {
            $endTime = now();
        }
        
        $startTime = $session->started_at;
        $duration = $startTime->diffAsCarbonInterval($endTime)->cascade();
        
        // Calculate progress for active sessions
        $progress = null;
        if (!$session->ended_at && $session->planned_hours) {
            $minutesTotal = ($duration->hours * 60) + $duration->minutes;
            $plannedMinutes = $session->planned_hours * 60;
            $progress = min(100, ($minutesTotal / $plannedMinutes) * 100);
        }
        
        // Use the eager-loaded sale relationship
        $sale = $session->sale;
        
        return view('cashier.sessions.show', compact('session', 'duration', 'progress', 'sale', 'playSessionsCount'));
    }

    /**
     * Show the session end form with calculated totals.
     */
    public function showEnd(PlaySession $session, Request $request)
    {
        if ($session->ended_at) {
            return redirect()->route('cashier.sessions.index')
                ->with('error', 'This session has already ended');
        }

        // Always calculate the current duration at the time this page is loaded
        $startTime = $session->started_at;
        $now = now();

        // Calculate the time difference ensuring proper direction
        if ($now->lt($startTime)) {
            // If current time is less than start time (shouldn't happen normally)
            return redirect()->route('cashier.sessions.index')
                ->with('error', 'Invalid session start time');
        }

        // Calculate current duration
        $durationInSeconds = $now->getTimestamp() - $startTime->getTimestamp();
        $hours = floor($durationInSeconds / 3600);
        $minutes = floor(($durationInSeconds % 3600) / 60);
        $seconds = $durationInSeconds % 60;
        $initialDuration = "{$hours}h {$minutes}m {$seconds}s";

        // Calculate duration in hours for billing (rounded to 2 decimals)
        $durationInHours = round($durationInSeconds / 3600, 2);
        
        // Only cap the billable hours if the actual time EXCEEDS the planned hours
        // If they played less than planned, they should only be billed for actual time
        $actualDurationForBilling = $durationInHours;
        $cappedHours = false;
        
        // Only cap if they played MORE than planned (not less)
        if ($session->planned_hours > 0 && $durationInHours > $session->planned_hours) {
            $actualDurationForBilling = $session->planned_hours;
            $cappedHours = true;
        }
        // If they played less than or equal to planned hours, bill for actual time

        // Get hourly rate from config
        $hourlyRate = config('play.hourly_rate', 10.00);

        // Calculate time cost before discount - use the capped duration
        $rawTimeCost = round($actualDurationForBilling * $hourlyRate, 2);

        // Store calculated values in session for later use in end method
        $sessionKey = "play_session_{$session->id}_end_data";
        $request->session()->put($sessionKey, [
            'initialDuration' => $initialDuration,
            'durationInHours' => $actualDurationForBilling, // Store the capped duration
            'rawTimeCost' => $rawTimeCost,
            'endTime' => $now,
            'cappedHours' => $cappedHours,
            'actualElapsedHours' => $durationInHours // Store the actual elapsed hours for reference
        ]);

        // Get add-ons and their total - this can change between requests
        $addOns = AddOn::where('active', true)->get();
        $sessionAddOns = $session->addOns()->get();
        
        // Calculate add-ons total in USD first (as stored in pivot)
        $addonsBaseTotal = $sessionAddOns->sum('pivot.subtotal');
        
        // Convert to selected payment currency for display
        $paymentMethod = $request->get('payment_method', 'USD'); // Default to USD
        if ($paymentMethod === 'LBP') {
            $lbpRate = config('play.lbp_exchange_rate', 90000);
            $addonsTotal = round($addonsBaseTotal * $lbpRate);
            $rawTimeCostConverted = round($rawTimeCost * $lbpRate);
        } else {
            $addonsTotal = $addonsBaseTotal;
            $rawTimeCostConverted = $rawTimeCost;
        }
        
        // Apply discount only to time cost, not to add-ons (same as end method)
        $discountPct = $session->discount_pct ?? 0;
        $discountMultiplier = (100 - $discountPct) / 100;
        $timeCost = round($rawTimeCostConverted * $discountMultiplier, 2);
        
        // Discount amount is the difference between raw time cost and discounted time cost
        $discountAmount = $rawTimeCostConverted - $timeCost;
        
        // Get pending product sales
        $pendingSales = \App\Models\Sale::where('play_session_id', $session->id)
            ->where('status', 'pending')
            ->with('items.product')
            ->get();
            
        $pendingSalesBaseTotal = $pendingSales->sum('total_amount');
        
        // Convert pending sales total to selected currency if needed
        if ($paymentMethod === 'LBP') {
            $pendingSalesTotal = round($pendingSalesBaseTotal * $lbpRate);
        } else {
            $pendingSalesTotal = $pendingSalesBaseTotal;
        }
        
        // Subtotal for display (raw time cost + add-ons) - use converted amounts
        $subtotal = $rawTimeCostConverted + $addonsTotal + $pendingSalesTotal;
        
        // Total amount is discounted time cost plus add-ons plus pending sales - use converted amounts
        $totalAmount = round($timeCost + $addonsTotal + $pendingSalesTotal, $paymentMethod === 'LBP' ? 0 : 2);
        
        $paymentMethods = config('play.payment_methods', []);
        
        // TEMPORARY DEBUG - log what we're passing to the view
        \Log::info("PlaySession showEnd Debug", [
            'session_id' => $session->id,
            'payment_method' => $paymentMethod,
            'rawTimeCost' => $rawTimeCost,
            'rawTimeCostConverted' => $rawTimeCostConverted,
            'addonsBaseTotal' => $addonsBaseTotal,
            'addonsTotal' => $addonsTotal,
            'timeCost' => $timeCost,
            'totalAmount' => $totalAmount,
            'lbpRate' => config('play.lbp_exchange_rate', 90000)
        ]);
        
        // Pass the converted rawTimeCost as rawTimeCost for the view
        return view('cashier.sessions.end', compact(
            'session',
            'addOns',
            'sessionAddOns',
            'paymentMethods',
            'addonsTotal',
            'durationInHours',
            'actualDurationForBilling',
            'cappedHours',
            'initialDuration',
            'timeCost',
            'subtotal',
            'discountAmount',
            'totalAmount',
            'pendingSales',
            'pendingSalesTotal'
        ) + ['rawTimeCost' => $rawTimeCostConverted]);
    }

    /**
     * End the session and calculate totals.
     */
    public function end(Request $request, PlaySession $session)
    {
        if ($session->ended_at) {
            return redirect()->route('cashier.sessions.index')
                ->with('error', 'This session has already ended');
        }
    
        $request->validate([
            'payment_method' => ['required', Rule::in(config('play.payment_methods', ['USD', 'LBP']))],
            'total_cost' => 'required|numeric|min:0',
            'amount_paid' => 'required|numeric|min:0',
        ]);

        // Use the custom total_cost set by the cashier (no conversions)
        $totalCost = $request->total_cost;
        $amountPaid = $request->amount_paid;
        
        // Validate amount paid is sufficient
        if ($amountPaid < $totalCost) {
            return redirect()->back()
                ->with('error', 'Amount paid must be at least the total cost.')
                ->withInput();
        }
    
        // Update add-ons if provided
        if ($request->has('add_ons')) {
            $session->addOns()->detach();
    
            foreach ($request->add_ons as $addOnId => $data) {
                if (isset($data['qty']) && (float)$data['qty'] > 0) {
                    $addOn = AddOn::find($addOnId);
                    if ($addOn) {
                        $qty = (float)$data['qty'];
                        $subtotal = $addOn->price * $qty;
    
                    $session->addOns()->attach($addOnId, [
                            'qty' => $qty,
                        'subtotal' => $subtotal
                    ]);
                    }
                }
            }
        }
    
        // Get the saved end time data from session
        $sessionKey = "play_session_{$session->id}_end_data";
        $endData = $request->session()->get($sessionKey);
        
        if ($endData && isset($endData['durationInHours'])) {
            // Use the duration stored in the session (which might be capped)
            $actualHours = $endData['durationInHours'];
            $endTime = $endData['endTime'];
            
            // Set note if hours were capped
            $noteAboutCapping = '';
            if (isset($endData['cappedHours']) && $endData['cappedHours']) {
                $noteAboutCapping = "Note: Billable hours capped at planned hours ({$session->planned_hours}h). Actual session duration: {$endData['actualElapsedHours']}h.";
                
                // If there's an existing note, append to it
                if ($session->notes) {
                    $session->notes .= "\n\n" . $noteAboutCapping;
                } else {
                    $session->notes = $noteAboutCapping;
                }
            }
            
        } else {
            // Fallback to calculating it now
            $startTime = Carbon::parse($session->started_at);
            $endTime = Carbon::now();
            $durationHours = max(0, $endTime->diffInMinutes($startTime) / 60);
            
            // Only cap if they played MORE than planned hours (not less)
            if ($session->planned_hours > 0 && $durationHours > $session->planned_hours) {
                $actualHours = $session->planned_hours;
                
                // Add note about capping
                $noteAboutCapping = "Note: Billable hours capped at planned hours ({$session->planned_hours}h). Actual session duration: " . number_format($durationHours, 2) . "h.";
                
                // If there's an existing note, append to it
                if ($session->notes) {
                    $session->notes .= "\n\n" . $noteAboutCapping;
                } else {
                    $session->notes = $noteAboutCapping;
                }
            } else {
                // Bill for actual time played (not capped to planned hours if less)
                // Ensure proper precision for duration
                $actualHours = round($durationHours, 2);
            }
        }
        
        // We'll still calculate the standard cost for record-keeping
        // Get hourly rate from config
        $hourlyRate = config('play.hourly_rate', 10.00);
        // Calculate base total from actual hours - this is the exact duration in decimal hours
        $baseTotal = $actualHours * $hourlyRate;
    
        // Get add-ons total only after refreshing the add-ons relationship
        $session->load('addOns');
        $addOnsTotal = $session->addOns->sum(function ($addOn) {
            return $addOn->pivot->subtotal;
        });
    
        // Apply discount only to the session time cost, not to add-ons
        $discountMultiplier = (100 - ($session->discount_pct ?? 0)) / 100;
        $timeCost = $baseTotal * $discountMultiplier;
        
        // Get pending product sales
        $pendingSales = \App\Models\Sale::where('play_session_id', $session->id)
            ->where('status', 'pending')
            ->with('items.product')
            ->get();
            
        $pendingSalesTotal = $pendingSales->sum('total_amount');
        
        // Calculate the standard total cost as before (for reference only)
        $calculatedTotalCost = round($timeCost + $addOnsTotal + $pendingSalesTotal, 2);
    
        // Use the custom price instead of the calculated cost
        $paymentMethod = $request->payment_method;
        $lbpRate = config('play.lbp_exchange_rate', 90000);
    
        // BETTER APPROACH: Always store amounts in their native currency
        // LBP payments: store large LBP numbers (e.g., 180000)
        // USD payments: store small USD numbers (e.g., 2.00)
        if ($paymentMethod === 'LBP') {
            // For LBP payments, always ensure we store meaningful LBP amounts
            if ($request->total_cost < 1000) {
                // User likely entered USD amount, convert to LBP for storage
                $lbpRate = config('play.lbp_exchange_rate', 90000);
                $totalAmountToStore = round($request->total_cost * $lbpRate, 0);
                $amountPaidToStore = round($request->amount_paid * $lbpRate, 0);
                
                $conversionNote = "Note: Amounts entered in USD (${$request->total_cost} total, ${$request->amount_paid} paid) were converted to LBP (" . number_format($totalAmountToStore) . " total, " . number_format($amountPaidToStore) . " paid) for storage.";
                
                if ($session->notes) {
                    $session->notes .= "\n\n" . $conversionNote;
                } else {
                    $session->notes = $conversionNote;
                }
            } else {
                // User entered LBP amounts directly - store as-is
                $totalAmountToStore = round($request->total_cost, 0);
                $amountPaidToStore = round($request->amount_paid, 0);
            }
            
            // For comparison notes with LBP, convert calculated USD amount to LBP
            $lbpRate = config('play.lbp_exchange_rate', 90000);
            $calculatedAmountLbp = round($calculatedTotalCost * $lbpRate);
            if (abs($totalAmountToStore - $calculatedAmountLbp) > 0.01 * $calculatedAmountLbp) {
                $customPriceNote = "Note: Manual price set by cashier: " . number_format($totalAmountToStore) . " LBP. ";
                $customPriceNote .= "Standard calculated price would have been: " . number_format($calculatedAmountLbp) . " LBP.";
                
                if ($session->notes) {
                    $session->notes .= "\n\n" . $customPriceNote;
                } else {
                    $session->notes = $customPriceNote;
                }
            }
        } else {
            // For USD payments, store USD amounts directly
            $totalAmountToStore = round($request->total_cost, 2);
            $amountPaidToStore = round($request->amount_paid, 2);
            
            // Add a note if the manual price differs from the calculated price
            if (abs($totalAmountToStore - $calculatedTotalCost) > 0.01) {
                $customPriceNote = "Note: Manual price set by cashier: $" . number_format($totalAmountToStore, 2) . ". ";
                $customPriceNote .= "Standard calculated price would have been: $" . number_format($calculatedTotalCost, 2) . ".";
                
                if ($session->notes) {
                    $session->notes .= "\n\n" . $customPriceNote;
                } else {
                    $session->notes = $customPriceNote;
                }
            }
        }
    
        // Update session - CRITICAL: store in payment currency without any conversion
        $session->ended_at = now();
        $session->actual_hours = $actualHours; // Store actual hours for record keeping
        $session->payment_method = $paymentMethod;
        $session->total_cost = $totalAmountToStore; // CASHIER'S CUSTOM AMOUNT - NOT CALCULATED
        $session->amount_paid = $amountPaidToStore; // CASHIER'S CUSTOM AMOUNT - NOT CALCULATED
        $session->save();

        // Create or update the main sale for this session - CRITICAL: same currency consistency
        $existingSale = Sale::where('play_session_id', $session->id)->first();
        
        if ($existingSale) {
            $existingSale->update([
                'total_amount' => $totalAmountToStore, // Same currency as payment_method
                'amount_paid' => $amountPaidToStore,   // Same currency as payment_method
            'payment_method' => $paymentMethod,
                'currency' => $paymentMethod,
            'status' => 'completed'
        ]);
            $sale = $existingSale;
            } else {
            $sale = Sale::create([
                'play_session_id' => $session->id,
                'shift_id' => $session->shift_id,
                'user_id' => Auth::id(),
                'child_id' => $session->child_id,
                'total_amount' => $totalAmountToStore, // Same currency as payment_method
                'amount_paid' => $amountPaidToStore,   // Same currency as payment_method
                'payment_method' => $paymentMethod,
                'currency' => $paymentMethod,
                'status' => 'completed',
                'notes' => 'Play session payment'
            ]);
        }

        // CRITICAL: Refresh sale model to ensure we have the latest custom amounts
        $sale->refresh();
        
        // Create sale items for the session time and add-ons to display on receipt
        $this->createSaleItemsForSession($sale, $session);
    
        // Redirect to sale detail view
        return redirect()->route('cashier.sales.show', $sale->id)
            ->with('success', 'Play session ended successfully');
    }

    /**
     * Update add-ons for a session.
     */
    public function updateAddOns(Request $request, PlaySession $session)
    {
        if ($session->ended_at) {
            return redirect()->route('cashier.sessions.index')
                ->with('error', 'Cannot modify add-ons for a completed session');
        }

        if ($request->has('add_ons')) {
            $session->addOns()->detach();
            
            // Create an array with required pivot data
            $addOnsWithQty = [];
            
            foreach ($request->add_ons as $addOnId => $data) {
                $addOn = AddOn::find($addOnId);
                if ($addOn && isset($data['qty']) && (float)$data['qty'] > 0) {
                        $qty = (float)$data['qty'];
                        $subtotal = $addOn->price * $qty;
                    
                    $addOnsWithQty[$addOnId] = [
                        'qty' => $qty,
                        'subtotal' => $subtotal
                    ];
                }
            }
            
            if (!empty($addOnsWithQty)) {
                $session->addOns()->attach($addOnsWithQty);
            }
        }
        
        return redirect()->route('cashier.sessions.show', $session->id)
            ->with('success', 'Add-ons updated successfully');
    }

    /**
     * Show the add-ons management page for a session.
     */
    public function showAddOns(PlaySession $session)
    {
        if ($session->ended_at) {
            return redirect()->route('cashier.sessions.index')
                ->with('error', 'Cannot modify add-ons for a completed session');
        }

        $addOns = AddOn::where('active', true)->get();
        $sessionAddOns = $session->addOns()->get();
        return view('cashier.sessions.addons', compact('session', 'addOns', 'sessionAddOns'));
    }
    
    /**
     * Show the form to add products to a session.
     */
    public function showAddProducts(PlaySession $session)
    {
        if ($session->ended_at) {
            return redirect()->route('cashier.sessions.index')
                ->with('error', 'Cannot add products to a completed session');
        }
        
        // Get all products with stock
        $products = \App\Models\Product::where('stock_qty', '>', 0)
                                      ->where('active', true)
                                      ->get();
        
        // Get existing pending sales for this session
        $pendingSales = \App\Models\Sale::where('play_session_id', $session->id)
            ->where('status', 'pending')
            ->with('items.product')
            ->get();
        
        return view('cashier.sessions.add-products', compact('session', 'products', 'pendingSales'));
    }
    
    /**
     * Store products as a pending sale for a session.
     */
    public function storeProducts(Request $request, PlaySession $session)
    {
        if ($session->ended_at) {
            return redirect()->route('cashier.sessions.index')
                ->with('error', 'Cannot add products to a completed session');
        }
        
        // Validate request
        $request->validate([
            'products' => 'required',
        ]);
        
        // Decode the products from JSON string or use array directly
        if (is_string($request->products)) {
        $productsData = json_decode($request->products, true);
        } else {
            $productsData = $request->products;
        }
        
        if (empty($productsData)) {
            return redirect()->back()->with('error', 'No products selected.');
        }
        
        // Normalize the products data structure
        $normalizedProducts = [];
        
        // Handle different possible data structures
        foreach ($productsData as $key => $productData) {
            if (is_array($productData)) {
                // Structure 1: Array with 'id', 'quantity' keys (from JavaScript)
                if (isset($productData['id']) && isset($productData['quantity'])) {
                    if ((int)$productData['quantity'] > 0) {
                        $normalizedProducts[] = [
                            'id' => (int)$productData['id'],
                            'quantity' => (int)$productData['quantity']
                        ];
                    }
                }
                // Structure 2: Nested array like products[product_id][qty] (from form)
                elseif (isset($productData['qty'])) {
                    if ((int)$productData['qty'] > 0) {
                        $normalizedProducts[] = [
                            'id' => (int)$key, // key is the product ID
                            'quantity' => (int)$productData['qty']
                        ];
                    }
                }
            }
            // Structure 3: Direct quantity value (products[product_id] = quantity)
            elseif (is_numeric($productData) && (int)$productData > 0) {
                $normalizedProducts[] = [
                    'id' => (int)$key, // key is the product ID
                    'quantity' => (int)$productData
                ];
            }
        }
        
        if (empty($normalizedProducts)) {
            return redirect()->back()->with('error', 'No valid products selected.');
        }
        
        // Find active shift
        $shift = \App\Models\Shift::where('cashier_id', Auth::id())
            ->whereNull('closed_at')
            ->first();
        
        if (!$shift) {
            // Create a new shift for the cashier if none exists
            $shift = \App\Models\Shift::create([
                'cashier_id' => Auth::id(),
                'date' => now()->toDateString(),
                'type' => (now()->hour < 12) ? 'morning' : (now()->hour < 18 ? 'afternoon' : 'evening'),
                'opening_amount' => 0.00,
                'opened_at' => now(),
            ]);
        }
        
        // Validate all products exist and calculate total amount
        $totalAmount = 0;
        $validatedProducts = [];
        
        foreach ($normalizedProducts as $productData) {
            $product = \App\Models\Product::find($productData['id']);
            
            if (!$product) {
                return redirect()->back()->with('error', "Product with ID {$productData['id']} not found.");
            }
            
            if (!$product->active) {
                return redirect()->back()->with('error', "Product '{$product->name}' is not currently available.");
            }
            
            if ($product->stock_qty < $productData['quantity']) {
                return redirect()->back()->with('error', "Not enough stock available for '{$product->name}'. Available: {$product->stock_qty}, Requested: {$productData['quantity']}");
            }
            
            $validatedProducts[] = [
                'product' => $product,
                'quantity' => $productData['quantity'],
                'subtotal' => $product->price * $productData['quantity']
            ];
            
            $totalAmount += $product->price * $productData['quantity'];
        }
        
        // Create the sale with pending status
        $sale = new \App\Models\Sale();
        $sale->shift_id = $shift->id;
        $sale->user_id = Auth::id();
        $sale->total_amount = round($totalAmount, 2);
        $sale->amount_paid = 0; // Will be paid when session ends
        $sale->payment_method = 'pending'; // Will be set when session ends
        $sale->child_id = $session->child_id;
        $sale->play_session_id = $session->id;
        $sale->status = 'pending';
        $sale->save();
        
        // Process each validated product
        foreach ($validatedProducts as $validatedProduct) {
            $product = $validatedProduct['product'];
            $quantity = $validatedProduct['quantity'];
            $subtotal = $validatedProduct['subtotal'];
            
            // Create the sale item
            $saleItem = new \App\Models\SaleItem();
            $saleItem->sale_id = $sale->id;
            $saleItem->product_id = $product->id;
            $saleItem->quantity = $quantity;
            $saleItem->unit_price = $product->price;
            $saleItem->subtotal = $subtotal;
            $saleItem->description = $product->name; // Add description
            $saleItem->save();
            
            // Update the stock quantity
            $product->update([
                'stock_qty' => $product->stock_qty - $quantity
            ]);
        }
        
        return redirect()->route('cashier.sessions.show', $session->id)
            ->with('success', 'Products added to session successfully');
    }

    /**
     * Remove the specified play session.
     */
    public function destroy(PlaySession $session)
    {
        // Check if this session has associated sales
        $hasSales = \App\Models\Sale::where('play_session_id', $session->id)->exists();
        
        if ($hasSales) {
            return redirect()->route('cashier.sessions.show', $session->id)
                ->with('error', 'Cannot delete this session because it has associated sales records.');
        }
        
        // Check if there are add-ons
        if ($session->addOns()->count() > 0) {
            // Detach all add-ons first
            $session->addOns()->detach();
        }
        
        // Check for pending sales
        $pendingSales = \App\Models\Sale::where('play_session_id', $session->id)
            ->where('status', 'pending')
            ->get();
            
        foreach ($pendingSales as $sale) {
            // For each sale item, restore product stock
            foreach ($sale->items as $item) {
                $item->product->increment('stock_qty', $item->quantity);
            }
            
            // Delete the sale items
            $sale->items()->delete();
            
            // Delete the sale
            $sale->delete();
        }
        
        // Delete the play session
        $session->delete();
        
        return redirect()->route('cashier.sessions.index')
            ->with('success', 'Play session deleted successfully. You can now create a new one with the correct information.');
    }

    /**
     * Show the form for bulk closing old sessions.
     */
    public function showCloseOldSessions()
    {
        // Get all unclosed sessions that are older than 24 hours
        $oldSessions = PlaySession::with(['child', 'user'])
            ->whereNull('ended_at')
            ->where('started_at', '<', now()->subHours(24))
            ->orderBy('started_at')
            ->get();

        return view('cashier.sessions.close-old', compact('oldSessions'));
    }

    /**
     * Bulk close multiple sessions.
     */
    public function bulkCloseSessions(Request $request)
    {
        $request->validate([
            'sessions' => 'required|array',
            'sessions.*' => 'exists:play_sessions,id',
            'payment_method' => ['required', Rule::in(config('play.payment_methods', []))],
        ]);

        $successCount = 0;
        $errorCount = 0;
        $hourlyRate = config('play.hourly_rate', 10.00);

        foreach ($request->sessions as $sessionId) {
            $session = PlaySession::find($sessionId);
            
            if (!$session || $session->ended_at) {
                $errorCount++;
                continue;
            }

            try {
                DB::beginTransaction();

                // Calculate actual hours
                $startTime = Carbon::parse($session->started_at);
                $endTime = $session->started_at->addHours($session->planned_hours ?? 1);
                $actualHours = max(0, $endTime->diffInMinutes($startTime) / 60);

                // Cap at planned hours if specified
                if ($session->planned_hours && $actualHours > $session->planned_hours) {
                    $actualHours = $session->planned_hours;
                }

                // Calculate total cost in USD first
                $totalCostUSD = $actualHours * $hourlyRate;
                if ($session->discount_pct > 0) {
                    $totalCostUSD = $totalCostUSD * ((100 - $session->discount_pct) / 100);
                }

                // Convert to payment currency for storage consistency
                $paymentMethod = $request->payment_method;
                if ($paymentMethod === 'LBP') {
                    $lbpRate = config('play.lbp_exchange_rate', 90000);
                    $totalAmountToStore = round($totalCostUSD * $lbpRate, 0);
                    $amountPaidToStore = $totalAmountToStore; // Same for auto-close
                } else {
                    $totalAmountToStore = round($totalCostUSD, 2);
                    $amountPaidToStore = $totalAmountToStore; // Same for auto-close
                }

                // Add note about automatic closure
                $note = "Session automatically closed due to being inactive. Original duration: " . 
                        number_format($actualHours, 2) . " hours.";
                if ($session->notes) {
                    $session->notes .= "\n\n" . $note;
                } else {
                    $session->notes = $note;
                }

                // Update session - store in payment currency
                $session->update([
                    'ended_at' => $endTime,
                    'actual_hours' => $actualHours,
                    'amount_paid' => $amountPaidToStore,
                    'payment_method' => $paymentMethod,
                    'total_cost' => $totalAmountToStore,
                ]);

                // Get the current cashier's active shift
                $currentCashierShift = Shift::where('cashier_id', Auth::id())
                    ->whereNull('closed_at')
                    ->first();

                if (!$currentCashierShift) {
                    $currentCashierShift = Shift::create([
                        'cashier_id' => Auth::id(),
                        'date' => now()->toDateString(),
                        'type' => (now()->hour < 12) ? 'morning' : (now()->hour < 18 ? 'afternoon' : 'evening'),
                        'opening_amount' => 0.00,
                        'opened_at' => now(),
                    ]);
                }

                // Create sale record - store same amounts as session
                \App\Models\Sale::create([
                    'shift_id' => $currentCashierShift->id,
                    'user_id' => Auth::id(),
                    'total_amount' => $totalAmountToStore, // Same as session.total_cost
                    'amount_paid' => $amountPaidToStore,   // Same as session.amount_paid
                    'payment_method' => $paymentMethod,
                    'currency' => $paymentMethod,
                    'child_id' => $session->child_id,
                    'play_session_id' => $session->id,
                    'status' => 'completed',
                    'notes' => 'Automatically closed session'
                ]);

                DB::commit();
                $successCount++;
            } catch (\Exception $e) {
                DB::rollBack();
                $errorCount++;
            }
        }

        $message = "Successfully closed {$successCount} sessions.";
        if ($errorCount > 0) {
            $message .= " Failed to close {$errorCount} sessions.";
        }

        return redirect()->route('cashier.sessions.index')
            ->with('success', $message);
    }

    /**
     * Create sale items for a session to display properly on receipt.
     * CRITICAL: This method uses the cashier's custom total amount, not calculated amounts.
     */
    private function createSaleItemsForSession(Sale $sale, PlaySession $session)
    {
        // Delete existing sale items for this sale to avoid duplicates
        \App\Models\SaleItem::where('sale_id', $sale->id)->delete();
        
        // Calculate session duration
        $startTime = $session->started_at;
        $endTime = $session->ended_at ?? now();
        $durationInHours = round($startTime->diffInMinutes($endTime) / 60, 2);
        
        // IMPORTANT: Use the cashier's custom total amount (sale.total_amount)
        // This is the amount the cashier entered, not any server calculation
        $cashierCustomAmount = $sale->total_amount;
        $customUnitPrice = $durationInHours > 0 ? round($cashierCustomAmount / $durationInHours, 2) : 0;
        
        // Create sale item for session time using ONLY the cashier's custom amount
        \App\Models\SaleItem::create([
            'sale_id' => $sale->id,
            'description' => 'Play session time (' . $durationInHours . ' hours)',
            'quantity' => $durationInHours,
            'unit_price' => $customUnitPrice,
            'subtotal' => $cashierCustomAmount, // CASHIER'S CUSTOM AMOUNT - NOT CALCULATED
            'product_id' => null,
            'add_on_id' => null
        ]);
    }


} 