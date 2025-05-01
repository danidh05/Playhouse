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
                                @if($sale->play_session)
                                Time
                                @else
                                Qty
                                @endif
                            </th>
                            <th
                                class="px-4 py-3 bg-gray-50 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Subtotal</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @php
                        $lbpRate = config('play.lbp_exchange_rate');
                        $suffix = $sale->payment_method === 'LBP' ? ' L.L' : '';
                        $multiplier = $sale->payment_method === 'LBP' ? $lbpRate : 1;

                        // We don't need to recalculate these - use stored values
                        if ($sale->play_session) {
                            $baseTotal = $sale->total_amount;
                            $sessionCost = $sale->total_amount;
                            if ($sale->play_session->addOns->count() > 0) {
                                $addOnsTotal = $sale->play_session->addOns->sum(function ($addOn) {
                                    return $addOn->pivot->subtotal;
                                });
                                $sessionCost = $baseTotal - $addOnsTotal;
                            }
                            
                            // Calculate billed time display
                        if ($sale->play_session->actual_hours == 0 && $sale->play_session->ended_at) {
                        $startTime = $sale->play_session->started_at;
                        $endTime = $sale->play_session->ended_at;
                        $durationInMinutes = $startTime->diffInMinutes($endTime);
                        $calculatedHours = $durationInMinutes / 60;
                                $displayHours = $calculatedHours;
                        } else {
                                $displayHours = $sale->play_session->actual_hours ?: 0;
                        }

                            // Format for display
                            $hoursDisplay = floor($displayHours);
                            $minutesDisplay = round(($displayHours - $hoursDisplay) * 60);
                            $billTimeDisplay = ($hoursDisplay > 0 ? $hoursDisplay . 'h ' : '') . $minutesDisplay . 'm';
                            $timeDisplay = $billTimeDisplay; // Use the same time display everywhere
                            } else {
                            $baseTotal = $sale->total_amount;
                            $timeDisplay = '';
                            $billTimeDisplay = '';
                            $sessionCost = 0;
                            }

                            // If this is a parent sale with child sales, add their totals to the display total
                            $childSalesTotal = 0;
                            if ($sale->child_sales && $sale->child_sales->count() > 0) {
                                $childSalesTotal = $sale->child_sales->sum('total_amount');
                            }

                            // For display purposes, we'll show the combined total
                            $displayTotal = ($baseTotal + $childSalesTotal) * $multiplier;
                            @endphp

                            @if($sale->play_session)
                            <!-- Display play session as an item -->
                            <tr>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">Play Session</div>
                                    <div class="text-xs text-gray-500">
                                        {{ $timeDisplay }}
                                        @if($sale->play_session->discount_pct > 0)
                                        ({{ $sale->play_session->discount_pct }}% discount)
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-right text-sm">
                                    {{ number_format(config('play.hourly_rate', 10.00) * $multiplier, 2) }}{{ $suffix }}/hr
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-right text-sm">
                                    {{ $timeDisplay }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-right text-sm font-medium">
                                    {{ number_format($sessionCost * $multiplier, 2) }}{{ $suffix }}
                                </td>
                            </tr>

                            <!-- Display add-ons if any -->
                            @foreach($sale->play_session->addOns as $addOn)
                            <tr>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">{{ $addOn->name }}</div>
                                    <div class="text-xs text-gray-500">Add-on</div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-right text-sm">
                                    {{ number_format($addOn->price * $multiplier, 2) }}{{ $suffix }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-right text-sm">
                                <span class="text-xs text-gray-500">Qty: </span>{{ $addOn->pivot->qty }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-right text-sm font-medium">
                                    {{ number_format($addOn->pivot->subtotal * $multiplier, 2) }}{{ $suffix }}
                                </td>
                            </tr>
                            @endforeach

                            <!-- Include child sales' products as part of the main sale -->
                            @if($sale->child_sales && $sale->child_sales->count() > 0)
                                @foreach($sale->child_sales as $childSale)
                                    @foreach($childSale->items as $item)
                                    <tr>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">{{ $item->product->name }}</div>
                                            <div class="text-xs text-gray-500">Product</div>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-right text-sm">
                                            {{ number_format($item->unit_price * $multiplier, 2) }}{{ $suffix }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-right text-sm">
                                            {{ $item->quantity }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-right text-sm font-medium">
                                            {{ number_format($item->subtotal * $multiplier, 2) }}{{ $suffix }}
                                        </td>
                                    </tr>
                                    @endforeach
                                @endforeach
                            @endif
                            @else
                            <!-- Display regular product items -->
                            @foreach($sale->items as $item)
                            <tr>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">{{ $item->product->name }}</div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-right text-sm">
                                    {{ number_format($item->unit_price * $multiplier, 2) }}{{ $suffix }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-right text-sm">
                                    {{ $item->quantity }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-right text-sm font-medium">
                                    {{ number_format($item->subtotal * $multiplier, 2) }}{{ $suffix }}
                                </td>
                            </tr>
                            @endforeach
                            @endif
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="px-4 py-3 text-sm font-medium text-gray-900 text-right">Total:</td>
                            <td class="px-4 py-3 whitespace-nowrap text-base font-bold text-gray-900 text-right">
                                {{ number_format($displayTotal, 2) }}{{ $suffix }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Payment Details -->
        <div class="border-t p-6">
            <h2 class="text-lg font-medium text-gray-800 mb-4">Payment Details</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <p class="text-sm text-gray-500">Method:</p>
                    <p class="font-medium">
                        @if($sale->payment_method === 'LBP')
                        Lebanese Pounds (L.L)
                        @elseif($sale->payment_method === 'USD')
                        US Dollars ($)
                        @elseif($sale->payment_method === 'Card')
                        Credit/Debit Card
                        @else
                        {{ $sale->payment_method }}
                        @endif
                    </p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Amount Paid:</p>
                    <p class="font-medium">
                        @if($sale->payment_method === 'LBP')
                        {{ number_format($sale->amount_paid * config('play.lbp_exchange_rate', 90000)) }} L.L
                        @else
                        ${{ number_format($sale->amount_paid, 2) }}
                        @endif
                    </p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Exchange Rate:</p>
                    <p class="font-medium">
                        @if($sale->payment_method === 'LBP')
                        1 USD = {{ number_format(config('play.lbp_exchange_rate', 90000)) }} L.L
                        @else
                        N/A
                        @endif
                    </p>
                </div>
                @php
                // Use the combined total from earlier calculation
                $total = $baseTotal + $childSalesTotal;
                @endphp

                <div>
                    <p class="text-sm text-gray-500">Total:</p>
                    <p class="font-medium">
                        @if($sale->payment_method === 'LBP')
                        {{ number_format($total * config('play.lbp_exchange_rate', 90000)) }} L.L
                        @else
                        ${{ number_format($total, 2) }}
                        @endif
                    </p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Change:</p>
                    <p class="font-medium">
                        @php
                        // Calculate change as the difference between amount paid and combined total
                        $change = $sale->amount_paid - $total;
                        @endphp

                        @if($change > 0)
                        @if($sale->payment_method === 'LBP')
                        {{ number_format($change * config('play.lbp_exchange_rate', 90000)) }} L.L
                        @else
                        ${{ number_format($change, 2) }}
                        @endif
                        @else
                        -
                        @endif
                    </p>
                </div>
            </div>
        </div>

        <!-- Related Sales Section -->
        @if($sale->parent_sale || $sale->child_sales->count() > 0)
        <div class="border-t p-6">
            <h2 class="text-lg font-medium text-gray-800 mb-4">Related Sales Information</h2>

            @if($sale->parent_sale)
            <div class="mb-6">
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-sm font-medium">This is a product sale linked to a play session checkout</p>
                            <p class="text-xs text-gray-600">Main sale: #{{ $sale->parent_sale->id }} ({{ $sale->parent_sale->created_at->format('M d, Y h:i A') }})</p>
                        </div>
                        <a href="{{ route('cashier.sales.show', $sale->parent_sale->id) }}" 
                           class="px-3 py-1 text-xs bg-blue-500 text-white rounded hover:bg-blue-600">
                            View Main Sale
                        </a>
                    </div>
                </div>
            </div>
            @endif

            @if($sale->child_sales->count() > 0)
            <div>
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                    <p class="text-sm mb-2">
                        <strong>Note:</strong> This sale includes {{ $sale->child_sales->count() }} product transactions that were added during the play session.
                        The products are already included in the items list above.
                    </p>
                    <p class="text-xs text-gray-600">
                        For reference, these products were added in {{ $sale->child_sales->count() }} separate transactions during the session,
                        but have been combined into this single bill for payment at checkout.
                    </p>
                </div>
            </div>
            @endif
        </div>
        @endif

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
        @php
        // Duration for display (difference between start and end time)
        if ($sale->play_session->ended_at) {
            $sessionDuration = $sale->play_session->started_at->diffAsCarbonInterval($sale->play_session->ended_at)->cascade();
        } else {
            $sessionDuration = $sale->play_session->started_at->diffAsCarbonInterval(now())->cascade();
        }
        @endphp
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
                        {{ $billTimeDisplay }}
                        @if($sale->play_session->planned_hours > 0 && $displayHours != $sale->play_session->planned_hours)
                        <span class="text-xs ml-2 text-gray-500">(Actual time played)</span>
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

            @if(!$sale->play_session->ended_at)
            <div class="mt-4 text-center">
                <a href="{{ route('cashier.sessions.show-end', $sale->play_session) }}"
                    class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20"
                        fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm.75-11.25a.75.75 0 00-1.5 0v4.59L6.3 9.24a.75.75 0 00-1.1 1.02l3.25 3.5a.75.75 0 001.1 0l3.25-3.5a.75.75 0 10-1.1-1.02l-2.1 2.1V6.75z"
                            clip-rule="evenodd" />
                    </svg>
                    Manage Add-ons & End Session
                </a>
            </div>
            @endif
        </div>
        @endif

        <!-- Add-ons (if available) -->
        @if($sale->play_session_id && $sale->play_session->addOns->count() > 0)
        <div class="border-t p-6">
            <h2 class="text-lg font-medium text-gray-800 mb-4">Add-ons</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Add-on</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Price</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Qty</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Subtotal</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($sale->play_session->addOns as $addOn)
                        <tr>
                            <td class="px-4 py-3 whitespace-nowrap">{{ $addOn->name }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-right">
                                @if($sale->payment_method === 'LBP')
                                {{ number_format($addOn->price * config('play.lbp_exchange_rate', 90000)) }} L.L
                                @else
                                ${{ number_format($addOn->price, 2) }}
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-right">
                                <span class="text-xs text-gray-500">Qty: </span>{{ $addOn->pivot->qty }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-right">
                                @if($sale->payment_method === 'LBP')
                                {{ number_format($addOn->pivot->subtotal * config('play.lbp_exchange_rate', 90000)) }}
                                L.L
                                @else
                                ${{ number_format($addOn->pivot->subtotal, 2) }}
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
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