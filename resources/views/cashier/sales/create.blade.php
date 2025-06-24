@extends('layouts.cashier-layout')

@section('title', 'Sell')

@section('head')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('toolbar')
<div class="px-4 py-2 bg-white border-b flex justify-between items-center">
    <div>
        <div class="relative">
            <input type="text" id="product-search" placeholder="Search Products"
                class="border border-gray-300 rounded-lg pl-8 pr-4 py-1 w-64">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400 absolute left-2 top-2" fill="none"
                viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
        </div>
    </div>

    <div class="flex items-center">
        <!-- Customer selection -->
        <div class="flex items-center mr-4">
            <span class="text-sm text-gray-600 mr-2">Customer:</span>
            <div id="customer-display" class="text-primary font-medium">
                @if(request()->has('child_id') && request()->child_id)
                {{ \App\Models\Child::find(request()->child_id)->name ?? 'Select Child' }}
                @else
                Walk-in Customer
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
<div class="flex h-full">
    <!-- Snacks Grid (Left side) -->
    <div class="w-3/4 p-4 overflow-y-auto">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">New Sale</h1>
            <div class="flex space-x-4">
                <div class="relative inline-block">
                    <select id="currency-selector"
                        class="form-select rounded-md shadow-sm border-gray-300 focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 pr-10">
                        <option value="usd">USD ($)</option>
                        <option value="lbp">LBP (Lebanese Pound)</option>
                    </select>
                </div>
                <a href="{{ route('cashier.sales.index') }}"
                    class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">
                    Back to Sales
                </a>
            </div>
        </div>

        <div class="grid grid-cols-4 gap-4">
            @foreach($products as $product)
            <div class="bg-blue-100 p-3 rounded-lg cursor-pointer hover:bg-blue-200 transition product-item"
                data-id="{{ $product->id }}" data-name="{{ $product->name }}" data-price="{{ $product->price }}"
                data-price-lbp="{{ $product->price_lbp }}" data-stock="{{ $product->stock_qty }}"
                onclick="addToOrder(this)">
                <div class="text-center">
                    <div class="font-medium text-gray-800">{{ $product->name }}</div>
                    <div class="text-gray-600">${{ number_format($product->price, 2) }}</div>
                    <div class="text-gray-600">{{ number_format($product->price_lbp, 0) }} LBP</div>
                    <div class="text-xs text-gray-500">Stock: {{ $product->stock_qty }}</div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <!-- Order Panel (Right side) -->
    <div class="w-1/4 bg-white border-l">
        <div class="p-4 border-b">
            <h2 class="font-bold text-lg">Order Summary</h2>
            <div class="flex justify-between text-sm font-medium text-gray-600 mt-2">
                <div>Item</div>
                <div>Qty</div>
                <div>Price</div>
            </div>
        </div>

        <div id="order-items" class="overflow-y-auto p-2" style="height: 40vh; min-height: 200px;">
            <!-- Order items will be added here dynamically -->
        </div>

        <div class="p-4 border-t">
            <div class="flex justify-between font-medium mb-2">
                <div>Total Price:</div>
                <div id="total-price" class="text-right">$0.00</div>
            </div>

            <div class="mb-3">
                <div class="font-medium mb-1">Payment Method:</div>
                <div class="flex items-center space-x-2 mb-3">
                    <label class="inline-flex items-center">
                        <input type="radio" name="payment_method" value="LBP" checked class="form-radio">
                        <span class="ml-2 text-gray-700">LBP</span>
                    </label>
                    <label class="inline-flex items-center">
                        <input type="radio" name="payment_method" value="USD" class="form-radio">
                        <span class="ml-2 text-gray-700">USD</span>
                    </label>
                </div>

                <!-- USD Payment Section -->
                <div id="usd-payment-section" class="hidden">
                    <div class="flex justify-between font-medium mb-2">
                        <div>Total in USD:</div>
                        <div id="total-price-usd" class="text-right">$0.00</div>
                    </div>
                    <div class="font-medium mb-1">Cash Amount (USD):</div>
                    <div class="flex items-center">
                        <input type="text" id="cash-input-usd" class="border rounded p-1 w-full"
                            oninput="calculateChangeUSD()">
                    </div>
                    <div class="flex justify-between font-medium mt-2 mb-4">
                        <div>Change (USD):</div>
                        <div id="change-amount-usd" class="text-right">$0.00</div>
                    </div>
                </div>

                <!-- LBP Payment Section -->
                <div id="lbp-payment-section">
                    <div class="flex justify-between font-medium mb-2">
                        <div>Total in LBP:</div>
                        <div id="total-price-lbp" class="text-right">0 L.L</div>
                    </div>
                    <div class="font-medium mb-1">Cash Amount (LBP):</div>
                    <div class="flex items-center">
                        <input type="text" id="cash-input-lbp" class="border rounded p-1 w-full"
                            oninput="calculateChangeLBP()">
                    </div>
                    <div class="flex justify-between font-medium mt-2 mb-4">
                        <div>Change (LBP):</div>
                        <div id="change-amount-lbp" class="text-right">0 L.L</div>
                    </div>
                </div>
            </div>

            <button id="pay-button"
                class="w-full bg-primary text-white p-2 rounded-lg hover:bg-primary-dark disabled:bg-gray-300 disabled:cursor-not-allowed"
                onclick="processSale()" disabled>
                COMPLETE SALE
            </button>
        </div>
    </div>
