@extends('layouts.cashier-layout')

@section('title', 'Add Products to Session')

@section('content')
<div class="max-w-5xl mx-auto p-4">
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="p-4 bg-blue-50 border-b border-blue-100 flex justify-between items-center">
            <div>
                <h1 class="text-xl font-medium text-gray-800">
                    Add Products for {{ $session->child->name }}
                </h1>
                <p class="text-sm text-gray-600">These products will be added to the final bill when the session ends.</p>
            </div>
            <a href="{{ route('cashier.sessions.show', $session) }}" class="px-3 py-1 text-xs bg-gray-200 text-gray-700 rounded hover:bg-gray-300">
                Back to Session
            </a>
        </div>
        
        <div class="p-4 grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Product Selection Form -->
            <div class="md:col-span-2">
                <h2 class="text-lg font-medium mb-4">Select Products</h2>
                
                <div class="bg-gray-50 p-4 rounded-lg mb-4">
                    <label for="product-search" class="block text-sm font-medium text-gray-700 mb-2">Search Products</label>
                    <input type="text" id="product-search" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500" placeholder="Type to search products...">
                </div>
                
                <form id="product-form" action="{{ route('cashier.sessions.store-products', $session) }}" method="POST" class="space-y-6">
                    @csrf
                    <input type="hidden" name="products" id="products-json">
                    
                    <div class="overflow-hidden rounded-md border border-gray-200">
                        <div class="grid grid-cols-3 bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700">
                            <div>Product</div>
                            <div>Price</div>
                            <div>Quantity</div>
                        </div>
                        
                        <div class="divide-y divide-gray-200" id="products-container">
                            @foreach($products as $product)
                            <div class="grid grid-cols-3 items-center px-4 py-3 product-row" data-product-id="{{ $product->id }}" data-product-name="{{ $product->name }}" data-product-price="{{ $product->price }}" data-stock="{{ $product->stock_qty }}">
                                <div class="font-medium">{{ $product->name }}</div>
                                <div>${{ number_format($product->price, 2) }}</div>
                                <div>
                                    <div class="flex items-center">
                                        <button type="button" class="quantity-btn minus bg-gray-200 p-1 rounded-l" data-action="minus">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
                                            </svg>
                                        </button>
                                        <input type="number" class="quantity-input w-12 text-center border-t border-b border-gray-300 py-1" min="0" max="{{ $product->stock_qty }}" value="0" readonly>
                                        <button type="button" class="quantity-btn plus bg-blue-500 p-1 rounded-r text-white" data-action="plus">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    
                    <div class="flex justify-between items-center">
                        <span id="selected-products-count" class="text-sm font-medium px-3 py-1 bg-blue-100 text-blue-800 rounded-full">0 products selected</span>
                        <button type="submit" id="submit-button" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50 disabled:opacity-50" disabled>
                            Add to Session
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Cart Summary -->
            <div>
                <h2 class="text-lg font-medium mb-4">Cart Summary</h2>
                
                <div class="bg-gray-50 rounded-lg border border-gray-200 overflow-hidden">
                    <div class="p-4 bg-gray-100 border-b border-gray-200">
                        <h3 class="font-medium">Selected Products</h3>
                    </div>
                    
                    <div id="cart-items" class="p-4 divide-y divide-gray-200 max-h-80 overflow-y-auto">
                        <div id="empty-cart-message" class="text-center text-gray-500 py-6">
                            No products selected
                        </div>
                    </div>
                    
                    <div class="p-4 border-t border-gray-200 bg-gray-50">
                        <div class="flex justify-between items-center font-medium">
                            <span>Total:</span>
                            <span id="cart-total">$0.00</span>
                        </div>
                    </div>
                </div>
                
                @if(count($pendingSales) > 0)
                <div class="mt-6">
                    <h2 class="text-lg font-medium mb-4">Previous Pending Sales</h2>
                    
                    <div class="bg-yellow-50 rounded-lg border border-yellow-200 overflow-hidden">
                        <div class="p-3 bg-yellow-100 border-b border-yellow-200">
                            <h3 class="font-medium text-yellow-800">Already Added Products</h3>
                        </div>
                        
                        <div class="divide-y divide-yellow-200 max-h-80 overflow-y-auto">
                            @foreach($pendingSales as $pendingSale)
                            <div class="p-3">
                                <div class="text-sm font-medium text-yellow-800">
                                    Sale #{{ $pendingSale->id }} ({{ $pendingSale->created_at->format('M d, H:i') }})
                                </div>
                                <div class="mt-2 space-y-1">
                                    @foreach($pendingSale->items as $item)
                                    <div class="flex justify-between text-sm">
                                        <span>{{ $item->quantity }}x {{ $item->product->name }}</span>
                                        <span>${{ number_format($item->subtotal, 2) }}</span>
                                    </div>
                                    @endforeach
                                </div>
                                <div class="mt-2 pt-2 border-t border-yellow-200 flex justify-between font-medium text-sm">
                                    <span>Total:</span>
                                    <span>${{ number_format($pendingSale->total_amount, 2) }}</span>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const productSearch = document.getElementById('product-search');
        const productRows = document.querySelectorAll('.product-row');
        const cartItems = document.getElementById('cart-items');
        const emptyCartMessage = document.getElementById('empty-cart-message');
        const cartTotal = document.getElementById('cart-total');
        const selectedProductsCount = document.getElementById('selected-products-count');
        const submitButton = document.getElementById('submit-button');
        const productsJsonInput = document.getElementById('products-json');
        
        let selectedProducts = [];
        
        // Filter products
        productSearch.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            
            productRows.forEach(row => {
                const productName = row.getAttribute('data-product-name').toLowerCase();
                if (productName.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
        
        // Handle quantity buttons
        document.querySelectorAll('.quantity-btn').forEach(button => {
            button.addEventListener('click', function() {
                const row = this.closest('.product-row');
                const input = row.querySelector('.quantity-input');
                const currentValue = parseInt(input.value);
                const maxValue = parseInt(input.getAttribute('max'));
                const action = this.getAttribute('data-action');
                
                if (action === 'plus' && currentValue < maxValue) {
                    input.value = currentValue + 1;
                } else if (action === 'minus' && currentValue > 0) {
                    input.value = currentValue - 1;
                }
                
                updateSelectedProducts();
            });
        });
        
        function updateSelectedProducts() {
            selectedProducts = [];
            
            productRows.forEach(row => {
                const quantity = parseInt(row.querySelector('.quantity-input').value);
                
                if (quantity > 0) {
                    selectedProducts.push({
                        id: row.getAttribute('data-product-id'),
                        name: row.getAttribute('data-product-name'),
                        price: parseFloat(row.getAttribute('data-product-price')),
                        quantity: quantity
                    });
                }
            });
            
            updateCart();
        }
        
        function updateCart() {
            // Clear cart
            while (cartItems.firstChild) {
                cartItems.removeChild(cartItems.firstChild);
            }
            
            if (selectedProducts.length === 0) {
                cartItems.appendChild(emptyCartMessage);
                cartTotal.textContent = '$0.00';
                selectedProductsCount.textContent = '0 products selected';
                submitButton.disabled = true;
                productsJsonInput.value = JSON.stringify([]);
                return;
            }
            
            emptyCartMessage.remove();
            let total = 0;
            let count = 0;
            
            selectedProducts.forEach(product => {
                const item = document.createElement('div');
                item.className = 'py-2';
                
                const subtotal = product.price * product.quantity;
                total += subtotal;
                count += product.quantity;
                
                item.innerHTML = `
                    <div class="flex justify-between">
                        <span>${product.quantity}x ${product.name}</span>
                        <span>$${subtotal.toFixed(2)}</span>
                    </div>
                `;
                
                cartItems.appendChild(item);
            });
            
            cartTotal.textContent = `$${total.toFixed(2)}`;
            selectedProductsCount.textContent = `${count} ${count === 1 ? 'product' : 'products'} selected`;
            submitButton.disabled = false;
            
            // Update hidden input with JSON data for form submission
            productsJsonInput.value = JSON.stringify(selectedProducts);
        }
    });
</script>
@endsection 