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

    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6">
        <div class="flex">
            <div class="ml-3">
                <p class="text-sm text-blue-700">
                    Child: <strong>{{ $session->child->name }}</strong><br>
                    Started: <strong>{{ $session->started_at->format('M d, Y H:i') }}</strong><br>
                    Duration: <strong><span id="duration-counter"
                            data-start="{{ $session->started_at->timestamp }}">{{ $session->started_at->diffForHumans(null, true) }}</span></strong><br>
                    @if($session->discount_pct > 0)
                    Discount: <strong>{{ $session->discount_pct }}%</strong>
                    @endif
                </p>
            </div>
        </div>
    </div>

    <h2 class="text-xl font-bold text-gray-800 mb-4">Add-ons</h2>

    <form action="{{ route('cashier.sessions.update-addons', $session) }}" method="POST" class="mb-6">
        @csrf
        @method('PATCH')

        <div class="overflow-x-auto">
            <table class="min-w-full bg-white">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Add-on</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Quantity</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Subtotal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($addOns as $addOn)
                    @php
                    $sessionAddOn = $sessionAddOns->firstWhere('id', $addOn->id);
                    $qty = $sessionAddOn ? $sessionAddOn->pivot->qty : 0;
                    $subtotal = $sessionAddOn ? $sessionAddOn->pivot->subtotal : 0;
                    @endphp
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $addOn->name }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">${{ number_format($addOn->price, 2) }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <input type="number" name="add_ons[{{ $addOn->id }}][qty]" min="0"
                                class="shadow appearance-none border rounded w-20 py-2 px-3 text-gray-700"
                                value="{{ $qty }}" data-price="{{ $addOn->price }}" onchange="updateSubtotal(this)">
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap subtotal-display">${{ number_format($subtotal, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="flex justify-end mt-4">
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                Update Add-ons
            </button>
        </div>
    </form>

    <!-- Cost Calculation Section -->
    <div class="bg-gray-50 p-4 rounded-lg mb-6 mt-8">
        <h2 class="text-xl font-bold text-gray-800 mb-4">Bill Summary</h2>
        
        <div class="space-y-2">
            <div class="flex justify-between">
                <span class="text-gray-600">Time (<span id="duration-hours">0.00</span> hours @ ${{ config('play.hourly_rate') }}/hour):</span>
                <span class="font-medium" id="time-cost">$0.00</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-600">Add-ons:</span>
                <span class="font-medium" id="addons-cost">$0.00</span>
            </div>
            @if($session->discount_pct > 0)
            <div class="flex justify-between">
                <span class="text-gray-600">Subtotal:</span>
                <span class="font-medium" id="subtotal-cost">$0.00</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-600">Discount ({{ $session->discount_pct }}%):</span>
                <span class="font-medium text-green-600" id="discount-amount">-$0.00</span>
            </div>
            @endif
            <div class="flex justify-between border-t border-gray-300 pt-2 mt-2">
                <span class="text-gray-800 font-bold">Total:</span>
                <span class="font-bold text-lg" id="calculated-total">$0.00</span>
            </div>
        </div>
    </div>

    <form action="{{ route('cashier.sessions.end', $session) }}" method="POST" class="mt-8">
        @csrf
        @method('PUT')

        <div class="mb-4">
            <label for="payment_method" class="block text-gray-700 text-sm font-bold mb-2">Payment Method</label>
            <select name="payment_method" id="payment_method" onchange="updateCurrencyFormat()"
                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" required>
                <option value="">Select Payment Method</option>
                @foreach($paymentMethods as $method)
                <option value="{{ $method }}">{{ $method }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-4">
            <div class="flex items-center justify-between">
                <label for="amount_paid" class="block text-gray-700 text-sm font-bold mb-2">Amount Paid</label>
                <button type="button" onclick="copyTotalToAmount()" class="text-xs text-blue-600 hover:underline">
                    Use calculated total
                </button>
            </div>
            <div class="flex items-center">
                <span id="currency-symbol" class="bg-gray-100 border border-r-0 border-gray-300 rounded-l-md px-3 py-2 text-gray-600">$</span>
                <input type="number" name="amount_paid" id="amount_paid" step="0.01" min="0"
                    class="shadow appearance-none border border-l-0 rounded-r-md w-full py-2 px-3 text-gray-700" required>
            </div>
        </div>

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
</div>
@endsection

@section('scripts')
<script>
// Global variables
let hourlyRate = {{ config('play.hourly_rate', 10.00) }};
let discountPct = {{ $session->discount_pct ?? 0 }};
let durationInHours = 0;
let addonsTotal = 0;
let timeCost = 0;
let totalAmount = 0;
const LBP_RATE = {{ config('play.lbp_exchange_rate', 90000) }}; // LBP to USD exchange rate

// Helper function to format currency based on payment method
function formatCurrency(amount, currency) {
    if (currency === 'LBP') {
        return Math.round(amount * LBP_RATE).toLocaleString() + ' L.L';
    } else {
        return '$' + amount.toFixed(2);
    }
}

// Update currency symbol and format based on payment method
function updateCurrencyFormat() {
    const paymentMethod = document.getElementById('payment_method').value;
    const currencySymbol = document.getElementById('currency-symbol');
    
    if (paymentMethod === 'LBP') {
        currencySymbol.textContent = 'L.L';
    } else {
        currencySymbol.textContent = '$';
    }
    
    // Update the displayed total with proper currency format
    updateTotalDisplay();
}

// Calculate current duration in hours - with a minimum of 0.01 hours (ensures we don't get zero)
function calculateDurationInHours() {
    const startTime = {{ $session->started_at->timestamp }};
    const currentTime = Math.floor(Date.now() / 1000);
    const durationInSeconds = Math.max(1, currentTime - startTime); // Ensure at least 1 second
    return Math.max(0.01, durationInSeconds / 3600); // Ensure at least 0.01 hours
}

// Duration counter
document.addEventListener('DOMContentLoaded', function() {
    const durationElement = document.getElementById('duration-counter');
    const startTime = parseInt(durationElement.dataset.start);
    
    // Initial calculation of add-ons total
    calculateAddonTotal();

    // Update timer every second
    setInterval(function() {
        const currentTime = Math.floor(Date.now() / 1000);
        const durationInSeconds = currentTime - startTime;

        const hours = Math.floor(durationInSeconds / 3600);
        const minutes = Math.floor((durationInSeconds % 3600) / 60);
        const seconds = durationInSeconds % 60;

        durationElement.textContent = `${hours}h ${minutes}m ${seconds}s`;
        
        // Update duration in hours for cost calculation
        durationInHours = calculateDurationInHours();
        
        // Update duration hours display
        const durationHoursElement = document.getElementById('duration-hours');
        if (durationHoursElement) {
            durationHoursElement.textContent = durationInHours.toFixed(2);
        }
        
        // Update total amount
        updateTotalAmount();
        
        // Check if we need to show alerts (e.g., at 1 hour, 2 hours, etc.)
        if (hours > 0 && minutes === 0 && seconds <= 5) {
            alert(`Alert: This play session has been running for ${hours} hour(s)!`);
        }
    }, 1000);
});

// Calculate subtotal when quantity changes
function updateSubtotal(input) {
    const qty = parseInt(input.value);
    const price = parseFloat(input.dataset.price);
    const subtotal = qty * price;

    const subtotalDisplay = input.closest('tr').querySelector('.subtotal-display');
    subtotalDisplay.textContent = '$' + subtotal.toFixed(2);
    
    // Recalculate add-ons total after changing a quantity
    calculateAddonTotal();
}

// Calculate total of all add-ons
function calculateAddonTotal() {
    addonsTotal = 0;
    const subtotals = document.querySelectorAll('.subtotal-display');
    
    subtotals.forEach(element => {
        // Extract number from "$X.XX" format
        const value = parseFloat(element.textContent.replace('$', ''));
        if (!isNaN(value)) {
            addonsTotal += value;
        }
    });
    
    updateTotalAmount();
}

// Update total amount calculation
function updateTotalAmount() {
    // Calculate time cost - ensure it's never zero
    timeCost = durationInHours * hourlyRate;
    
    // Update time cost display
    const timeCostElement = document.getElementById('time-cost');
    if (timeCostElement) {
        timeCostElement.textContent = '$' + timeCost.toFixed(2);
    }
    
    // Update addons cost display
    const addonsCostElement = document.getElementById('addons-cost');
    if (addonsCostElement) {
        addonsCostElement.textContent = '$' + addonsTotal.toFixed(2);
    }
    
    // Calculate subtotal before discount
    const subtotal = timeCost + addonsTotal;
    
    // Update subtotal display if discount is applied
    const subtotalElement = document.getElementById('subtotal-cost');
    if (subtotalElement) {
        subtotalElement.textContent = '$' + subtotal.toFixed(2);
    }
    
    // Calculate discount amount if applicable
    const discountAmount = (subtotal * discountPct) / 100;
    
    // Update discount amount display
    const discountElement = document.getElementById('discount-amount');
    if (discountElement) {
        discountElement.textContent = '-$' + discountAmount.toFixed(2);
    }
    
    // Calculate total with discount
    const discountMultiplier = (100 - discountPct) / 100;
    totalAmount = subtotal * discountMultiplier;
    
    // Update the display
    updateTotalDisplay();
}

// Update total display with proper currency format
function updateTotalDisplay() {
    const totalElement = document.getElementById('calculated-total');
    const paymentMethod = document.getElementById('payment_method').value;
    
    if (totalElement) {
        if (paymentMethod === 'LBP') {
            totalElement.textContent = formatCurrency(totalAmount, 'LBP');
        } else {
            totalElement.textContent = '$' + totalAmount.toFixed(2);
        }
    }
}

// Copy total amount to the amount paid field
function copyTotalToAmount() {
    const paymentMethod = document.getElementById('payment_method').value;
    
    if (paymentMethod === 'LBP') {
        document.getElementById('amount_paid').value = Math.round(totalAmount * LBP_RATE);
    } else {
        document.getElementById('amount_paid').value = totalAmount.toFixed(2);
    }
}
</script>
@endsection