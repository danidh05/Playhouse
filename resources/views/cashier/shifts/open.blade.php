@extends('layouts.cashier-layout')

@section('title', 'Start Shift')

@section('content')
<div class="p-6">
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="bg-primary p-4 text-white">
            <h1 class="text-xl font-bold">Start Your Shift</h1>
        </div>

        <div class="p-6">
            <p class="text-gray-700 text-center mb-6">Welcome! Please provide the information below to start your shift.
            </p>

            @if ($errors->any())
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6">
                <ul class="list-disc pl-4">
                    @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            <form method="POST" action="{{ route('cashier.shifts.store') }}">
                @csrf

                <div class="mb-6">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Shift Times</label>
                    <p class="text-sm text-gray-600 mb-3">Please specify your shift start and end times:</p>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="shift_start_time" class="block text-sm font-medium text-gray-700 mb-1">Start
                                Time</label>
                            <input type="time" name="shift_start_time" id="shift_start_time"
                                class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary"
                                value="{{ old('shift_start_time', now()->format('H:i')) }}" required>
                            <p class="text-xs text-gray-500 mt-1">The time you're starting your shift</p>
                        </div>

                        <div>
                            <label for="shift_end_time" class="block text-sm font-medium text-gray-700 mb-1">Expected
                                End Time</label>
                            <input type="time" name="shift_end_time" id="shift_end_time"
                                class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary"
                                value="{{ old('shift_end_time') }}" required>
                            <p class="text-xs text-gray-500 mt-1">When you expect to end your shift</p>
                        </div>
                    </div>
                </div>

                <input type="hidden" id="opening_amount" name="opening_amount"
                    value="{{ old('opening_amount', '0.00') }}">

                <div class="mb-6">
                    <label for="notes" class="block text-sm font-semibold text-gray-700 mb-1">Notes (Optional)</label>
                    <textarea
                        class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary"
                        id="notes" name="notes" rows="3"
                        placeholder="Any special notes about this shift?">{{ old('notes') }}</textarea>
                </div>

                <div class="mb-6">
                    <div class="flex items-center">
                        <input type="checkbox" id="confirm" name="confirm" required
                            class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded">
                        <label for="confirm" class="ml-2 block text-sm text-gray-700">
                            I understand that I am responsible for all transactions during my shift
                        </label>
                    </div>
                </div>

                <div>
                    <button type="submit"
                        class="w-full py-2 px-4 bg-primary hover:bg-primary-dark text-white font-bold rounded-md transition duration-200 flex justify-center items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                        </svg>
                        Start Shift
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection