@extends('layouts.cashier-layout')

@section('title', 'Shifts History')

@section('content')
<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Your Shifts</h1>
            <p class="text-gray-600">View and manage your work shifts</p>
        </div>
        <a href="{{ route('cashier.shifts.open') }}"
            class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 active:bg-green-800 focus:outline-none focus:border-green-800 focus:ring ring-green-300 transition ease-in-out duration-150">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
            Start New Shift
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="border-b border-gray-200 p-4 bg-gray-50">
            <h2 class="text-lg font-semibold text-gray-700">Shift History</h2>
        </div>

        @if(count($shifts) > 0)
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date
                        </th>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type
                        </th>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Started</th>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ended
                        </th>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Duration</th>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Status</th>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($shifts as $shift)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            {{ $shift->date->format('M d, Y') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            {{ ucfirst($shift->type) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            {{ $shift->opened_at->format('H:i') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            {{ $shift->closed_at ? $shift->closed_at->format('H:i') : '-' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($shift->closed_at)
                            @php
                            $duration = $shift->opened_at->diffAsCarbonInterval($shift->closed_at)->cascade();
                            @endphp
                            {{ $duration->hours }}h {{ $duration->minutes }}m
                            @else
                            @php
                            $duration = $shift->opened_at->diffAsCarbonInterval(now())->cascade();
                            @endphp
                            {{ $duration->hours }}h {{ $duration->minutes }}m
                            @endif
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap">
                            <span
                                class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $shift->closed_at ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' }}">
                                {{ $shift->closed_at ? 'Closed' : 'Active' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex space-x-2">
                                <a href="{{ route('cashier.shifts.report', $shift) }}"
                                    class="text-blue-600 hover:text-blue-900">Report</a>

                                @if(!$shift->closed_at)
                                <a href="{{ route('cashier.shifts.close', $shift) }}"
                                    class="text-red-600 hover:text-red-900">Close</a>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="px-6 py-3 bg-gray-50">
            {{ $shifts->links() }}
        </div>
        @else
        <div class="text-center py-12">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-gray-400 mx-auto mb-4" fill="none"
                viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <h3 class="text-lg font-medium text-gray-900 mb-1">No Shifts Found</h3>
            <p class="text-gray-500 mb-4">You haven't created any shifts yet.</p>
            <a href="{{ route('cashier.shifts.open') }}"
                class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                Start Your First Shift
            </a>
        </div>
        @endif
    </div>
</div>
@endsection