</div>

<!-- Customer Selection Modal -->
<div id="customer-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-lg w-11/12 md:w-3/4 lg:w-1/2 mx-4 flex flex-col" style="max-height: 80vh;">
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

        <div class="px-6 py-2">
            <button onclick="selectWalkInCustomer()"
                class="w-full bg-gray-100 hover:bg-gray-200 p-3 rounded-lg flex items-center justify-between">
                <div>
                    <div class="font-medium">Walk-in Customer</div>
                    <div class="text-sm text-gray-500">No registration needed</div>
                </div>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500" viewBox="0 0 20 20"
                    fill="currentColor">
                    <path fill-rule="evenodd"
                        d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                        clip-rule="evenodd" />
                </svg>
            </button>
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
                    <a href="{{ route('cashier.children.create') }}" class="text-blue-600 hover:text-blue-800">Register a child</a>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Sale Completion Modal -->
<div id="completion-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-lg p-6 w-96">
        <h2 class="text-xl font-bold mb-4">Sale Complete!</h2>
        <p class="mb-4">The sale has been processed successfully.</p>
        <div class="flex justify-end">
            <button onclick="closeModal()" class="bg-primary text-white px-4 py-2 rounded hover:bg-primary-dark">
                OK
            </button>
        </div>
    </div>
</div>

<form id="sale-form" action="{{ route('cashier.sales.store') }}" method="POST" class="hidden">
    @csrf
    <input type="hidden" name="items" id="products-input">
    <input type="hidden" name="total_amount" id="total-amount-input">
    <input type="hidden" name="total_amount_lbp" id="total-amount-lbp-input">
    <input type="hidden" name="payment_method" id="payment-method-input" value="LBP">
    <input type="hidden" name="amount_paid" id="amount-paid-input" value="0">
    <input type="hidden" name="shift_id" value="{{ $activeShift ? $activeShift->id : '' }}">
    <input type="hidden" name="child_id" id="child-id-input" value="{{ request()->child_id ?? '' }}">
    <input type="hidden" name="currency" id="currency-input" value="usd">
</form>
@endsection

@section('scripts')
<script>
// Global variables and functions - these need to be defined BEFORE any HTML elements try to use them
let orderItems = [];
let totalPrice = 0;
let selectedChildId = "{{ request()->child_id ?? '' }}";
// Use JS comments to prevent Prettier from breaking the Blade syntax
const LBP_RATE = <?php echo config('play.lbp_exchange_rate', 90000); ?>;
// Fixed the syntax error
let currency = 'usd';

