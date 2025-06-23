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
use Illuminate\Support\Facades\Session;

class DashboardController extends Controller
{
    /**
     * Display the cashier dashboard.
     */
    public function index(Request $request)
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
            
        // Group sessions by alert thresholds
        $groupedAlerts = [
            'planned_ending_soon' => [], // For sessions with planned hours ending within 15 minutes
            'one_hour_mark' => [],       // For sessions at 55-60 minute mark
            'two_hour_mark' => [],       // For sessions at 115-120 minute mark
        ];
        
        foreach ($activeSessions as $session) {
            $duration = $session->started_at->diffAsCarbonInterval(now())->cascade();
            $minutesElapsed = ($duration->h * 60) + $duration->i;
            
            // If session has planned hours
            if ($session->planned_hours) {
                $totalPlannedMinutes = $session->planned_hours * 60;
                $minutesRemaining = $totalPlannedMinutes - $minutesElapsed;
                
                // Add to nearing completion if less than 15 minutes remaining
                if ($minutesRemaining > 0 && $minutesRemaining <= 15) {
                    $groupedAlerts['planned_ending_soon'][] = [
                        'session' => $session,
                        'minutesRemaining' => $minutesRemaining,
                    ];
                }
            } 
            // For sessions without planned hours, alert if exceeding thresholds
            else {
                // Alert at 55-60 minutes mark
                if ($minutesElapsed >= 55 && $minutesElapsed <= 60) {
                    $groupedAlerts['one_hour_mark'][] = [
                        'session' => $session,
                        'minutesRemaining' => $minutesElapsed,
                    ];
                }
                // Also alert at 115-120 minutes mark (near 2 hours)
                else if ($minutesElapsed >= 115 && $minutesElapsed <= 120) {
                    $groupedAlerts['two_hour_mark'][] = [
                        'session' => $session,
                        'minutesRemaining' => $minutesElapsed,
                    ];
                }
            }
        }
        
        // Convert grouped alerts to consolidated alerts for the view
        $consolidatedAlerts = [];
        
        // Check if alerts have been shown already this session
        $alertsShownKey = 'alerts_shown';
        $alertsShown = $request->session()->get($alertsShownKey, []);
        $currentTime = now()->timestamp;
        $showAlerts = false;
        
        // If it's been more than 30 minutes since alerts were shown, or they haven't been shown yet
        if (!isset($alertsShown['timestamp']) || ($currentTime - $alertsShown['timestamp']) > 1800) {
            $showAlerts = true;
            
            // Get session IDs that have been alerted about already
            $alertedSessionIds = $alertsShown['session_ids'] ?? [];
            
            // Process planned hours ending soon
            if (!empty($groupedAlerts['planned_ending_soon'])) {
                // Filter out sessions that have already been alerted about
                $newAlerts = array_filter($groupedAlerts['planned_ending_soon'], function($alert) use ($alertedSessionIds) {
                    return !in_array($alert['session']->id, $alertedSessionIds);
                });
                
                if (!empty($newAlerts)) {
                    $sessionsCount = count($newAlerts);
                    if ($sessionsCount === 1) {
                        // If only one session, show individual alert
                        $consolidatedAlerts[] = reset($newAlerts);
                    } else {
                        // If multiple sessions, create a consolidated alert
                        $firstSession = reset($newAlerts)['session'];
                        $consolidatedAlerts[] = [
                            'consolidated' => true,
                            'type' => 'planned_ending_soon',
                            'count' => $sessionsCount,
                            'session' => $firstSession, // Use first session for routing
                            'sessions' => array_column($newAlerts, 'session'),
                            'minutesRemaining' => min(array_column($newAlerts, 'minutesRemaining')),
                        ];
                    }
                    
                    // Add these session IDs to the alerted list
                    foreach ($newAlerts as $alert) {
                        $alertedSessionIds[] = $alert['session']->id;
                    }
                }
            }
            
            // Process one hour mark
            if (!empty($groupedAlerts['one_hour_mark'])) {
                // Filter out sessions that have already been alerted about
                $newAlerts = array_filter($groupedAlerts['one_hour_mark'], function($alert) use ($alertedSessionIds) {
                    return !in_array($alert['session']->id, $alertedSessionIds);
                });
                
                if (!empty($newAlerts)) {
                    $sessionsCount = count($newAlerts);
                    if ($sessionsCount === 1) {
                        // If only one session, show individual alert
                        $consolidatedAlerts[] = reset($newAlerts);
                    } else {
                        // If multiple sessions, create a consolidated alert
                        $firstSession = reset($newAlerts)['session'];
                        $consolidatedAlerts[] = [
                            'consolidated' => true,
                            'type' => 'one_hour_mark',
                            'count' => $sessionsCount,
                            'session' => $firstSession, // Use first session for routing
                            'sessions' => array_column($newAlerts, 'session'),
                            'minutesRemaining' => 60,
                        ];
                    }
                    
                    // Add these session IDs to the alerted list
                    foreach ($newAlerts as $alert) {
                        $alertedSessionIds[] = $alert['session']->id;
                    }
                }
            }
            
            // Process two hour mark
            if (!empty($groupedAlerts['two_hour_mark'])) {
                // Filter out sessions that have already been alerted about
                $newAlerts = array_filter($groupedAlerts['two_hour_mark'], function($alert) use ($alertedSessionIds) {
                    return !in_array($alert['session']->id, $alertedSessionIds);
                });
                
                if (!empty($newAlerts)) {
                    $sessionsCount = count($newAlerts);
                    if ($sessionsCount === 1) {
                        // If only one session, show individual alert
                        $consolidatedAlerts[] = reset($newAlerts);
                    } else {
                        // If multiple sessions, create a consolidated alert
                        $firstSession = reset($newAlerts)['session'];
                        $consolidatedAlerts[] = [
                            'consolidated' => true,
                            'type' => 'two_hour_mark',
                            'count' => $sessionsCount,
                            'session' => $firstSession, // Use first session for routing
                            'sessions' => array_column($newAlerts, 'session'),
                            'minutesRemaining' => 120,
                        ];
                    }
                    
                    // Add these session IDs to the alerted list
                    foreach ($newAlerts as $alert) {
                        $alertedSessionIds[] = $alert['session']->id;
                    }
                }
            }
            
            // Update the session with the new timestamp and alerted session IDs
            if (!empty($consolidatedAlerts)) {
                $request->session()->put($alertsShownKey, [
                    'timestamp' => $currentTime,
                    'session_ids' => $alertedSessionIds
                ]);
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
            'consolidatedAlerts',
            'showAlerts'
        ));
    }

    /**
     * Calculate daily revenue from sales and play sessions
     */
    private function calculateDailyRevenue($date)
    {
        // Calculate revenue from play sessions using total_cost (what customer should pay) with fallback
        $sessionsRevenue = PlaySession::whereDate('ended_at', $date)
            ->whereNotNull('ended_at')
            ->get()
            ->sum(function($session) {
                // Use total_cost if available, otherwise fall back to amount_paid for old records
                return $session->total_cost ?? $session->amount_paid ?? 0;
            });

        // Calculate revenue from product sales
        $salesRevenue = Sale::whereDate('created_at', $date)
            ->whereNull('play_session_id') // Only count standalone product sales
            ->sum('total_amount');

        return $sessionsRevenue + $salesRevenue;
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