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

class PlaySessionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $activeSessions = PlaySession::whereNull('ended_at')->latest()->get();
        
        // Get filter parameter from request
        $filter = $request->input('filter', 'today');
        
        // Base query for recent sessions
        $recentSessionsQuery = PlaySession::whereNotNull('ended_at')->latest();
        
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
        
        $recentSessions = $recentSessionsQuery->paginate(10);
        
        return view('cashier.sessions.index', compact('activeSessions', 'recentSessions'));
    }

    /**
     * Show the form for starting a new session.
     * If child_id is provided in the query string, it will be pre-selected.
     */
    public function create(Request $request, Child $child = null)
    {
        // If no specific child was provided via route model binding 
        // but a child_id was provided in the query string
        if (!$child && $request->has('child_id')) {
            $child = Child::find($request->child_id);
        }
        
        // If we still don't have a child, show all children to select from
        $children = Child::orderBy('name')->get();
        
        // Add play session count for each child
        foreach ($children as $childOption) {
            $childOption->play_sessions_count = PlaySession::where('child_id', $childOption->id)->count();
        }
        
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
        
        // Check if this would be a free session (for a specific child)
        $isFreeSession = false;
        if ($child) {
            // Count completed paid sessions for this child
            $paidSessionsCount = PlaySession::where('child_id', $child->id)
                ->whereNotNull('ended_at')
                ->where('discount_pct', '<', 100) // Exclude free sessions
                ->count();
            
            // Every 6th session is free (after 5, 10, 15, etc. paid sessions)
            $isFreeSession = ($paidSessionsCount > 0) && ($paidSessionsCount % 5 === 0);
        }
        
        return view('cashier.sessions.start', compact('child', 'children', 'activeShift', 'hourlyRate', 'isFreeSession'));
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
        
        // Check if this would be a free session
        $paidSessionsCount = PlaySession::where('child_id', $child->id)
            ->whereNotNull('ended_at')
            ->where('discount_pct', '<', 100) // Exclude free sessions
            ->count();
        
        // Every 6th session is free (after 5, 10, 15, etc. paid sessions)
        $isFreeSession = ($paidSessionsCount > 0) && ($paidSessionsCount % 5 === 0);
        
        return view('cashier.sessions.start', compact('child', 'children', 'activeShift', 'hourlyRate', 'isFreeSession'));
    }

    /**
     * Store a newly created session in storage.
     */
    public function store(PlaySessionRequest $request)
    {
        $validated = $request->validated();
        
        // Check if this should be a free session
        $childId = $validated['child_id'];
        $paidSessionsCount = PlaySession::where('child_id', $childId)
            ->whereNotNull('ended_at')
            ->where('discount_pct', '<', 100) // Exclude free sessions
            ->count();
        
        // Every 6th session is free (after 5, 10, 15, etc. paid sessions)
        $isFreeSession = ($paidSessionsCount > 0) && ($paidSessionsCount % 5 === 0);
        
        // If this is a free session, override planned_hours and discount_pct
        if ($isFreeSession) {
            $validated['planned_hours'] = 1;
            $validated['discount_pct'] = 100;
        }
        
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
        
        $successMessage = 'Play session started successfully';
        if ($isFreeSession) {
            $successMessage .= ' (Free loyalty session applied: 1 hour free)';
        }
        
        return redirect()->route('cashier.sessions.index')
            ->with('success', $successMessage);
    }
    
    /**
     * Display the specified session.
     *
     * @param  \App\Models\PlaySession  $session
     * @return \Illuminate\View\View
     */
    public function show(PlaySession $session)
    {
        // Load relationships
        $session->load(['child', 'user', 'shift', 'addOns']);
        
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
        
        // Check if there's a related sale
        $sale = \App\Models\Sale::where('play_session_id', $session->id)->first();
        
        return view('cashier.sessions.show', compact('session', 'duration', 'progress', 'sale', 'playSessionsCount'));
    }

    /**
     * Display the end session form.
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
        
        // Cap the billable hours at the planned hours if specified
        $actualDurationForBilling = $durationInHours;
        $cappedHours = false;
        if ($session->planned_hours > 0 && $durationInHours > $session->planned_hours) {
            $actualDurationForBilling = $session->planned_hours;
            $cappedHours = true;
        }

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
        $addonsTotal = $sessionAddOns->sum('pivot.subtotal');
        
        // Apply discount only to time cost, not to add-ons (same as end method)
        $discountPct = $session->discount_pct ?? 0;
        $discountMultiplier = (100 - $discountPct) / 100;
        $timeCost = round($rawTimeCost * $discountMultiplier, 2);
        
        // Discount amount is the difference between raw time cost and discounted time cost
        $discountAmount = $rawTimeCost - $timeCost;
        
        // Get pending product sales
        $pendingSales = \App\Models\Sale::where('play_session_id', $session->id)
            ->where('status', 'pending')
            ->with('items.product')
            ->latest()
            ->get();
            
        $pendingSalesTotal = $pendingSales->sum('total_amount');
        
        // Subtotal for display (raw time cost + add-ons)
        $subtotal = $rawTimeCost + $addonsTotal + $pendingSalesTotal;
        
        // Total amount is discounted time cost plus add-ons plus pending sales
        $totalAmount = round($timeCost + $addonsTotal + $pendingSalesTotal, 2);
        
        $paymentMethods = config('play.payment_methods', []);
        
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
            'rawTimeCost',
            'timeCost',
            'subtotal',
            'discountAmount',
            'totalAmount',
            'pendingSales',
            'pendingSalesTotal'
        ));
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
    
        // Validate payment method
        $paymentMethods = config('play.payment_methods', []);
        $request->validate([
            'payment_method' => ['required', Rule::in($paymentMethods)],
            'amount_paid' => 'required|numeric|min:0',
            'custom_total' => 'required|numeric|min:0', // Validate the new custom price field
            'total_amount' => 'required|numeric|min:0',
        ]);
    
        // Update add-ons if provided
        if ($request->has('add_ons')) {
            $session->addOns()->detach();
    
            foreach ($request->add_ons as $addOnId => $data) {
                if (isset($data['qty']) && $data['qty'] > 0) {
                    $addOn = AddOn::find($addOnId);
                    $subtotal = $addOn->price * (float)$data['qty'];
    
                    $session->addOns()->attach($addOnId, [
                        'qty' => (float)$data['qty'],
                        'subtotal' => $subtotal
                    ]);
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
            
            // Cap at planned hours if needed
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
                $actualHours = number_format($durationHours, 2);
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
            ->get();
            
        $pendingSalesTotal = $pendingSales->sum('total_amount');
        
        // Calculate the standard total cost as before (for reference only)
        $calculatedTotalCost = round($timeCost + $addOnsTotal + $pendingSalesTotal, 2);
    
        // Use the custom price instead of the calculated cost
        $paymentMethod = $request->payment_method;
        $lbpRate = config('play.lbp_exchange_rate', 90000);
    
        if ($paymentMethod === 'LBP') {
            // Convert the custom price from LBP to USD for storage
            $totalAmountUsd = round($request->custom_total / $lbpRate, 2);
            $amountPaidUsd = round($request->amount_paid / $lbpRate, 2);
            
            // Add a note if the manual price differs from the calculated price
            $calculatedAmountLbp = round($calculatedTotalCost * $lbpRate);
            if (abs($request->custom_total - $calculatedAmountLbp) > 0.01 * $calculatedAmountLbp) {
                $customPriceNote = "Note: Manual price set by cashier: " . number_format($request->custom_total) . " LBP. ";
                $customPriceNote .= "Standard calculated price would have been: " . number_format($calculatedAmountLbp) . " LBP.";
                
                if ($session->notes) {
                    $session->notes .= "\n\n" . $customPriceNote;
                } else {
                    $session->notes = $customPriceNote;
                }
            }
        } else {
            // For USD payments
            $totalAmountUsd = round($request->custom_total, 2);
            $amountPaidUsd = round($request->amount_paid, 2);
            
            // Add a note if the manual price differs from the calculated price
            if (abs($totalAmountUsd - $calculatedTotalCost) > 0.01) {
                $customPriceNote = "Note: Manual price set by cashier: $" . number_format($totalAmountUsd, 2) . ". ";
                $customPriceNote .= "Standard calculated price would have been: $" . number_format($calculatedTotalCost, 2) . ".";
                
                if ($session->notes) {
                    $session->notes .= "\n\n" . $customPriceNote;
                } else {
                    $session->notes = $customPriceNote;
                }
            }
        }
    
        // Update play session
        $session->update([
            'ended_at' => $endTime,
            'actual_hours' => $actualHours,
            'amount_paid' => $amountPaidUsd,
            'payment_method' => $paymentMethod,
            'total_cost' => $totalAmountUsd, // Use the custom price here
            'notes' => $session->notes // Updated with custom price notes
        ]);
    
        // Clean up the session data
        $request->session()->forget($sessionKey);
    
        // Get the current cashier's active shift
        $currentCashierShift = Shift::where('cashier_id', Auth::id())->whereNull('closed_at')->first();
        
        // If there's no active shift for the current cashier, create one
        if (!$currentCashierShift) {
            $currentCashierShift = Shift::create([
                'cashier_id' => Auth::id(),
                'date' => now()->toDateString(),
                'type' => (now()->hour < 12) ? 'morning' : 'afternoon',
                'opened_at' => now(),
            ]);
        }
        
        // Create sale record
        // Important: The sale is associated with the current cashier's shift,
        // but the play session remains with its original shift
        $sale = \App\Models\Sale::create([
            'shift_id' => $currentCashierShift->id, // Associate with current cashier's shift
            'user_id' => Auth::id(), // Current user
            'total_amount' => $totalAmountUsd, // Using the custom price set by cashier
            'amount_paid' => $amountPaidUsd,
            'payment_method' => $paymentMethod,
            'child_id' => $session->child_id,
            'play_session_id' => $session->id,
            'status' => 'completed'
        ]);
        
        // Update pending sales status to completed and link to main sale
        foreach($pendingSales as $pendingSale) {
            $pendingSale->update([
                'status' => 'completed',
                'payment_method' => $paymentMethod,
                'parent_sale_id' => $sale->id
            ]);
        }
    
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
                if ($addOn) {
                    // Check if we received a qty in the data or if it's just an ID
                    if (is_array($data) && isset($data['qty'])) {
                        $qty = (float)$data['qty'];
                        $subtotal = $addOn->price * $qty;
                    } else {
                        $qty = 1; // Default quantity
                        $subtotal = $addOn->price;
                    }
                    
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
        
        return redirect()->route('cashier.sessions.show', $session)
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
            ->latest()
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
            'products' => 'required|json',
        ]);
        
        // Decode the products from the JSON string
        $productsData = json_decode($request->products, true);
        
        if (empty($productsData)) {
            return redirect()->back()->with('error', 'No products selected.');
        }
        
        // Find active shift
        $shift = \App\Models\Shift::where('cashier_id', Auth::id())
            ->whereNull('closed_at')
            ->first();
        
        if (!$shift) {
            // Create a new shift for the cashier if none exists
            $shift = \App\Models\Shift::create([
                'cashier_id' => Auth::id(),
                'opened_at' => now(),
            ]);
        }
        
        // Calculate total amount
        $totalAmount = 0;
        foreach ($productsData as $productData) {
            $product = \App\Models\Product::find($productData['id']);
            if ($product) {
                $totalAmount += $product->price * $productData['quantity'];
            }
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
        
        // Process each product
        foreach ($productsData as $productData) {
            $product = \App\Models\Product::find($productData['id']);
            
            if (!$product || $product->stock_qty < $productData['quantity']) {
                // If we don't have enough stock, delete the sale and redirect back
                $sale->delete();
                return redirect()->back()->with('error', "Not enough stock available for {$product->name}");
            }
            
            // Create the sale item
            $saleItem = new \App\Models\SaleItem();
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
        
        return redirect()->route('cashier.sessions.show', $session)
            ->with('success', 'Products added to the session successfully. They will be included in the final bill.');
    }

    /**
     * Remove the specified play session.
     */
    public function destroy(PlaySession $session)
    {
        // Check if this session has associated sales
        $hasSales = \App\Models\Sale::where('play_session_id', $session->id)->exists();
        
        if ($hasSales) {
            return redirect()->route('cashier.sessions.show', $session)
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

                // Calculate total cost
                $totalCost = $actualHours * $hourlyRate;
                if ($session->discount_pct > 0) {
                    $totalCost = $totalCost * ((100 - $session->discount_pct) / 100);
                }

                // Add note about automatic closure
                $note = "Session automatically closed due to being inactive. Original duration: " . 
                        number_format($actualHours, 2) . " hours.";
                if ($session->notes) {
                    $session->notes .= "\n\n" . $note;
                } else {
                    $session->notes = $note;
                }

                // Update session
                $session->update([
                    'ended_at' => $endTime,
                    'actual_hours' => $actualHours,
                    'amount_paid' => $totalCost,
                    'payment_method' => $request->payment_method,
                    'total_cost' => $totalCost,
                ]);

                // Get the current cashier's active shift
                $currentCashierShift = Shift::where('cashier_id', Auth::id())
                    ->whereNull('closed_at')
                    ->first();

                if (!$currentCashierShift) {
                    $currentCashierShift = Shift::create([
                        'cashier_id' => Auth::id(),
                        'date' => now()->toDateString(),
                        'type' => (now()->hour < 12) ? 'morning' : 'afternoon',
                        'opened_at' => now(),
                    ]);
                }

                // Create sale record
                \App\Models\Sale::create([
                    'shift_id' => $currentCashierShift->id,
                    'user_id' => Auth::id(),
                    'total_amount' => $totalCost,
                    'amount_paid' => $totalCost,
                    'payment_method' => $request->payment_method,
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
} 