// Define all critical functions in global scope
function addToOrder(element) {
    console.log('addToOrder called with element:', element);

    const id = element.getAttribute('data-id');
    const name = element.getAttribute('data-name');
    const priceUsd = parseFloat(element.getAttribute('data-price'));
    const priceLbp = parseFloat(element.getAttribute('data-price-lbp'));
    const stock = parseInt(element.getAttribute('data-stock'));

    console.log('Product data:', {
        id,
        name,
        priceUsd,
        priceLbp,
        stock
    });

    // Check for existing item in cart
    const orderItems = document.getElementById('order-items');
    const existingItem = orderItems.querySelector(`.order-item[data-id="${id}"]`);

    console.log('Order items container:', orderItems);
    console.log('Existing item found:', existingItem);

    if (existingItem) {
        // Update quantity if already in cart
        const qtyElement = existingItem.querySelector('.item-qty');
        let currentQty = parseInt(qtyElement.textContent);

        if (currentQty < stock) {
            currentQty++;
            qtyElement.textContent = currentQty;

            // Update price display
            const priceElement = existingItem.querySelector('.item-price');
            const price = currency === 'usd' ? priceUsd : priceLbp;
            const totalItemPrice = price * currentQty;

            if (currency === 'usd') {
                priceElement.textContent = `$${totalItemPrice.toFixed(2)}`;
            } else {
                priceElement.textContent = `${totalItemPrice.toLocaleString()} LBP`;
            }

            // Update qty input
            const qtyInput = existingItem.querySelector(`input[name="items[${id}][qty]"]`);
            qtyInput.value = currentQty;

            console.log('Updated existing item quantity to:', currentQty);
        } else {
            alert('Cannot add more of this item - stock limit reached');
        }
    } else {
        // Create new item in cart - this code only runs when an item doesn't exist yet
        const itemDiv = document.createElement('div');
        itemDiv.className = 'order-item flex justify-between items-center p-2 border-b';
        itemDiv.setAttribute('data-id', id);

        const price = currency === 'usd' ? priceUsd : priceLbp;
        const priceDisplay = currency === 'usd' ? `$${price.toFixed(2)}` : `${price.toLocaleString()} LBP`;

        itemDiv.innerHTML = `
                <div class="flex-1">${name}</div>
                <div class="flex items-center mx-2">
                    <button type="button" class="bg-gray-200 px-2 py-1 rounded" onclick="decrementQty('${id}')">-</button>
                    <span class="mx-2 item-qty">1</span>
                    <button type="button" class="bg-blue-500 px-2 py-1 rounded text-white" onclick="incrementQty('${id}', ${stock})">+</button>
                </div>
                <div class="ml-2 item-price">${priceDisplay}</div>
                <button type="button" class="ml-2 text-red-500" onclick="removeOrderItem('${id}')">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
                <input type="hidden" name="items[${id}][id]" value="${id}">
                <input type="hidden" name="items[${id}][qty]" value="1">
                <input type="hidden" name="items[${id}][price]" value="${price}">
                <input type="hidden" name="items[${id}][price_usd]" value="${priceUsd}">
                <input type="hidden" name="items[${id}][price_lbp]" value="${priceLbp}">
            `;

        console.log('Created new item div:', itemDiv);
        orderItems.appendChild(itemDiv);
        console.log('Added new item to cart');
    }

    console.log('Updating total price');
    updateTotalPrice();
}

function decrementQty(id) {
    const orderItem = document.querySelector(`.order-item[data-id="${id}"]`);
    const qtyElement = orderItem.querySelector('.item-qty');
    let currentQty = parseInt(qtyElement.textContent);

    if (currentQty > 1) {
        currentQty--;
        qtyElement.textContent = currentQty;

        // Update price display
        updateItemPrice(orderItem, currentQty);

        // Update qty input
        const qtyInput = orderItem.querySelector(`input[name="items[${id}][qty]"]`);
        qtyInput.value = currentQty;
    } else {
        removeOrderItem(id);
    }

    updateTotalPrice();
}

function incrementQty(id, maxStock) {
    const orderItem = document.querySelector(`.order-item[data-id="${id}"]`);
    const qtyElement = orderItem.querySelector('.item-qty');
    let currentQty = parseInt(qtyElement.textContent);

    if (currentQty < maxStock) {
        currentQty++;
        qtyElement.textContent = currentQty;

        // Update price display
        updateItemPrice(orderItem, currentQty);

        // Update qty input
        const qtyInput = orderItem.querySelector(`input[name="items[${id}][qty]"]`);
        qtyInput.value = currentQty;
    } else {
        alert('Cannot add more of this item - stock limit reached');
    }

    updateTotalPrice();
}

