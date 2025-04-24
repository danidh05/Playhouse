@extends('layouts.cashier-layout')

@section('title', 'Start Play Session')

@section('content')
<div class="bg-white rounded-lg shadow-md p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Start Play Session</h1>
        <a href="{{ isset($child) ? route('cashier.children.index') : route('cashier.sessions.index') }}"
            class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">
            {{ isset($child) ? 'Back to Children' : 'Back to Sessions' }}
        </a>
    </div>

    @if(isset($child))
    <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-green-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                    fill="currentColor">
                    <path fill-rule="evenodd"
                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                        clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3">
                <p class="text-sm text-green-700">
                    Starting a play session for <strong>{{ $child->name }}</strong>
                </p>
            </div>
        </div>
    </div>
    @endif

    <form action="{{ route('cashier.sessions.store') }}" method="POST">
        @csrf

        <input type="hidden" name="shift_id" value="{{ $activeShift->id }}">

        @if(!isset($child))
        <div class="mb-4">
            <label for="child_id" class="block text-gray-700 text-sm font-bold mb-2">Select Child</label>
            <select name="child_id" id="child_id"
                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('child_id') border-red-500 @enderror"
                required>
                <option value="">-- Select a child --</option>
                @foreach($children as $childOption)
                <option value="{{ $childOption->id }}">
                    {{ $childOption->name }} ({{ $childOption->age ?? 'Age unknown' }})
                </option>
                @endforeach
            </select>
            @error('child_id')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>
        @else
        <input type="hidden" name="child_id" value="{{ $child->id }}">
        @endif

        <div class="mb-4">
            <label for="start_time" class="block text-gray-700 text-sm font-bold mb-2">Start Time</label>
            <input type="datetime-local" name="start_time" id="start_time"
                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('start_time') border-red-500 @enderror"
                value="{{ old('start_time', now()->format('Y-m-d\TH:i')) }}" required>
            @error('start_time')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-4">
            <label for="planned_hours" class="block text-gray-700 text-sm font-bold mb-2">Planned Hours</label>
            <input type="number" name="planned_hours" id="planned_hours" min="0.1" max="24" step="0.1"
                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('planned_hours') border-red-500 @enderror"
                value="{{ old('planned_hours', 1) }}" required>
            @error('planned_hours')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
            <p class="text-sm text-gray-500 mt-1">Current hourly rate: ${{ number_format($hourlyRate, 2) }}</p>
            <p class="text-sm text-gray-500 mt-1">For testing: You can set as low as 0.1 hours (6 minutes)</p>
        </div>

        <div class="mb-4">
            <label for="discount_pct" class="block text-gray-700 text-sm font-bold mb-2">Discount Percentage
                (Optional)</label>
            <input type="number" name="discount_pct" id="discount_pct" min="0" max="100" step="1"
                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('discount_pct') border-red-500 @enderror"
                value="{{ old('discount_pct', 0) }}">
            @error('discount_pct')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
            <p class="text-sm text-gray-500 mt-1">Enter a number between 0-100</p>
        </div>

        <div class="mb-4">
            <label for="notes" class="block text-gray-700 text-sm font-bold mb-2">Notes (Optional)</label>
            <textarea name="notes" id="notes" rows="3"
                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('notes') border-red-500 @enderror">{{ old('notes') }}</textarea>
            @error('notes')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex justify-end">
            <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                Start Session
            </button>
        </div>
    </form>
</div>
@endsection