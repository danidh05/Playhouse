@extends('layouts.cashier-layout')

@section('title', 'Play Sessions')

@section('content')
<div class="p-6">
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Play Sessions</h1>
            <p class="text-gray-600">Manage active and past play sessions</p>
        </div>
        <a href="{{ route('cashier.sessions.create') }}"
            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Start New Session
        </a>
    </div>

    <!-- Active Sessions -->
    <div class="bg-white rounded-lg shadow-md mb-8">
        <div class="border-b border-gray-200 p-6">
            <div class="flex items-center">
                <div class="p-2 bg-green-100 rounded-full mr-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-600" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h2 class="text-lg font-semibold text-gray-700">Active Sessions ({{ count($activeSessions) }})</h2>
            </div>
        </div>

        <div class="p-6">
            @if(count($activeSessions) > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Child</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Guardian</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Start Time</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Duration</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($activeSessions as $session)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div
                                        class="flex-shrink-0 h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                                        <span
                                            class="text-blue-800 font-medium">{{ substr($session->child->name, 0, 1) }}</span>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900">{{ $session->child->name }}</div>
                                        <div class="text-sm text-gray-500">{{ $session->child->birth_date->age }} years
                                            old</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $session->child->guardian_name }}</div>
                                <div class="text-sm text-gray-500">{{ $session->child->guardian_phone }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $session->start_time->format('h:i A') }}</div>
                                <div class="text-sm text-gray-500">{{ $session->start_time->format('M d, Y') }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">
                                    {{ $session->start_time->diffForHumans(null, true) }}
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2.5 mt-2">
                                    @php
                                    $duration = $session->start_time->diffAsCarbonInterval(now())->cascade();
                                    $minutesTotal = ($duration->hours * 60) + $duration->minutes;
                                    $plannedMinutes = $session->planned_hours * 60;
                                    $percentage = ($plannedMinutes > 0) ? min(100, ($minutesTotal / $plannedMinutes) *
                                    100) : 100;
                                    $bgColor = $percentage > 80 ? 'bg-red-600' : ($percentage > 50 ? 'bg-yellow-500' :
                                    'bg-green-600');
                                    @endphp
                                    <div class="{{ $bgColor }} h-2.5 rounded-full" style="width: {{ $percentage }}%">
                                    </div>
                                </div>
                                @if($session->planned_hours > 0)
                                @php
                                $minutesRemaining = max(0, $plannedMinutes - $minutesTotal);
                                $hoursRemaining = floor($minutesRemaining / 60);
                                $minutesRemainder = $minutesRemaining % 60;
                                @endphp
                                <div class="text-xs mt-1 text-right">
                                    @if($minutesRemaining <= 0) <span class="text-red-600 font-medium">Time
                                        exceeded</span>
                                        @else
                                        <span
                                            class="{{ $percentage > 80 ? 'text-red-600' : ($percentage > 50 ? 'text-yellow-600' : 'text-green-600') }} font-medium">
                                            Time left: {{ $hoursRemaining }}h {{ $minutesRemainder }}m
                                        </span>
                                        @endif
                                </div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <a href="{{ route('cashier.sessions.show-end', $session->id) }}"
                                    class="text-white bg-purple-600 hover:bg-purple-700 py-2 px-4 rounded-md">
                                    End Session
                                </a>
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
                <h3 class="text-lg font-medium text-gray-900 mb-1">No Active Sessions</h3>
                <p class="text-gray-500">There are no active play sessions at the moment.</p>
            </div>
            @endif
        </div>
    </div>

    <!-- Recent Sessions -->
    <div class="bg-white rounded-lg shadow-md">
        <div class="border-b border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="p-2 bg-blue-100 rounded-full mr-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-600" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h2 class="text-lg font-semibold text-gray-700">Recent Sessions</h2>
                </div>

                <form action="{{ route('cashier.sessions.index') }}" method="GET" class="flex items-center space-x-2">
                    <select name="filter"
                        class="border border-gray-300 rounded-md shadow-sm py-2 px-3 bg-white text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="today" {{ request('filter') == 'today' ? 'selected' : '' }}>Today</option>
                        <option value="week" {{ request('filter') == 'week' ? 'selected' : '' }}>This Week</option>
                        <option value="month" {{ request('filter') == 'month' ? 'selected' : '' }}>This Month</option>
                        <option value="all" {{ request('filter') == 'all' ? 'selected' : '' }}>All Time</option>
                    </select>
                    <button type="submit" class="bg-gray-200 hover:bg-gray-300 text-gray-700 py-2 px-4 rounded-md">
                        Filter
                    </button>
                </form>
            </div>
        </div>

        <div class="p-6">
            @if(count($recentSessions) > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Child</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Guardian</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Date & Time</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Duration</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Amount</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($recentSessions as $session)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div
                                        class="flex-shrink-0 h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                                        <span
                                            class="text-blue-800 font-medium">{{ substr($session->child->name, 0, 1) }}</span>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900">{{ $session->child->name }}</div>
                                        <div class="text-sm text-gray-500">{{ $session->child->birth_date->age }} years
                                            old</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $session->child->guardian_name }}</div>
                                <div class="text-sm text-gray-500">{{ $session->child->guardian_phone }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $session->start_time->format('h:i A') }} -
                                    {{ $session->end_time->format('h:i A') }}</div>
                                <div class="text-sm text-gray-500">{{ $session->start_time->format('M d, Y') }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">
                                    @php
                                    $sessionDuration =
                                    $session->start_time->diffAsCarbonInterval($session->end_time)->cascade();
                                    @endphp
                                    {{ $sessionDuration->hours }}h {{ $sessionDuration->minutes }}m
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">
                                    @if($session->payment_method === 'LBP')
                                    {{ number_format($session->total_cost * config('play.lbp_exchange_rate', 90000)) }}
                                    L.L
                                    @else
                                    ${{ number_format($session->total_cost, 2) }}
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <a href="{{ route('cashier.sessions.show', $session) }}"
                                    class="text-primary hover:text-primary-dark">
                                    View Details
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $recentSessions->links() }}
            </div>
            @else
            <div class="text-center py-8">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-gray-400 mx-auto mb-4" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <h3 class="text-lg font-medium text-gray-900 mb-1">No Recent Sessions</h3>
                <p class="text-gray-500">There are no recent play sessions.</p>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection