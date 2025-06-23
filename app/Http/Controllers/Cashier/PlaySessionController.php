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
        $loyaltyInfo = null;
        if ($child) {
            // Count ALL paid sessions (both completed and incomplete with discount < 100%)
            $paidSessionsCount = PlaySession::where('child_id', $child->id)
                ->where('discount_pct', '<', 100) // Exclude free sessions
                ->count();
            
            // Check if there's already a free session that was started but not completed
            $pendingFreeSession = PlaySession::where('child_id', $child->id)
                ->whereNull('ended_at') // Not completed
                ->where('discount_pct', '=', 100) // Free session
                ->exists();
            
            // Also get total sessions for debugging
            $totalSessionsCount = PlaySession::where('child_id', $child->id)->count();
            $completedFreeSessionsCount = PlaySession::where('child_id', $child->id)
                ->whereNotNull('ended_at')
                ->where('discount_pct', '=', 100)
                ->count();
            $incompleteSessionsCount = PlaySession::where('child_id', $child->id)
                ->whereNull('ended_at')
                ->count();
            
            // Every 6th session is free (after 5, 10, 15, etc. paid sessions)
            // BUT only if there's no pending free session already
            $isFreeSession = ($paidSessionsCount > 0) && ($paidSessionsCount % 5 === 0) && !$pendingFreeSession;
            
            // Calculate loyalty information for display
            $remainingForFree = $paidSessionsCount > 0 ? (5 - ($paidSessionsCount % 5)) : 5;
            if ($remainingForFree == 5 && $paidSessionsCount > 0) {
                $remainingForFree = 0; // They're at a free session milestone
            }
            
            $loyaltyInfo = [
                'paid_sessions' => $paidSessionsCount,
                'remaining_for_free' => $remainingForFree,
                'next_free_at' => $paidSessionsCount + $remainingForFree,
                'has_pending_free' => $pendingFreeSession
            ];

            // Optional: Log loyalty program decision for debugging (can be removed in production)
            if (config('app.debug')) {
                \Log::info('Loyalty Program Check', [
                    'child_id' => $child->id,
                    'child_name' => $child->name,
                    'total_sessions' => $totalSessionsCount,
                    'paid_sessions_count' => $paidSessionsCount,
                    'completed_free_sessions_count' => $completedFreeSessionsCount,
                    'incomplete_sessions_count' => $incompleteSessionsCount,
                    'pending_free_session' => $pendingFreeSession,
                    'is_free_session' => $isFreeSession,
                    'modulo_result' => $paidSessionsCount % 5,
                    'expected_next_session' => $paidSessionsCount + 1,
                    'loyalty_info' => $loyaltyInfo
                ]);
            }
        }
        
        return view('cashier.sessions.start', compact('child', 'children', 'activeShift', 'hourlyRate', 'isFreeSession', 'loyaltyInfo'));
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
            ->where('discount_pct', '<', 100) // Exclude free sessions
            ->count();
        
        // Check if there's already a free session that was started but not completed
        $pendingFreeSession = PlaySession::where('child_id', $child->id)
            ->whereNull('ended_at') // Not completed
            ->where('discount_pct', '=', 100) // Free session
            ->exists();
        
        // Every 6th session is free (after 5, 10, 15, etc. paid sessions)
        // BUT only if there's no pending free session already
        $isFreeSession = ($paidSessionsCount > 0) && ($paidSessionsCount % 5 === 0) && !$pendingFreeSession;
        
        // Calculate loyalty information for display
        $remainingForFree = $paidSessionsCount > 0 ? (5 - ($paidSessionsCount % 5)) : 5;
        if ($remainingForFree == 5 && $paidSessionsCount > 0) {
            $remainingForFree = 0; // They're at a free session milestone
        }
        
        $loyaltyInfo = [
            'paid_sessions' => $paidSessionsCount,
            'remaining_for_free' => $remainingForFree,
            'next_free_at' => $paidSessionsCount + $remainingForFree,
            'has_pending_free' => $pendingFreeSession
        ];

        // Optional: Log loyalty program decision for debugging (can be removed in production)
        if (config('app.debug')) {
            \Log::info('Loyalty Program Check (start method)', [
                'child_id' => $child->id,
                'child_name' => $child->name,
                'paid_sessions_count' => $paidSessionsCount,
                'pending_free_session' => $pendingFreeSession,
                'is_free_session' => $isFreeSession,
                'modulo_result' => $paidSessionsCount % 5,
                'expected_next_session' => $paidSessionsCount + 1,
                'loyalty_info' => $loyaltyInfo
            ]);
        }
        
        return view('cashier.sessions.start', compact('child', 'children', 'activeShift', 'hourlyRate', 'isFreeSession', 'loyaltyInfo'));
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
            ->where('discount_pct', '<', 100) // Exclude free sessions
            ->count();
        
        // Check if there's already a free session that was started but not completed
        $pendingFreeSession = PlaySession::where('child_id', $childId)
            ->whereNull('ended_at') // Not completed
            ->where('discount_pct', '=', 100) // Free session
            ->exists();
        
        // Every 6th session is free (after 5, 10, 15, etc. paid sessions)
        // BUT only if there's no pending free session already
        $isFreeSession = ($paidSessionsCount > 0) && ($paidSessionsCount % 5 === 0) && !$pendingFreeSession;
        
        // Optional: Log loyalty program decision for debugging (can be removed in production)
        if (config('app.debug')) {
            \Log::info('Loyalty Program Check (store method)', [
                'child_id' => $childId,
                'paid_sessions_count' => $paidSessionsCount,
                'pending_free_session' => $pendingFreeSession,
                'is_free_session' => $isFreeSession,
                'modulo_result' => $paidSessionsCount % 5,
                'expected_next_session' => $paidSessionsCount + 1
            ]);
        }
        
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
            'custom_total' => 'nullable|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
        ]);
    
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
            ->with('items.product')
            ->get();
            
        $pendingSalesTotal = $pendingSales->sum('total_amount');
        
        // Calculate the standard total cost as before (for reference only)
        $calculatedTotalCost = round($timeCost + $addOnsTotal + $pendingSalesTotal, 2);
    
        // Use the custom price instead of the calculated cost
        $paymentMethod = $request->payment_method;
        $lbpRate = config('play.lbp_exchange_rate', 90000);
    
        // Store amounts in the original currency to preserve cashier's exact input
        if ($paymentMethod === 'LBP') {
            // For LBP payments, store the LBP amounts directly (don't convert to USD)
            $totalAmountToStore = round($request->custom_total, 0); // LBP amounts (no decimals)
            $amountPaidToStore = round($request->amount_paid, 0); // LBP amounts (no decimals)
            
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
            // For USD payments, store USD amounts
            $totalAmountToStore = round($request->custom_total, 2);
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
    
        // Update play session (store amounts in original currency for LBP, USD equivalent for USD)
        if ($paymentMethod === 'LBP') {
            // For LBP, we still store USD equivalent in the session for backward compatibility
            $sessionAmountPaid = round($amountPaidToStore / $lbpRate, 2);
            $sessionTotalCost = round($totalAmountToStore / $lbpRate, 2);
        } else {
            $sessionAmountPaid = $amountPaidToStore;
            $sessionTotalCost = $totalAmountToStore;
        }
        
        $session->update([
            'ended_at' => $endTime,
            'actual_hours' => $actualHours,
            'amount_paid' => $sessionAmountPaid,
            'payment_method' => $paymentMethod,
            'total_cost' => $sessionTotalCost,
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
                'type' => (now()->hour < 12) ? 'morning' : (now()->hour < 18 ? 'afternoon' : 'evening'),
                'opening_amount' => 0.00,
                'opened_at' => now(),
            ]);
        }
        
        // Create sale record
        // Important: The sale is associated with the current cashier's shift,
        // but the play session remains with its original shift
        $sale = \App\Models\Sale::create([
            'shift_id' => $currentCashierShift->id, // Associate with current cashier's shift
            'user_id' => Auth::id(), // Current user
            'total_amount' => $totalAmountToStore, // Store in original currency (LBP or USD)
            'amount_paid' => $amountPaidToStore, // Store in original currency (LBP or USD)
            'payment_method' => $paymentMethod,
            'currency' => $paymentMethod === 'LBP' ? 'LBP' : 'USD', // Set correct currency
            'child_id' => $session->child_id,
            'play_session_id' => $session->id,
            'status' => 'completed'
        ]);
        
        // Create sale items for session time and add-ons
        // For LBP sales, we need to convert the time cost to LBP for the sale item
        if ($timeCost > 0) {
            $itemTimeCost = $paymentMethod === 'LBP' ? round($timeCost * $lbpRate, 0) : $timeCost;
            
            \App\Models\SaleItem::create([
                'sale_id' => $sale->id,
                'product_id' => null, // No product for session time
                'quantity' => 1,
                'unit_price' => $itemTimeCost,
                'subtotal' => $itemTimeCost,
                'description' => 'Play session (' . number_format($actualHours, 2) . ' hours)' . 
                               ($session->discount_pct > 0 ? ' - ' . $session->discount_pct . '% discount applied' : '')
            ]);
        }
        
        // Add-ons items
        foreach ($session->addOns as $addon) {
            $itemPrice = $paymentMethod === 'LBP' ? round($addon->price * $lbpRate, 0) : $addon->price;
            $itemSubtotal = $paymentMethod === 'LBP' ? round($addon->pivot->subtotal * $lbpRate, 0) : $addon->pivot->subtotal;
            
            \App\Models\SaleItem::create([
                'sale_id' => $sale->id,
                'product_id' => null, // No product for add-ons
                'quantity' => $addon->pivot->qty,
                'unit_price' => $itemPrice,
                'subtotal' => $itemSubtotal,
                'description' => $addon->name . ' (add-on)'
            ]);
        }
        
        // Transfer pending product sales to main sale
        foreach($pendingSales as $pendingSale) {
            // Copy each product item from pending sale to main sale
            foreach($pendingSale->items as $item) {
                // Convert product prices to the payment currency
                $productPrice = $paymentMethod === 'LBP' ? round($item->unit_price * $lbpRate, 0) : $item->unit_price;
                $productSubtotal = $paymentMethod === 'LBP' ? round($item->subtotal * $lbpRate, 0) : $item->subtotal;
                
                \App\Models\SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'unit_price' => $productPrice,
                    'subtotal' => $productSubtotal,
                    'description' => $item->product->name
                ]);
            }
            
            // Mark pending sale as completed and link to main sale
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
                        'type' => (now()->hour < 12) ? 'morning' : (now()->hour < 18 ? 'afternoon' : 'evening'),
                        'opening_amount' => 0.00,
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