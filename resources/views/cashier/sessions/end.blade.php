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
                            data-start="{{ $session->started_at->timestamp }}">{{ $initialDuration }}</span></strong><br>
                    @if($session->discount_pct > 0)
                    Discount: <strong>{{ $session->discount_pct }}%</strong>
                    @endif
                </p>
            </div>
        </div>
    </div>

    <!-- Cost Calculation Section -->
    <div class="bg-gray-50 p-4 rounded-lg mb-6 mt-8">
        <h2 class="text-xl font-bold text-gray-800 mb-4">Bill Summary</h2>

        <div class="space-y-2">
            <div class="flex justify-between">
                <span class="text-gray-600">Time ({{ number_format($durationInHours, 2) }} hours @
                    ${{ config('play.hourly_rate') }}/hour):</span>
                <span class="font-medium">${{ number_format($rawTimeCost, 2) }}</span>
            </div>

            @if($session->discount_pct > 0)
            <div class="flex justify-between">
                <span class="text-gray-600">Discount on Time ({{ $session->discount_pct }}%):</span>
                <span class="font-medium text-green-600">-${{ number_format($discountAmount, 2) }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-600">Discounted Time Cost:</span>
                <span class="font-medium">${{ number_format($timeCost, 2) }}</span>
            </div>
            @endif

            <div class="flex justify-between">
                <span class="text-gray-600">Add-ons:</span>
                <span class="font-medium">${{ number_format($addonsTotal, 2) }}</span>
            </div>

            <div class="flex justify-between border-t border-gray-300 pt-2 mt-2">
                <span class="text-gray-800 font-bold">Total:</span>
                <span class="font-bold text-lg" id="calculated-total">${{ number_format($totalAmount, 2) }}</span>
            </div>
        </div>
    </div>

    <form action="{{ route('cashier.sessions.end', $session) }}" method="POST" class="mt-8">
        @csrf
        @method('PUT')

        <div class="mb-4">
            <label for="payment_method" class="block text-gray-700 text-sm font-bold mb-2">Payment Method</label>
            <form id="payment-method-form" action="{{ route('cashier.sessions.end', $session) }}" method="GET">
                @if(request('prefill'))
                <input type="hidden" name="prefill" value="{{ request('prefill') }}">
                @endif
                <select name="payment_method" id="payment_method"
                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700"
                    onchange="document.getElementById('payment-method-form').submit()" required>
                    <option value="">Select Payment Method</option>
                    @foreach($paymentMethods as $method)
                    <option value="{{ $method }}" {{ request('payment_method') == $method ? 'selected' : '' }}>
                        {{ $method }}</option>
                    @endforeach
                </select>
            </form>
        </div>

        <div class="mb-4">
            <div class="flex items-center justify-between">
                <label for="amount_paid" class="block text-gray-700 text-sm font-bold mb-2">Amount Paid</label>
                <a href="{{ route('cashier.sessions.end', ['session' => $session->id, 'prefill' => 'total', 'payment_method' => request('payment_method', '')]) }}"
                    class="text-xs text-blue-600 hover:underline">
                    Use calculated total
                </a>
            </div>
            <div class="flex items-center">
                <span id="currency-symbol"
                    class="bg-gray-100 border border-r-0 border-gray-300 rounded-l-md px-3 py-2 text-gray-600">
                    {{ request('payment_method') === 'LBP' ? 'L.L' : '$' }}
                </span>
                <input type="number" name="amount_paid" id="amount_paid"
                    step="{{ request('payment_method') === 'LBP' ? '100' : '0.01' }}" min="0"
                    class="shadow appearance-none border border-l-0 rounded-r-md w-full py-2 px-3 text-gray-700"
                    value="{{ request('prefill') === 'total' ? (request('payment_method') === 'LBP' ? round($totalAmount * config('play.lbp_exchange_rate', 90000) / 100) * 100 : number_format($totalAmount, 2, '.', '')) : '' }}"
                    required>
            </div>
        </div>

        <!-- Add hidden total_amount field -->
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
</div>
@endsection

@section('scripts')
<script>
const LBP_RATE = {
    {
        config('play.lbp_exchange_rate', 90000)
    }
};
const totalAmount = {
    {
        $totalAmount
    }
};
let updateInterval;

// Duration counter
document.addEventListener('DOMContentLoaded', function() {
    const durationElement = document.getElementById('duration-counter');
    const startTime = parseInt(durationElement.dataset.start);

    function updateDuration() {
        const currentTime = Math.floor(Date.now() / 1000);
        const durationInSeconds = Math.max(0, currentTime - startTime);

        // Calculate hours, minutes, seconds
        const hours = Math.floor(durationInSeconds / 3600);
        const minutes = Math.floor((durationInSeconds % 3600) / 60);
        const seconds = durationInSeconds % 60;

        // Format with leading zeros
        const formattedHours = hours.toString();
        const formattedMinutes = minutes.toString().padStart(2, '0');
        const formattedSeconds = seconds.toString().padStart(2, '0');

        // Update duration display
        durationElement.textContent = `${formattedHours}h ${formattedMinutes}m ${formattedSeconds}s`;
    }

    // Run initial update
    updateDuration();

    // Update every second
    updateInterval = setInterval(updateDuration, 1000);

    // Add event listener for payment method change
    const paymentMethodSelect = document.getElementById('payment_method');
    if (paymentMethodSelect) {
        paymentMethodSelect.addEventListener('change', updateCurrencyFormat);
        console.log('Added change event listener to payment method select');
    }
});

// Update currency symbol and format based on payment method
function updateCurrencyFormat() {
    const paymentMethod = document.getElementById('payment_method').value;
    const currencySymbol = document.getElementById('currency-symbol');
    const amountPaidInput = document.getElementById('amount_paid');
    const calculatedTotalElement = document.getElementById('calculated-total');

    console.log('Payment method changed to:', paymentMethod);
    console.log('LBP Rate:', LBP_RATE);
    console.log('Total Amount:', totalAmount);

    // Get values from PHP for calculations
    const rawTimeCost = {
        {
            $rawTimeCost
        }
    };
    const discountAmount = {
        {
            $discountAmount
        }
    };
    const timeCost = {
        {
            $timeCost
        }
    };
    const addonsTotal = {
        {
            $addonsTotal
        }
    };
    const hourlyRate = {
        {
            config('play.hourly_rate')
        }
    };

    console.log('PHP Values:', {
        rawTimeCost,
        discountAmount,
        timeCost,
        addonsTotal,
        hourlyRate
    });

    // Find all amount elements
    const summaryContainer = document.querySelector('.space-y-2');

    // First try to find elements by more specific selectors
    const timeCostElement = document.querySelector('.space-y-2 > div:first-child > span.font-medium');
    const discountElement = document.querySelector('.space-y-2 .text-green-600');
    const discountedTimeElement = document.querySelector('.space-y-2 > div:nth-child(3) > span.font-medium');
    const addonsElement = document.querySelector('.space-y-2 > div:nth-last-child(2) > span.font-medium');
    const hourlyRateText = document.querySelector('.space-y-2 > div:first-child > span.text-gray-600');

    console.log('Elements found:', {
        summaryContainer: !!summaryContainer,
        timeCostElement: !!timeCostElement,
        discountElement: !!discountElement,
        discountedTimeElement: !!discountedTimeElement,
        addonsElement: !!addonsElement,
        hourlyRateText: !!hourlyRateText
    });

    if (paymentMethod === 'LBP') {
        // Update all bill summary amounts to LBP
        currencySymbol.textContent = 'L.L';
        amountPaidInput.value = '';
        amountPaidInput.step = '100';
        amountPaidInput.min = '0';

        // Update hourly rate text
        if (hourlyRateText) {
            const durationText = hourlyRateText.textContent.split('hours @')[0] + 'hours @';
            hourlyRateText.textContent = `${durationText} ${(hourlyRate * LBP_RATE).toLocaleString()} L.L/hour):`;
        }

        // Update amount displays
        if (timeCostElement) {
            timeCostElement.textContent = (rawTimeCost * LBP_RATE).toLocaleString() + ' L.L';
        }

        if (discountElement) {
            discountElement.textContent = '-' + (discountAmount * LBP_RATE).toLocaleString() + ' L.L';
        }

        if (discountedTimeElement) {
            discountedTimeElement.textContent = (timeCost * LBP_RATE).toLocaleString() + ' L.L';
        }

        if (addonsElement) {
            addonsElement.textContent = (addonsTotal * LBP_RATE).toLocaleString() + ' L.L';
        }

        // Update total amount
        const lbpAmount = Math.round(totalAmount * LBP_RATE);
        calculatedTotalElement.textContent = lbpAmount.toLocaleString() + ' L.L';
        console.log('LBP Amount:', lbpAmount);
    } else {
        // Reset to USD values
        currencySymbol.textContent = '$';
        amountPaidInput.value = '';
        amountPaidInput.step = '0.01';
        amountPaidInput.min = '0';

        // Update hourly rate text
        if (hourlyRateText) {
            const durationText = hourlyRateText.textContent.split('hours @')[0] + 'hours @';
            hourlyRateText.textContent = `${durationText} $${hourlyRate.toFixed(2)}/hour):`;
        }

        // Update amount displays
        if (timeCostElement) {
            timeCostElement.textContent = '$' + rawTimeCost.toFixed(2);
        }

        if (discountElement) {
            discountElement.textContent = '-$' + discountAmount.toFixed(2);
        }

        if (discountedTimeElement) {
            discountedTimeElement.textContent = '$' + timeCost.toFixed(2);
        }

        if (addonsElement) {
            addonsElement.textContent = '$' + addonsTotal.toFixed(2);
        }

        calculatedTotalElement.textContent = '$' + totalAmount.toFixed(2);
    }
}

// Copy total to amount paid
function copyTotalToAmount() {
    const paymentMethod = document.getElementById('payment_method').value;
    const amountPaidInput = document.getElementById('amount_paid');
    const totalAmountField = document.getElementById('total_amount');

    if (!paymentMethod) {
        alert('Please select a payment method first.');
        document.getElementById('payment_method').focus();
        return;
    }

    if (paymentMethod === 'LBP') {
        // For LBP, set the amount in LBP (rounded to nearest 100)
        const amountInLBP = Math.round(totalAmount * LBP_RATE / 100) * 100;
        amountPaidInput.value = amountInLBP;
        // Keep total_amount in USD
        totalAmountField.value = totalAmount.toFixed(2);
    } else {
        // For USD, set the exact amount with 2 decimal places
        amountPaidInput.value = totalAmount.toFixed(2);
        totalAmountField.value = totalAmount.toFixed(2);
    }
}

// Clean up interval when leaving the page
window.addEventListener('beforeunload', function() {
    if (updateInterval) {
        clearInterval(updateInterval);
    }
});
</script>
@endsection