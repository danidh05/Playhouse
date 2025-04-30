@extends('layouts.cashier-layout')

@section('title', 'Close Shift')

@section('content')
<div class="p-6">
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="bg-red-600 text-white p-4">
            <h1 class="text-xl font-bold">Close Your Shift</h1>
        </div>
        
        <div class="p-6">
            <p class="text-gray-700 text-center mb-6">You're about to close your shift. Please provide the following information.</p>

            @if ($errors->any())
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6">
                <ul class="list-disc pl-4">
                    @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            @if(isset($activeSessionsCount) && $activeSessionsCount > 0)
            <div class="bg-yellow-50 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium">
                            Warning: You have {{ $activeSessionsCount }} active play {{ $activeSessionsCount == 1 ? 'session' : 'sessions' }}.
                        </p>
                        <p class="text-sm mt-1">
                            If you close your shift now, these sessions will still be associated with this shift when they end, even if another cashier processes them.
                        </p>
                        @if($activeSessionsCount <= 3)
                        <ul class="mt-2 list-disc pl-5 text-xs">
                            @foreach($activeSessions as $session)
                            <li>{{ $session->child->name }} (started at {{ $session->started_at->format('H:i') }})</li>
                            @endforeach
                        </ul>
                        @endif
                    </div>
                </div>
            </div>
            @endif

            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                <h2 class="text-lg font-semibold text-blue-800 flex items-center mb-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Shift Summary
                </h2>
                <p class="mb-1">Date: {{ $shift->date->format('M d, Y') }}</p>
                <p class="mb-1">Shift started: {{ $shift->opened_at->format('H:i') }}</p>
                <p class="mb-1">Shift type: {{ ucfirst($shift->type) }}</p>
                <div class="border-t border-blue-200 my-2 pt-2">
                    @php
                        $duration = $shift->opened_at->diffAsCarbonInterval(now())->cascade();
                    @endphp
                    <p><span class="font-medium">Duration:</span> {{ $duration->hours }}h {{ $duration->minutes }}m</p>
                </div>
            </div>

            <form method="POST" action="{{ route('cashier.shifts.update', $shift) }}">
                @csrf
                @method('PUT')

                <input type="hidden" id="closing_amount" name="closing_amount" value="{{ old('closing_amount', '0.00') }}">

                <div class="mb-6">
                    <label for="notes" class="block text-sm font-semibold text-gray-700 mb-1">Closing Notes (Optional)</label>
                    <textarea class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                        id="notes" name="notes" rows="3" 
                        placeholder="Any notes about this shift?">{{ old('notes', $shift->notes) }}</textarea>
                </div>

                <div class="flex justify-between">
                    <a href="{{ route('cashier.dashboard') }}" class="inline-flex items-center px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Back to Dashboard
                    </a>
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Close Shift
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection 