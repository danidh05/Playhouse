@extends('layouts.cashier-layout')

@section('title', 'Dashboard')

@section('content')
<div class="p-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Dashboard</h1>
        <p class="text-gray-600">Welcome to Fluffy Puffy Kids Zone management system</p>
    </div>

    <!-- Session Alerts Section -->
    @if(count($consolidatedAlerts) > 0 && $showAlerts)
    <div class="mb-6">
        <h2 class="text-lg font-bold text-gray-800 mb-3">Session Alerts</h2>

        @foreach($consolidatedAlerts as $alert)
            @if(isset($alert['consolidated']) && $alert['consolidated'])
                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-yellow-700">
                                <span class="font-medium">Multiple Sessions Alert:</span>
                                {{ $alert['count'] }} sessions 
                                @if($alert['type'] == 'planned_ending_soon')
                                    have <span class="font-medium">{{ $alert['minutesRemaining'] }} minutes</span> or less remaining.
                                @elseif($alert['type'] == 'one_hour_mark')
                                    have been running for approximately 1 hour.
                                @elseif($alert['type'] == 'two_hour_mark')
                                    have been running for approximately 2 hours.
                                @endif
                            </p>
                            <div class="mt-2">
                                <a href="{{ route('cashier.sessions.index') }}" 
                                   class="inline-flex items-center px-3 py-1 border border-transparent text-sm leading-4 font-medium rounded-md text-yellow-700 bg-yellow-100 hover:bg-yellow-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500">
                                    View All Sessions
                                </a>
                            </div>
                            <div class="mt-2 text-xs text-gray-600">
                                @foreach($alert['sessions'] as $session)
                                    <div class="inline-block mr-2">
                                        <a href="{{ route('cashier.sessions.show-end', $session->id) }}" class="underline">{{ $session->child->name }}</a>,
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <x-session-expiry-alert :session="$alert['session']" :minutesRemaining="$alert['minutesRemaining']" />
            @endif
        @endforeach
    </div>
    @endif

    <!-- Manage Old Sessions Banner -->
    @if(count($consolidatedAlerts) > 0 && !$showAlerts)
    <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-6">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2h-1V9a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
            </div>
            <div class="ml-3">
                <p class="text-sm text-blue-700">
                    There are active sessions that may need attention. 
                    <a href="{{ route('cashier.sessions.index') }}" class="font-medium underline text-blue-700 hover:text-blue-600 mr-2">
                        View all active sessions
                    </a> or 
                    <a href="{{ route('cashier.sessions.show-close-old') }}" class="font-medium underline text-yellow-600 hover:text-yellow-700">
                        Close old sessions
                    </a>
                </p>
            </div>
        </div>
    </div>
    @endif

    <!-- Shift Status Section -->
    @if($activeShift)
    <div class="bg-green-50 border border-green-200 rounded-xl p-6 mb-8">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <div class="p-3 bg-green-100 rounded-full mr-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-green-600" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-green-800">Active Shift</h2>
                    <p class="text-green-700">
                        Started: {{ $activeShift->opened_at->format('M d, Y - H:i') }} |
                        @php
                        $duration = $activeShift->opened_at->diffAsCarbonInterval(now())->cascade();
                        @endphp
                        Duration: {{ $duration->hours }}h {{ $duration->minutes }}m
                    </p>
                    <p class="text-green-700">
                        Shift Type: {{ ucfirst($activeShift->type) }}
                    </p>
                </div>
            </div>
            <div class="flex space-x-3">
                <a href="{{ route('cashier.shifts.report', $activeShift) }}"
                    class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    View Report
                </a>

            </div>
        </div>
    </div>
    @else
    <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-6 mb-8">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <div class="p-3 bg-yellow-100 rounded-full mr-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-yellow-600" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-yellow-800">No Active Shift</h2>
                    <p class="text-yellow-700">You need to start a shift before you can process any transactions.</p>
                </div>
            </div>
            <div>
                <a href="{{ route('cashier.shifts.open') }}"
                    class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                    </svg>
                    Start Shift
                </a>
            </div>
        </div>
    </div>
    @endif

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <!-- Revenue Card -->
        <div class="bg-white rounded-xl shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-700">Today's Revenue</h2>
                <div class="p-2 bg-green-100 rounded-full">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-green-600" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
            <div class="space-y-2">
                <!-- USD Revenue -->
                @if($todayRevenue['usd_total'] > 0)
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">USD:</span>
                    <span class="text-xl font-bold text-gray-800">${{ number_format($todayRevenue['usd_total'], 2) }}</span>
                </div>
                @endif
                
                <!-- LBP Revenue -->
                @if($todayRevenue['lbp_total'] > 0)
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">LBP:</span>
                    <span class="text-xl font-bold text-gray-800">{{ number_format($todayRevenue['lbp_total'], 0) }} L.L</span>
                </div>
                @endif
                
                <!-- Total USD Equivalent -->
                <div class="border-t pt-2 mt-2">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500">Total (USD equiv.):</span>
                        <div class="flex items-center">
                            <span class="text-2xl font-bold text-gray-800">${{ number_format($todayRevenue['total_usd_equivalent'], 2) }}</span>
                            <span class="ml-2 text-sm {{ $revenueGrowth >= 0 ? 'text-green-500' : 'text-red-500' }} flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="{{ $revenueGrowth >= 0 ? 'M5 15l7-7 7 7' : 'M19 9l-7 7-7-7' }}" />
                                </svg>
                                {{ number_format(abs($revenueGrowth), 1) }}%
                            </span>
                        </div>
                    </div>
                </div>
                
                <!-- Show message if no revenue -->
                @if($todayRevenue['usd_total'] == 0 && $todayRevenue['lbp_total'] == 0)
                <div class="text-center py-2">
                    <span class="text-2xl font-bold text-gray-400">$0.00</span>
                    <p class="text-sm text-gray-500">No revenue today</p>
                </div>
                @endif
            </div>
            <p class="text-gray-500 text-sm mt-2">Compared to yesterday</p>
        </div>

        <!-- Play Sessions Card -->
        <div class="bg-white rounded-xl shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-700">Play Sessions</h2>
                <div class="p-2 bg-primary-light rounded-full">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-primary" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
            <div class="flex items-end">
                <span class="text-3xl font-bold text-gray-800">{{ $todaySessions }}</span>
                <span
                    class="ml-2 text-sm {{ $sessionsGrowth >= 0 ? 'text-green-500' : 'text-red-500' }} flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="{{ $sessionsGrowth >= 0 ? 'M5 15l7-7 7 7' : 'M19 9l-7 7-7-7' }}" />
                    </svg>
                    {{ number_format(abs($sessionsGrowth), 1) }}%
                </span>
            </div>
            <p class="text-gray-500 text-sm mt-2">Compared to yesterday</p>
        </div>

        <!-- Complaints Card -->
        <div class="bg-white rounded-xl shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-700">Complaints</h2>
                <div class="p-2 bg-red-100 rounded-full">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
            <div class="flex items-end">
                <span class="text-3xl font-bold text-gray-800">{{ $todayComplaints }}</span>
                <span
                    class="ml-2 text-sm {{ $complaintsGrowth <= 0 ? 'text-green-500' : 'text-red-500' }} flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="{{ $complaintsGrowth <= 0 ? 'M5 15l7-7 7 7' : 'M19 9l-7 7-7-7' }}" />
                    </svg>
                    {{ number_format(abs($complaintsGrowth), 1) }}%
                </span>
            </div>
            <p class="text-gray-500 text-sm mt-2">Compared to yesterday</p>
        </div>
    </div>

    <!-- Recent Activities -->
    <div class="bg-white rounded-xl shadow-sm">
        <div class="border-b border-gray-200 p-6">
            <div class="flex items-center">
                <div class="p-2 bg-blue-100 rounded-full mr-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-primary" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h2 class="text-lg font-semibold text-gray-700">Recent Activities</h2>
            </div>
        </div>

        <div class="p-6">
            @if(count($recentActivities) > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Type
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Details</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Time
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($recentActivities as $activity)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    @if($activity->type == 'session')
                                    <div class="bg-primary-light p-2 rounded-full mr-3">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-primary" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                        </svg>
                                    </div>
                                    <span class="font-medium text-gray-900">Play Session</span>
                                    @elseif($activity->type == 'sale')
                                    <div class="bg-green-100 p-2 rounded-full mr-3">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-600"
                                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                                        </svg>
                                    </div>
                                    <span class="font-medium text-gray-900">Sale</span>
                                    @elseif($activity->type == 'complaint')
                                    <div class="bg-red-100 p-2 rounded-full mr-3">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-600" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    <span class="font-medium text-gray-900">Complaint</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $activity->details }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $activity->created_at->diffForHumans() }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="text-center py-8">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-gray-400 mx-auto mb-4" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <h3 class="text-lg font-medium text-gray-900 mb-1">No Recent Activities</h3>
                <p class="text-gray-500">There are no recent activities to show.</p>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection