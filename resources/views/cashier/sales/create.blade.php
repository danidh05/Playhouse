@extends('layouts.cashier-layout')

@section('title', 'Sell')

@section('toolbar')
<div class="px-4 py-2 bg-white border-b flex justify-between items-center">
    <div>
        <div class="relative">
            <input type="text" id="product-search" placeholder="Search Products" class="border border-gray-300 rounded-lg pl-8 pr-4 py-1 w-64">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400 absolute left-2 top-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
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
            <button id="change-customer-btn" class="ml-2 text-xs px-2 py-1 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">
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
        <div class="grid grid-cols-4 gap-4">
            @foreach($products as $product)
            <div class="bg-blue-100 p-3 rounded-lg cursor-pointer hover:bg-blue-200 transition product-item" 
                 data-id="{{ $product->id }}" 
                 data-name="{{ $product->name }}" 
                 data-price="{{ $product->price }}"
                 data-stock="{{ $product->stock_qty }}"
                 onclick="addToOrder(this)">
                <div class="text-center">
                    <div class="font-medium text-gray-800">{{ $product->name }}</div>
                    <div class="text-gray-600">${{ number_format($product->price, 2) }}</div>
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

        <div id="order-items" class="overflow-y-auto h-48 p-2">
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
                        <input type="text" id="cash-input-usd" class="border rounded p-1 w-full" oninput="calculateChangeUSD()">
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
                        <input type="text" id="cash-input-lbp" class="border rounded p-1 w-full" oninput="calculateChangeLBP()">
                    </div>
                    <div class="flex justify-between font-medium mt-2 mb-4">
                        <div>Change (LBP):</div>
                        <div id="change-amount-lbp" class="text-right">0 L.L</div>
                    </div>
                </div>
            </div>

            <button id="pay-button" class="w-full bg-primary text-white p-2 rounded-lg hover:bg-primary-dark disabled:bg-gray-300 disabled:cursor-not-allowed" onclick="processSale()" disabled>
                COMPLETE SALE
            </button>
        </div>
    </div>
</div>

<!-- Customer Selection Modal -->
<div id="customer-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-lg p-6 w-1/2 max-h-3/4 overflow-y-auto">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold">Select Customer</h2>
            <button onclick="closeCustomerModal()" class="text-gray-500 hover:text-gray-700">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        
        <div class="mb-4">
            <input type="text" id="child-search" placeholder="Search children..." class="border border-gray-300 rounded w-full p-2 mb-4">
            
            <div class="mb-6">
                <button onclick="selectWalkInCustomer()" class="w-full bg-gray-100 hover:bg-gray-200 p-3 rounded-lg mb-4 flex items-center justify-between">
                    <div>
                        <div class="font-medium">Walk-in Customer</div>
                        <div class="text-sm text-gray-500">No registration needed</div>
                    </div>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
            
            <div id="children-list">
                <!-- Children will be dynamically loaded here -->
                @if(isset($children) && count($children) > 0)
                    @foreach($children as $child)
                    <div class="child-item p-3 border-b hover:bg-gray-50 cursor-pointer" onclick="selectChild({{ $child->id }}, '{{ $child->name }}')">
                        <div class="flex justify-between items-center">
                            <div>
                                <div class="font-medium">{{ $child->name }}</div>
                                @if(isset($child->guardian_name))
                                <div class="text-sm text-gray-500">Parent: {{ $child->guardian_name }}</div>
                                @endif
                            </div>
                            <div class="bg-primary-light text-primary px-2 py-1 rounded-full text-sm font-medium">
                                {{ $child->play_sessions_count ?? 0 }} sessions
                            </div>
                        </div>
                    </div>
                    @endforeach
                @else
                    <div class="text-center py-4 text-gray-500">
                        No registered children found. 
                        <a href="{{ route('cashier.children.create') }}" class="text-primary">Register a child</a>
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
    <input type="hidden" name="products" id="products-input">
    <input type="hidden" name="total_amount" id="total-amount-input">
    <input type="hidden" name="total_amount_lbp" id="total-amount-lbp-input">
    <input type="hidden" name="payment_method" id="payment-method-input" value="LBP">
    <input type="hidden" name="shift_id" value="{{ $activeShift ? $activeShift->id : '' }}">
    <input type="hidden" name="child_id" id="child-id-input" value="{{ request()->child_id ?? '' }}">
</form>
@endsection

