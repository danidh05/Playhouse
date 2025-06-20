<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Models\Expense;
use App\Models\PlaySession;
use App\Models\Sale;
use App\Models\Shift;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Display the admin dashboard.
     */
    public function index()
    {
        // Get count of active shifts today
        $activeShiftsCount = Shift::whereDate('date', today())
            ->whereNull('closed_at')
            ->count();
            
        // Get count of unresolved complaints
        $unresolvedComplaintsCount = Complaint::where('resolved', false)->count();
        
        // Get total expenses for today
        $totalExpensesToday = Expense::whereDate('created_at', today())
            ->sum('amount');
            
        return view('admin.dashboard', compact(
            'activeShiftsCount',
            'unresolvedComplaintsCount',
            'totalExpensesToday'
        ));
    }

    /**
     * Display data visualizations.
     */
    public function visualizations()
    {
        // Get revenue data for the last 30 days
        $startDate = Carbon::now()->subDays(30);
        $endDate = Carbon::now();
        
        // Daily revenue (play sessions + product sales)
        $dailyRevenue = [];
        $salesData = [];
        $sessionsData = [];
        $labels = [];
        
        // Generate date range for the last 30 days
        $currentDate = clone $startDate;
        while ($currentDate <= $endDate) {
            $date = $currentDate->format('Y-m-d');
            $labels[] = $currentDate->format('M d');
            
            // Get sales revenue for this day
            $salesRevenue = Sale::whereDate('created_at', $date)->sum('total_amount');
            $salesData[] = round($salesRevenue, 2);
            
            // Get play sessions revenue for this day (using amount_paid not total_cost)
            $sessionsRevenue = PlaySession::whereDate('ended_at', $date)
                                ->whereNotNull('ended_at')
                                ->whereNotNull('amount_paid')
                                ->sum('amount_paid');
            $sessionsData[] = round($sessionsRevenue, 2);
            
            // Total revenue for the day
            $dailyRevenue[] = round($salesRevenue + $sessionsRevenue, 2);
            
            $currentDate->addDay();
        }
        
        // Monthly expenses by item for the last 6 months
        $lastSixMonths = [];
        $allExpenseItems = [];
        
        // First, collect data about all items across all months
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $lastSixMonths[] = $month->format('M Y');
            
            $monthExpenses = Expense::whereYear('created_at', $month->year)
                           ->whereMonth('created_at', $month->month)
                           ->get();
                           
            foreach ($monthExpenses as $expense) {
                if (!isset($allExpenseItems[$expense->item])) {
                    $allExpenseItems[$expense->item] = 0;
                }
                $allExpenseItems[$expense->item] += $expense->amount;
            }
        }
        
        // Sort items by amount
        arsort($allExpenseItems);
        
        // Get top 5 items
        $topItems = array_slice($allExpenseItems, 0, 5, true);
        
        // Initialize expense arrays
        $expensesByCategory = [];
        foreach (array_keys($topItems) as $item) {
            $expensesByCategory[$item] = array_fill(0, 6, 0);
        }
        
        // Add 'Other' category if there are more than 5 items
        if (count($allExpenseItems) > 5) {
            $expensesByCategory['Other'] = array_fill(0, 6, 0);
        }
        
        // Now fill in the data for each month
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $monthIndex = 5 - $i;
            
            $monthExpenses = Expense::whereYear('created_at', $month->year)
                           ->whereMonth('created_at', $month->month)
                           ->get();
                           
            foreach ($monthExpenses as $expense) {
                if (array_key_exists($expense->item, $topItems)) {
                    $expensesByCategory[$expense->item][$monthIndex] += round($expense->amount, 2);
                } elseif (isset($expensesByCategory['Other'])) {
                    $expensesByCategory['Other'][$monthIndex] += round($expense->amount, 2);
                }
            }
        }
        
        // Handle case when there are no expenses
        if (empty($expensesByCategory)) {
            $expensesByCategory['No Expenses'] = array_fill(0, 6, 0);
        }
        
        // Get most popular play session hours
        $popularHours = PlaySession::whereNotNull('ended_at')
                        ->selectRaw('HOUR(started_at) as hour, COUNT(*) as count')
                        ->groupBy('hour')
                        ->orderBy('hour')
                        ->pluck('count', 'hour')
                        ->toArray();
        
        $hoursLabels = [];
        $hoursCounts = [];
        
        // Format hours from 0-23 to 12-hour format with AM/PM
        for ($hour = 0; $hour < 24; $hour++) {
            $hoursLabels[] = Carbon::createFromTime($hour)->format('g A');
            $hoursCounts[] = $popularHours[$hour] ?? 0;
        }
        
        // Get average play session durations for the last 30 days by day
        $sessionDurations = [];
        $sessionCounts = [];
        
        for ($i = 0; $i < 30; $i++) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            
            // Get all completed sessions for this day
            $daySessions = PlaySession::whereDate('ended_at', $date)
                            ->whereNotNull('ended_at')
                            ->get();
                            
            $totalDuration = 0;
            $count = count($daySessions);
            
            foreach ($daySessions as $session) {
                $start = Carbon::parse($session->started_at);
                $end = Carbon::parse($session->ended_at);
                $durationInHours = $start->diffInMinutes($end) / 60;
                $totalDuration += $durationInHours;
            }
            
            $avgDuration = $count > 0 ? round($totalDuration / $count, 2) : 0;
            
            // Add in reverse order (most recent last)
            array_unshift($sessionDurations, $avgDuration);
            array_unshift($sessionCounts, $count);
        }
        
        // Last 30 days labels for duration chart
        $durationLabels = [];
        for ($i = 29; $i >= 0; $i--) {
            $durationLabels[] = Carbon::now()->subDays($i)->format('M d');
        }
        
        return view('admin.visualizations', compact(
            'labels', 
            'dailyRevenue', 
            'salesData', 
            'sessionsData',
            'lastSixMonths',
            'expensesByCategory',
            'hoursLabels',
            'hoursCounts',
            'durationLabels',
            'sessionDurations',
            'sessionCounts'
        ));
    }
}