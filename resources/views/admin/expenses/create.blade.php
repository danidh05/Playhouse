@extends('layouts.admin-layout')

@section('title', 'Add New Expense')

@section('content')
<div class="bg-white rounded-lg shadow-md p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Add New Expense</h1>
        <a href="{{ route('admin.expenses.index') }}"
            class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">
            Back to List
        </a>
    </div>

    <form action="{{ route('admin.expenses.store') }}" method="POST">
        @csrf

        <div class="mb-4">
            <label for="item" class="block text-gray-700 text-sm font-bold mb-2">Item Description</label>
            <input type="text" name="item" id="item"
                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('item') border-red-500 @enderror"
                value="{{ old('item') }}" required>
            @error('item')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-4">
            <label for="amount" class="block text-gray-700 text-sm font-bold mb-2">Amount ($)</label>
            <input type="number" name="amount" id="amount" step="0.01" min="0"
                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('amount') border-red-500 @enderror"
                value="{{ old('amount') }}" required>
            @error('amount')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-4">
            <label for="created_at" class="block text-gray-700 text-sm font-bold mb-2">Date (Optional)</label>
            <input type="datetime-local" name="created_at" id="created_at"
                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('created_at') border-red-500 @enderror"
                value="{{ old('created_at') }}">
            <p class="text-gray-500 text-xs mt-1">If left blank, current date/time will be used</p>
            @error('created_at')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex justify-end">
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                Add Expense
            </button>
        </div>
    </form>
</div>
@endsection 