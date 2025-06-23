@extends('layouts.cashier-layout')

@section('title', 'Session Details')

@section('toolbar')
<div class="px-4 py-2 bg-white border-b flex justify-between items-center">
    <div class="flex items-center space-x-2">
        <a href="{{ route('cashier.sessions.index') }}" class="px-3 py-1 text-xs bg-gray-200 text-gray-700 rounded flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Back to Sessions
        </a>
    </div>
    
    <div class="flex space-x-2">
        @if(!$session->ended_at)
        <a href="{{ route('cashier.sessions.show-addons', $session) }}" class="px-3 py-1 text-xs bg-amber-600 text-white rounded flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
            Add-ons
        </a>
        
        <a href="{{ route('cashier.sessions.add-products', $session) }}" class="px-3 py-1 text-xs bg-blue-600 text-white rounded flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
            </svg>
            Add Products
        </a>
        
        <a href="{{ route('cashier.sessions.show-end', $session) }}" class="px-3 py-1 text-xs bg-purple-600 text-white rounded flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            End Session
        </a>
        
        <form action="{{ route('cashier.sessions.destroy', $session) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this session? This cannot be undone.');">
            @csrf
            @method('DELETE')
            <button type="submit" class="px-3 py-1 text-xs bg-red-600 text-white rounded flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
                Delete Session
            </button>
        </form>
        @endif
    </div>
</div>
@endsection

