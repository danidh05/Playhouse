<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin Dashboard') - FluffyPuffy</title>
    <script src="https://cdn.tailwindcss.com"></script>
    @yield('styles')
</head>

<body class="bg-gray-100">
    <nav class="bg-blue-600 text-white shadow-lg">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center space-x-4">
                    <a href="{{ route('admin.dashboard') }}" class="text-xl font-bold">FluffyPuffy</a>
                    <div class="hidden md:flex space-x-4">
                        <a href="{{ route('admin.visualizations') }}" class="hover:text-blue-200">Visualizations</a>
                        <a href="{{ route('admin.products.index') }}" class="hover:text-blue-200">Products</a>
                        <a href="{{ route('admin.addons.index') }}" class="hover:text-blue-200">Add-ons</a>
                        <a href="{{ route('admin.complaints.index') }}" class="hover:text-blue-200">Complaints</a>
                        <a href="{{ route('admin.expenses.index') }}" class="hover:text-blue-200">Expenses</a>
                        <a href="{{ route('admin.settings.index') }}" class="hover:text-blue-200">Settings</a>
                    </div>
                </div>
                <div>
                    <form action="{{ route('logout') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="px-4 py-2 bg-blue-700 hover:bg-blue-800 rounded-lg">Logout</button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    <main class="container mx-auto px-4 py-6">
        @if (session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('success') }}
        </div>
        @endif

        @if (session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            {{ session('error') }}
        </div>
        @endif

        @yield('content')
    </main>

    <footer class="bg-white py-4 mt-8 border-t">
        <div class="container mx-auto px-4 text-center text-gray-500">
            &copy; {{ date('Y') }} FluffyPuffy Management System
        </div>
    </footer>

    @yield('scripts')
</body>

</html>