function updateItemPrice(orderItem, qty) {
    const id = orderItem.getAttribute('data-id');
    const priceUsdInput = orderItem.querySelector(`input[name="items[${id}][price_usd]"]`);
    const priceLbpInput = orderItem.querySelector(`input[name="items[${id}][price_lbp]"]`);
    const priceInput = orderItem.querySelector(`input[name="items[${id}][price]"]`);
    const priceElement = orderItem.querySelector('.item-price');

    // Get price in current currency
    let price;
    if (currency === 'usd') {
        price = parseFloat(priceUsdInput.value);
        priceInput.value = price;
        priceElement.textContent = `$${(price * qty).toFixed(2)}`;
    } else {
        price = parseFloat(priceLbpInput.value);
        priceInput.value = price;
        priceElement.textContent = `${(price * qty).toLocaleString()} LBP`;
    }
}

function removeOrderItem(id) {
    const orderItem = document.querySelector(`.order-item[data-id="${id}"]`);
    orderItem.remove();
    updateTotalPrice();
}

function updateTotalPrice() {
    console.log('updateTotalPrice called');

    const totalPriceElement = document.getElementById('total-price');
    const totalPriceLbpElement = document.getElementById('total-price-lbp');
    const totalPriceUsdElement = document.getElementById('total-price-usd');
    const orderItemElements = document.querySelectorAll('.order-item');

    console.log('Total price elements:', {
        totalPriceElement,
        totalPriceLbpElement,
        totalPriceUsdElement
    });
    console.log('Order items count:', orderItemElements.length);

    let total = 0;

    orderItemElements.forEach(item => {
        const id = item.getAttribute('data-id');
        const qtyElement = item.querySelector('.item-qty');
        const qty = parseInt(qtyElement.textContent);

        let price;
        if (currency === 'usd') {
            price = parseFloat(item.querySelector(`input[name="items[${id}][price_usd]"]`).value);
        } else {
            price = parseFloat(item.querySelector(`input[name="items[${id}][price_lbp]"]`).value);
        }

        const itemTotal = price * qty;
        total += itemTotal;

        console.log('Item total:', {
            id,
            qty,
            price,
            itemTotal,
            runningTotal: total
        });
    });

    // Update global totalPrice variable
    totalPrice = total;
    console.log('New total price:', totalPrice);

    // Update total price display
    if (currency === 'usd') {
        totalPriceElement.textContent = `$${total.toFixed(2)}`;
        // For USD currency, USD total is the same, LBP total is converted
        totalPriceUsdElement.textContent = `$${total.toFixed(2)}`;
        totalPriceLbpElement.textContent = `${Math.round(total * LBP_RATE).toLocaleString()} L.L`;
    } else {
        totalPriceElement.textContent = `${total.toLocaleString()} LBP`;
        // For LBP currency, LBP total is the same, USD total is converted
        totalPriceLbpElement.textContent = `${total.toLocaleString()} L.L`;
        // Convert LBP to USD by dividing
        const usdValue = total / LBP_RATE;
        totalPriceUsdElement.textContent = `$${usdValue.toFixed(2)}`;
    }

    console.log('Updated price displays');

    // Enable/disable pay button based on cart content
    const payButton = document.getElementById('pay-button');
    payButton.disabled = orderItemElements.length === 0;
    console.log('Pay button disabled:', payButton.disabled);
}

function updateOrderDisplay() {
    // Update price display for all items in the cart
    const orderItems = document.querySelectorAll('.order-item');

    orderItems.forEach(item => {
        const id = item.getAttribute('data-id');
        const qtyElement = item.querySelector('.item-qty');
        const qty = parseInt(qtyElement.textContent);
        const priceInput = item.querySelector(`input[name="items[${id}][price]"]`);

        updateItemPrice(item, qty);
    });

    updateTotalPrice();
}

function calculateChangeLBP() {
    const cashInput = document.getElementById('cash-input-lbp');
    const cashValue = parseFloat(cashInput.value.replace(/,/g, '')) || 0;

    // Calculate the total in LBP depending on the current currency
    let totalInLbp;
    if (currency === 'usd') {
        totalInLbp = Math.round(totalPrice * LBP_RATE);
    } else {
        // Currency is already LBP, so no conversion needed
        totalInLbp = totalPrice;
    }

    // Make sure the LBP total display is updated
    document.getElementById('total-price-lbp').textContent = `${totalInLbp.toLocaleString()} L.L`;

    const change = cashValue - totalInLbp;

    document.getElementById('change-amount-lbp').textContent = `${change.toLocaleString()} L.L`;

    // Only enable pay button if cash covers total price and there are items
    document.getElementById('pay-button').disabled = change < 0 || document.querySelectorAll('.order-item').length ===
        0;
}

