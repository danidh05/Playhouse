@extends('layouts.admin-layout')

@section('title', 'Add New Add-On')

@section('content')
<div class="bg-white rounded-lg shadow-md p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Add New Add-On</h1>
        <a href="{{ route('admin.addons.index') }}"
            class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">
            Back to List
        </a>
    </div>

    <form action="{{ route('admin.addons.store') }}" method="POST">
        @csrf

        <div class="mb-4">
            <label for="name" class="block text-gray-700 text-sm font-bold mb-2">Name</label>
            <input type="text" name="name" id="name"
                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('name') border-red-500 @enderror"
                value="{{ old('name') }}" required>
            @error('name')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-4">
            <label for="price" class="block text-gray-700 text-sm font-bold mb-2">Price ($)</label>
            <input type="number" name="price" id="price" step="0.01" min="0"
                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('price') border-red-500 @enderror"
                value="{{ old('price') }}" required>
            @error('price')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-4">
            <label class="flex items-center">
                <input type="checkbox" name="active" value="1" class="form-checkbox h-5 w-5 text-blue-600" {{ old('active', true) ? 'checked' : '' }}>
                <span class="ml-2 text-gray-700 text-sm font-bold">Active Add-on</span>
            </label>
            <p class="text-gray-500 text-xs mt-1">Active add-ons are available for purchase</p>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                Add Add-On
            </button>
        </div>
    </form>
</div>
@endsection 