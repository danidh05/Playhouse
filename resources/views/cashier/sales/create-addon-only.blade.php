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
                @if($selectedChild)
                {{ $selectedChild->name }}
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

    <form id="addon-sale-form" action="{{ route('cashier.sales.store-addon-only') }}" method="POST">
        @csrf
        <input type="hidden" id="child_id" name="child_id" value="{{ $selectedChild?->id ?? '' }}">

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Add-ons Selection -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-lg font-semibold mb-4">Available Add-ons</h2>

                <div class="space-y-4">
                    @foreach($addOns as $addOn)
                    <div class="border rounded-lg p-4 hover:bg-blue-50 transition">
                        <div class="flex justify-between items-center">
                            <div>
                                <h3 class="font-medium text-lg">{{ $addOn->name }}</h3>
                                <p class="text-gray-600 text-sm">Base Price: ${{ number_format($addOn->price, 2) }}</p>
                            </div>
                            <div class="flex items-center space-x-2">
                                <label class="text-sm">Qty:</label>
                                <input type="number" name="add_ons[{{ $addOn->id }}][qty]" value="0" 
                                    min="0" step="0.5" class="border rounded w-16 p-1 text-center addon-qty" 
                                    data-price="{{ $addOn->price }}" data-id="{{ $addOn->id }}" 
                                    data-name="{{ $addOn->name }}">
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- Payment and Summary -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-lg font-semibold mb-4">Payment Details</h2>

                <!-- Order Summary -->
                <div class="bg-gray-100 p-4 rounded-lg mb-6">
                    <h3 class="font-medium mb-3">Order Summary</h3>
                    <div id="order-summary" class="space-y-2 mb-4 min-h-[80px]">
                        <p class="text-gray-500 italic text-center">No add-ons selected</p>
                    </div>
                    <div class="border-t pt-3">
                        <div class="flex justify-between font-medium">
                            <span>Calculated Total:</span>
                            <span id="calculated-total">$0.00</span>
                        </div>
                    </div>
                </div>

                <!-- Payment Method -->
                <div class="mb-4">
                    <label class="block text-gray-700 mb-2 font-medium">Payment Method</label>
                    <div class="flex items-center space-x-4">
                        <label class="inline-flex items-center">
                            <input type="radio" name="payment_method" value="USD" class="form-radio payment-method" checked>
                            <span class="ml-2">USD</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="radio" name="payment_method" value="LBP" class="form-radio payment-method">
                            <span class="ml-2">LBP</span>
                        </label>
                    </div>
                </div>

                <!-- Custom Total -->
                <div class="mb-4">
                    <label for="custom_total" class="block text-gray-700 mb-2 font-medium">
                        Total Cost <span class="text-sm text-gray-500">(You can adjust this)</span>
                    </label>
                    <div class="relative">
                        <span id="currency-symbol" class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500">$</span>
                        <input type="number" id="custom_total" name="custom_total" step="0.01" min="0.01" 
                            class="border rounded p-2 w-full pl-8" value="0" required>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">This is the amount the customer should pay</p>
                </div>

                <!-- Amount Paid -->
                <div class="mb-4">
                    <label for="amount_paid" class="block text-gray-700 mb-2 font-medium">Amount Paid</label>
                    <div class="relative">
                        <span id="paid-currency-symbol" class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500">$</span>
                        <input type="number" id="amount_paid" name="amount_paid" step="0.01" min="0" 
                            class="border rounded p-2 w-full pl-8" value="0" required>
                    </div>
                    <div id="change-display" class="text-sm mt-1 hidden">
                        <span class="text-green-600 font-medium">Change due: <span id="change-amount">$0.00</span></span>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="flex justify-end space-x-2">
                    <a href="{{ route('cashier.sales.index') }}" 
                        class="bg-gray-200 text-gray-700 px-4 py-2 rounded hover:bg-gray-300">
                        Cancel
                    </a>
                    <button type="submit" id="submit-btn" 
                        class="bg-primary text-white px-4 py-2 rounded hover:bg-primary-dark opacity-50 cursor-not-allowed" 
                        disabled>
                        Complete Sale
                    </button>
                </div>
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
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div class="px-6 pb-2">
            <input type="text" id="child-search" placeholder="Search children..." class="border border-gray-300 rounded w-full p-2">
        </div>

        <div class="flex-1 overflow-y-auto px-6 pb-6">
            <div id="children-list" class="space-y-2">
                @forelse($children as $child)
                <div class="child-item p-3 border rounded-lg hover:bg-gray-50 cursor-pointer"
                    onclick="selectChild({{ $child->id }}, '{{ addslashes($child->name) }}')">
                    <div class="flex justify-between items-center">
                        <div>
                            <div class="font-medium">{{ $child->name }}</div>
                            @if($child->guardian_name)
                            <div class="text-sm text-gray-500">Parent: {{ $child->guardian_name }}</div>
                            @endif
                        </div>
                    </div>
                </div>
                @empty
                <div class="text-center py-4 text-gray-500">
                    No registered children found.
                    <a href="{{ route('cashier.children.create') }}" class="text-blue-600 hover:text-blue-800">Register a child</a>
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
const LBP_RATE = {{ config('play.lbp_exchange_rate', 90000) }};

