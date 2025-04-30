@extends('layouts.cashier-layout')

@section('title', 'Manage Add-ons')

@section('content')
<div class="bg-white rounded-lg shadow-md p-6 max-w-2xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Manage Add-ons</h1>
        <a href="{{ route('cashier.sessions.index') }}"
            class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">
            Back to Sessions
        </a>
    </div>

    <div class="mb-4">
        <p class="text-gray-700">
            <strong>Child:</strong> {{ $session->child->name }}<br>
            <strong>Started:</strong> {{ $session->started_at->format('M d, Y H:i') }}
        </p>
    </div>

    <form action="{{ route('cashier.sessions.update-addons', $session) }}" method="POST">
        @csrf
        @method('PATCH')

        <div class="overflow-x-auto mb-4">
            <table class="min-w-full bg-white">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Add-on</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subtotal</th>
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
                            <input type="number" name="add_ons[{{ $addOn->id }}][qty]" min="0" class="shadow appearance-none border rounded w-20 py-2 px-3 text-gray-700" value="{{ $qty }}" data-price="{{ $addOn->price }}" onchange="updateSubtotal(this)">
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap subtotal-display">${{ number_format($subtotal, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="flex justify-end mt-4">
            <button type="submit" class="px-6 py-3 bg-blue-600 text-white rounded hover:bg-blue-700 font-bold">
                Save Add-ons
            </button>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
// Update subtotal when quantity changes
function updateSubtotal(input) {
    const qty = parseInt(input.value);
    const price = parseFloat(input.dataset.price);
    const subtotal = qty * price;
    const subtotalDisplay = input.closest('tr').querySelector('.subtotal-display');
    subtotalDisplay.textContent = '$' + subtotal.toFixed(2);
}
</script>
@endsection 