@extends('layouts.admin-layout')

@section('title', 'Shift Details')

@section('content')
<div class="bg-white rounded-lg shadow-md p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Shift Details</h1>
        <a href="{{ route('admin.shifts.index') }}" class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">
            Back to Shifts
        </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <div class="bg-gray-50 p-4 rounded-lg">
            <h2 class="text-lg font-semibold mb-3">Shift Information</h2>
            <div class="space-y-2">
                <p><span class="font-medium">Date:</span> {{ $shift->date->format('M d, Y') }}</p>
                <p><span class="font-medium">Type:</span> {{ ucfirst($shift->type) }}</p>
                <p><span class="font-medium">Cashier:</span> {{ $shift->cashier->name }}</p>
                <p><span class="font-medium">Opened at:</span> {{ $shift->opened_at->format('H:i') }}</p>
                @if($shift->closed_at)
                    <p><span class="font-medium">Closed at:</span> {{ $shift->closed_at->format('H:i') }}</p>
                    @php
                        $duration = $shift->opened_at->diffAsCarbonInterval($shift->closed_at)->cascade();
                    @endphp
                    <p><span class="font-medium">Duration:</span> {{ $duration->hours }}h {{ $duration->minutes }}m</p>
                @else
                    <p><span class="text-yellow-600 font-medium">Status:</span> <span class="text-yellow-600">Currently Open</span></p>
                @endif
            </div>
        </div>

        <div class="bg-gray-50 p-4 rounded-lg">
            <h2 class="text-lg font-semibold mb-3">Financial Summary</h2>
            <div class="space-y-2">
                <p><span class="font-medium">Total Revenue:</span> ${{ number_format($totalRevenue ?? 0, 2) }}</p>
                <p><span class="font-medium">- Play Sessions:</span> ${{ number_format($sessionsTotal ?? 0, 2) }}</p>
                <p><span class="font-medium">- Sales:</span> ${{ number_format($salesTotal ?? 0, 2) }}</p>
                
                @if(isset($cashVariance))
                    <p class="mt-4 pt-2 border-t border-gray-200">
                        <span class="font-medium">Cash Variance:</span> 
                        <span class="{{ $cashVariance >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            ${{ number_format(abs($cashVariance), 2) }} {{ $cashVariance >= 0 ? 'over' : 'short' }}
                        </span>
                    </p>
                @endif
            </div>
        </div>
    </div>

    <div class="mb-6">
        <h2 class="text-lg font-semibold mb-3">Payment Methods</h2>
        <div class="bg-gray-50 p-4 rounded-lg">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <h3 class="font-medium">Cash</h3>
                    <p>Sessions: ${{ number_format($cashSessions ?? 0, 2) }}</p>
                    <p>Sales: ${{ number_format($cashSales ?? 0, 2) }}</p>
                    <p class="font-semibold mt-1">Total: ${{ number_format(($cashSessions ?? 0) + ($cashSales ?? 0), 2) }}</p>
                </div>
                <div>
                    <h3 class="font-medium">Card</h3>
                    <p>Sessions: ${{ number_format($cardSessions ?? 0, 2) }}</p>
                    <p>Sales: ${{ number_format($cardSales ?? 0, 2) }}</p>
                    <p class="font-semibold mt-1">Total: ${{ number_format(($cardSessions ?? 0) + ($cardSales ?? 0), 2) }}</p>
                </div>
                <div>
                    <h3 class="font-medium">Other Methods</h3>
                    <p>Sessions: ${{ number_format($otherSessions ?? 0, 2) }}</p>
                    <p>Sales: ${{ number_format($otherSales ?? 0, 2) }}</p>
                    <p class="font-semibold mt-1">Total: ${{ number_format(($otherSessions ?? 0) + ($otherSales ?? 0), 2) }}</p>
                </div>
            </div>
        </div>
    </div>

    @if(count($sessions) > 0)
    <div class="mb-6">
        <h2 class="text-lg font-semibold mb-3">Play Sessions ({{ count($sessions) }})</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Child</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Started</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ended</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Duration</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payment</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cost</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($sessions as $session)
                    <tr>
                        <td class="px-4 py-2">{{ $session->child->name }}</td>
                        <td class="px-4 py-2">{{ $session->started_at->format('H:i') }}</td>
                        <td class="px-4 py-2">{{ $session->ended_at ? $session->ended_at->format('H:i') : 'Active' }}</td>
                        <td class="px-4 py-2">
                            @if($session->ended_at)
                                @php
                                    $sessionDuration = $session->started_at->diffAsCarbonInterval($session->ended_at)->cascade();
                                @endphp
                                {{ $sessionDuration->hours }}h {{ $sessionDuration->minutes }}m
                            @else
                                N/A
                            @endif
                        </td>
                        <td class="px-4 py-2">{{ $session->payment_method ?? 'Pending' }}</td>
                        <td class="px-4 py-2">${{ number_format($session->total_cost, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    @if(count($sales) > 0)
    <div>
        <h2 class="text-lg font-semibold mb-3">Sales ({{ count($sales) }})</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Unit Price</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payment</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($sales as $sale)
                    <tr>
                        <td class="px-4 py-2">{{ $sale->product->name }}</td>
                        <td class="px-4 py-2">{{ $sale->quantity }}</td>
                        <td class="px-4 py-2">${{ number_format($sale->unit_price, 2) }}</td>
                        <td class="px-4 py-2">${{ number_format($sale->total_price, 2) }}</td>
                        <td class="px-4 py-2">{{ $sale->payment_method }}</td>
                        <td class="px-4 py-2">{{ $sale->created_at->format('H:i') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    @if($shift->notes)
    <div class="mt-6 p-4 bg-yellow-50 rounded-lg">
        <h2 class="text-lg font-semibold mb-2">Notes</h2>
        <p>{{ $shift->notes }}</p>
    </div>
    @endif
</div>
@endsection 