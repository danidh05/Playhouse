@extends('layouts.cashier-layout')

@section('title', 'Shift Report')

@section('content')
<div class="bg-white rounded-lg shadow-md p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Shift Report</h1>
        <a href="{{ route('cashier.dashboard') }}" class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">
            Back to Dashboard
        </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <div class="bg-gray-50 p-4 rounded-lg">
            <h2 class="text-lg font-semibold mb-3">Shift Information</h2>
            <div class="space-y-2">
                <p><span class="font-medium">Date:</span> {{ $shift->date->format('M d, Y') }}</p>
                <p><span class="font-medium">Type:</span> {{ ucfirst($shift->type) }}</p>
                <p><span class="font-medium">Started at:</span> {{ $shift->opened_at->format('H:i') }}</p>
                @if($shift->closed_at)
                <p><span class="font-medium">Ended at:</span> {{ $shift->closed_at->format('H:i') }}</p>
                @php
                $duration = $shift->opened_at->diffAsCarbonInterval($shift->closed_at)->cascade();
                @endphp
                <p><span class="font-medium">Duration:</span> {{ $duration->hours }}h {{ $duration->minutes }}m</p>
                @else
                <p><span class="text-yellow-600 font-medium">Status:</span> <span class="text-yellow-600">Currently
                        Open</span></p>
                @endif
            </div>
        </div>

        <div class="bg-gray-50 p-4 rounded-lg">
            <h2 class="text-lg font-semibold mb-3">Financial Summary</h2>
            <div class="space-y-2">
                @if($currencyBreakdown['total_lbp'] > 0)
                <p><span class="font-medium">LBP Revenue:</span> {{ number_format($currencyBreakdown['total_lbp'], 0) }} L.L</p>
                @endif
                @if($currencyBreakdown['total_usd'] > 0)
                <p><span class="font-medium">USD Revenue:</span> ${{ number_format($currencyBreakdown['total_usd'], 2) }}</p>
                @endif
                <p class="text-gray-600"><span class="font-medium">USD Equivalent:</span> ${{ number_format($totalRevenue, 2) }}</p>
                
                <div class="mt-3 pt-2 border-t border-gray-200">
                    <p class="text-sm"><span class="font-medium">Play Sessions:</span> {{ $playSessionSales->count() }} transactions</p>
                    <p class="text-sm"><span class="font-medium">Product Sales:</span> {{ $productSales->count() }} transactions</p>
                </div>
            </div>
            <div class="mt-3 text-xs text-gray-600">
                <p>* Revenue displayed by currency to avoid mixing LBP and USD amounts.</p>
            </div>
        </div>
    </div>

    @if(count($playSessionSales) > 0)
    <div class="mb-6">
        <h2 class="text-lg font-semibold mb-3">Play Sessions</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Child
                        </th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Started</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ended
                        </th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Duration</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Amount Paid</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Payment</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($playSessionSales as $sale)
                    <tr>
                        <td class="px-4 py-2">{{ $sale->child ? $sale->child->name : 'Unknown' }}</td>
                        <td class="px-4 py-2">
                            {{ $sale->play_session ? $sale->play_session->started_at->format('H:i') : 'N/A' }}</td>
                        <td class="px-4 py-2">
                            {{ $sale->play_session && $sale->play_session->ended_at ? $sale->play_session->ended_at->format('H:i') : 'N/A' }}
                        </td>
                        <td class="px-4 py-2">
                            @if($sale->play_session && $sale->play_session->ended_at)
                            @php
                            $sessionDuration =
                            $sale->play_session->started_at->diffAsCarbonInterval($sale->play_session->ended_at)->cascade();
                            @endphp
                            {{ $sessionDuration->hours }}h {{ $sessionDuration->minutes }}m
                            @else
                            N/A
                            @endif
                        </td>
                        <td class="px-4 py-2">
                            @if($sale->play_session)
                            @php
                            // Use total_cost if available, otherwise fall back to amount_paid for old records
                            $displayAmount = $sale->play_session->total_cost ?? $sale->play_session->amount_paid ?? 0;
                            @endphp
                            @if($displayAmount > 0)
                            @if($sale->payment_method === 'LBP')
                            {{ number_format($displayAmount) }} L.L
                            @else
                            ${{ number_format($displayAmount, 2) }}
                            @endif
                            @else
                            <span class="text-red-600">Not Calculated</span>
                            @endif
                            @else
                            <span class="text-red-600">No Session</span>
                            @endif
                        </td>
                        <td class="px-4 py-2">{{ $sale->payment_method }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-gray-50">
                    <tr>
                        <td colspan="4" class="px-4 py-2 text-right font-medium">Play Sessions Total:</td>
                        <td class="px-4 py-2 font-medium">
                            @if($currencyBreakdown['sessions_lbp'] > 0 && $currencyBreakdown['sessions_usd'] > 0)
                                {{ number_format($currencyBreakdown['sessions_lbp']) }} L.L + ${{ number_format($currencyBreakdown['sessions_usd'], 2) }}
                            @elseif($currencyBreakdown['sessions_lbp'] > 0)
                                {{ number_format($currencyBreakdown['sessions_lbp']) }} L.L
                            @else
                                ${{ number_format($currencyBreakdown['sessions_usd'], 2) }}
                            @endif
                        </td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    @endif

    @if(count($productSales) > 0)
    <div>
        <h2 class="text-lg font-semibold mb-3">Product Sales</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Product</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Quantity</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Unit
                            Price</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Amount Paid</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Payment</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($productSales as $sale)
                    <tr>
                        <td class="px-4 py-2">
                            @foreach($sale->items as $item)
                            {{ $item->product ? $item->product->name : 'Unknown Product' }}{{ !$loop->last ? ', ' : '' }}
                            @endforeach
                        </td>
                        <td class="px-4 py-2">
                            @foreach($sale->items as $item)
                            {{ $item->quantity }}
                            @endforeach
                        </td>
                        <td class="px-4 py-2">
                            @foreach($sale->items as $item)
                            @if($sale->payment_method === 'LBP')
                            {{ number_format($item->unit_price) }} L.L
                            @else
                            ${{ number_format($item->unit_price, 2) }}
                            @endif
                            @endforeach
                        </td>
                        <td class="px-4 py-2">
                            @if($sale->amount_paid)
                            @if($sale->payment_method === 'LBP')
                            {{ number_format($sale->amount_paid) }} L.L
                            @else
                            ${{ number_format($sale->amount_paid, 2) }}
                            @endif
                            @else
                            <span class="text-red-600">Not Paid</span>
                            @endif
                        </td>
                        <td class="px-4 py-2">{{ $sale->payment_method }}</td>
                        <td class="px-4 py-2">{{ $sale->created_at->format('H:i') }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-gray-50">
                    <tr>
                        <td colspan="3" class="px-4 py-2 text-right font-medium">Product Sales Total:</td>
                        <td class="px-4 py-2 font-medium">
                            @if($currencyBreakdown['sales_lbp'] > 0 && $currencyBreakdown['sales_usd'] > 0)
                                {{ number_format($currencyBreakdown['sales_lbp']) }} L.L + ${{ number_format($currencyBreakdown['sales_usd'], 2) }}
                            @elseif($currencyBreakdown['sales_lbp'] > 0)
                                {{ number_format($currencyBreakdown['sales_lbp']) }} L.L
                            @else
                                ${{ number_format($currencyBreakdown['sales_usd'], 2) }}
                            @endif
                        </td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
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