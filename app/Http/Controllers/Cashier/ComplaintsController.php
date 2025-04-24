<?php

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Controller;
use App\Http\Requests\ComplaintRequest;
use App\Models\Child;
use App\Models\Complaint;
use App\Models\ComplaintType;
use App\Models\Shift;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ComplaintsController extends Controller
{
    /**
     * Display a listing of the complaints.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = Complaint::with(['child', 'shift']);
        
        // Apply date filters
        $filter = $request->input('filter', 'today');
        
        switch ($filter) {
            case 'today':
                $query->whereDate('created_at', today());
                break;
            case 'week':
                $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                break;
            case 'month':
                $query->whereMonth('created_at', now()->month)
                      ->whereYear('created_at', now()->year);
                break;
            case 'all':
                // No filter, show all
                break;
        }
        
        // Apply status filters
        if ($request->has('status')) {
            if ($request->status === 'open') {
                $query->where('resolved', false);
            } elseif ($request->status === 'resolved') {
                $query->where('resolved', true);
            }
        }
        
        $complaints = $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString();
            
        // Calculate counts for the dashboard widgets
        $openCount = Complaint::where('resolved', false)->count();
        $todayCount = Complaint::whereDate('created_at', today())->count();
        $resolvedThisWeek = Complaint::where('resolved', true)
            ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->count();
            
        return view('cashier.complaints.index', compact('complaints', 'openCount', 'todayCount', 'resolvedThisWeek'));
    }

    /**
     * Show the form for creating a new complaint.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        // Find active shift for the current cashier
        $activeShift = Shift::where('cashier_id', Auth::id())
                           ->whereNull('closed_at')
                           ->latest()
                           ->first();
        
        if (!$activeShift) {
            // Create a new shift if none exists
            $activeShift = Shift::create([
                'cashier_id' => Auth::id(),
                'date' => now()->toDateString(),
                'type' => (now()->hour < 12) ? 'morning' : 'evening',
                'opened_at' => now(),
                'opening_amount' => 0, // Default value
            ]);
        }
        
        $children = Child::orderBy('name')->get();
        $complaintTypes = config('play.complaint_types');
        
        return view('cashier.complaints.create', compact('activeShift', 'children', 'complaintTypes'));
    }

    /**
     * Store a newly created complaint in storage.
     *
     * @param  \App\Http\Requests\ComplaintRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(ComplaintRequest $request)
    {
        $complaint = new Complaint();
        $complaint->shift_id = $request->shift_id;
        $complaint->child_id = $request->child_id;
        $complaint->type = $request->type;
        $complaint->description = $request->description;
        $complaint->user_id = Auth::id();
        $complaint->resolved = false;
        $complaint->save();

        return redirect()->route('cashier.dashboard')->with('success', 'Complaint submitted successfully.');
    }
    
    /**
     * Display the specified complaint.
     *
     * @param  \App\Models\Complaint  $complaint
     * @return \Illuminate\View\View
     */
    public function show(Complaint $complaint)
    {
        // Load relationships
        $complaint->load(['child', 'user', 'shift']);
        
        return view('cashier.complaints.show', compact('complaint'));
    }
    
    /**
     * Mark a complaint as resolved.
     *
     * @param  \App\Models\Complaint  $complaint
     * @return \Illuminate\Http\RedirectResponse
     */
    public function markAsResolved(Complaint $complaint)
    {
        $complaint->resolved = true;
        $complaint->save();
        
        return redirect()->route('cashier.complaints.index')
            ->with('success', 'Complaint marked as resolved successfully.');
    }
} 