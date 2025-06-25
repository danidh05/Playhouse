@extends('layouts.cashier-layout')

@section('title', 'End Play Session')

@section('content')
<div class="bg-white rounded-lg shadow-md p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">End Play Session</h1>
        <a href="{{ route('cashier.sessions.index') }}"
            class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">
            Back to Sessions
        </a>
    </div>

    @if($errors->any())
    <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6">
        <div class="flex">
            <div class="ml-3">
                <p class="text-sm text-red-700 font-medium">Please fix the following errors:</p>
                <ul class="text-sm text-red-700 mt-2 list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
    @endif

    @if(session('error'))
    <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6">
        <div class="flex">
            <div class="ml-3">
                <p class="text-sm text-red-700">{{ session('error') }}</p>
            </div>
        </div>
    </div>
    @endif

    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6">
        <div class="flex">
            <div class="ml-3">
                <p class="text-sm text-blue-700">
                    Child: <strong>{{ $session->child->name }}</strong><br>
                    Started: <strong>{{ $session->started_at->format('M d, Y H:i') }}</strong><br>
                        Duration: <strong><span id="duration-counter">{{ $initialDuration }}</span></strong><br>
                    @if($session->discount_pct > 0)
                    Discount: <strong>{{ $session->discount_pct }}%</strong>
                    @endif
                </p>
            </div>
        </div>
    </div>

    @if(isset($cappedHours) && $cappedHours)
    <div class="bg-yellow-50 border-l-4 border-yellow-500 p-4 mb-6">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3">
                <p class="text-sm text-yellow-700">
                    <strong>Note:</strong> The actual session duration ({{ number_format($durationInHours, 2) }} hours) exceeds the planned duration ({{ $session->planned_hours }} hours). You will only be billed for {{ $session->planned_hours }} hours.
                </p>
            </div>
        </div>
    </div>
    @endif

        <!-- Payment Method Selection -->
        <div class="mb-4">
            <div class="mb-4">
                <label for="payment_method_selector" class="block text-gray-700 text-sm font-bold mb-2">Select Payment
                    Method</label>
                <div class="flex">
                    <select name="payment_method" id="payment_method_selector"
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" required>
                        @foreach($paymentMethods as $method)
                        <option value="{{ $method }}" {{ (request('payment_method') ?: 'USD') === $method ? 'selected' : '' }}>
                            {{ $method }}</option>
                        @endforeach
                    </select>
                    <div class="ml-2 px-4 py-2 bg-gray-100 text-gray-600 rounded" id="loading-indicator" style="display: none;">
                        <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

    <!-- Cost Calculation Section -->
    <div id="payment-content">
        <div class="bg-gray-50 p-4 rounded-lg mb-6 mt-8">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Bill Summary</h2>

            <div class="space-y-2">
                <div class="flex justify-between">
                    <span class="text-gray-600">
                        @if(isset($cappedHours) && $cappedHours)
                        Time (Capped at {{ $session->planned_hours }} hours @
                        @else
                        Time ({{ number_format($durationInHours, 2) }} hours @
                        @endif
                            @if(request('payment_method') === 'LBP')
                            {{ number_format(config('play.hourly_rate') * config('play.lbp_exchange_rate', 90000)) }}
                            L.L/hour):
                            @else
                            ${{ config('play.hourly_rate') }}/hour):
                            @endif
                        </span>
                        <span class="font-medium">
                            @if(request('payment_method') === 'LBP')
                            {{ number_format($rawTimeCost) }}
                            L.L
                            @else
                            ${{ number_format($rawTimeCost, 2) }}
                            @endif
                        </span>
                </div>

                @if($session->discount_pct > 0)
                <div class="flex justify-between">
                    <span class="text-gray-600">Discount on Time ({{ $session->discount_pct }}%):</span>
                        <span class="font-medium text-green-600">
                            @if(request('payment_method') === 'LBP')
                            -{{ number_format($discountAmount) }}
                            L.L
                            @else
                            -${{ number_format($discountAmount, 2) }}
                            @endif
                        </span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Discounted Time Cost:</span>
                        <span class="font-medium">
                            @if(request('payment_method') === 'LBP')
                            {{ number_format($timeCost) }} L.L
                            @else
                            ${{ number_format($timeCost, 2) }}
                            @endif
                        </span>
                </div>
                @endif

                <div class="flex justify-between">
                    <span class="text-gray-600">Add-ons:</span>
                        <span class="font-medium">
                            @if(request('payment_method') === 'LBP')
                            {{ number_format($addonsTotal) }}
                            L.L
                            @else
                            ${{ number_format($addonsTotal, 2) }}
                            @endif
                        </span>
                </div>

                    @if(isset($pendingSales) && count($pendingSales) > 0)
                    <div class="flex justify-between">
                        <div class="text-gray-600">
                            <span>Products:</span>
                            <span class="text-xs ml-2">({{ count($pendingSales) }} {{ Str::plural('sale', count($pendingSales)) }})</span>
                        </div>
                        <span class="font-medium">
                            @if(request('payment_method') === 'LBP')
                            {{ number_format($pendingSalesTotal) }}
                            L.L
                            @else
                            ${{ number_format($pendingSalesTotal, 2) }}
                            @endif
                        </span>
                    </div>
                    @endif

                <div class="flex justify-between border-t border-gray-300 pt-2 mt-2">
                    <span class="text-gray-800 font-bold">Total:</span>
                        <span class="font-bold text-lg" id="calculated-total">
                            @if(request('payment_method') === 'LBP')
                            {{ number_format($totalAmount) }}
                            L.L
                            @else
                            ${{ number_format($totalAmount, 2) }}
                            @endif
                        </span>
                    </div>
                </div>
            </div>

        @if(isset($pendingSales) && count($pendingSales) > 0)
        <div class="bg-yellow-50 p-4 rounded-lg mb-6 border border-yellow-200">
            <div class="flex justify-between items-center mb-2 cursor-pointer" id="pending-sales-toggle">
                <h3 class="text-lg font-semibold text-yellow-800">Pending Product Sales</h3>
                <button type="button" class="text-yellow-600 hover:text-yellow-800">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
            </div>
            
            <div id="pending-sales-details" class="hidden mt-2">
                @foreach($pendingSales as $sale)
                    <div class="border-b border-yellow-200 pb-2 mb-2">
                        <div class="flex justify-between text-sm">
                            <span class="font-medium">Sale #{{ $sale->id }}</span>
                            <span class="text-gray-600">{{ $sale->created_at->format('M d, h:i A') }}</span>
    </div>
                        
                        <div class="mt-1">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="text-left text-xs text-yellow-700">
                                        <th class="py-1">Product</th>
                                        <th class="py-1">Qty</th>
                                        <th class="py-1 text-right">Price</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($sale->items as $item)
                                    <tr>
                                        <td class="py-1">{{ $item->product->name }}</td>
                                        <td class="py-1">{{ $item->quantity }}</td>
                                        <td class="py-1 text-right">
                                            @if($sale->payment_method === 'LBP')
                                            {{ number_format($item->subtotal) }} L.L
                                            @else
                                            ${{ number_format($item->subtotal, 2) }}
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="border-t border-yellow-200">
                                        <td colspan="2" class="py-1 text-right font-medium">Subtotal:</td>
                                        <td class="py-1 text-right font-medium">
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
                @endforeach
            </div>
        </div>
        @endif

    <form action="{{ route('cashier.sessions.end', $session) }}" method="POST" class="mt-8">
        @csrf
        @method('PUT')

            <input type="hidden" name="payment_method" id="payment_method_hidden" value="{{ request('payment_method') ?: 'USD' }}">

        <!-- Custom manual price entry -->
        <div class="mb-4">
            <div class="flex items-center justify-between">
                <label for="total_cost" class="block text-gray-700 text-sm font-bold mb-2">Total Cost</label>
                <button type="button" id="use-calculated-total-button" class="text-xs text-blue-600 hover:underline">
                    Use calculated total ({{ request('payment_method') === 'LBP' ? number_format($totalAmount) . ' L.L' : '$' . number_format($totalAmount, 2) }})
                </button>
            </div>
            <div class="flex items-center">
                <span class="bg-gray-100 border border-r-0 border-gray-300 rounded-l-md px-3 py-2 text-gray-600">
                    {{ request('payment_method') === 'LBP' ? 'L.L' : '$' }}
                </span>
                <input type="number" name="total_cost" id="total_cost"
                    step="{{ request('payment_method') === 'LBP' ? '1000' : '0.01' }}" min="0"
                    class="shadow appearance-none border border-l-0 rounded-r-md w-full py-2 px-3 text-gray-700"
                    value="{{ request('payment_method') === 'LBP' ? 
                           number_format($totalAmount, 0, '.', '') : 
                           number_format($totalAmount, 2, '.', '') }}" required>
            </div>
            <p class="text-xs text-gray-500 mt-1">Enter the amount you want to charge the customer (defaults to calculated total).</p>
        </div>

        <div class="mb-4">
            <div class="flex items-center justify-between">
                <label for="amount_paid" class="block text-gray-700 text-sm font-bold mb-2">Amount Paid</label>
                <button type="button" id="use-total-button" class="text-xs text-blue-600 hover:underline">
                    Use total cost as payment
                </button>
            </div>
            <p class="text-xs text-gray-600 mb-2">This is the amount the customer actually paid (for calculating change)</p>
            <div class="flex items-center">
                <span id="currency-symbol"
                        class="bg-gray-100 border border-r-0 border-gray-300 rounded-l-md px-3 py-2 text-gray-600">
                        {{ request('payment_method') === 'LBP' ? 'L.L' : '$' }}
                    </span>
                    <input type="number" name="amount_paid" id="amount_paid"
                        step="{{ request('payment_method') === 'LBP' ? '100' : '0.01' }}" min="0"
                    class="shadow appearance-none border border-l-0 rounded-r-md w-full py-2 px-3 text-gray-700"
                    required>
            </div>
        </div>

        <!-- Add hidden total_amount field for reference -->
        <input type="hidden" name="total_amount" id="total_amount" value="{{ $totalAmount }}">

        <div class="bg-gray-50 border-l-4 border-yellow-500 p-4 mb-6 mt-4">
            <p class="text-yellow-700">
                <strong>Note:</strong> Once you end this session, you will not be able to modify it.
                Make sure all add-ons and details are correct before proceeding.
            </p>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="px-6 py-3 bg-red-600 text-white rounded hover:bg-red-700 font-bold">
                End Session & Process Payment
            </button>
        </div>
    </form>
    </div> <!-- End payment-content -->
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // JavaScript for the duration counter
    let startTime = new Date('{{ $session->started_at->toIso8601String() }}');
    let counterElement = document.getElementById('duration-counter');
    
    // Update counter every second
    setInterval(function() {
        let now = new Date();
        let diffInSeconds = Math.floor((now - startTime) / 1000);
        
        let hours = Math.floor(diffInSeconds / 3600);
        let minutes = Math.floor((diffInSeconds % 3600) / 60);
        let seconds = diffInSeconds % 60;
        
        counterElement.textContent = hours + 'h ' + minutes + 'm ' + seconds + 's';
    }, 1000);

        // AJAX Payment Method Selection
    let paymentMethodSelector = document.getElementById('payment_method_selector');
    let loadingIndicator = document.getElementById('loading-indicator');
    let paymentContent = document.getElementById('payment-content');
    
    if (paymentMethodSelector) {
        paymentMethodSelector.addEventListener('change', function() {
            let selectedMethod = this.value;
            
            console.log('Payment method changed to:', selectedMethod);
            
            if (!selectedMethod) {
                console.log('No method selected, returning');
                return; // Don't do anything if no method selected
            }
            
            // Update the hidden payment method field
            let hiddenPaymentMethod = document.getElementById('payment_method_hidden');
            if (hiddenPaymentMethod) {
                hiddenPaymentMethod.value = selectedMethod;
            }
            
            // Show loading indicator
            loadingIndicator.style.display = 'flex';
            
            let url = `{{ route('cashier.sessions.show-end', $session) }}?payment_method=${selectedMethod}`;
            console.log('Making AJAX request to:', url);
            
            // Make AJAX request
            fetch(url, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'text/html',
                }
            })
            .then(response => {
                console.log('Response received:', response.status);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.text();
            })
            .then(html => {
                console.log('HTML received, length:', html.length);
                
                // Create a temporary DOM element to parse the response
                let tempDiv = document.createElement('div');
                tempDiv.innerHTML = html;
                
                // Extract the payment-content section from the response
                let newPaymentContent = tempDiv.querySelector('#payment-content');
                if (newPaymentContent) {
                    console.log('Found payment-content, updating...');
                    paymentContent.innerHTML = newPaymentContent.innerHTML;
                    
                    // Re-initialize event listeners for the new content
                    initializeEventListeners();
                    console.log('Content updated and listeners reinitialized');
                } else {
                    console.error('Could not find payment-content in response');
                    console.log('Response HTML preview:', html.substring(0, 500));
                }
                
                // Hide loading indicator
                loadingIndicator.style.display = 'none';
            })
            .catch(error => {
                console.error('Error updating payment method:', error);
                loadingIndicator.style.display = 'none';
                alert('Error updating payment method: ' + error.message);
            });
        });
    }
    
    // Function to initialize event listeners (for both initial load and after AJAX updates)
    function initializeEventListeners() {
        // Toggle pending sales details
        let pendingSalesToggle = document.getElementById('pending-sales-toggle');
        if (pendingSalesToggle) {
            pendingSalesToggle.addEventListener('click', function() {
                let detailsElement = document.getElementById('pending-sales-details');
                detailsElement.classList.toggle('hidden');
            });
        }

        // Add-ons toggle
        let addOnsToggle = document.getElementById('addons-toggle');
        if (addOnsToggle) {
            addOnsToggle.addEventListener('click', function() {
                let detailsElement = document.getElementById('addons-details');
                detailsElement.classList.toggle('hidden');
            });
        }

        // Use total button
        let useCustomPriceButton = document.getElementById('use-total-button');
        let amountPaidInput = document.getElementById('amount_paid');
        let customTotalInput = document.getElementById('total_cost');
        
        if (useCustomPriceButton && amountPaidInput && customTotalInput) {
            useCustomPriceButton.addEventListener('click', function() {
                amountPaidInput.value = customTotalInput.value;
            });
        }
        
        // Use calculated total button
        let useCalculatedTotalButton = document.getElementById('use-calculated-total-button');
        let totalAmountInput = document.getElementById('total_amount');
        
        if (useCalculatedTotalButton && customTotalInput && totalAmountInput) {
            useCalculatedTotalButton.addEventListener('click', function() {
                // Get current payment method from the selector
                let currentPaymentMethod = document.getElementById('payment_method_selector').value;
                const isLBP = currentPaymentMethod === 'LBP';
                let calculatedValue = parseFloat(totalAmountInput.value);
                
                if (isLBP) {
                    // Round to whole number for LBP (no decimals)
                    calculatedValue = Math.round(calculatedValue);
                } else {
                    // Use 2 decimal places for USD
                    calculatedValue = calculatedValue.toFixed(2);
                }
                
                customTotalInput.value = calculatedValue;
            });
        }
    }
    
    // Initialize event listeners on page load
    initializeEventListeners();
});
</script>
@endsection