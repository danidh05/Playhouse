<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Models\Shift;
use Illuminate\Http\Request;

class ComplaintController extends Controller
{
    /**
     * Display a listing of complaints.
     */
    public function index(Request $request)
    {
        $query = Complaint::with(['shift', 'user', 'child']);
        
        // Filter by shift if provided
        if ($request->has('shift') && $request->shift) {
            $query->where('shift_id', $request->shift);
        }
        
        $complaints = $query->latest()->paginate(10);
        $shifts = Shift::orderBy('date', 'desc')->get();
        
        return view('admin.complaints.index', compact('complaints', 'shifts'));
    }
    
    /**
     * Toggle the resolved status of a complaint.
     */
    public function toggleResolved(Complaint $complaint)
    {
        $complaint->resolved = !$complaint->resolved;
        $complaint->save();
        
        return redirect()->route('admin.complaints.index')
            ->with('success', 'Complaint status updated successfully');
    }
}