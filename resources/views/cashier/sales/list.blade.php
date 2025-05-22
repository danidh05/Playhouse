@extends('layouts.cashier-layout')

@section('title', 'Sales List')

@section('toolbar')
<div class="px-4 py-2 bg-white border-b flex justify-between items-center">
    <div class="flex space-x-2">
        <a href="{{ route('cashier.sales.list', ['filter' => 'today']) }}"
            class="px-3 py-1 text-xs rounded {{ request()->input('filter', 'today') === 'today' ? 'bg-primary text-white' : 'bg-gray-200 text-gray-700' }}">Today</a>
        <a href="{{ route('cashier.sales.list', ['filter' => 'week']) }}"
            class="px-3 py-1 text-xs rounded {{ request()->input('filter') === 'week' ? 'bg-primary text-white' : 'bg-gray-200 text-gray-700' }}">This
            Week</a>
        <a href="{{ route('cashier.sales.list', ['filter' => 'month']) }}"
            class="px-3 py-1 text-xs rounded {{ request()->input('filter') === 'month' ? 'bg-primary text-white' : 'bg-gray-200 text-gray-700' }}">This
            Month</a>
        <a href="{{ route('cashier.sales.list', ['filter' => 'all']) }}"
            class="px-3 py-1 text-xs rounded {{ request()->input('filter') === 'all' ? 'bg-primary text-white' : 'bg-gray-200 text-gray-700' }}">All
            Time</a>
    </div>

    <div class="flex space-x-2">
        <div class="relative">
            <select id="payment-filter" onchange="location = this.value;"
                class="appearance-none px-3 py-1 text-xs bg-gray-200 text-gray-700 rounded pr-8">
                <option value="{{ route('cashier.sales.list', ['filter' => request()->input('filter', 'today')]) }}"
                    {{ !request()->has('payment_method') ? 'selected' : '' }}>All Payments</option>
                @foreach(config('play.payment_methods', []) as $method)
                <option
                    value="{{ route('cashier.sales.list', ['filter' => request()->input('filter', 'today'), 'payment_method' => $method]) }}"
                    {{ request()->input('payment_method') === $method ? 'selected' : '' }}>
                    {{ ucfirst($method) }}
                </option>
                @endforeach
            </select>
            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                    <path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z" />
                </svg>
            </div>
        </div>

        <a href="{{ route('cashier.sales.create') }}"
            class="px-3 py-1 text-xs bg-primary text-white rounded flex items-center">
            <span class="mr-1">New Sale</span>
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
        </a>
    </div>
</div>
@endsection

