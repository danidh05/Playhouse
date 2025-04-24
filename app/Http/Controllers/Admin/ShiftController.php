<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Shift;
use App\Models\PlaySession;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Http\Request;

class ShiftController extends Controller
{
    /**
     * Display a listing of shifts.
     */
    public function index(Request $request)
    {
        $query = Shift::with('cashier');
        
        // Filter by date if provided
        if ($request->has('date') && $request->date) {
            $query->whereDate('date', $request->date);
        }
        
        // Filter by cashier if provided
        if ($request->has('cashier_id') && $request->cashier_id) {
            $query->where('cashier_id', $request->cashier_id);
        }
        
        $shifts = $query->latest()->paginate(15);
        $cashiers = User::whereHas('role', function($query) {
            $query->where('name', 'cashier');
        })->get();
        
        return view('admin.shifts.index', compact('shifts', 'cashiers'));
    }
    
    /**
     * Display the specified shift.
     */
    public function show(Shift $shift)
    {
        $shift->load('cashier');
        
        $sessions = PlaySession::where('shift_id', $shift->id)->get();
        $sales = Sale::where('shift_id', $shift->id)->get();
        
        $sessionsTotal = $sessions->sum('total_cost');
        $salesTotal = $sales->sum('total_price');
        $totalRevenue = $sessionsTotal + $salesTotal;
        
        // Calculate payment method breakdown
        $cashSessions = $sessions->where('payment_method', 'cash')->sum('total_cost');
        $cardSessions = $sessions->where('payment_method', 'credit card')->sum('total_cost') + 
                        $sessions->where('payment_method', 'debit card')->sum('total_cost');
        $otherSessions = $sessionsTotal - $cashSessions - $cardSessions;
        
        $cashSales = $sales->where('payment_method', 'cash')->sum('total_price');
        $cardSales = $sales->where('payment_method', 'credit card')->sum('total_price') + 
                     $sales->where('payment_method', 'debit card')->sum('total_price');
        $otherSales = $salesTotal - $cashSales - $cardSales;
        
        // Calculate cash variance if shift is closed
        $cashVariance = null;
        if ($shift->closed_at) {
            $expectedCash = $shift->opening_amount + $cashSessions + $cashSales;
            $cashVariance = $shift->closing_amount - $expectedCash;
        }
        
        return view('admin.shifts.show', compact(
            'shift', 
            'sessions', 
            'sales', 
            'totalRevenue',
            'sessionsTotal',
            'salesTotal',
            'cashSessions',
            'cardSessions',
            'otherSessions',
            'cashSales',
            'cardSales',
            'otherSales',
            'cashVariance'
        ));
    }
} 