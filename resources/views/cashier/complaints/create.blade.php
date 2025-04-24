@extends('layouts.cashier-layout')

@section('title', 'Submit Complaint')

@section('content')
<div class="bg-white rounded-lg shadow-md p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Submit Complaint</h1>
        <a href="{{ route('cashier.dashboard') }}" class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">
            Back to Dashboard
        </a>
    </div>

    <form action="{{ route('cashier.complaints.store') }}" method="POST">
        @csrf

        <input type="hidden" name="shift_id" value="{{ $activeShift->id }}">

        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-bold mb-2">Current Shift</label>
            <div class="bg-gray-100 p-3 rounded">
                <span class="text-gray-800">
                    {{ $activeShift->date->format('M d, Y') }} ({{ ucfirst($activeShift->type) }}) -
                    Started at {{ $activeShift->opened_at->format('H:i') }}
                </span>
            </div>
        </div>

        <div class="mb-4">
            <label for="child_id" class="block text-gray-700 text-sm font-bold mb-2">Related Child (Optional)</label>
            <select name="child_id" id="child_id"
                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('child_id') border-red-500 @enderror">
                <option value="">Not related to a specific child</option>
                @foreach($children as $child)
                <option value="{{ $child->id }}" {{ old('child_id') == $child->id ? 'selected' : '' }}>
                    {{ $child->name }}
                </option>
                @endforeach
            </select>
            @error('child_id')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-bold mb-2">Complaint Type</label>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                @foreach($complaintTypes as $type)
                <div class="flex items-center">
                    <input type="radio" name="type" id="type_{{ $type }}" value="{{ $type }}"
                        class="mr-2 @error('type') border-red-500 @enderror" {{ old('type') == $type ? 'checked' : '' }}
                        required>
                    <label for="type_{{ $type }}">{{ $type }}</label>
                </div>
                @endforeach
            </div>
            @error('type')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-4">
            <label for="description" class="block text-gray-700 text-sm font-bold mb-2">Description</label>
            <textarea name="description" id="description" rows="5"
                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('description') border-red-500 @enderror"
                required>{{ old('description') }}</textarea>
            @error('description')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex justify-end">
            <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">
                Submit Complaint
            </button>
        </div>
    </form>
</div>
@endsection