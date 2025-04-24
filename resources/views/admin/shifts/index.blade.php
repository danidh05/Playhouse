@extends('layouts.admin-layout')

@section('title', 'Shifts Management')

@section('content')
<div class="bg-white rounded-lg shadow-md p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Shifts Management</h1>
        <div>
            <form action="{{ route('admin.shifts.index') }}" method="GET" class="flex items-center">
                <div class="mr-2">
                    <input type="date" name="date" id="date" value="{{ request('date') }}" class="border rounded py-2 px-3">
                </div>
                <div class="mr-2">
                    <select name="cashier_id" id="cashier_id" class="border rounded py-2 px-3">
                        <option value="">All Cashiers</option>
                        @foreach($cashiers as $cashier)
                        <option value="{{ $cashier->id }}" {{ request('cashier_id') == $cashier->id ? 'selected' : '' }}>
                            {{ $cashier->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="bg-blue-600 text-white py-2 px-4 rounded hover:bg-blue-700">
                    Filter
                </button>
            </form>
        </div>
    </div>

    @if($shifts->isEmpty())
    <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-4">
        No shifts found.
    </div>
    @else
    <div class="overflow-x-auto">
        <table class="min-w-full bg-white">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cashier</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Started</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ended</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Opening</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Closing</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach($shifts as $shift)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap">{{ $shift->date->format('M d, Y') }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">{{ ucfirst($shift->type) }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">{{ $shift->cashier->name }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">{{ $shift->opened_at->format('H:i') }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">{{ $shift->closed_at ? $shift->closed_at->format('H:i') : 'Active' }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">${{ number_format($shift->opening_amount, 2) }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">{{ $shift->closed_at ? '$'.number_format($shift->closing_amount, 2) : '-' }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                            {{ $shift->closed_at ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' }}">
                            {{ $shift->closed_at ? 'Closed' : 'Open' }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <a href="{{ route('admin.shifts.show', $shift) }}" class="text-indigo-600 hover:text-indigo-900">
                            View Details
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-4">
        {{ $shifts->links() }}
    </div>
    @endif
</div>
@endsection 