@section('scripts')
<script>
    let orderItems = [];
    let totalPrice = 0;
    let selectedChildId = "{{ request()->child_id ?? '' }}";
    const LBP_RATE = {{ config('play.lbp_exchange_rate', 90000) }}; // Use configured exchange rate value
    
    // Initialize payment method handling
    document.querySelectorAll('input[name="payment_method"]').forEach(input => {
        input.addEventListener('change', function() {
            const lbpSection = document.getElementById('lbp-payment-section');
            const usdSection = document.getElementById('usd-payment-section');
            
            if (this.value === 'LBP') {
                lbpSection.style.display = 'block';
                usdSection.style.display = 'none';
                document.getElementById('cash-input-lbp').value = '';
                calculateChangeLBP();
            } else {
                lbpSection.style.display = 'none';
                usdSection.style.display = 'block';
                document.getElementById('cash-input-usd').value = '';
                calculateChangeUSD();
            }
            
            // Enable pay button if we have items
            document.getElementById('pay-button').disabled = orderItems.length === 0;
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
    
    // Filter children based on search
    if (document.getElementById('child-search')) {
        document.getElementById('child-search').addEventListener('input', function() {
            const searchText = this.value.toLowerCase();
            document.querySelectorAll('.child-item').forEach(item => {
                const childName = item.querySelector('.font-medium').textContent.toLowerCase();
                if (childName.includes(searchText)) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    }

    function addToOrder(element) {
        const id = element.dataset.id;
        const name = element.dataset.name;
        const price = parseFloat(element.dataset.price);
        const stock = parseInt(element.dataset.stock);
        
        if (stock <= 0) {
            alert("This item is out of stock!");
            return;
        }

        // Check if item is already in the order
        const existingItem = orderItems.find(item => item.id === id);
        
        if (existingItem) {
            if (existingItem.quantity < stock) {
                existingItem.quantity += 1;
            } else {
                alert("Cannot add more of this item - maximum stock reached!");
                return;
            }
        } else {
            orderItems.push({
                id: id,
                name: name,
                price: price,
                quantity: 1
            });
        }
        
        updateOrderDisplay();
    }

    function removeFromOrder(index) {
        orderItems.splice(index, 1);
        updateOrderDisplay();
    }

    function updateQuantity(index, delta) {
        const item = orderItems[index];
        const newQuantity = item.quantity + delta;
        
        if (newQuantity <= 0) {
            removeFromOrder(index);
        } else {
            // Check stock limit before increasing
            if (delta > 0) {
                const stockLimit = parseInt(document.querySelector(`[data-id="${item.id}"]`).dataset.stock);
                if (newQuantity > stockLimit) {
                    alert("Cannot add more - maximum stock reached!");
                    return;
                }
            }
            
            item.quantity = newQuantity;
            updateOrderDisplay();
        }
    }

    function updateOrderDisplay() {
        const orderContainer = document.getElementById('order-items');
        orderContainer.innerHTML = '';
        
        totalPrice = 0;
        
        orderItems.forEach((item, index) => {
            const itemTotal = item.price * item.quantity;
            totalPrice += itemTotal;
            
            const itemElement = document.createElement('div');
            itemElement.className = 'flex justify-between items-center mb-2 bg-gray-50 p-2 rounded';
            itemElement.innerHTML = `
                <div class="text-sm">${item.name}</div>
                <div class="flex items-center">
                    <button class="px-1 text-xs bg-gray-200 rounded" onclick="updateQuantity(${index}, -1)">-</button>
                    <span class="mx-2">${item.quantity}</span>
                    <button class="px-1 text-xs bg-gray-200 rounded" onclick="updateQuantity(${index}, 1)">+</button>
                </div>
                <div class="text-right">$${(itemTotal).toFixed(2)}</div>
            `;
            
            orderContainer.appendChild(itemElement);
        });
        
        document.getElementById('total-price').textContent = `$${totalPrice.toFixed(2)}`;
        
        // Update LBP and USD totals
        const totalPriceLBP = totalPrice * LBP_RATE;
        document.getElementById('total-price-lbp').textContent = `${totalPriceLBP.toLocaleString()} L.L`;
        document.getElementById('total-price-usd').textContent = `$${totalPrice.toFixed(2)}`;
        
        // Enable/disable pay button based on items in order
        const payButton = document.getElementById('pay-button');
        const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;
        
        if (orderItems.length === 0) {
            payButton.disabled = true;
        } else if (paymentMethod === 'LBP') {
            calculateChangeLBP();
        } else {
            calculateChangeUSD();
        }
    }

    function calculateChangeLBP() {
        const cashInput = document.getElementById('cash-input-lbp');
        const cashValue = parseFloat(cashInput.value.replace(/,/g, '')) || 0;
        const totalLBP = totalPrice * LBP_RATE;
        const change = cashValue - totalLBP;
        
        document.getElementById('change-amount-lbp').textContent = `${change.toLocaleString()} L.L`;
        
        // Only enable pay button if cash covers total price and there are items
        document.getElementById('pay-button').disabled = change < 0 || orderItems.length === 0;
    }
    
    function calculateChangeUSD() {
        const cashInput = document.getElementById('cash-input-usd');
        const cashValue = parseFloat(cashInput.value) || 0;
        const change = cashValue - totalPrice;
        
        document.getElementById('change-amount-usd').textContent = `$${change.toFixed(2)}`;
        
        // Only enable pay button if cash covers total price and there are items
        document.getElementById('pay-button').disabled = change < 0 || orderItems.length === 0;
    }

    function processSale() {
        if (orderItems.length === 0) return;
        
        // Get selected payment method
        const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;
        
        // Prepare data for submission
        document.getElementById('products-input').value = JSON.stringify(orderItems);
        document.getElementById('total-amount-input').value = totalPrice;
        document.getElementById('total-amount-lbp-input').value = totalPrice * LBP_RATE;
        document.getElementById('payment-method-input').value = paymentMethod;
        
        // Submit the form
        document.getElementById('sale-form').submit();
        
        // Show completion modal
        document.getElementById('completion-modal').classList.remove('hidden');
    }

    function closeModal() {
        document.getElementById('completion-modal').classList.add('hidden');
        // Clear the order
        orderItems = [];
        updateOrderDisplay();
        document.getElementById('cash-input-lbp').value = '';
        document.getElementById('cash-input-usd').value = '';
    }
</script>
@endsection