@section('content')
<div class="p-4">
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-blue-100 text-blue-500 mr-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                    </svg>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">Today's Sales</p>
                    <p class="text-2xl font-bold">{{ $todaySalesCount }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-100 text-green-500 mr-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">Today's Revenue</p>
                    <p class="text-2xl font-bold">${{ number_format($todayRevenue, 2) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-purple-100 text-purple-500 mr-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">Products Sold Today</p>
                    <p class="text-2xl font-bold">{{ $productsSoldCount }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Sales Table -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-4 py-3 border-b">
            <h2 class="text-lg font-medium text-gray-800">Sales List - {{ $currentDate }}</h2>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID
                        </th>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date
                            & Time</th>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Customer</th>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Items
                        </th>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Cashier</th>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Payment</th>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Amount</th>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($sales as $sale)
                    @php
                        // Calculate the correct total amount similar to the show view
                        $lbpRate = config('play.lbp_exchange_rate', 90000);
                        $multiplier = $sale->payment_method === 'LBP' ? $lbpRate : 1;
                        $baseTotal = $sale->total_amount;
                        
                        // Add child sales totals if any
                        $childSalesTotal = 0;
                        if ($sale->child_sales && $sale->child_sales->count() > 0) {
                            $childSalesTotal = $sale->child_sales->sum('total_amount');
                        }
                        
                        // Check for custom pricing in play session notes
                        $hasCustomPrice = false;
                        $customPriceForTotals = 0;
                        
                        if ($sale->play_session && $sale->play_session->notes && strpos($sale->play_session->notes, 'Manual price set by cashier') !== false) {
                            $hasCustomPrice = true;
                            $notes = explode("\n\n", $sale->play_session->notes);
                            
                            foreach ($notes as $note) {
                                if (strpos($note, 'Manual price set by cashier') !== false) {
                                    if ($sale->payment_method === 'LBP') {
                                        preg_match('/Manual price set by cashier: (.*?) LBP\./', $note, $matches);
                                    } else {
                                        preg_match('/Manual price set by cashier: \$(.*?)\./', $note, $matches);
                                    }
                                    
                                    if (isset($matches[1])) {
                                        // Clean up the number (remove commas)
                                        $customPriceNumeric = str_replace(',', '', $matches[1]);
                                        $customPriceForTotals = (float)$customPriceNumeric;
                                        
                                        // For USD, no conversion needed
                                        if ($sale->payment_method === 'LBP') {
                                            // For display in the total column, we'll keep it in LBP
                                            $displayTotal = $customPriceForTotals;
                                        } else {
                                            $displayTotal = $customPriceForTotals;
                                        }
                                    }
                                    break;
                                }
                            }
                        } else {
                            // Standard calculation
                            $displayTotal = ($baseTotal + $childSalesTotal) * $multiplier;
                        }
                    @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">#{{ $sale->id }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                            {{ $sale->created_at->format('d M Y, H:i') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                            @if($sale->child_id)
                            <span class="font-medium text-primary">{{ $sale->child->name }}</span>
                            @if(isset($sale->child->guardian_name))
                            <br><span class="text-xs text-gray-500">Parent: {{ $sale->child->guardian_name }}</span>
                            @endif
                            @else
                            <span class="text-gray-500">Walk-in Customer</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600">
                            <div class="max-w-xs">
                                @foreach($sale->items as $item)
                                <div class="mb-1 last:mb-0">
                                    <span class="font-medium">{{ $item->quantity }}x</span> {{ $item->product->name }}
                                    <span class="text-xs text-gray-500">
                                        @if($sale->payment_method === 'LBP')
                                            {{ number_format($item->subtotal * config('play.lbp_exchange_rate', 90000)) }} L.L
                                        @else
                                            ${{ number_format($item->subtotal, 2) }}
                                        @endif
                                    </span>
                                </div>
                                @endforeach
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                            {{ $sale->user->name }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                {{ $sale->payment_method === 'cash' ? 'bg-green-100 text-green-800' : 
                                  ($sale->payment_method === 'card' ? 'bg-blue-100 text-blue-800' : 
                                   'bg-purple-100 text-purple-800') }}">
                                {{ ucfirst($sale->payment_method) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            @if($hasCustomPrice)
                                @if($sale->payment_method === 'LBP')
                                    {{ number_format($displayTotal) }} L.L
                                @else
                                    ${{ number_format($displayTotal, 2) }}
                                @endif
                                <div class="text-xs text-blue-600 font-normal">(Custom)</div>
                            @else
                                @if($sale->payment_method === 'LBP')
                                    {{ number_format($displayTotal) }} L.L
                                @else
                                    ${{ number_format($displayTotal, 2) }}
                                @endif
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                            <a href="{{ route('cashier.sales.show', $sale) }}"
                                class="text-primary hover:text-primary-dark mr-3">
                                View
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-10 text-center text-gray-500">
                            <div class="flex flex-col items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-gray-400 mb-3" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                                <p class="text-lg">No sales found for the selected period</p>
                                <p class="text-sm text-gray-400 mt-1">Try changing your filter options or create a new
                                    sale</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($sales->count() > 0)
        <div class="px-4 py-3 bg-gray-50 border-t border-gray-200 sm:px-6">
            {{ $sales->links() }}
        </div>
        @endif
    </div>
</div>
@endsection