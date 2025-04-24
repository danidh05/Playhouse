<header class="bg-white border-b border-gray-200">
    <div class="flex items-center justify-between px-6 py-4">
        <!-- Company Name and Logo -->
        <div class="flex items-center">
            <div class="flex items-center mr-8">
                <img src="{{ asset('images/logo-icon.svg') }}" alt="Logo" class="w-8 h-8 mr-2">
                <h1 class="text-xl font-semibold text-gray-800">Fluffy Puffy</h1>
            </div>
            
            <!-- Navigation Tabs -->
            <nav class="flex space-x-6">
                <a href="{{ route('cashier.children.index') }}" class="flex items-center px-3 py-2 {{ request()->routeIs('cashier.children*') ? 'text-blue-600 border-b-2 border-blue-600 font-medium' : 'text-gray-500 hover:text-gray-700' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    Kids
                </a>
                
                <a href="{{ route('cashier.sessions.index') }}" class="flex items-center px-3 py-2 {{ request()->routeIs('cashier.sessions*') ? 'text-blue-600 border-b-2 border-blue-600 font-medium' : 'text-gray-500 hover:text-gray-700' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Play
                </a>
                
                <a href="{{ route('cashier.sales.create') }}" class="flex items-center px-3 py-2 {{ request()->routeIs('cashier.sales*') ? 'text-blue-600 border-b-2 border-blue-600 font-medium' : 'text-gray-500 hover:text-gray-700' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    Sell
                </a>
                
                <a href="{{ route('cashier.complaints.create') }}" class="flex items-center px-3 py-2 {{ request()->routeIs('cashier.complaints*') ? 'text-blue-600 border-b-2 border-blue-600 font-medium' : 'text-gray-500 hover:text-gray-700' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Complaints
                </a>
            </nav>
        </div>
        
        <!-- Date Display -->
        <div class="text-gray-500">
            {{ now()->format('D, d M Y') }}
        </div>
    </div>
</header> 
 