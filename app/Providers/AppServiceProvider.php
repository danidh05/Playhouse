<?php

namespace App\Providers;

use App\Models\Shift;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Share active shift with all views
        View::composer('*', function ($view) {
            if (Auth::check() && Auth::user()->hasRole('cashier')) {
                $activeShift = Shift::where('cashier_id', Auth::id())
                    ->whereNull('closed_at')
                    ->first();
                    
                $view->with('activeShift', $activeShift);
            }
        });
    }
}
