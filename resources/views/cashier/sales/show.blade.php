@extends('layouts.cashier-layout')

@section('title', 'Sale Details')

@php
use App\Helpers\CurrencyHelper;
@endphp

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

        <button onclick="showDeleteConfirmation()"
            class="ml-2 px-3 py-1 text-xs bg-red-600 text-white rounded flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
            Delete Receipt
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
                        // All sales are now stored in LBP, so always display as LBP
                        $displayTotal = $sale->total_amount;

                        // Add child sales totals if they exist
                        if ($sale->child_sales && $sale->child_sales->count() > 0) {
                        $displayTotal += $sale->child_sales->sum('total_amount');
                        }

                        // Check for custom pricing from session notes
                        $hasCustomPrice = false;
                        if ($sale->play_session && $sale->play_session->notes && strpos($sale->play_session->notes,
                        'Manual price set by cashier') !== false) {
                        $hasCustomPrice = true;
                        }
                        @endphp

                        @if($sale->play_session)
                        <!-- Display all sale items (including session time, add-ons, and products) -->
                        @foreach($sale->items as $item)
                        <tr>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">
                                    @if($item->product_id)
                                    {{ $item->product->name }}
                                    @elseif($item->description)
                                    {{ $item->description }}
                                    @else
                                    Item
                                    @endif
                                </div>
                                @if($item->description && strpos($item->description, 'Play session') !== false)
                                <div class="text-xs text-gray-500">
                                    @if($sale->play_session->discount_pct > 0)
                                    ({{ $sale->play_session->discount_pct }}% discount applied)
                                    @endif
                                </div>
                                @elseif($item->description && strpos($item->description, 'add-on') !== false)
                                <div class="text-xs text-gray-500">Add-on</div>
                                @elseif($item->product_id)
                                <div class="text-xs text-gray-500">Product</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-right text-sm">
                                @if($item->product_id)
                                @php
                                $product = $item->product;
                                @endphp
                                @if($product->price_lbp > 0)
                                {{ number_format($product->price_lbp, 0) }} L.L
                                @else
                                ${{ number_format($product->price, 2) }} <span
                                    class="text-xs text-gray-500">(USD)</span>
                                @endif
                                @elseif(strpos($item->description, 'Play session') !== false)
                                @if($hasCustomPrice)
                                <span class="text-blue-600 font-medium">Flat Rate</span>
                                @else
                                {{ number_format(config('play.hourly_rate', 10.00) * config('play.lbp_exchange_rate', 90000), 0) }}
                                L.L/hr
                                @endif
                                @else
                                {{ number_format($item->unit_price, 0) }} L.L
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-right text-sm">
                                {{ $item->quantity }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-right text-sm font-medium">
                                {!! CurrencyHelper::formatAmount($item->subtotal, $sale->payment_method, $sale->currency
                                ?? $sale->payment_method) !!}
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
                                <div class="text-xs text-gray-500">Product (from session)</div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-right text-sm">
                                @php
                                $product = $item->product;
                                @endphp
                                @if($product->price_lbp > 0)
                                {{ number_format($product->price_lbp, 0) }} L.L
                                @else
                                ${{ number_format($product->price, 2) }} <span
                                    class="text-xs text-gray-500">(USD)</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-right text-sm">
                                {{ $item->quantity }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-right text-sm font-medium">
                                {!! CurrencyHelper::formatAmount($item->subtotal, $sale->payment_method, $sale->currency
                                ?? $sale->payment_method) !!}
                            </td>
                        </tr>
                        @endforeach
                        @endforeach
                        @endif
                        @else
                        <!-- Display regular product items -->
                        @foreach($sale->items as $item)
                        <tr class="border-b">
                            <td class="px-4 py-3">
                                @if($item->product_id)
                                {{ $item->product->name }}
                                @elseif($item->add_on_id)
                                {{ $item->addOn->name }} <span class="text-xs text-primary font-medium">(Add-on)</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">{{ $item->quantity }}</td>
                            <td class="px-4 py-3 text-right">
                                @if($item->product_id)
                                @php
                                $product = $item->product;
                                @endphp
                                @if($product->price_lbp > 0)
                                {{ number_format($product->price_lbp, 0) }} L.L
                                @else
                                ${{ number_format($product->price, 2) }} <span
                                    class="text-xs text-gray-500">(USD)</span>
                                @endif
                                @elseif($item->add_on_id)
                                @php
                                $addOn = $item->addOn;
                                @endphp
                                @if($addOn->price_lbp > 0)
                                {{ number_format($addOn->price_lbp, 0) }} L.L
                                @else
                                ${{ number_format($addOn->price, 2) }} <span class="text-xs text-gray-500">(USD)</span>
                                @endif
                                @else
                                {{ number_format($item->unit_price, 0) }} L.L
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                {!! CurrencyHelper::formatAmount($item->subtotal, $sale->payment_method, $sale->currency
                                ?? $sale->payment_method) !!}
                            </td>
                        </tr>
                        @endforeach
                        @endif
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="px-4 py-3 text-sm font-medium text-gray-900 text-right">Total:</td>
                            <td class="px-4 py-3 whitespace-nowrap text-base font-bold text-gray-900 text-right">
                                {!! CurrencyHelper::formatAmount($displayTotal, $sale->payment_method, $sale->currency
                                ?? $sale->payment_method) !!}
                                @if($hasCustomPrice)
                                <div class="text-xs text-blue-600">(Custom price set by cashier)</div>
                                @endif
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
                        @else
                        {{ $sale->payment_method }}
                        @endif
                    </p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Currency:</p>
                    <p class="font-medium">
                        @if($sale->payment_method === 'LBP')
                        LBP
                        @else
                        {{ strtoupper($sale->currency ?? 'USD') }}
                        @endif
                    </p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Total Cost:</p>
                    <p class="font-medium">
                        {!! CurrencyHelper::formatAmount($displayTotal, $sale->payment_method, $sale->currency ??
                        $sale->payment_method) !!}
                    </p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Amount Paid:</p>
                    <p class="font-medium">
                        {!! CurrencyHelper::formatAmount($sale->amount_paid, $sale->payment_method, $sale->currency ??
                        $sale->payment_method) !!}
                    </p>
                </div>
                @if($sale->amount_paid > $displayTotal)
                <div>
                    <p class="text-sm text-gray-500">Change Due:</p>
                    <p class="font-medium text-green-600">
                        @php
                        $change = $sale->amount_paid - $displayTotal;
                        @endphp
                        {!! CurrencyHelper::formatAmount($change, $sale->payment_method, $sale->currency ??
                        $sale->payment_method) !!}
                    </p>
                </div>
                @endif
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
                            <p class="text-xs text-gray-600">Main sale: #{{ $sale->parent_sale->id }}
                                ({{ $sale->parent_sale->created_at->format('M d, Y h:i A') }})</p>
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
                        <strong>Note:</strong> This sale includes {{ $sale->child_sales->count() }} product transactions
                        that were added during the play session.
                        The products are already included in the items list above.
                    </p>
                    <p class="text-xs text-gray-600">
                        For reference, these products were added in {{ $sale->child_sales->count() }} separate
                        transactions during the session,
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
                <div>
                    <p class="text-sm text-gray-500">Play Sessions:</p>
                    <p class="font-medium">
                        <span class="bg-primary-light text-primary px-2 py-1 rounded-full text-sm font-medium">
                            {{ $playSessionsCount ?? 0 }}
                        </span>
                        <span class="text-sm text-gray-500 ml-1">total sessions</span>
                    </p>
                </div>

                @if(!empty($sale->child->marketing_sources))
                <div class="col-span-1 md:col-span-2 mt-3">
                    <p class="text-sm text-gray-500">Marketing Sources:</p>
                    <div class="flex flex-wrap gap-2 mt-1">
                        @foreach($sale->child->marketing_sources as $source)
                        <span class="px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded-full">
                            @switch($source)
                            @case('facebook')
                            Facebook
                            @break
                            @case('instagram')
                            Instagram
                            @break
                            @case('tiktok')
                            TikTok
                            @break
                            @case('passing_by')
                            Saw from outside
                            @break
                            @case('mascot')
                            Mascot outside
                            @break
                            @case('word_of_mouth')
                            Word of mouth
                            @break
                            @default
                            {{ $source }}
                            @endswitch
                        </span>
                        @endforeach
                    </div>

                    @if(!empty($sale->child->marketing_notes))
                    <p class="text-xs text-gray-600 mt-2">{{ $sale->child->marketing_notes }}</p>
                    @endif
                </div>
                @endif
            </div>
        </div>
        @endif

        <!-- Play Session (if available) -->
        @if($sale->play_session_id)
        @php
        // Duration for display (difference between start and end time)
        if ($sale->play_session->ended_at) {
        $sessionDuration =
        $sale->play_session->started_at->diffAsCarbonInterval($sale->play_session->ended_at)->cascade();

        // Calculate billed hours for display
        $actualMinutes = $sale->play_session->started_at->diffInMinutes($sale->play_session->ended_at);
        $displayHours = round($actualMinutes / 60, 2);

        // Use billed_hours if available, otherwise calculate from actual time
        if ($sale->play_session->billed_hours !== null) {
        $billedHours = $sale->play_session->billed_hours;
        } else {
        // Legacy calculation - use actual time or planned time, whichever is appropriate
        $billedHours = $displayHours;
        }

        $billTimeDisplay = number_format($billedHours, 2) . 'h';
        } else {
        $sessionDuration = $sale->play_session->started_at->diffAsCarbonInterval(now())->cascade();
        $displayHours = 0;
        $billTimeDisplay = 'Session ongoing';
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
                        @if($hasCustomPrice)
                        <span class="text-blue-600">Custom price applied</span>
                        @else
                        {{ $billTimeDisplay }}
                        @if($sale->play_session->planned_hours > 0 && $displayHours !=
                        $sale->play_session->planned_hours)
                        <span class="text-xs ml-2 text-gray-500">(Actual time played)</span>
                        @endif
                        @endif
                    </p>
                </div>

                <div>
                    <p class="text-sm text-gray-500">Hourly Rate:</p>
                    <p class="font-medium">
                        @if($hasCustomPrice)
                        <span class="text-blue-600">N/A - Custom pricing</span>
                        @else
                        @if($sale->payment_method === 'LBP')
                        {{ number_format(config('play.hourly_rate', 10.00) * config('play.lbp_exchange_rate', 90000)) }}
                        L.L
                        @else
                        ${{ number_format(config('play.hourly_rate', 10.00), 2) }}
                        @endif
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
                                @php
                                // Pivot subtotals are stored in USD, convert to payment currency for display
                                if($sale->payment_method === 'LBP') {
                                $displaySubtotal = $addOn->pivot->subtotal * config('play.lbp_exchange_rate', 90000);
                                $formattedSubtotal = number_format($displaySubtotal) . ' L.L';
                                } else {
                                $formattedSubtotal = '$' . number_format($addOn->pivot->subtotal, 2);
                                }
                                @endphp
                                {{ $formattedSubtotal }}
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

<!-- Delete Confirmation Modal -->
<div id="delete-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-lg p-6 w-1/3 max-w-md">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold text-red-600">Delete Confirmation</h2>
            <button onclick="hideDeleteConfirmation()" class="text-gray-500 hover:text-gray-700">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div class="mb-6">
            <p class="text-gray-700 mb-2">Are you sure you want to delete this sale receipt?</p>

            @if($sale->play_session_id)
            <div class="bg-yellow-50 border-l-4 border-yellow-500 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                            fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-yellow-700">
                            <strong>Warning!</strong> This will also delete the associated play session.
                        </p>
                    </div>
                </div>
            </div>
            @endif

            <div class="mt-4 bg-red-50 border-l-4 border-red-500 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                            fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-red-700">
                            <strong>Important!</strong> This action cannot be undone.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex justify-end space-x-3">
            <button onclick="hideDeleteConfirmation()"
                class="px-4 py-2 bg-gray-300 text-gray-800 rounded hover:bg-gray-400">
                Cancel
            </button>
            <form action="{{ route('cashier.sales.destroy', $sale->id) }}" method="POST">
                @csrf
                @method('DELETE')
                <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">
                    Delete
                </button>
            </form>
        </div>
    </div>
</div>

<script>
function showDeleteConfirmation() {
    document.getElementById('delete-modal').classList.remove('hidden');
}

function hideDeleteConfirmation() {
    document.getElementById('delete-modal').classList.add('hidden');
}
</script>
@endsection