<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Cashier Dashboard') - Fluffy Puffy Kids Zone</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
    tailwind.config = {
        theme: {
            extend: {
                colors: {
                    'primary': '#5192dd',
                    'primary-dark': '#4481c7',
                    'primary-light': '#9fc2ee',
                }
            },
            borderRadius: {
                'xl': '20px',
            }
        }
    }
    </script>
    @yield('styles')
</head>

<body class="bg-gray-100 h-screen flex overflow-hidden">
    <!-- Sidebar - Minimal with just icons -->
    <aside class="h-screen sticky top-0 bg-primary text-white w-16">
        <!-- Logo -->
        <div class="flex justify-center p-2 mb-4">
            <img src="{{ asset('images/d4a70d2423d97599cccd8185d9046e60ff917ce0.png') }}" alt="Logo"
                class="w-12 h-12 object-contain">
        </div>



        <!-- Logout at bottom -->
        <div class="absolute bottom-4 w-full flex justify-center">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-white p-2 hover:bg-primary-dark rounded-full">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                </button>
            </form>
        </div>
    </aside>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col overflow-hidden">
        <!-- Header with navigation -->
        <header class="bg-white shadow-sm z-10">
            <div class="px-4 py-2 flex justify-between items-center border-b">
                <div class="flex items-center space-x-4">
                    <!-- Main Navigation -->
                    <a href="{{ route('cashier.dashboard') }}"
                        class="px-3 py-1 text-primary {{ request()->routeIs('cashier.dashboard') ? 'border-b-2 border-primary font-medium' : '' }}">
                        Dashboard
                    </a>
                    <a href="{{ route('cashier.children.index') }}"
                        class="px-3 py-1 text-primary {{ request()->routeIs('cashier.children.*') ? 'border-b-2 border-primary font-medium' : '' }}">
                        Kids
                    </a>
                    <a href="{{ route('cashier.sessions.index') }}"
                        class="px-3 py-1 text-primary {{ request()->routeIs('cashier.sessions.*') ? 'border-b-2 border-primary font-medium' : '' }}">
                        Play
                    </a>
                    <a href="{{ route('cashier.sales.index') }}"
                        class="px-3 py-1 text-primary {{ request()->routeIs('cashier.sales.*') ? 'border-b-2 border-primary font-medium' : '' }}">
                        Sell
                    </a>
                    <a href="{{ route('cashier.complaints.index') }}"
                        class="px-3 py-1 text-primary {{ request()->routeIs('cashier.complaints.*') ? 'border-b-2 border-primary font-medium' : '' }}">
                        Complaints
                    </a>

                    <!-- Shift Management -->
                    <a href="{{ route('cashier.shifts.index') }}"
                        class="px-3 py-1 text-primary {{ request()->routeIs('cashier.shifts.*') ? 'border-b-2 border-primary font-medium' : '' }}">
                        Shifts
                    </a>
                </div>

                <div class="flex items-center space-x-4">
                    <!-- Shift Quick Actions -->
                    @if(isset($activeShift) && $activeShift)
                    <a href="{{ route('cashier.shifts.close', $activeShift) }}"
                        class="inline-flex items-center px-3 py-1 bg-red-100 text-red-700 rounded-md text-sm hover:bg-red-200">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Close Shift
                    </a>
                    @else
                    <a href="{{ route('cashier.shifts.open') }}"
                        class="inline-flex items-center px-3 py-1 bg-green-100 text-green-700 rounded-md text-sm hover:bg-green-200">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                        </svg>
                        Start Shift
                    </a>
                    @endif

                    <!-- Date display -->
                    <div class="flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-primary mr-1" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <span class="text-primary">{{ now()->format('D,d M Y') }}</span>
                    </div>
                </div>
            </div>

            <!-- Page-specific toolbar if needed -->
            @yield('toolbar')
        </header>

        <!-- Page Content -->
        <main class="flex-1 overflow-y-auto bg-gray-50 relative">
            <!-- Alerts -->
            @if(session('success'))
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 m-4">
                {{ session('success') }}
            </div>
            @endif

            @if(session('error'))
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 m-4">
                {{ session('error') }}
            </div>
            @endif

            <!-- Main content -->
            <div class="h-full">
                @yield('content')
            </div>
        </main>
    </div>

    @yield('scripts')
</body>

</html>