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
        <a href="{{ route('cashier.sessions.show-end', $session) }}" class="px-3 py-1 text-xs bg-purple-600 text-white rounded flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            End Session
        </a>
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
                        <div class="grid grid-cols-3">
                            <div class="py-2 px-3 bg-gray-50 font-medium text-xs text-gray-600">Contact</div>
                            <div class="py-2 px-3 col-span-2">{{ $session->child->guardian_phone }}</div>
                        </div>
                    </div>
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
                    <div class="grid grid-cols-3 border-b">
                        <div class="py-2 px-3 bg-gray-50 font-medium text-xs text-gray-600">Status</div>
                        <div class="py-2 px-3 col-span-2">
                            @if($session->ended_at && $session->amount_paid)
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Paid</span>
                            @elseif($session->ended_at)
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Not Paid</span>
                            @else
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">In Progress</span>
                            @endif
                        </div>
                    </div>
                    @if($session->amount_paid)
                    <div class="grid grid-cols-3 border-b">
                        <div class="py-2 px-3 bg-gray-50 font-medium text-xs text-gray-600">Amount Paid</div>
                        <div class="py-2 px-3 col-span-2">
                            @if($session->payment_method === 'LBP')
                                {{ number_format($session->amount_paid * config('play.lbp_exchange_rate', 90000)) }} L.L
                            @else
                                ${{ number_format($session->amount_paid, 2) }}
                            @endif
                        </div>
                    </div>
                    <div class="grid grid-cols-3 border-b">
                        <div class="py-2 px-3 bg-gray-50 font-medium text-xs text-gray-600">Payment Method</div>
                        <div class="py-2 px-3 col-span-2">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                {{ $session->payment_method === 'LBP' ? 'bg-green-100 text-green-800' : 
                                   'bg-blue-100 text-blue-800' }}">
                                {{ $session->payment_method }}
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
                                {{ number_format($session->total_cost * config('play.lbp_exchange_rate', 90000)) }} L.L
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
                                        {{ number_format($addOn->price * config('play.lbp_exchange_rate', 90000)) }} L.L
                                    @else
                                        ${{ number_format($addOn->price, 2) }}
                                    @endif
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap text-sm font-medium">
                                    @if($session->payment_method === 'LBP')
                                        {{ number_format($addOn->pivot->subtotal * config('play.lbp_exchange_rate', 90000)) }} L.L
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
                                        {{ number_format($session->addOns->sum('pivot.subtotal') * config('play.lbp_exchange_rate', 90000)) }} L.L
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