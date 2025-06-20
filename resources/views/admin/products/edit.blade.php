@extends('layouts.admin-layout')

@section('title', 'Edit Product')

@section('content')
<div class="bg-white rounded-lg shadow-md p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Edit Product</h1>
        <a href="{{ route('admin.products.index') }}"
            class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">
            Back to List
        </a>
    </div>

    <form action="{{ route('admin.products.update', $product) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="mb-4">
            <label for="name" class="block text-gray-700 text-sm font-bold mb-2">Name</label>
            <input type="text" name="name" id="name"
                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('name') border-red-500 @enderror"
                value="{{ old('name', $product->name) }}" required>
            @error('name')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="mb-4">
                <label for="price" class="block text-gray-700 text-sm font-bold mb-2">Price (USD)</label>
                <input type="number" name="price" id="price" step="0.01" min="0"
                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('price') border-red-500 @enderror"
                    value="{{ old('price', $product->price) }}" required>
                @error('price')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4">
                <label for="price_lbp" class="block text-gray-700 text-sm font-bold mb-2">Price (LBP)</label>
                <input type="number" name="price_lbp" id="price_lbp" step="1000" min="0"
                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('price_lbp') border-red-500 @enderror"
                    value="{{ old('price_lbp', $product->price_lbp) }}" required>
                @error('price_lbp')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="mb-6">
            <label for="stock_qty" class="block text-gray-700 text-sm font-bold mb-2">Stock Quantity</label>
            <input type="number" name="stock_qty" id="stock_qty" min="0"
                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('stock_qty') border-red-500 @enderror"
                value="{{ old('stock_qty', $product->stock_qty) }}" required>
            @error('stock_qty')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-6">
            <label class="flex items-center">
                <input type="checkbox" name="active" value="1" class="form-checkbox h-5 w-5 text-blue-600" {{ old('active', $product->active) ? 'checked' : '' }}>
                <span class="ml-2 text-gray-700 text-sm font-bold">Active Product</span>
            </label>
            <p class="text-gray-500 text-xs mt-1">Uncheck to discontinue this product</p>
        </div>

        <div class="flex justify-end">
            <button type="submit"
                class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                Update Product
            </button>
        </div>
    </form>
</div>
@endsection 