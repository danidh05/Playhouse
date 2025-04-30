<?php

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Models\PlaySession;
use App\Models\Sale;
use App\Models\SaleItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Display the cashier dashboard.
     */
    public function index()
    {
        // Get today's date with start and end times
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();

        // Calculate Today's Revenue
        $todayRevenue = $this->calculateDailyRevenue($today);
        $yesterdayRevenue = $this->calculateDailyRevenue($yesterday);
        $revenueGrowth = $yesterdayRevenue > 0 
            ? (($todayRevenue - $yesterdayRevenue) / $yesterdayRevenue) * 100 
            : 100;

        // Count Today's Play Sessions
        $todaySessions = PlaySession::whereDate('created_at', $today)->count();
        $yesterdaySessions = PlaySession::whereDate('created_at', $yesterday)->count();
        $sessionsGrowth = $yesterdaySessions > 0 
            ? (($todaySessions - $yesterdaySessions) / $yesterdaySessions) * 100 
            : 100;

        // Count Today's Complaints
        $todayComplaints = Complaint::whereDate('created_at', $today)->count();
        $yesterdayComplaints = Complaint::whereDate('created_at', $yesterday)->count();
        $complaintsGrowth = $yesterdayComplaints > 0 
            ? (($todayComplaints - $yesterdayComplaints) / $yesterdayComplaints) * 100 
            : 0;

        // Get Active Sessions that are near completion
        $activeSessions = PlaySession::with('child')
            ->whereNull('ended_at')
            ->orderBy('started_at')
            ->get();
            
        $nearingCompletionSessions = [];
        
        foreach ($activeSessions as $session) {
            $duration = $session->started_at->diffAsCarbonInterval(now())->cascade();
            $minutesElapsed = ($duration->h * 60) + $duration->i;
            
            // If session has planned hours
            if ($session->planned_hours) {
                $totalPlannedMinutes = $session->planned_hours * 60;
                $minutesRemaining = $totalPlannedMinutes - $minutesElapsed;
                
                // Add to nearing completion if less than 15 minutes remaining
                if ($minutesRemaining > 0 && $minutesRemaining <= 15) {
                    $nearingCompletionSessions[] = [
                        'session' => $session,
                        'minutesRemaining' => $minutesRemaining,
                    ];
                }
            } 
            // For sessions without planned hours, alert if exceeding thresholds
            else {
                // Alert at 55-60 minutes mark
                if ($minutesElapsed >= 55 && $minutesElapsed <= 60) {
                    $nearingCompletionSessions[] = [
                        'session' => $session,
                        'minutesRemaining' => $minutesElapsed,
                    ];
                }
                // Also alert at 115-120 minutes mark (near 2 hours)
                else if ($minutesElapsed >= 115 && $minutesElapsed <= 120) {
                    $nearingCompletionSessions[] = [
                        'session' => $session,
                        'minutesRemaining' => $minutesElapsed,
                    ];
                }
            }
        }

        // Get Recent Activities (combining sessions, sales and complaints)
        $recentActivities = $this->getRecentActivities();

        // Get current active shift for the cashier
        $activeShift = \App\Models\Shift::where('cashier_id', Auth::id())
            ->whereNull('closed_at')
            ->first();

        return view('cashier.dashboard', compact(
            'todayRevenue',
            'revenueGrowth', 
            'todaySessions',
            'sessionsGrowth', 
            'todayComplaints',
            'complaintsGrowth', 
            'recentActivities', 
            'activeShift',
            'nearingCompletionSessions'
        ));
    }

    /**
     * Calculate daily revenue from sales and play sessions
     */
    private function calculateDailyRevenue($date)
    {
        // Only count sales revenue since play sessions are already included in sales
        return Sale::whereDate('created_at', $date)->sum('total_amount');
    }

    /**
     * Get recent activities for the dashboard feed
     */
    private function getRecentActivities()
    {
        // Database-agnostic string concatenation
        $sessionDetails = DB::connection()->getDriverName() === 'sqlite' 
            ? "('Started session for ' || children.name)" 
            : "CONCAT('Started session for ', children.name)";
            
        $salesDetails = DB::connection()->getDriverName() === 'sqlite' 
            ? "('Sale #' || sales.id || ' processed')" 
            : "CONCAT('Sale #', sales.id, ' processed')";
            
        $complaintsDetails = DB::connection()->getDriverName() === 'sqlite' 
            ? "('New ' || type || ' complaint submitted')" 
            : "CONCAT('New ', type, ' complaint submitted')";
        
        // Get recent play sessions
        $sessions = PlaySession::with('child')
            ->select(
                DB::raw("'session' as type"),
                DB::raw($sessionDetails . " as details"),
                'play_sessions.created_at'
            )
            ->join('children', 'play_sessions.child_id', '=', 'children.id')
            ->whereDate('play_sessions.created_at', '>=', Carbon::now()->subDays(2))
            ->orderBy('play_sessions.created_at', 'desc')
            ->limit(5);
        
        // Get recent sales
        $sales = Sale::select(
                DB::raw("'sale' as type"),
                DB::raw($salesDetails . " as details"),
                'sales.created_at'
            )
            ->whereDate('sales.created_at', '>=', Carbon::now()->subDays(2))
            ->orderBy('sales.created_at', 'desc')
            ->limit(5);
        
        // Get recent complaints
        $complaints = Complaint::select(
                DB::raw("'complaint' as type"),
                DB::raw($complaintsDetails . " as details"),
                'created_at'
            )
            ->whereDate('created_at', '>=', Carbon::now()->subDays(2))
            ->orderBy('created_at', 'desc')
            ->limit(5);
            
        // Combine and sort by created_at
        $activities = $sessions->union($sales)->union($complaints)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
            
        return $activities;
    }
} 