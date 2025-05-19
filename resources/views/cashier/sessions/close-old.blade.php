@extends('layouts.cashier-layout')

@section('title', 'Close Old Sessions')

@section('content')
<div class="p-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Close Old Sessions</h1>
        <p class="text-gray-600">Select sessions that need to be closed</p>
    </div>

    @if($oldSessions->isEmpty())
        <div class="bg-green-50 border-l-4 border-green-400 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-green-700">
                        No old sessions found that need to be closed.
                    </p>
                </div>
            </div>
        </div>
    @else
        <form action="{{ route('cashier.sessions.bulk-close') }}" method="POST" class="space-y-6">
            @csrf
            
            <div class="bg-white shadow-md rounded-lg overflow-hidden">
                <div class="p-4 bg-gray-50 border-b">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <label class="flex items-center">
                                <input type="checkbox" id="select-all" class="form-checkbox h-4 w-4 text-blue-600">
                                <span class="ml-2 text-gray-700">Select All</span>
                            </label>
                        </div>
                        <div class="flex items-center space-x-4">
                            <label class="text-gray-700">Payment Method:</label>
                            <select name="payment_method" required class="form-select rounded-md shadow-sm border-gray-300 focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                @foreach(config('play.payment_methods', []) as $method)
                                    <option value="{{ $method }}">{{ ucfirst($method) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="divide-y divide-gray-200">
                    @foreach($oldSessions as $session)
                        <div class="p-4 hover:bg-gray-50">
                            <div class="flex items-center space-x-4">
                                <input type="checkbox" 
                                       name="sessions[]" 
                                       value="{{ $session->id }}" 
                                       class="session-checkbox form-checkbox h-4 w-4 text-blue-600">
                                <div class="flex-1">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <h3 class="text-lg font-medium text-gray-900">
                                                {{ $session->child->name }}
                                            </h3>
                                            <p class="text-sm text-gray-500">
                                                Started: {{ $session->started_at->format('M d, Y H:i') }}
                                                ({{ $session->started_at->diffForHumans() }})
                                            </p>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-sm text-gray-500">
                                                Created by: {{ $session->user->name }}
                                            </p>
                                            @if($session->planned_hours)
                                                <p class="text-sm text-gray-500">
                                                    Planned Hours: {{ $session->planned_hours }}
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="flex justify-end space-x-4">
                <a href="{{ route('cashier.sessions.index') }}" 
                   class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Cancel
                </a>
                <button type="submit" 
                        class="px-4 py-2 border border-transparent rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Close Selected Sessions
                </button>
            </div>
        </form>

        @push('scripts')
        <script>
            document.getElementById('select-all').addEventListener('change', function() {
                document.querySelectorAll('.session-checkbox').forEach(checkbox => {
                    checkbox.checked = this.checked;
                });
            });
        </script>
        @endpush
    @endif
</div>
@endsection 