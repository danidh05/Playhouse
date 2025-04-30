@extends('layouts.admin-layout')

@section('title', 'Fluffy Puffy')

@section('content')
<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <!-- Active Shifts Today -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-blue-100 text-blue-500">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div class="ml-4">
                <p class="text-gray-500 text-sm font-medium">Active Shifts Today</p>
                <p class="text-2xl font-bold">{{ $activeShiftsCount }}</p>
            </div>
        </div>
        <div class="mt-4">
            <a href="{{ route('admin.dashboard') }}" class="text-blue-500 hover:text-blue-700 text-sm">View Details
                &rarr;</a>
        </div>
    </div>

    <!-- Unresolved Complaints -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-red-100 text-red-500">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            <div class="ml-4">
                <p class="text-gray-500 text-sm font-medium">Unresolved Complaints</p>
                <p class="text-2xl font-bold">{{ $unresolvedComplaintsCount }}</p>
            </div>
        </div>
        <div class="mt-4">
            <a href="{{ route('admin.complaints.index') }}" class="text-red-500 hover:text-red-700 text-sm">View
                Complaints &rarr;</a>
        </div>
    </div>

    <!-- Total Expenses Today -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-green-100 text-green-500">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div class="ml-4">
                <p class="text-gray-500 text-sm font-medium">Expenses Today</p>
                <p class="text-2xl font-bold">${{ number_format($totalExpensesToday, 2) }}</p>
            </div>
        </div>
        <div class="mt-4">
            <a href="{{ route('admin.expenses.index') }}" class="text-green-500 hover:text-green-700 text-sm">View
                Expenses &rarr;</a>
        </div>
    </div>
</div>

<div class="mt-8 bg-white rounded-lg shadow-md p-6">
    <h2 class="text-xl font-bold text-gray-800 mb-4">Quick Links</h2>
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <a href="{{ route('admin.visualizations') }}" class="block p-4 border rounded-lg hover:bg-gray-50">
            <h3 class="font-bold">Visualizations</h3>
            <p class="text-sm text-gray-500">Data insights and charts</p>
        </a>
        <a href="{{ route('admin.products.index') }}" class="block p-4 border rounded-lg hover:bg-gray-50">
            <h3 class="font-bold">Products</h3>
            <p class="text-sm text-gray-500">Manage inventory</p>
        </a>
        <a href="{{ route('admin.addons.index') }}" class="block p-4 border rounded-lg hover:bg-gray-50">
            <h3 class="font-bold">Add-Ons</h3>
            <p class="text-sm text-gray-500">Manage available add-ons</p>
        </a>
        <a href="{{ route('admin.expenses.index') }}" class="block p-4 border rounded-lg hover:bg-gray-50">
            <h3 class="font-bold">Expenses</h3>
            <p class="text-sm text-gray-500">Track and manage expenses</p>
        </a>
        <a href="{{ route('admin.complaints.index') }}" class="block p-4 border rounded-lg hover:bg-gray-50">
            <h3 class="font-bold">Complaints</h3>
            <p class="text-sm text-gray-500">Handle customer complaints</p>
        </a>
        <a href="{{ route('admin.settings.index') }}" class="block p-4 border rounded-lg hover:bg-gray-50">
            <h3 class="font-bold">Settings</h3>
            <p class="text-sm text-gray-500">Manage system settings</p>
        </a>
    </div>
</div>
@endsection