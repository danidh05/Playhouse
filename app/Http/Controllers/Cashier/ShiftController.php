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
        $activeShift = Shift::where('user_id', auth()->id())
            ->whereNull('ending_time')
            ->first();

        if ($activeShift) {
            return redirect()->route('cashier.dashboard')
                ->with('info', 'You already have an active shift.');
        }

        // Define shift types
        $shiftTypes = [
            'morning' => 'Morning Shift (Open - 2pm)',
            'evening' => 'Evening Shift (2pm - Close)',
            'full' => 'Full Day Shift'
        ];

        // Set default shift type based on time of day
        $hour = now()->hour;
        $defaultShiftType = ($hour < 14) ? 'morning' : 'evening';

        return view('cashier.shifts.open', [
            'shiftTypes' => $shiftTypes,
            'defaultShiftType' => $defaultShiftType
        ]);
    }
    
    /**
     * Store a newly created shift in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'opening_amount' => 'required|numeric|min:0',
            'type' => 'required|in:morning,evening,full',
            'notes' => 'nullable|string|max:500',
            'confirm' => 'required|accepted'
        ]);

        $shift = new Shift();
        $shift->user_id = auth()->id();
        $shift->date = now()->toDateString();
        $shift->starting_time = now();
        $shift->type = $request->type;
        $shift->opening_amount = $request->opening_amount;
        $shift->notes = $request->notes;
        $shift->save();

        return redirect()->route('cashier.dashboard')
            ->with('success', 'Your shift has started successfully!');
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
        if ($shift->cashier_id != Auth::id() || $shift->closed_at !== null) {
            return redirect()->route('cashier.dashboard')
                ->with('error', 'You cannot close this shift.');
        }
        
        // Check for active play sessions, but only to display a warning
        $activeSessions = PlaySession::where('shift_id', $shift->id)
            ->whereNull('ended_at')
            ->get();
        
        $activeSessionsCount = $activeSessions->count();
        
        return view('cashier.shifts.close', compact('shift', 'activeSessions', 'activeSessionsCount'));
    }
    
    /**
     * Update the specified shift (close it).
     */
    public function update(Request $request, Shift $shift)
    {
        // Ensure this is the current user's shift and it's open
        if ($shift->cashier_id != Auth::id() || $shift->closed_at !== null) {
            return redirect()->route('cashier.dashboard')
                ->with('error', 'You cannot close this shift.');
        }
        
        $request->validate([
            'closing_amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);
        
        // Close the shift even if there are active play sessions
        // The play sessions will remain associated with this shift
        $shift->update([
            'closed_at' => now(),
            'closing_amount' => $request->closing_amount,
            'notes' => $request->notes,
        ]);
        
        return redirect()->route('cashier.dashboard')
            ->with('success', 'Shift closed successfully. Any active play sessions will remain associated with this shift when they end.');
    }
    
    /**
     * Display the shift report.
     */
    public function report(Shift $shift)
    {
        // Ensure this is the current user's shift
        if ($shift->cashier_id != Auth::id()) {
            return redirect()->route('cashier.dashboard')
                ->with('error', 'You cannot view this shift report.');
        }
        
        $playSessions = PlaySession::where('shift_id', $shift->id)->get();
        $sales = Sale::where('shift_id', $shift->id)->get();
        
        $sessionsTotal = $playSessions->sum('total_cost');
        $salesTotal = $sales->sum('total_price');
        $totalRevenue = $sessionsTotal + $salesTotal;
        
        return view('cashier.shifts.report', compact('shift', 'playSessions', 'sales', 'totalRevenue'));
    }
} 