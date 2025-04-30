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

        <!-- Payment Method Selection Form -->
        <form action="{{ route('cashier.sessions.show-end', $session) }}" method="GET" id="payment-form" class="mb-4">
            <div class="mb-4">
                <label for="payment_method_selector" class="block text-gray-700 text-sm font-bold mb-2">Select Payment
                    Method</label>
                <div class="flex">
                    <select name="payment_method" id="payment_method_selector"
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" required>
                        <option value="">Select Payment Method</option>
                        @foreach($paymentMethods as $method)
                        <option value="{{ $method }}" {{ request('payment_method') === $method ? 'selected' : '' }}>
                            {{ $method }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="ml-2 px-4 bg-blue-500 text-white rounded hover:bg-blue-600">
                        Update
                    </button>
                </div>
            </div>
        </form>

        <!-- Cost Calculation Section -->
        <div class="bg-gray-50 p-4 rounded-lg mb-6 mt-8">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Bill Summary</h2>

            <div class="space-y-2">
                <div class="flex justify-between">
                    <span class="text-gray-600">Time ({{ number_format($durationInHours, 2) }} hours @
                        @if(request('payment_method') === 'LBP')
                        {{ number_format(config('play.hourly_rate') * config('play.lbp_exchange_rate', 90000)) }}
                        L.L/hour):
                        @else
                        ${{ config('play.hourly_rate') }}/hour):
                        @endif
                    </span>
                    <span class="font-medium">
                        @if(request('payment_method') === 'LBP')
                        {{ number_format(floor(($rawTimeCost * config('play.lbp_exchange_rate', 90000))/1000)*1000) }}
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
                        -{{ number_format(floor(($discountAmount * config('play.lbp_exchange_rate', 90000))/1000)*1000) }}
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
                        {{ number_format(floor(($timeCost * config('play.lbp_exchange_rate', 90000))/1000)*1000) }} L.L
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
                        {{ number_format(floor(($addonsTotal * config('play.lbp_exchange_rate', 90000))/1000)*1000) }}
                        L.L
                        @else
                        ${{ number_format($addonsTotal, 2) }}
                        @endif
                    </span>
                </div>

                <div class="flex justify-between border-t border-gray-300 pt-2 mt-2">
                    <span class="text-gray-800 font-bold">Total:</span>
                    <span class="font-bold text-lg" id="calculated-total">
                        @if(request('payment_method') === 'LBP')
                        {{ number_format(floor(($totalAmount * config('play.lbp_exchange_rate', 90000))/1000)*1000) }}
                        L.L
                        @else
                        ${{ number_format($totalAmount, 2) }}
                        @endif
                    </span>
                </div>
            </div>
        </div>

        <form action="{{ route('cashier.sessions.end', $session) }}" method="POST" class="mt-8">
            @csrf
            @method('PUT')

            <input type="hidden" name="payment_method" value="{{ request('payment_method') }}">

            <div class="mb-4">
                <div class="flex items-center justify-between">
                    <label for="amount_paid" class="block text-gray-700 text-sm font-bold mb-2">Amount Paid</label>
                    <button type="button" id="use-total-button" class="text-xs text-blue-600 hover:underline">
                        Use calculated total
                    </button>
                </div>
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
let updateInterval;

// Duration counter
document.addEventListener('DOMContentLoaded', function() {
            // Duration counter
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

            // "Use calculated total" button
            document.getElementById('use-total-button').addEventListener('click', function() {
                    const paymentMethod = "{{ request('payment_method') }}";

                    if (!paymentMethod) {
                        alert('Please select a payment method first and click "Update".');
                        return;
                    }

                    if (paymentMethod === 'LBP') {
                        // Round to nearest 100 L.L.
                        const amountInLBP = Math.round({
                                {
                                    $totalAmount * config('play.lbp_exchange_rate', 90000)
                                }
                            }
                            / 100) * 100;
                            document.getElementById('amount_paid').value = amountInLBP;
                        }
                        else {
                            document.getElementById('amount_paid').value =
                                "{{ number_format($totalAmount, 2, '.', '') }}";
                        }
                    });
            });

        // Clean up interval when leaving the page
        window.addEventListener('beforeunload', function() {
            if (updateInterval) {
                clearInterval(updateInterval);
            }
        });
    </script>
    @endsection