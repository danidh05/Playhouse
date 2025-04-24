<?php

namespace App\Http\Middleware;

use App\Models\Shift;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckActiveShift
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Only apply this middleware to cashiers
        if (Auth::check() && Auth::user()->hasRole('cashier')) {
            
            // Skip this check for shift-related routes to avoid redirect loops
            if ($request->routeIs('cashier.shifts.*') || $request->routeIs('logout')) {
                return $next($request);
            }
            
            // Check if the cashier has an active shift
            $activeShift = Shift::where('cashier_id', Auth::id())
                                ->whereNull('closed_at')
                                ->first();
            
            if (!$activeShift) {
                // Flash a message explaining why they're being redirected
                session()->flash('info', 'Please start your shift before accessing the cashier dashboard.');
                
                // Redirect to the shift selection page
                return redirect()->route('cashier.shifts.open');
            }
        }
        
        return $next($request);
    }
} 