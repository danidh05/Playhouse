<?php

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Controller;
use App\Models\Shift;
use App\Models\Sale;
use App\Models\PlaySession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ShiftController extends Controller
{
    /**
     * Display a list of the cashier's shifts.
     */
    public function index()
    {
        $shifts = Shift::where('cashier_id', Auth::id())
            ->orderBy('date', 'desc')
            ->paginate(15);
            
        return view('cashier.shifts.index', compact('shifts'));
    }
    
    /**
     * Show the form for opening a new shift.
     */
    public function showOpen()
    {
        // Check if the cashier already has an active shift
        $activeShift = Shift::where('cashier_id', auth()->id())
            ->whereNull('closed_at')
            ->first();

        if ($activeShift) {
            return redirect()->route('cashier.dashboard')
                ->with('info', 'You already have an active shift.');
        }

        return view('cashier.shifts.open');
    }
    
    /**
     * Store a newly created shift in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'opening_amount' => 'required|numeric|min:0',
            'shift_start_time' => 'required|date_format:H:i',
            'shift_end_time' => 'required|date_format:H:i|after:shift_start_time',
            'notes' => 'nullable|string|max:500',
            'confirm' => 'required|accepted'
        ]);

        // Get current date
        $today = now()->toDateString();
        
        // Create DateTime objects for start and end times
        $startDateTime = \Carbon\Carbon::createFromFormat(
            'Y-m-d H:i', 
            $today . ' ' . $request->shift_start_time
        );
        
        $endDateTime = \Carbon\Carbon::createFromFormat(
            'Y-m-d H:i', 
            $today . ' ' . $request->shift_end_time
        );
        
        // If end time is earlier than start time, assume it's for the next day
        if ($endDateTime->lt($startDateTime)) {
            $endDateTime->addDay();
        }
        
        // Determine shift type based on duration and time of day
        $duration = $startDateTime->diffInHours($endDateTime);
        $startHour = (int)$startDateTime->format('H');
        
        if ($duration >= 8) {
            $shiftType = 'full';
        } elseif ($startHour < 14) {
            $shiftType = 'morning';
        } else {
            $shiftType = 'evening';
        }

        $shift = new Shift();
        $shift->user_id = auth()->id();
        $shift->date = $today;
        $shift->starting_time = $startDateTime;
        $shift->expected_ending_time = $endDateTime;
        $shift->type = $shiftType; // Keep type for compatibility with existing reports
        $shift->opening_amount = $request->opening_amount;
        $shift->notes = $request->notes;
        $shift->save();

        return redirect()->route('cashier.dashboard')
            ->with('success', 'Your shift has started successfully! Scheduled from ' . 
                $startDateTime->format('g:i A') . ' to ' . $endDateTime->format('g:i A'));
    }
    
    /**
     * Display the specified shift.
     */
    public function show(Shift $shift)
    {
        $this->authorize('view', $shift);
        
        return view('cashier.shifts.show', compact('shift'));
    }
    
    /**
     * Show the form for closing a shift.
     */
    public function showClose(Shift $shift)
    {
        // Ensure this is the current user's shift and it's open
        if (($shift->cashier_id != Auth::id() && $shift->user_id != Auth::id()) || $shift->closed_at !== null) {
            return redirect()->route('cashier.dashboard')
                ->with('error', 'You cannot close this shift.');
        }
        
        // Check for active play sessions, but only to display a warning
        $activeSessions = PlaySession::where('shift_id', $shift->id)
            ->whereNull('ended_at')
            ->get();
        
        $activeSessionsCount = $activeSessions->count();
        
        // Calculate financial summary
        // 1. Get completed play sessions (that don't have a sale record)
        $playSessions = PlaySession::where('shift_id', $shift->id)
            ->whereNotNull('ended_at')
            ->get();
            
        // 2. Get all sales
        $allSales = Sale::where('shift_id', $shift->id)->get();
        
        // 3. Separate sales that are linked to play sessions
        $playSessionSales = $allSales->whereNotNull('play_session_id');
        $productSales = $allSales->whereNull('play_session_id');
        
        // 4. Calculate totals by currency (don't mix LBP and USD)
        $lbpRate = config('play.lbp_exchange_rate', 90000);
        
        // Initialize currency totals
        $sessionsLBP = 0;
        $sessionsUSD = 0;
        $salesLBP = 0;
        $salesUSD = 0;
        
        // Calculate play session revenue by currency
        foreach ($playSessionSales as $sale) {
            if ($sale->play_session) {
                $amount = $sale->play_session->total_cost ?? $sale->play_session->amount_paid ?? 0;
                if ($sale->payment_method === 'LBP') {
                    $sessionsLBP += $amount;
                } else {
                    $sessionsUSD += $amount;
                }
            }
        }
        
        // Calculate product sales revenue by currency
        foreach ($productSales as $sale) {
            if ($sale->payment_method === 'LBP') {
                $salesLBP += $sale->total_amount ?? 0;
            } else {
                $salesUSD += $sale->total_amount ?? 0;
            }
        }
        
        // Calculate totals for display
        $sessionsTotal = $sessionsUSD + ($sessionsLBP / $lbpRate); // USD equivalent for sessions
        $salesTotal = $salesUSD + ($salesLBP / $lbpRate); // USD equivalent for sales
        $totalRevenue = $sessionsTotal + $salesTotal;
        
        // Store separate currency amounts for detailed breakdown
        $currencyBreakdown = [
            'sessions_lbp' => $sessionsLBP,
            'sessions_usd' => $sessionsUSD,
            'sales_lbp' => $salesLBP,
            'sales_usd' => $salesUSD,
            'total_lbp' => $sessionsLBP + $salesLBP,
            'total_usd' => $sessionsUSD + $salesUSD
        ];
        
        // Payment method breakdown using TOTAL COST for sessions (with fallback)
        $paymentMethods = config('play.payment_methods', ['Cash', 'Card', 'Transfer', 'LBP']);
        $paymentBreakdown = [];
        
        foreach ($paymentMethods as $method) {
            $sessionAmount = $playSessionSales->where('payment_method', $method)
                ->filter(function($sale) {
                    return $sale->play_session;
                })->sum(function($sale) {
                    // Use total_cost if available, otherwise fall back to amount_paid for old records
                    return $sale->play_session->total_cost ?? $sale->play_session->amount_paid ?? 0;
                });
            
            $salesAmount = $productSales->where('payment_method', $method)->whereNotNull('total_amount')->sum('total_amount');
            $totalAmount = $sessionAmount + $salesAmount;
            
            if ($totalAmount > 0) {
                $paymentBreakdown[$method] = $totalAmount;
            }
        }
        
        return view('cashier.shifts.close', compact(
            'shift', 
            'activeSessions', 
            'activeSessionsCount', 
            'playSessions', 
            'salesTotal', 
            'sessionsTotal', 
            'totalRevenue',
            'paymentBreakdown',
            'playSessionSales',
            'productSales',
            'currencyBreakdown'
        ));
    }
    
    /**
     * Update the specified shift (close it).
     */
    public function update(Request $request, Shift $shift)
    {
        // Ensure this is the current user's shift and it's open
        if (($shift->cashier_id != Auth::id() && $shift->user_id != Auth::id()) || $shift->closed_at !== null) {
            return redirect()->route('cashier.dashboard')
                ->with('error', 'You cannot close this shift.');
        }
        
        $validated = $request->validate([
            'closing_amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);
        
        // Close the shift using Eloquent model to ensure proper updates
        $shift->closed_at = now();
        $shift->ending_time = now(); // For compatibility
        $shift->closing_amount = $validated['closing_amount'];
        $shift->notes = $validated['notes'];
        $shift->save();
        
        // Redirect to the shift report instead of the dashboard
        return redirect()->route('cashier.shifts.report', $shift)
            ->with('success', 'Shift closed successfully. Here is your shift report.');
    }
    
    /**
     * Display the shift report.
     */
    public function report(Shift $shift)
    {
        // Ensure this is the current user's shift
        if ($shift->cashier_id != Auth::id() && $shift->user_id != Auth::id()) {
            return redirect()->route('cashier.dashboard')
                ->with('error', 'You cannot view this shift report.');
        }
        
        // Get completed play sessions
        $playSessions = PlaySession::where('shift_id', $shift->id)
            ->whereNotNull('ended_at')
            ->get();
        
        // Get all sales
        $allSales = Sale::where('shift_id', $shift->id)->get();
        
        // Separate sales that are linked to play sessions from regular product sales
        $playSessionSales = $allSales->whereNotNull('play_session_id');
        $productSales = $allSales->whereNull('play_session_id');
        
        // Calculate totals by currency (don't mix LBP and USD)
        $lbpRate = config('play.lbp_exchange_rate', 90000);
        
        // Initialize currency totals
        $sessionsLBP = 0;
        $sessionsUSD = 0;
        $salesLBP = 0;
        $salesUSD = 0;
        
        // Calculate play session revenue by currency
        foreach ($playSessionSales as $sale) {
            if ($sale->play_session) {
                $amount = $sale->play_session->total_cost ?? $sale->play_session->amount_paid ?? 0;
                if ($sale->payment_method === 'LBP') {
                    $sessionsLBP += $amount;
                } else {
                    $sessionsUSD += $amount;
                }
            }
        }
        
        // Calculate product sales revenue by currency
        foreach ($productSales as $sale) {
            if ($sale->payment_method === 'LBP') {
                $salesLBP += $sale->total_amount ?? 0;
            } else {
                $salesUSD += $sale->total_amount ?? 0;
            }
        }
        
        // Calculate totals for display
        $sessionsTotal = $sessionsUSD + ($sessionsLBP / $lbpRate); // USD equivalent for sessions
        $salesTotal = $salesUSD + ($salesLBP / $lbpRate); // USD equivalent for sales
        $totalRevenue = $sessionsTotal + $salesTotal;
        
        // Store separate currency amounts for detailed breakdown
        $currencyBreakdown = [
            'sessions_lbp' => $sessionsLBP,
            'sessions_usd' => $sessionsUSD,
            'sales_lbp' => $salesLBP,
            'sales_usd' => $salesUSD,
            'total_lbp' => $sessionsLBP + $salesLBP,
            'total_usd' => $sessionsUSD + $salesUSD
        ];
        
        return view('cashier.shifts.report', compact(
            'shift', 
            'playSessions', 
            'playSessionSales',
            'productSales',
            'sessionsTotal',
            'salesTotal',
            'totalRevenue',
            'currencyBreakdown'
        ));
    }
} 