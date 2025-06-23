@extends('layouts.cashier-layout')

@section('title', 'Add-on Only Sale')

@section('toolbar')
<div class="px-4 py-2 bg-white border-b flex justify-between items-center">
    <div>
        <h1 class="text-xl font-semibold text-primary">Add-on Only Sale</h1>
        <p class="text-sm text-gray-600">Sell add-ons without creating a play session</p>
    </div>

    <div class="flex items-center">
        <!-- Customer selection -->
        <div class="flex items-center mr-4">
            <span class="text-sm text-gray-600 mr-2">Customer:</span>
            <div id="customer-display" class="text-primary font-medium">
                @if(request()->has('child_id') && request()->child_id)
                {{ \App\Models\Child::find(request()->child_id)->name ?? 'Select Child' }}
                @else
                Select Child
                @endif
            </div>
            <button id="change-customer-btn"
                class="ml-2 text-xs px-2 py-1 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">
                Change
            </button>
        </div>
    </div>
</div>
@endsection

@section('content')
<div class="p-4">
    <form id="addon-sale-form" action="{{ route('cashier.sales.store-addon-only') }}" method="POST">
        @csrf
        <input type="hidden" id="child_id" name="child_id" value="{{ request()->child_id ?? '' }}">

        @if ($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <strong>Error:</strong> Please fix the following issues:
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-lg font-semibold mb-4">Available Add-ons</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($addOns as $addOn)
                <div class="border rounded-lg p-4 hover:bg-blue-50 transition">
                    <h3 class="font-medium text-lg">{{ $addOn->name }}</h3>
                    <p class="text-gray-600">${{ number_format($addOn->price, 2) }}</p>

                    <div class="mt-3 flex items-center">
                        <label class="mr-2 text-sm">Quantity:</label>
                        <input type="number" name="add_ons[{{ $addOn->id }}][qty]" value="0" min="0" step="0.5"
                            class="border rounded w-16 p-1 text-center addon-qty" data-price="{{ $addOn->price }}"
                            data-id="{{ $addOn->id }}" data-name="{{ $addOn->name }}">
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-lg font-semibold mb-4">Payment Details</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <div class="mb-4">
                        <label class="block text-gray-700 mb-2">Payment Method</label>
                        <div class="flex items-center space-x-4">
                            @foreach(config('play.payment_methods', ['USD' => 'USD', 'LBP' => 'LBP']) as $code => $name)
                            <label class="inline-flex items-center">
                                <input type="radio" name="payment_method" value="{{ $code }}"
                                    class="form-radio payment-method" @if($loop->first) checked @endif>
                                <span class="ml-2">{{ $name }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>

                    <div id="lbp-payment-section" class="mb-4">
                        <label class="block text-gray-700 mb-2">Total (LBP)</label>
                        <div class="text-xl font-bold mb-4" id="total-lbp">0 L.L.</div>

                        <label class="block text-gray-700 mb-2">Amount Paid (LBP)</label>
                        <input type="text" name="amount_paid" id="amount-paid-lbp" class="border rounded p-2 w-full"
                            value="0" oninput="validateForm()">
                    </div>

                    <div id="usd-payment-section" class="mb-4 hidden">
                        <label class="block text-gray-700 mb-2">Total (USD)</label>
                        <div class="text-xl font-bold mb-4" id="total-usd">$0.00</div>

                        <label class="block text-gray-700 mb-2">Amount Paid (USD)</label>
                        <input type="text" name="amount_paid" id="amount-paid-usd" class="border rounded p-2 w-full"
                            value="0" oninput="validateForm()">
                    </div>
                </div>

                <div>
                    <div class="bg-gray-100 p-4 rounded-lg">
                        <h3 class="font-medium mb-3">Order Summary</h3>

                        <div id="order-summary" class="space-y-2 mb-4 min-h-[100px]">
                            <p class="text-gray-500 italic text-center" id="no-items-message">No add-ons selected</p>
                        </div>

                        <div class="border-t pt-3">
                            <div class="flex justify-between font-medium">
                                <span>Total:</span>
                                <span id="total-display">$0.00</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <a href="{{ route('cashier.sales.index') }}"
                    class="bg-gray-200 text-gray-700 px-4 py-2 rounded mr-2 hover:bg-gray-300">
                    Cancel
                </a>
                <button type="submit" id="submit-btn"
                    class="bg-primary text-white px-4 py-2 rounded hover:bg-primary-dark">
                    Complete Sale
                </button>
            </div>
        </div>
    </form>
</div>

<!-- Customer Selection Modal -->
<div id="customer-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-lg w-11/12 md:w-3/4 lg:w-1/2 max-w-4xl mx-4 flex flex-col" style="max-height: 80vh;">
        <div class="flex justify-between items-center mb-4 p-6 border-b">
            <h2 class="text-xl font-bold">Select Customer</h2>
            <button onclick="closeCustomerModal()" class="text-gray-500 hover:text-gray-700">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div class="px-6 pb-2">
            <input type="text" id="child-search" placeholder="Search children..."
                class="border border-gray-300 rounded w-full p-2">
        </div>

        <div class="flex-1 overflow-y-auto px-6 pb-6">
            <div id="children-list" class="space-y-2">
                <!-- Children will be dynamically loaded here -->
                @if(isset($children) && count($children) > 0)
                @foreach($children as $child)
                <div class="child-item p-3 border rounded-lg hover:bg-gray-50 cursor-pointer"
                    onclick="selectChild({{ $child->id }}, '{{ addslashes($child->name) }}')">
                    <div class="flex justify-between items-center">
                        <div>
                            <div class="font-medium">{{ $child->name }}</div>
                            @if($child->guardian_name)
                            <div class="text-sm text-gray-500">Parent: {{ $child->guardian_name }}</div>
                            @endif
                        </div>
                        <div class="bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-sm font-medium">
                            {{ $child->play_sessions_count ?? 0 }} sessions
                        </div>
                    </div>
                </div>
                @endforeach
                @else
                <div class="text-center py-4 text-gray-500">
                    No registered children found.
                    <a href="{{ route('cashier.children.create') }}" class="text-blue-600 hover:text-blue-800">Register
                        a child</a>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
// LBP Exchange rate
const LBP_RATE = {
    {
        config('play.lbp_exchange_rate', 90000)
    }
};

// Initial setup
document.addEventListener('DOMContentLoaded', function() {
    // Set up customer selection
    document.getElementById('change-customer-btn').addEventListener('click', function() {
        document.getElementById('customer-modal').classList.remove('hidden');
    });

    // Set up payment method changes
    document.querySelectorAll('.payment-method').forEach(radio => {
        radio.addEventListener('change', function() {
            togglePaymentSections();
            updateTotalDisplay();
        });
    });

    // Add event listeners to all addon quantity inputs
    document.querySelectorAll('.addon-qty').forEach(input => {
        input.addEventListener('input', function() {
            updateAllCalculations();
        });
        input.addEventListener('change', function() {
            updateAllCalculations();
        });
    });

    // Set up form submission
    document.getElementById('addon-sale-form').addEventListener('submit', function(e) {
        const childId = document.getElementById('child_id').value;
        const total = calculateTotal();

        if (!childId) {
            e.preventDefault();
            alert('Please select a child before completing the sale.');
            return false;
        }

        if (total <= 0) {
            e.preventDefault();
            alert(
                'Please add at least one add-on with quantity greater than 0 before completing the sale.'
            );
            return false;
        }

        // Validate that amount paid is sufficient
        const method = document.querySelector('input[name="payment_method"]:checked').value;
        let amountPaid = 0;

        if (method === 'LBP') {
            amountPaid = parseFloat(document.getElementById('amount-paid-lbp').value) || 0;
            const totalLBP = Math.round(total * LBP_RATE);
            if (amountPaid < totalLBP) {
                e.preventDefault();
                alert(
                    `Amount paid (${amountPaid.toLocaleString()} L.L) is less than total (${totalLBP.toLocaleString()} L.L)`
                );
                return false;
            }
            document.getElementById('amount-paid-lbp').setAttribute('name', 'amount_paid');
            document.getElementById('amount-paid-usd').removeAttribute('name');
        } else {
            amountPaid = parseFloat(document.getElementById('amount-paid-usd').value) || 0;
            if (amountPaid < total) {
                e.preventDefault();
                alert(
                    `Amount paid ($${amountPaid.toFixed(2)}) is less than total ($${total.toFixed(2)})`
                );
                return false;
            }
            document.getElementById('amount-paid-usd').setAttribute('name', 'amount_paid');
            document.getElementById('amount-paid-lbp').removeAttribute('name');
        }

        console.log('Form submitted with child:', childId, 'total:', total, 'amount paid:', amountPaid);
        return true;
    });

    // Initialize all calculations
    updateAllCalculations();
    togglePaymentSections();
});

function closeCustomerModal() {
    document.getElementById('customer-modal').classList.add('hidden');
}

function selectChild(id, name) {
    console.log('Selecting child:', id, name); // Debug log

    const childIdField = document.getElementById('child_id');
    const customerDisplay = document.getElementById('customer-display');

    if (childIdField && customerDisplay) {
        childIdField.value = id;
        customerDisplay.textContent = name;
        closeCustomerModal();
        validateForm();

        // Show success feedback
        customerDisplay.classList.add('text-green-600');
        setTimeout(() => {
            customerDisplay.classList.remove('text-green-600');
        }, 1000);
    } else {
        console.error('Could not find child_id or customer-display elements');
    }
}

function updateAllCalculations() {
    calculateTotal();
    updateTotalDisplay();
    updateOrderSummary();
    validateForm();
}

function updateOrderSummary() {
    let total = 0;
    let hasItems = false;
    const orderSummary = document.getElementById('order-summary');

    // Clear previous summary
    orderSummary.innerHTML = '';

    // Build summary
    document.querySelectorAll('.addon-qty').forEach(input => {
        const qty = parseFloat(input.value) || 0;
        if (qty > 0) {
            hasItems = true;
            const price = parseFloat(input.dataset.price);
            const id = input.dataset.id;
            const name = input.dataset.name || input.closest('.border').querySelector('h3').textContent;
            const itemTotal = qty * price;
            total += itemTotal;

            // Add to summary
            const itemElement = document.createElement('div');
            itemElement.className = 'flex justify-between';
            itemElement.innerHTML = `
                    <span>${name} x ${qty}</span>
                    <span>$${itemTotal.toFixed(2)}</span>
                `;
            orderSummary.appendChild(itemElement);
        }
    });

    // Show no items message if needed
    if (!hasItems) {
        orderSummary.innerHTML =
            '<p class="text-gray-500 italic text-center" id="no-items-message">No add-ons selected</p>';
    }

    return total;
}

function calculateTotal() {
    let total = 0;

    // Calculate total from all add-ons
    document.querySelectorAll('.addon-qty').forEach(input => {
        const qty = parseFloat(input.value) || 0;
        if (qty > 0) {
            const price = parseFloat(input.dataset.price);
            total += qty * price;
        }
    });

    // Update all total displays
    document.getElementById('total-display').textContent = `$${total.toFixed(2)}`;
    document.getElementById('total-usd').textContent = `$${total.toFixed(2)}`;
    document.getElementById('total-lbp').textContent = `${Math.round(total * LBP_RATE).toLocaleString()} L.L.`;

    // Update form validation
    validateForm();

    return total;
}

function togglePaymentSections() {
    const method = document.querySelector('input[name="payment_method"]:checked').value;
    const lbpSection = document.getElementById('lbp-payment-section');
    const usdSection = document.getElementById('usd-payment-section');

    if (method === 'LBP') {
        lbpSection.classList.remove('hidden');
        usdSection.classList.add('hidden');
    } else {
        lbpSection.classList.add('hidden');
        usdSection.classList.remove('hidden');
    }
}

function updateTotalDisplay() {
    const total = calculateTotal();
    const method = document.querySelector('input[name="payment_method"]:checked').value;

    // Update the total display in the order summary
    if (method === 'LBP') {
        const totalLBP = Math.round(total * LBP_RATE);
        document.getElementById('total-display').textContent = `${totalLBP.toLocaleString()} L.L`;
        // Auto-fill the amount paid field with the total (can be modified by user)
        if (total > 0) {
            document.getElementById('amount-paid-lbp').value = totalLBP;
        }
    } else {
        document.getElementById('total-display').textContent = `$${total.toFixed(2)}`;
        // Auto-fill the amount paid field with the total (can be modified by user)
        if (total > 0) {
            document.getElementById('amount-paid-usd').value = total.toFixed(2);
        }
    }
}

function validateForm() {
    const childId = document.getElementById('child_id').value;
    const total = calculateTotal();
    const submitBtn = document.getElementById('submit-btn');

    // Only disable the button if no child selected or no items added
    if (!childId || total <= 0) {
        submitBtn.disabled = true;
        submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
    } else {
        submitBtn.disabled = false;
        submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
    }
}

// Filter children in the modal
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('child-search')) {
        document.getElementById('child-search').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            document.querySelectorAll('.child-item').forEach(item => {
                const childName = item.querySelector('.font-medium').textContent.toLowerCase();
                const parentName = item.querySelector('.text-gray-500')?.textContent
                    .toLowerCase() || '';

                if (childName.includes(searchTerm) || parentName.includes(searchTerm)) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    }
});
</script>
@endsection