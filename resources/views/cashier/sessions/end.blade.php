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
                        Duration: <strong><span id="duration-counter">{{ $initialDuration }}</span></strong><br>
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

                @if(isset($pendingSales) && count($pendingSales) > 0)
                <div class="flex justify-between">
                    <div class="text-gray-600">
                        <span>Products:</span>
                        <span class="text-xs ml-2">({{ count($pendingSales) }} {{ Str::plural('sale', count($pendingSales)) }})</span>
                    </div>
                    <span class="font-medium">
                        @if(request('payment_method') === 'LBP')
                        {{ number_format(floor(($pendingSalesTotal * config('play.lbp_exchange_rate', 90000))/1000)*1000) }}
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
                        {{ number_format(floor(($totalAmount * config('play.lbp_exchange_rate', 90000))/1000)*1000) }}
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
                                            @if(request('payment_method') === 'LBP')
                                            {{ number_format($item->subtotal * config('play.lbp_exchange_rate', 90000)) }} L.L
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
                                            @if(request('payment_method') === 'LBP')
                                            {{ number_format($sale->total_amount * config('play.lbp_exchange_rate', 90000)) }} L.L
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
// Duration counter is now fixed at page load time by the server
document.addEventListener('DOMContentLoaded', function() {
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
    </script>
    
    @if(isset($pendingSales) && count($pendingSales) > 0)
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const toggle = document.getElementById('pending-sales-toggle');
        const details = document.getElementById('pending-sales-details');
        const toggleIcon = toggle.querySelector('svg');
        
        toggle.addEventListener('click', function() {
            if (details.classList.contains('hidden')) {
                details.classList.remove('hidden');
                toggleIcon.setAttribute('viewBox', '0 0 24 24');
                toggleIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />';
            } else {
                details.classList.add('hidden');
                toggleIcon.setAttribute('viewBox', '0 0 24 24');
                toggleIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />';
            }
        });
    });
    </script>
    @endif
    @endsection