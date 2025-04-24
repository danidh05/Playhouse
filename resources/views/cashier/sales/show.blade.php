@extends('layouts.cashier-layout')

@section('title', 'Sale Details')

@section('toolbar')
<!-- Sale details specific toolbar -->
<div class="px-4 py-2 bg-white border-b flex justify-between items-center">
    <div>
        <a href="{{ route('cashier.sales.index') }}"
            class="px-3 py-1 text-xs bg-gray-200 text-gray-700 rounded flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Back to Sales
        </a>
    </div>

    <div>
        <button onclick="window.print()" class="px-3 py-1 text-xs bg-indigo-500 text-white rounded flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
            </svg>
            Print Receipt
        </button>
    </div>
</div>
@endsection

@section('content')
<div class="max-w-4xl mx-auto p-6">
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <!-- Sale Header -->
        <div class="border-b p-6">
            <div class="flex justify-between items-start">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Sale #{{ $sale->id }}</h1>
                    <p class="text-gray-500">{{ $sale->created_at->format('F j, Y g:i A') }}</p>
                    <p
                        class="mt-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                        {{ $sale->payment_method === 'LBP' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800' }}">
                        {{ $sale->payment_method }}
                    </p>
                </div>
                <div class="text-right">
                    <p class="text-sm text-gray-500">Cashier:</p>
                    <p class="font-medium">{{ $sale->user->name }}</p>
                    <p class="text-sm text-gray-500 mt-2">Shift:</p>
                    <p class="font-medium">{{ $sale->shift->date->format('M d, Y') }} ({{ $sale->shift->type }})</p>
                </div>
            </div>
        </div>

        <!-- Sale Items -->
        <div class="p-6">
            <h2 class="text-lg font-medium text-gray-800 mb-4">Items</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr>
                            <th
                                class="px-4 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Item</th>
                            <th
                                class="px-4 py-3 bg-gray-50 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Price</th>
                            <th
                                class="px-4 py-3 bg-gray-50 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Qty</th>
                            <th
                                class="px-4 py-3 bg-gray-50 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Subtotal</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($sale->items as $item)
                        <tr>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div>
                                    <div class="text-sm font-medium text-gray-900">{{ $item->product->name }}</div>
                                    <div class="text-xs text-gray-500">Product ID: {{ $item->product->id }}</div>
                                </div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 text-right">
                                @if($sale->payment_method === 'LBP')
                                {{ number_format($item->unit_price * config('play.lbp_exchange_rate', 90000)) }} L.L
                                @else
                                ${{ number_format($item->unit_price, 2) }}
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 text-right">
                                {{ $item->quantity }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900 text-right">
                                @if($sale->payment_method === 'LBP')
                                {{ number_format($item->subtotal * config('play.lbp_exchange_rate', 90000)) }} L.L
                                @else
                                ${{ number_format($item->subtotal, 2) }}
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="px-4 py-3 text-sm font-medium text-gray-900 text-right">Total:</td>
                            <td class="px-4 py-3 whitespace-nowrap text-base font-bold text-gray-900 text-right">
                                @if($sale->payment_method === 'LBP')
                                {{ number_format($sale->total_amount) }} L.L
                                @else
                                ${{ number_format($sale->total_amount, 2) }}
                                @endif
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Payment Details -->
        <div class="border-t p-6 bg-gray-50">
            <h2 class="text-lg font-medium text-gray-800 mb-4">Payment Details</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <p class="text-sm text-gray-500">Payment Method:</p>
                    <p class="font-medium">{{ $sale->payment_method }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Total Amount:</p>
                    <p class="font-medium">
                        @if($sale->payment_method === 'LBP')
                        {{ number_format($sale->total_amount) }} L.L
                        @else
                        ${{ number_format($sale->total_amount, 2) }}
                        @endif
                    </p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Amount Paid:</p>
                    <p class="font-medium">
                        @if($sale->payment_method === 'LBP')
                        {{ number_format($sale->amount_paid) }} L.L
                        @else
                        ${{ number_format($sale->amount_paid, 2) }}
                        @endif
                    </p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Change:</p>
                    <p class="font-medium text-green-600">
                        @if($sale->payment_method === 'LBP')
                        {{ number_format($sale->change_given) }} L.L
                        @else
                        ${{ number_format($sale->change_given, 2) }}
                        @endif
                    </p>
                </div>
            </div>
        </div>

        <!-- Customer Information (if available) -->
        @if($sale->child_id)
        <div class="border-t p-6">
            <h2 class="text-lg font-medium text-gray-800 mb-4">Customer Information</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <p class="text-sm text-gray-500">Name:</p>
                    <p class="font-medium">{{ $sale->child->name }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Age:</p>
                    <p class="font-medium">{{ $sale->child->age ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Guardian:</p>
                    <p class="font-medium">{{ $sale->child->guardian_name ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Phone:</p>
                    <p class="font-medium">{{ $sale->child->phone ?? 'N/A' }}</p>
                </div>
            </div>
        </div>
        @endif

        <!-- Play Session (if available) -->
        @if($sale->play_session_id)
        <div class="border-t p-6">
            <h2 class="text-lg font-medium text-gray-800 mb-4">Play Session Details</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <p class="text-sm text-gray-500">Start Time:</p>
                    <p class="font-medium">{{ $sale->play_session->started_at->format('g:i A') }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">End Time:</p>
                    <p class="font-medium">
                        @if($sale->play_session->ended_at)
                        {{ $sale->play_session->ended_at->format('g:i A') }}
                        @else
                        <span
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">In
                            Progress</span>
                        @endif
                    </p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Duration:</p>
                    <p class="font-medium">
                        @if($sale->play_session->ended_at)
                        @php
                        $sessionDuration =
                        $sale->play_session->started_at->diffAsCarbonInterval($sale->play_session->ended_at)->cascade();
                        @endphp
                        @if($sessionDuration->hours == 0 && $sessionDuration->minutes == 0)
                        Less than a minute
                        @else
                        {{ $sessionDuration->hours }}h {{ $sessionDuration->minutes }}m
                        @endif
                        @else
                        @php
                        $sessionDuration = $sale->play_session->started_at->diffAsCarbonInterval(now())->cascade();
                        @endphp
                        @if($sessionDuration->hours == 0 && $sessionDuration->minutes == 0)
                        Just started
                        @else
                        {{ $sessionDuration->hours }}h {{ $sessionDuration->minutes }}m (ongoing)
                        @endif
                        @endif
                    </p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Planned Hours:</p>
                    <p class="font-medium">
                        @if($sale->play_session->planned_hours > 0)
                        {{ $sale->play_session->planned_hours }}
                        @else
                        Unspecified
                        @endif
                    </p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Billed Hours:</p>
                    <p class="font-medium">
                        @if($sale->play_session->actual_hours > 0)
                        {{ $sale->play_session->actual_hours }}
                        @elseif($sale->play_session->planned_hours > 0)
                        {{ $sale->play_session->planned_hours }} (planned)
                        @elseif(!$sale->play_session->ended_at)
                        In progress
                        @else
                        Unspecified
                        @endif
                    </p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Hourly Rate:</p>
                    <p class="font-medium">
                        @if($sale->payment_method === 'LBP')
                        {{ number_format(config('play.hourly_rate', 10.00) * config('play.lbp_exchange_rate', 90000)) }}
                        L.L
                        @else
                        ${{ number_format(config('play.hourly_rate', 10.00), 2) }}
                        @endif
                    </p>
                </div>
                @if($sale->play_session->discount_pct > 0)
                <div>
                    <p class="text-sm text-gray-500">Discount:</p>
                    <p class="font-medium text-green-600">{{ $sale->play_session->discount_pct }}%</p>
                </div>
                @endif
            </div>

            <!-- Add-ons for play session (if any) -->
            @if($sale->play_session->addOns->count() > 0)
            <div class="mt-4">
                <h3 class="text-md font-medium text-gray-700 mb-2">Add-ons</h3>
                <ul class="space-y-1">
                    @foreach($sale->play_session->addOns as $addOn)
                    <li class="text-sm">
                        {{ $addOn->pivot->qty }}x {{ $addOn->name }} -
                        @if($sale->payment_method === 'LBP')
                        {{ number_format($addOn->pivot->subtotal * config('play.lbp_exchange_rate', 90000)) }} L.L
                        @else
                        ${{ number_format($addOn->pivot->subtotal, 2) }}
                        @endif
                    </li>
                    @endforeach
                </ul>
            </div>
            @endif
        </div>
        @endif

        <!-- Notes -->
        <div class="border-t p-6 bg-gray-50">
            <p class="text-sm text-gray-500">
                This receipt was generated on {{ now()->format('F j, Y g:i A') }}.
                Thank you for your business!
            </p>
        </div>
    </div>
</div>

<!-- Print Styles -->
<style type="text/css" media="print">
@page {
    size: auto;
    margin: 10mm;
}

body {
    background-color: #fff;
    margin: 0;
    padding: 0;
}

.no-print,
.no-print * {
    display: none !important;
}

.receipt-container {
    width: 100%;
    max-width: 100%;
}
</style>
@endsection