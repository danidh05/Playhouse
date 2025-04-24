<div class="bg-blue-600 h-full w-16 flex flex-col items-center py-4 text-white">
    <!-- Navigation Icons -->
    <nav class="flex flex-col items-center space-y-8">
        <!-- Dashboard -->
        <a href="{{ route('cashier.dashboard') }}" class="flex flex-col items-center mb-8">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 mb-2 {{ request()->routeIs('cashier.dashboard') ? 'text-white' : 'text-white/70' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
            </svg>
            <span class="text-sm {{ request()->routeIs('cashier.dashboard') ? 'text-white' : 'text-white/70' }}">Dashboard</span>
        </a>
        
        <!-- Children -->
        <a href="{{ route('cashier.children.index') }}" class="flex flex-col items-center mb-8">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 mb-2 {{ request()->routeIs('cashier.children.*') ? 'text-white' : 'text-white/70' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
            <span class="text-sm {{ request()->routeIs('cashier.children.*') ? 'text-white' : 'text-white/70' }}">Children</span>
        </a>
        
        <!-- Play Sessions -->
        <a href="{{ route('cashier.sessions.index') }}" class="flex flex-col items-center mb-8">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 mb-2 {{ request()->routeIs('cashier.sessions.*') ? 'text-white' : 'text-white/70' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span class="text-sm {{ request()->routeIs('cashier.sessions.*') ? 'text-white' : 'text-white/70' }}">Sessions</span>
        </a>
        
        <!-- Sales -->
        <a href="{{ route('cashier.sales.index') }}" class="flex flex-col items-center mb-8">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 mb-2 {{ request()->routeIs('cashier.sales.*') ? 'text-white' : 'text-white/70' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
            <span class="text-sm {{ request()->routeIs('cashier.sales.*') ? 'text-white' : 'text-white/70' }}">Sales</span>
        </a>
        
        <!-- Complaints -->
        <a href="{{ route('cashier.complaints.index') }}" class="flex flex-col items-center mb-8">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 mb-2 {{ request()->routeIs('cashier.complaints.*') ? 'text-white' : 'text-white/70' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span class="text-sm {{ request()->routeIs('cashier.complaints.*') ? 'text-white' : 'text-white/70' }}">Complaints</span>
        </a>
        
        <!-- Shifts -->
        <a href="{{ route('cashier.shifts.index') }}" class="flex flex-col items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 mb-2 {{ request()->routeIs('cashier.shifts.*') ? 'text-white' : 'text-white/70' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span class="text-sm {{ request()->routeIs('cashier.shifts.*') ? 'text-white' : 'text-white/70' }}">Shifts</span>
        </a>
    </nav>
    
    <!-- Logout at bottom -->
    <div class="mt-auto">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="p-2 rounded-lg hover:bg-blue-700">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
            </button>
        </form>
    </div>
</div> 