document.addEventListener('DOMContentLoaded', function() {
    // Set up event listeners
    setupEventListeners();
    
    // Initial calculations
    updateAllCalculations();
});

function setupEventListeners() {
    // Customer selection
    document.getElementById('change-customer-btn')?.addEventListener('click', function() {
        document.getElementById('customer-modal').classList.remove('hidden');
    });
    
    // Payment method changes
    document.querySelectorAll('.payment-method').forEach(radio => {
        radio.addEventListener('change', function() {
            updateCurrencySymbols();
            updateTotalFromCalculated();
        });
    });
    
    // Add-on quantity changes
    document.querySelectorAll('.addon-qty').forEach(input => {
        input.addEventListener('input', updateAllCalculations);
        input.addEventListener('change', updateAllCalculations);
    });
    
    // Custom total changes
    document.getElementById('custom_total')?.addEventListener('input', updateChangeDisplay);
    
    // Amount paid changes
    document.getElementById('amount_paid')?.addEventListener('input', updateChangeDisplay);
    
    // Child search
    document.getElementById('child-search')?.addEventListener('input', function(e) {
        const searchTerm = e.target.value.toLowerCase();
        document.querySelectorAll('.child-item').forEach(item => {
            const childName = item.querySelector('.font-medium')?.textContent.toLowerCase() || '';
            const parentElement = item.querySelector('.text-gray-500');
            const parentName = parentElement ? parentElement.textContent.toLowerCase() : '';
            
            if (childName.includes(searchTerm) || parentName.includes(searchTerm)) {
                item.style.display = 'block';
            } else {
                item.style.display = 'none';
            }
        });
    });
    
    // Form submission
    document.getElementById('addon-sale-form')?.addEventListener('submit', function(e) {
        const childId = document.getElementById('child_id').value;
        const customTotal = parseFloat(document.getElementById('custom_total').value) || 0;
        const amountPaid = parseFloat(document.getElementById('amount_paid').value) || 0;
        
        if (!childId) {
            e.preventDefault();
            alert('Please select a child before completing the sale.');
            return false;
        }
        
        if (customTotal <= 0) {
            e.preventDefault();
            alert('Please enter a valid total cost.');
            return false;
        }
        
        if (amountPaid < customTotal) {
            e.preventDefault();
            alert('Amount paid must be at least the total cost.');
            return false;
        }
        
        // Check if any add-ons are selected
        const hasAddOns = Array.from(document.querySelectorAll('.addon-qty')).some(input => parseFloat(input.value) > 0);
        if (!hasAddOns) {
            e.preventDefault();
            alert('Please select at least one add-on.');
            return false;
        }
        
        return true;
    });
}