function calculateChangeUSD() {
    const cashInput = document.getElementById('cash-input-usd');
    const cashValue = parseFloat(cashInput.value) || 0;

    // Calculate the total in USD depending on the current currency
    let totalInUsd;
    if (currency === 'lbp') {
        totalInUsd = totalPrice / LBP_RATE;
    } else {
        totalInUsd = totalPrice;
    }

    // Make sure the USD total display is correct
    document.getElementById('total-price-usd').textContent = `$${totalInUsd.toFixed(2)}`;

    const change = cashValue - totalInUsd;

    document.getElementById('change-amount-usd').textContent = `$${change.toFixed(2)}`;

    // Only enable pay button if cash covers total price and there are items
    document.getElementById('pay-button').disabled = change < 0 || document.querySelectorAll('.order-item').length ===
        0;
}

function processSale() {
    const orderItemElements = document.querySelectorAll('.order-item');
    console.log("Process sale called, order items:", orderItemElements.length);

    if (orderItemElements.length === 0) {
        console.log("No order items found, aborting");
        return;
    }

    try {
        // Collect order items data in the format expected by the controller
        const formItems = {};

        orderItemElements.forEach(item => {
            const id = item.getAttribute('data-id');
            const qty = parseInt(item.querySelector('.item-qty').textContent);

            // Set the price based on the current currency
            let price;
            if (currency === 'usd') {
                price = parseFloat(item.querySelector(`input[name="items[${id}][price_usd]"]`).value);
            } else {
                price = parseFloat(item.querySelector(`input[name="items[${id}][price_lbp]"]`).value);
            }

            // Use the structure expected by the controller: items[id][property]
            formItems[id] = {
                id: id,
                qty: qty,
                price: price
            };
        });

        console.log("Order items data:", formItems);

        // Get selected payment method
        const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;
        console.log("Payment method:", paymentMethod);

        // Always calculate and store in LBP (convert USD to LBP if needed)
        let totalAmountLBP = 0;
        const orderItems = document.querySelectorAll('.order-item');
        
        orderItems.forEach(item => {
            const qty = parseInt(item.querySelector('.item-qty').textContent);
            if (paymentMethod === 'LBP') {
                // Use LBP price directly
                const priceLbp = parseFloat(item.querySelector('input[name*="[price_lbp]"]').value);
                totalAmountLBP += qty * priceLbp;
            } else {
                // Convert USD price to LBP for storage
                const priceUsd = parseFloat(item.querySelector('input[name*="[price_usd]"]').value);
                totalAmountLBP += qty * (priceUsd * LBP_RATE);
            }
        });

        console.log("Total amount in LBP:", totalAmountLBP);

        // Get amount paid and convert to LBP for storage
        let amountPaidLBP;
        if (paymentMethod === 'LBP') {
            const cashInputLbp = document.getElementById('cash-input-lbp');
            amountPaidLBP = parseFloat(cashInputLbp.value.replace(/,/g, '')) || 0;
        } else {
            const cashInputUsd = document.getElementById('cash-input-usd');
            const amountPaidUSD = parseFloat(cashInputUsd.value) || 0;
            amountPaidLBP = amountPaidUSD * LBP_RATE; // Convert to LBP
        }

        console.log("Amount paid in LBP:", amountPaidLBP);

        // Disable the pay button and show loading state
        const payButton = document.getElementById('pay-button');
        const originalButtonText = payButton.textContent;
        payButton.disabled = true;
        payButton.textContent = 'Processing...';

        // Get form action from the form element
        const form = document.getElementById('sale-form');
        const formAction = form ? form.action : '{{ route("cashier.sales.store") }}';
        console.log("Form action URL:", formAction);

        // Debug variables
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        console.log("CSRF Token exists:", !!csrfToken);

        // Prepare form data
        const formData = new FormData();
        formData.append('_token', csrfToken);

        // Handle the items differently - turn the object into individual array elements
        Object.keys(formItems).forEach(productId => {
            const item = formItems[productId];
            formData.append(`items[${productId}][id]`, item.id);
            formData.append(`items[${productId}][qty]`, item.qty);
            formData.append(`items[${productId}][price]`, item.price);
        });

        // No longer needed as we're appending items individually
        // formData.append('items', JSON.stringify(formItems));

        // Always use the amount customer paid as the cost (both in LBP)
        formData.append('total_amount', amountPaidLBP);  // Use what customer paid as the cost
        formData.append('payment_method', 'LBP');  // Always store as LBP
        formData.append('original_payment_method', paymentMethod);  // Track original payment method
        formData.append('amount_paid', amountPaidLBP);

        // Add shift_id if available
        const shiftIdInput = document.querySelector('input[name="shift_id"]');
        if (shiftIdInput && shiftIdInput.value) {
            formData.append('shift_id', shiftIdInput.value);
            console.log("Shift ID:", shiftIdInput.value);
        } else {
            console.error("No shift ID found! This is required.");
        }

        // Add child_id if available
        if (selectedChildId) {
            formData.append('child_id', selectedChildId);
            console.log("Child ID:", selectedChildId);
        }

        // Print out all form data for debugging
        console.log("Form data entries:");
        for (const pair of formData.entries()) {
            console.log(pair[0] + ': ' + pair[1]);
        }

        // Use fetch API to submit the form via AJAX
        console.log("Sending fetch request to:", formAction);
        fetch(formAction, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(response => {
                console.log("Response status:", response.status);
                console.log("Response OK:", response.ok);

                // Even if not OK, try to get the JSON to see error details
                return response.json().catch(error => {
                    console.error("Error parsing response JSON:", error);
                    // If we can't parse JSON, return a custom error object
                    if (!response.ok) {
                        return {
                            success: false,
                            message: `Server returned ${response.status}: ${response.statusText}`
                        };
                    }
                    throw error;
                });
            })
            .then(data => {
                console.log('Response data:', data);

                if (!data.success && data.message) {
                    // If we have an error message from the server, show it
                    throw new Error(data.message || 'Unknown server error');
                }

                console.log('Sale completed successfully:', data);

                // Show the completion modal
                const completionModal = document.getElementById('completion-modal');
                if (completionModal) {
                    completionModal.classList.remove('hidden');
                }

                // Clear the cart
                document.getElementById('order-items').innerHTML = '';
                totalPrice = 0;
                updateTotalPrice();

                // Reset form fields
                document.getElementById('cash-input-lbp').value = '';
                document.getElementById('cash-input-usd').value = '';

                // Reset the button
                payButton.textContent = originalButtonText;
                payButton.disabled = true;
            })
            .catch(error => {
                console.error('Error during form submission:', error);
                alert('An error occurred while processing the sale: ' + error.message);

                // Reset the button
                payButton.textContent = originalButtonText;
                payButton.disabled = false;
            });
    } catch (error) {
        console.error("Error in processSale:", error);
        alert("An error occurred while processing the sale. Please check the console for details.");
    }
}

function closeModal() {
    document.getElementById('completion-modal').classList.add('hidden');
    // Clear the order
    orderItems = [];
    updateOrderDisplay();
    document.getElementById('cash-input-lbp').value = '';
    document.getElementById('cash-input-usd').value = '';
}

function closeCustomerModal() {
    document.getElementById('customer-modal').classList.add('hidden');
}

function selectWalkInCustomer() {
    selectedChildId = '';
    document.getElementById('customer-display').textContent = 'Walk-in Customer';
    document.getElementById('child-id-input').value = '';
    closeCustomerModal();
}

function selectChild(childId, childName) {
    selectedChildId = childId;
    document.getElementById('customer-display').textContent = childName;
    document.getElementById('child-id-input').value = childId;
    closeCustomerModal();
}

// Initialize the page when DOM is loaded - keeps event handlers in one place
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM fully loaded');
    console.log('Initial values:', {
        totalPrice,
        selectedChildId,
        LBP_RATE,
        currency
    });

    // Verify product click handlers are attached
    const productItems = document.querySelectorAll('.product-item');
    console.log('Product items found:', productItems.length);
    productItems.forEach(item => {
        console.log('Product item:', {
            id: item.getAttribute('data-id'),
            name: item.getAttribute('data-name'),
            price: item.getAttribute('data-price')
        });
    });

    document.querySelectorAll('input[name="payment_method"]').forEach(input => {
        input.addEventListener('change', function() {
            const lbpSection = document.getElementById('lbp-payment-section');
            const usdSection = document.getElementById('usd-payment-section');

            if (this.value === 'LBP') {
                lbpSection.style.display = 'block';
                usdSection.style.display = 'none';
                document.getElementById('cash-input-lbp').value = '';

                // Update the LBP total price display based on current currency
                let totalInLbp;
                if (currency === 'usd') {
                    totalInLbp = Math.round(totalPrice * LBP_RATE);
                } else {
                    // Currency is already LBP, no conversion needed
                    totalInLbp = totalPrice;
                }
                document.getElementById('total-price-lbp').textContent =
                    `${totalInLbp.toLocaleString()} L.L`;

                calculateChangeLBP();
            } else {
                lbpSection.style.display = 'none';
                usdSection.style.display = 'block';
                document.getElementById('cash-input-usd').value = '';

                // If current currency is LBP, convert to USD
                if (currency === 'lbp') {
                    const usdValue = totalPrice / LBP_RATE;
                    document.getElementById('total-price-usd').textContent =
                        `$${usdValue.toFixed(2)}`;
                } else {
                    // Current currency is already USD
                    document.getElementById('total-price-usd').textContent =
                        `$${totalPrice.toFixed(2)}`;
                }

                calculateChangeUSD();
            }

            // Enable pay button if we have items
            document.getElementById('pay-button').disabled = document.querySelectorAll(
                    '.order-item')
                .length === 0;
        });
    });

    // Filter products based on search
    document.getElementById('product-search').addEventListener('input', function() {
        const searchText = this.value.toLowerCase();
        document.querySelectorAll('.product-item').forEach(item => {
            const productName = item.getAttribute('data-name').toLowerCase();
            if (productName.includes(searchText)) {
                item.style.display = 'block';
            } else {
                item.style.display = 'none';
            }
        });
    });

    // Show customer selection modal
    document.getElementById('change-customer-btn').addEventListener('click', function() {
        document.getElementById('customer-modal').classList.remove('hidden');
    });

    // Filter children based on search
    if (document.getElementById('child-search')) {
        document.getElementById('child-search').addEventListener('input', function() {
            const searchText = this.value.toLowerCase();
            document.querySelectorAll('.child-item').forEach(item => {
                const childName = item.querySelector('.font-medium').textContent.toLowerCase();
                const parentName = item.querySelector('.text-gray-500')?.textContent.toLowerCase() || '';
                
                if (childName.includes(searchText) || parentName.includes(searchText)) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    }

    // Initialize currency selector
    const currencySelector = document.getElementById('currency-selector');
    const currencyInput = document.getElementById('currency-input');

    currencySelector.addEventListener('change', function() {
        currency = this.value;
        currencyInput.value = currency;
        updateOrderDisplay();
    });

    // Initialize currency from selector
    currency = currencySelector.value;
    currencyInput.value = currency;

    // Initialize the payment method options
    if (document.querySelector('input[name="payment_method"]:checked')) {
        const selectedMethod = document.querySelector('input[name="payment_method"]:checked').value;
        const lbpSection = document.getElementById('lbp-payment-section');
        const usdSection = document.getElementById('usd-payment-section');

        if (selectedMethod === 'LBP') {
            lbpSection.style.display = 'block';
            usdSection.style.display = 'none';

            // Initialize the LBP total display
            let totalInLbp;
            if (currency === 'usd') {
                totalInLbp = Math.round(totalPrice * LBP_RATE);
            } else {
                // Currency is already LBP, no conversion needed
                totalInLbp = totalPrice;
            }
            document.getElementById('total-price-lbp').textContent = `${totalInLbp.toLocaleString()} L.L`;
        } else {
            lbpSection.style.display = 'none';
            usdSection.style.display = 'block';

            // Initialize the USD total display
            let totalInUsd;
            if (currency === 'lbp') {
                totalInUsd = totalPrice / LBP_RATE;
            } else {
                totalInUsd = totalPrice;
            }
            document.getElementById('total-price-usd').textContent = `$${totalInUsd.toFixed(2)}`;
        }
    }
});
</script>
@endsection