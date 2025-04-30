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
        // Load relationships
        $session->load(['child', 'user', 'shift', 'addOns']);
        
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
        
        return view('cashier.sessions.show', compact('session', 'duration', 'progress', 'sale'));
    }

    /**
     * Display the end session form.
     */
    public function showEnd(PlaySession $session)
    {
        if ($session->ended_at) {
            return redirect()->route('cashier.sessions.index')
                ->with('error', 'This session has already ended');
        }
        
        // Calculate initial duration
        $startTime = $session->started_at;
        $now = now();
        
        // Calculate the time difference ensuring proper direction
        if ($now->lt($startTime)) {
            // If current time is less than start time (shouldn't happen normally)
            return redirect()->route('cashier.sessions.index')
                ->with('error', 'Invalid session start time');
        }
        
        $durationInSeconds = $now->getTimestamp() - $startTime->getTimestamp();
        $hours = floor($durationInSeconds / 3600);
        $minutes = floor(($durationInSeconds % 3600) / 60);
        $seconds = $durationInSeconds % 60;
        $initialDuration = "{$hours}h {$minutes}m {$seconds}s";
        
        // Calculate duration in hours for billing (rounded to 2 decimals)
        $durationInHours = round($durationInSeconds / 3600, 2);
        
        // Get hourly rate from config
        $hourlyRate = config('play.hourly_rate', 10.00);
        
        // Calculate time cost before discount
        $rawTimeCost = round($durationInHours * $hourlyRate, 2);
        
        // Get add-ons and their total
        $addOns = AddOn::all();
        $sessionAddOns = $session->addOns()->get();
        $addonsTotal = $sessionAddOns->sum('pivot.subtotal');
        
        // Apply discount only to time cost, not to add-ons (same as end method)
        $discountPct = $session->discount_pct ?? 0;
        $discountMultiplier = (100 - $discountPct) / 100;
        $timeCost = round($rawTimeCost * $discountMultiplier, 2);
        
        // Discount amount is the difference between raw time cost and discounted time cost
        $discountAmount = $rawTimeCost - $timeCost;
        
        // Subtotal for display (raw time cost + add-ons)
        $subtotal = $rawTimeCost + $addonsTotal;
        
        // Total amount is discounted time cost plus add-ons
        $totalAmount = round($timeCost + $addonsTotal, 2);
        
        $paymentMethods = config('play.payment_methods', []);
        
        return view('cashier.sessions.end', compact(
            'session',
            'addOns',
            'sessionAddOns',
            'paymentMethods',
            'addonsTotal',
            'durationInHours',
            'initialDuration',
            'rawTimeCost',
            'timeCost',
            'subtotal',
            'discountAmount',
            'totalAmount'
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
            'total_amount' => 'required|numeric|min:0',
        ]);
    
        // Update add-ons if provided
        if ($request->has('add_ons')) {
            $session->addOns()->detach();
    
            foreach ($request->add_ons as $addOnId => $data) {
                if (isset($data['qty']) && $data['qty'] > 0) {
                    $addOn = AddOn::find($addOnId);
                    $subtotal = $addOn->price * $data['qty'];
    
                    $session->addOns()->attach($addOnId, [
                        'qty' => $data['qty'],
                        'subtotal' => $subtotal
                    ]);
                }
            }
        }
    
        // Calculate actual_hours
        $actualHours = $request->actual_hours;
    
        if (!$actualHours) {
            $startTime = Carbon::parse($session->started_at);
            $endTime = Carbon::now();
            $durationHours = max(0, $endTime->diffInMinutes($startTime) / 60);
            // Don't round up to hour increments, keep actual minutes (in decimal hours)
            $actualHours = number_format($durationHours, 2);
        } else {
            $actualHours = abs((float)$actualHours);
        }
    
        $endTime = $request->ended_at ? Carbon::parse($request->ended_at) : Carbon::now();
    
        // Calculate costs
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
        
        // Total cost is discounted time cost plus full add-ons cost
        $totalCost = round($timeCost + $addOnsTotal, 2);
    
        // Handle currency conversion
        $paymentMethod = $request->payment_method;
        $lbpRate = config('play.lbp_exchange_rate', 90000);
    
        if ($paymentMethod === 'LBP') {
            $amountPaidUsd = round($request->amount_paid / $lbpRate, 2);
            $totalAmountUsd = $totalCost;
        } else {
            $amountPaidUsd = round($request->amount_paid, 2);
            $totalAmountUsd = $totalCost;
        }
    
        // Update play session
        $session->update([
            'ended_at' => $endTime,
            'actual_hours' => $actualHours,
            'amount_paid' => $amountPaidUsd,
            'payment_method' => $paymentMethod,
            'total_cost' => $totalAmountUsd
        ]);
    
        // Ensure active shift
        $activeShift = Shift::where('cashier_id', Auth::id())->whereNull('closed_at')->first();
    
        if (!$activeShift) {
            $activeShift = Shift::create([
                'cashier_id' => Auth::id(),
                'date' => now()->toDateString(),
                'type' => (now()->hour < 12) ? 'morning' : 'afternoon',
                'opened_at' => now(),
            ]);
        }
    
        // Create sale record directly (without sale items)
        $sale = \App\Models\Sale::create([
            'shift_id' => $activeShift->id,
            'user_id' => Auth::id(),
            'total_amount' => $totalAmountUsd,
            'amount_paid' => $amountPaidUsd,
            'payment_method' => $paymentMethod,
            'child_id' => $session->child_id,
            'play_session_id' => $session->id
        ]);
    
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
                        $qty = (int)$data['qty'];
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
        
        return redirect()->route('cashier.sessions.show-end', $session)
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

        $addOns = AddOn::all();
        $sessionAddOns = $session->addOns()->get();
        return view('cashier.sessions.addons', compact('session', 'addOns', 'sessionAddOns'));
    }
} 