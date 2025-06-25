@extends('layouts.cashier-layout')

@section('title', 'Sales')

@php
use App\Helpers\CurrencyHelper;
@endphp

@section('toolbar')
<!-- Sales specific toolbar -->
<div class="px-4 py-2 bg-white border-b flex justify-between items-center">
    <div class="flex space-x-2">
        <a href="{{ route('cashier.sales.index', ['filter' => 'today']) }}"
            class="px-3 py-1 text-xs rounded {{ request()->input('filter', 'today') === 'today' ? 'bg-primary text-white' : 'bg-gray-200 text-gray-700' }}">Today</a>
        <a href="{{ route('cashier.sales.index', ['filter' => 'week']) }}"
            class="px-3 py-1 text-xs rounded {{ request()->input('filter') === 'week' ? 'bg-primary text-white' : 'bg-gray-200 text-gray-700' }}">This
            Week</a>
        <a href="{{ route('cashier.sales.index', ['filter' => 'month']) }}"
            class="px-3 py-1 text-xs rounded {{ request()->input('filter') === 'month' ? 'bg-primary text-white' : 'bg-gray-200 text-gray-700' }}">This
            Month</a>
        <a href="{{ route('cashier.sales.index', ['filter' => 'all']) }}"
            class="px-3 py-1 text-xs rounded {{ request()->input('filter') === 'all' ? 'bg-primary text-white' : 'bg-gray-200 text-gray-700' }}">All
            Time</a>
    </div>

    <div class="flex space-x-2">
        <div class="relative group" tabindex="0">
            <button class="px-3 py-1 text-xs bg-primary text-white rounded flex items-center focus:outline-none">
                <span class="mr-1">New Sale</span>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>
            <div
                class="absolute right-0 mt-1 bg-white shadow-lg rounded-lg py-2 w-48 z-10 hidden group-focus-within:block">
                <a href="{{ route('cashier.sales.create') }}?type=walkin"
                    class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                    Walk-in Sale
                </a>
                <a href="{{ route('cashier.children.index') }}?select_for_sale=true"
                    class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                    Sale for Specific Child
                </a>
                <a href="{{ route('cashier.sales.create-addon-only') }}"
                    class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                    Add-on Only Sale
                </a>
            </div>
        </div>


        <a href="{{ route('cashier.sales.list') }}"
            class="px-3 py-1 text-xs bg-gray-200 text-gray-700 rounded flex items-center">
            <span>View Detailed List</span>
        </a>
    </div>
</div>
@endsection

@section('content')
<div>
    <!-- Sales Table -->
    <div class="w-full overflow-x-auto">
        <table class="min-w-full bg-white border-collapse">
            <thead>
                <tr class="bg-gray-50 border-b">
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">#ID</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Customer</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Date & Time</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Items</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Payment Method</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody>
                @if(count($sales) > 0)
                @foreach($sales as $sale)
                @php
                // Use the stored amounts directly since they're now in the correct currency
                $baseTotal = $sale->total_amount;

                // Add child sales totals if any (they should be in same currency)
                $childSalesTotal = 0;
                if ($sale->child_sales && $sale->child_sales->count() > 0) {
                $childSalesTotal = $sale->child_sales->sum('total_amount');
                }

                // CRITICAL: Always use the stored total amount (cashier's custom amount is final)
                // We completely removed all calculation logic - no more overrides
                $displayTotal = $baseTotal + $childSalesTotal;

                // Check for custom pricing in play session notes
                $hasCustomPrice = false;
                if ($sale->play_session && $sale->play_session->notes && strpos($sale->play_session->notes, 'Manual
                price set by cashier') !== false) {
                $hasCustomPrice = true;
                }
                @endphp
                <tr class="border-b hover:bg-gray-50">
                    <td class="px-4 py-2 whitespace-nowrap">#{{ $sale->id }}</td>
                    <td class="px-4 py-2 whitespace-nowrap">
                        @if($sale->child_id)
                        <span class="font-medium text-primary">{{ $sale->child->name }}</span>
                        @if(isset($sale->child->guardian_name))
                        <div class="text-xs text-gray-500">Parent: {{ $sale->child->guardian_name }}</div>
                        @endif
                        @else
                        <span class="text-gray-500">Walk-in Customer</span>
                        @endif
                    </td>
                    <td class="px-4 py-2 whitespace-nowrap text-sm">
                        {{ $sale->created_at->format('d M Y, H:i') }}
                    </td>
                    <td class="px-4 py-2">
                        <div class="max-w-xs">
                            @foreach($sale->items as $item)
                            <div class="text-sm mb-1 last:mb-0">
                                <span class="font-medium">{{ $item->quantity }}x</span>
                                @if($item->product_id)
                                {{ $item->product->name }}
                                @elseif($item->add_on_id)
                                {{ $item->addOn->name }} <span class="text-xs text-primary">(Add-on)</span>
                                @endif
                                <span class="text-xs text-gray-500">
                                    {!! CurrencyHelper::formatAmount($item->subtotal, $sale->payment_method, $sale->currency ?? $sale->payment_method) !!}
                                </span>
                            </div>
                            @endforeach
                        </div>
                    </td>
                    <td class="px-4 py-2 whitespace-nowrap">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                {{ $sale->payment_method === 'LBP' ? 'bg-green-100 text-green-800' : 
                                  ($sale->payment_method === 'USD' ? 'bg-blue-100 text-blue-800' : 
                                   'bg-purple-100 text-purple-800') }}">
                            {{ $sale->payment_method }}
                        </span>
                    </td>
                    <td class="px-4 py-2 whitespace-nowrap font-semibold">
                        {!! CurrencyHelper::formatAmount($displayTotal, $sale->payment_method, $sale->currency ?? $sale->payment_method) !!}
                        @if($hasCustomPrice)
                        <div class="text-xs text-blue-600 font-normal">(Custom)</div>
                        @endif
                    </td>
                    <td class="px-4 py-2 whitespace-nowrap">
                        <a href="{{ route('cashier.sales.show', $sale) }}"
                            class="text-primary hover:text-primary-dark mr-2">
                            View
                        </a>
                    </td>
                </tr>
                @endforeach
                @else
                <tr>
                    <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                        <div class="flex flex-col items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-gray-400 mb-2" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            <p>No sales found for the selected period</p>
                        </div>
                    </td>
                </tr>
                @endif
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if(count($sales) > 0)
    <div class="p-4">
        {{ $sales->links() }}
    </div>
    @endif
</div>
@endsection