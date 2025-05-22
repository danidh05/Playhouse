@extends('layouts.admin-layout')

@section('title', 'Application Settings')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Application Settings</h1>
    </div>

    @if(session('success'))
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
        <p>{{ session('success') }}</p>
    </div>
    @endif

    @if(session('error'))
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
        <p>{{ session('error') }}</p>
    </div>
    @endif

    @if($errors->any())
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
        <p class="font-bold">Please fix the following errors:</p>
        <ul class="mt-2 list-disc list-inside">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <form action="{{ route('admin.settings.update') }}" method="POST">
            @csrf
            @method('PATCH')

            <div class="p-6 border-b">
                <h2 class="text-lg font-medium text-gray-800 mb-4">Play Session Settings</h2>
                
                <div class="mb-6">
                    <label for="hourly_rate" class="block text-sm font-medium text-gray-700 mb-2">
                        Hourly Rate (USD)
                    </label>
                    <div class="mt-1 relative rounded-md shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <span class="text-gray-500 sm:text-sm">$</span>
                        </div>
                        <input type="number" name="hourly_rate" id="hourly_rate" step="0.01" min="0" 
                            class="focus:ring-indigo-500 focus:border-indigo-500 block w-full pl-7 pr-12 sm:text-sm border-gray-300 rounded-md py-2 px-3 border @error('hourly_rate') border-red-500 @enderror"
                            value="{{ old('hourly_rate', $settings['hourly_rate']) }}" required>
                    </div>
                    <p class="mt-2 text-sm text-gray-500">
                        This is the base rate charged per hour for play sessions.
                    </p>
                    @error('hourly_rate')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                
                <div class="mb-6">
                    <label for="lbp_exchange_rate" class="block text-sm font-medium text-gray-700 mb-2">
                        LBP Exchange Rate
                    </label>
                    <div class="mt-1 relative rounded-md shadow-sm">
                        <input type="number" name="lbp_exchange_rate" id="lbp_exchange_rate" min="1" 
                            class="focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md py-2 px-3 border @error('lbp_exchange_rate') border-red-500 @enderror"
                            value="{{ old('lbp_exchange_rate', $settings['lbp_exchange_rate']) }}" required>
                    </div>
                    <p class="mt-2 text-sm text-gray-500">
                        The exchange rate for Lebanese Pounds (LBP) to USD (e.g., 90000 means 90,000 LBP = 1 USD).
                    </p>
                    @error('lbp_exchange_rate')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
            
            <div class="p-6 border-b">
                <h2 class="text-lg font-medium text-gray-800 mb-4">Payment Methods</h2>
                
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Available Payment Methods
                    </label>
                    
                    <div class="space-y-2">
                        @foreach($settings['payment_methods'] as $index => $method)
                        <div class="flex items-center">
                            <input type="text" name="payment_methods[]" 
                                class="focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md py-2 px-3 border"
                                value="{{ $method }}" required>
                                
                            @if($index > 1)
                            <button type="button" class="ml-2 text-red-500 hover:text-red-700" onclick="this.parentNode.remove()">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                </svg>
                            </button>
                            @endif
                        </div>
                        @endforeach
                    </div>
                    
                    <button type="button" id="add-payment-method" 
                        class="mt-2 px-2 py-1 text-xs text-blue-600 border border-blue-600 rounded hover:bg-blue-50">
                        + Add Payment Method
                    </button>
                </div>
            </div>
            
            <div class="px-6 py-4 bg-gray-50 flex justify-end">
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">
                    Save Settings
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.getElementById('add-payment-method').addEventListener('click', function() {
        const container = this.previousElementSibling;
        const newMethod = document.createElement('div');
        newMethod.className = 'flex items-center';
        newMethod.innerHTML = `
            <input type="text" name="payment_methods[]" 
                class="focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md py-2 px-3 border"
                value="" required>
                
            <button type="button" class="ml-2 text-red-500 hover:text-red-700" onclick="this.parentNode.remove()">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                </svg>
            </button>
        `;
        container.appendChild(newMethod);
    });
</script>
@endsection 