@section('content')
<div class="max-w-5xl mx-auto p-4">
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <!-- Session Header -->
        <div class="p-4 bg-gray-50 border-b">
            <div class="flex justify-between items-center">
                <h1 class="text-xl font-medium text-gray-800">
                    Play Session #{{ $session->id }}
                </h1>
                <div>
                    <span class="px-3 py-1 text-xs inline-flex items-center rounded-full 
                        {{ $session->ended_at ? 'bg-gray-100 text-gray-800' : 'bg-green-100 text-green-800' }}">
                        {{ $session->ended_at ? 'Completed' : 'Active' }}
                    </span>
                </div>
            </div>
        </div>
        
        <!-- Session Details -->
        <div class="p-4 grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Child Information -->
            <div>
                <h2 class="text-sm font-medium text-gray-500 mb-2">CHILD INFORMATION</h2>
                <div class="border rounded-lg overflow-hidden">
                    <div class="p-4 flex items-center">
                        <div class="flex-shrink-0 h-16 w-16 rounded-full bg-blue-100 flex items-center justify-center mr-4">
                            <span class="text-blue-800 font-medium text-2xl">{{ substr($session->child->name, 0, 1) }}</span>
                        </div>
                        <div>
                            <div class="text-lg font-medium text-gray-900">{{ $session->child->name }}</div>
                            <div class="text-sm text-gray-500">{{ $session->child->birth_date->age }} years old</div>
                        </div>
                    </div>
                    <div class="border-t">
                        <div class="grid grid-cols-3 border-b">
                            <div class="py-2 px-3 bg-gray-50 font-medium text-xs text-gray-600">Guardian</div>
                            <div class="py-2 px-3 col-span-2">{{ $session->child->guardian_name }}</div>
                        </div>
                        <div class="grid grid-cols-3 border-b">
                            <div class="py-2 px-3 bg-gray-50 font-medium text-xs text-gray-600">Contact</div>
                            <div class="py-2 px-3 col-span-2">{{ $session->child->guardian_phone }}</div>
                        </div>
                        <div class="grid grid-cols-3">
                            <div class="py-2 px-3 bg-gray-50 font-medium text-xs text-gray-600">Play Sessions</div>
                            <div class="py-2 px-3 col-span-2">
                                <span class="bg-primary-light text-primary px-2 py-1 rounded-full text-sm font-medium">{{ $playSessionsCount }}</span>
                                <span class="text-xs text-gray-500 ml-1">total sessions</span>
                            </div>
                        </div>
                    </div>
                    
                    @if(!empty($session->child->marketing_sources))
                    <div class="mt-3 border rounded-lg overflow-hidden">
                        <div class="py-2 px-3 bg-gray-50 font-medium text-xs text-gray-600">MARKETING SOURCES</div>
                        <div class="p-3">
                            <div class="flex flex-wrap gap-2">
                                @foreach($session->child->marketing_sources as $source)
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
                            
                            @if(!empty($session->child->marketing_notes))
                            <div class="mt-2 text-xs text-gray-600">
                                <p>{{ $session->child->marketing_notes }}</p>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endif
                </div>
            </div>
            
            <!-- Session Information -->
            <div>
                <h2 class="text-sm font-medium text-gray-500 mb-2">SESSION INFORMATION</h2>
                <div class="border rounded-lg overflow-hidden">
                    <div class="grid grid-cols-3 border-b">
                        <div class="py-2 px-3 bg-gray-50 font-medium text-xs text-gray-600">Started At</div>
                        <div class="py-2 px-3 col-span-2">{{ $session->started_at->format('M d, Y h:i A') }}</div>
                    </div>
                    <div class="grid grid-cols-3 border-b">
                        <div class="py-2 px-3 bg-gray-50 font-medium text-xs text-gray-600">Ended At</div>
                        <div class="py-2 px-3 col-span-2">
                            {{ $session->ended_at ? $session->ended_at->format('M d, Y h:i A') : 'Still Active' }}
                        </div>
                    </div>
                    <div class="grid grid-cols-3 border-b">
                        <div class="py-2 px-3 bg-gray-50 font-medium text-xs text-gray-600">Duration</div>
                        <div class="py-2 px-3 col-span-2">
                            {{ $duration->hours }}h {{ $duration->minutes }}m
                        </div>
                    </div>
                    <div class="grid grid-cols-3 border-b">
                        <div class="py-2 px-3 bg-gray-50 font-medium text-xs text-gray-600">Planned Hours</div>
                        <div class="py-2 px-3 col-span-2">{{ $session->planned_hours }}</div>
                    </div>
                    <div class="grid grid-cols-3">
                        <div class="py-2 px-3 bg-gray-50 font-medium text-xs text-gray-600">Created By</div>
                        <div class="py-2 px-3 col-span-2">{{ $session->user->name }}</div>
                    </div>
                </div>
                
                @if($session->planned_hours && !$session->ended_at)
                <div class="mt-4 bg-gray-100 rounded-lg p-3">
                    <label class="text-sm font-medium text-gray-500 block mb-1">Session Progress</label>
                    <div class="w-full bg-gray-200 rounded-full h-2.5">
                        @php
                        $bgColor = $progress > 80 ? 'bg-red-600' : ($progress > 50 ? 'bg-yellow-500' : 'bg-green-600');
                        @endphp
                        <div class="{{ $bgColor }} h-2.5 rounded-full" style="width: {{ $progress }}%"></div>
                    </div>
                    <div class="flex justify-between mt-1">
                        <span class="text-xs text-gray-500">0h</span>
                        <span class="text-xs text-gray-500">{{ number_format($session->planned_hours, 1) }}h</span>
                    </div>
                    
                    @php
                    $plannedMinutes = $session->planned_hours * 60;
                    $minutesTotal = $duration->hours * 60 + $duration->minutes;
                    $minutesRemaining = max(0, $plannedMinutes - $minutesTotal);
                    $hoursRemaining = floor($minutesRemaining / 60);
                    $minutesRemainder = $minutesRemaining % 60;
                    @endphp
                    
                    <div class="text-center mt-2">
                        @if($minutesRemaining <= 0)
                            <span class="text-red-600 font-medium text-sm">Time exceeded</span>
                        @else
                            <span class="{{ $progress > 80 ? 'text-red-600' : ($progress > 50 ? 'text-yellow-600' : 'text-green-600') }} font-medium text-sm">
                                Remaining: {{ $hoursRemaining }}h {{ $minutesRemainder }}m
                            </span>
                        @endif
                    </div>
                </div>
                @endif
            </div>
            
            <!-- Payment Information -->
            <div>
                <h2 class="text-sm font-medium text-gray-500 mb-2">PAYMENT INFORMATION</h2>
                <div class="border rounded-lg overflow-hidden">
                    @php
                        // Use the eager-loaded sale relationship
                        $sessionSale = $session->sale;
                        $sessionAmountPaid = $sessionSale ? $sessionSale->amount_paid : $session->amount_paid;
                        $sessionPaymentMethod = $sessionSale ? $sessionSale->payment_method : $session->payment_method;
                    @endphp
                    <div class="grid grid-cols-3 border-b">
                        <div class="py-2 px-3 bg-gray-50 font-medium text-xs text-gray-600">Status</div>
                        <div class="py-2 px-3 col-span-2">
                            @if($session->ended_at && $sessionAmountPaid)
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Paid</span>
                            @elseif($session->ended_at)
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Not Paid</span>
                            @else
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">In Progress</span>
                            @endif
                        </div>
                    </div>
                    @if($sessionAmountPaid)
                    <div class="grid grid-cols-3 border-b">
                        <div class="py-2 px-3 bg-gray-50 font-medium text-xs text-gray-600">Amount Paid</div>
                        <div class="py-2 px-3 col-span-2">
                            @if($sessionPaymentMethod === 'LBP')
                                {{ number_format($sessionAmountPaid) }} L.L
                            @else
                                ${{ number_format($sessionAmountPaid, 2) }}
                            @endif
                        </div>
                    </div>
                    <div class="grid grid-cols-3 border-b">
                        <div class="py-2 px-3 bg-gray-50 font-medium text-xs text-gray-600">Payment Method</div>
                        <div class="py-2 px-3 col-span-2">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                {{ $sessionPaymentMethod === 'LBP' ? 'bg-green-100 text-green-800' : 
                                   'bg-blue-100 text-blue-800' }}">
                                {{ $sessionPaymentMethod }}
                            </span>
                        </div>
                    </div>
                    @endif
                    <div class="grid grid-cols-3 border-b">
                        <div class="py-2 px-3 bg-gray-50 font-medium text-xs text-gray-600">Discount</div>
                        <div class="py-2 px-3 col-span-2">
                            {{ $session->discount_pct ? $session->discount_pct . '%' : 'None' }}
                        </div>
                    </div>
                    <div class="grid grid-cols-3">
                        <div class="py-2 px-3 bg-gray-50 font-medium text-xs text-gray-600">Total Cost</div>
                        <div class="py-2 px-3 col-span-2 font-medium">
                            @if($session->payment_method === 'LBP')
                                {{ number_format($session->total_cost) }} L.L
                            @else
                                ${{ number_format($session->total_cost, 2) }}
                            @endif
                        </div>
                    </div>
                </div>
                
                @if($sale)
                <div class="mt-4 bg-green-50 border border-green-200 rounded-lg p-3">
                    <div class="flex justify-between items-center">
                        <span class="text-sm font-medium text-green-700">Sale Record</span>
                        <a href="{{ route('cashier.sales.show', $sale) }}" class="text-xs text-primary hover:text-primary-dark">
                            View Sale #{{ $sale->id }}
                        </a>
                    </div>
                </div>
                @endif
                
                @php
                $pendingSales = \App\Models\Sale::where('play_session_id', $session->id)
                    ->where('status', 'pending')
                    ->latest()
                    ->get();
                @endphp
                
                @if(count($pendingSales) > 0)
                <div class="mt-4 bg-yellow-50 border border-yellow-200 rounded-lg p-3">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-sm font-medium text-yellow-700">Pending Product Sales</span>
                        <span class="text-xs bg-yellow-100 text-yellow-800 px-2 py-1 rounded-full">
                            {{ count($pendingSales) }} {{ Str::plural('item', count($pendingSales)) }}
                        </span>
                    </div>
                    
                    <div class="max-h-40 overflow-y-auto">
                        <table class="min-w-full text-xs">
                            <thead>
                                <tr class="text-left text-yellow-700">
                                    <th class="py-1">Product</th>
                                    <th class="py-1">Qty</th>
                                    <th class="py-1">Price</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($pendingSales as $pendingSale)
                                    @foreach($pendingSale->items as $item)
                                    <tr>
                                        <td class="py-1">{{ $item->product->name }}</td>
                                        <td class="py-1">{{ $item->quantity }}</td>
                                        <td class="py-1">${{ number_format($item->subtotal, 2) }}</td>
                                    </tr>
                                    @endforeach
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="border-t border-yellow-200">
                                    <td class="py-1 text-right font-medium" colspan="2">Total:</td>
                                    <td class="py-1 font-medium">${{ number_format($pendingSales->sum('total_amount'), 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    
                    <div class="mt-2 text-xs text-yellow-700">
                        <p>These items will be added to the final bill when the session ends.</p>
                    </div>
                </div>
                @endif
            </div>
        </div>
        
        <!-- Add-ons & Notes -->
        <div class="p-4 grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Add-ons -->
            <div>
                <h2 class="text-sm font-medium text-gray-500 mb-2">ADD-ONS</h2>
                @if(count($session->addOns) > 0)
                <div class="border rounded-lg overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Item</th>
                                <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Qty</th>
                                <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Price</th>
                                <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($session->addOns as $addOn)
                            <tr>
                                <td class="px-4 py-2 whitespace-nowrap text-sm">{{ $addOn->name }}</td>
                                <td class="px-4 py-2 whitespace-nowrap text-sm">{{ $addOn->pivot->qty }}</td>
                                <td class="px-4 py-2 whitespace-nowrap text-sm">
                                    @if($session->payment_method === 'LBP')
                                        @php
                                        // Add-ons have USD prices, need to convert to LBP for display
                                        $addOnPriceLbp = $addOn->price * config('play.lbp_exchange_rate', 90000);
                                        @endphp
                                        {{ number_format($addOnPriceLbp) }} L.L
                                    @else
                                        ${{ number_format($addOn->price, 2) }}
                                    @endif
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap text-sm font-medium">
                                    @if($session->payment_method === 'LBP')
                                        @php
                                        // Add-on subtotals in pivot are in USD, need to convert to LBP for display
                                        $addOnSubtotalLbp = $addOn->pivot->subtotal * config('play.lbp_exchange_rate', 90000);
                                        @endphp
                                        {{ number_format($addOnSubtotalLbp) }} L.L
                                    @else
                                        ${{ number_format($addOn->pivot->subtotal, 2) }}
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-50">
                            <tr>
                                <td colspan="3" class="px-4 py-2 text-sm font-medium text-right">Total Add-ons:</td>
                                <td class="px-4 py-2 whitespace-nowrap text-sm font-bold">
                                    @if($session->payment_method === 'LBP')
                                        @php
                                        // Add-on totals in pivot are in USD, need to convert to LBP for display
                                        $addOnsTotalLbp = $session->addOns->sum('pivot.subtotal') * config('play.lbp_exchange_rate', 90000);
                                        @endphp
                                        {{ number_format($addOnsTotalLbp) }} L.L
                                    @else
                                        ${{ number_format($session->addOns->sum('pivot.subtotal'), 2) }}
                                    @endif
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                @else
                <div class="border rounded-lg p-4 text-center text-gray-500">
                    No add-ons for this session.
                </div>
                @endif
            </div>
            
            <!-- Notes -->
            <div>
                <h2 class="text-sm font-medium text-gray-500 mb-2">NOTES</h2>
                <div class="border rounded-lg p-4 bg-gray-50 h-full">
                    @if($session->notes)
                    <p class="whitespace-pre-line text-gray-800">{{ $session->notes }}</p>
                    @else
                    <p class="text-center text-gray-500">No notes for this session.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 