function updateAllCalculations() {
    updateOrderSummary();
    updateTotalFromCalculated();
    updateChangeDisplay();
    validateForm();
}

function updateOrderSummary() {
    const orderSummary = document.getElementById('order-summary');
    const calculatedTotal = document.getElementById('calculated-total');
    
    let total = 0;
    let hasItems = false;
    orderSummary.innerHTML = '';
    
    document.querySelectorAll('.addon-qty').forEach(input => {
        const qty = parseFloat(input.value) || 0;
        if (qty > 0) {
            hasItems = true;
            const price = parseFloat(input.dataset.price); // USD base price
            const name = input.dataset.name;
            
            // Calculate in selected currency for display
            const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;
            const itemPrice = paymentMethod === 'LBP' ? price * LBP_RATE : price;
            const itemTotal = qty * itemPrice;
            total += itemTotal;
            
            // Add to summary
            const itemElement = document.createElement('div');
            itemElement.className = 'flex justify-between text-sm';
            itemElement.innerHTML = `
                <span>${name} x ${qty}</span>
                <span>${paymentMethod === 'LBP' ? Math.round(itemTotal).toLocaleString() + ' L.L' : '$' + itemTotal.toFixed(2)}</span>
            `;
            orderSummary.appendChild(itemElement);
        }
    });
    
    if (!hasItems) {
        orderSummary.innerHTML = '<p class="text-gray-500 italic text-center">No add-ons selected</p>';
    }
    
    // Update calculated total display
    const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;
    calculatedTotal.textContent = paymentMethod === 'LBP' ? 
        Math.round(total).toLocaleString() + ' L.L' : 
        '$' + total.toFixed(2);
    
    return total;
}

function updateTotalFromCalculated() {
    const calculatedTotal = updateOrderSummary();
    const customTotalInput = document.getElementById('custom_total');
    
    if (calculatedTotal > 0) {
        customTotalInput.value = Math.round(calculatedTotal * 100) / 100;
    }
}

function updateCurrencySymbols() {
    const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;
    const symbol = paymentMethod === 'LBP' ? 'L.L ' : '$';
    
    document.getElementById('currency-symbol').textContent = symbol;
    document.getElementById('paid-currency-symbol').textContent = symbol;
}

function updateChangeDisplay() {
    const customTotal = parseFloat(document.getElementById('custom_total').value) || 0;
    const amountPaid = parseFloat(document.getElementById('amount_paid').value) || 0;
    const changeDisplay = document.getElementById('change-display');
    const changeAmount = document.getElementById('change-amount');
    
    if (amountPaid > customTotal && customTotal > 0) {
        const change = amountPaid - customTotal;
        const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;
        
        changeAmount.textContent = paymentMethod === 'LBP' ? 
            Math.round(change).toLocaleString() + ' L.L' : 
            '$' + change.toFixed(2);
        
        changeDisplay.classList.remove('hidden');
    } else {
        changeDisplay.classList.add('hidden');
    }
}

function validateForm() {
    const childId = document.getElementById('child_id').value;
    const customTotal = parseFloat(document.getElementById('custom_total').value) || 0;
    const hasAddOns = Array.from(document.querySelectorAll('.addon-qty')).some(input => parseFloat(input.value) > 0);
    const submitBtn = document.getElementById('submit-btn');
    
    const isValid = childId && customTotal > 0 && hasAddOns;
    
    submitBtn.disabled = !isValid;
    submitBtn.classList.toggle('opacity-50', !isValid);
    submitBtn.classList.toggle('cursor-not-allowed', !isValid);
}

function closeCustomerModal() {
    document.getElementById('customer-modal').classList.add('hidden');
}

function selectChild(id, name) {
    document.getElementById('child_id').value = id;
    document.getElementById('customer-display').textContent = name;
    closeCustomerModal();
    validateForm();
}
</script>
@endsection 