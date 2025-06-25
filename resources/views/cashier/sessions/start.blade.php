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
                    <span class="bg-primary-light text-primary px-2 py-1 rounded-full text-sm font-medium ml-2">
                        {{ $child->play_sessions_count ?? 0 }} sessions
                    </span>
                </p>
            </div>
        </div>
    </div>
    @endif

    @if ($errors->any())
    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-6">
        <ul>
            @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form action="{{ route('cashier.sessions.store') }}" method="POST">
        @csrf

        <input type="hidden" name="shift_id" value="{{ $activeShift->id }}">

        @if(!isset($child))
        <div class="mb-4">
            <label for="child_display" class="block text-gray-700 text-sm font-bold mb-2">Select Child</label>
            <div class="relative">
                <input type="text" id="child_display"
                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('child_id') border-red-500 @enderror"
                    placeholder="Click to select a child" readonly
                    value="{{ old('child_id') ? $children->firstWhere('id', old('child_id'))->name : '' }}" required>
                <input type="hidden" name="child_id" id="child_id_input" value="{{ old('child_id') }}" required>
                <button type="button" id="select_child_btn" class="absolute right-0 top-0 h-full px-3 text-gray-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                            clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
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
            <p class="text-sm text-gray-500 mt-1">You can set past or future times as needed</p>
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
            <label for="discount_pct" class="block text-gray-700 text-sm font-bold mb-2">Discount Percentage (Optional)</label>
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

<!-- Child Selection Modal -->
@if(!isset($child))
<div id="child-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-lg w-11/12 md:w-3/4 lg:w-1/2 mx-4 flex flex-col" style="max-height: 80vh;">
        <div class="flex justify-between items-center mb-4 p-6 border-b">
            <h2 class="text-xl font-bold">Select Child</h2>
            <button onclick="closeChildModal()" class="text-gray-500 hover:text-gray-700">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div class="px-6 pb-2">
            <input type="text" id="child-search" placeholder="Search children..."
                class="border border-gray-300 rounded w-full p-2">
        </div>

        <div class="flex-1 overflow-y-auto px-6 pb-6">
            <div id="children-list" class="space-y-2">
                @foreach($children as $childOption)
                <div class="child-item p-3 border rounded-lg hover:bg-gray-50 cursor-pointer"
                    onclick="selectChild({{ $childOption->id }}, '{{ addslashes($childOption->name) }}')">
                    <div class="flex justify-between items-center">
                        <div>
                            <div class="font-medium">{{ $childOption->name }}</div>
                            <div class="text-sm text-gray-500">{{ $childOption->age ?? 'Age unknown' }}</div>
                        </div>
                        <div class="bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-sm font-medium">
                            {{ $childOption->play_sessions_count ?? 0 }} sessions
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endif
@endsection

@section('scripts')
@if(!isset($child))
<script>
document.getElementById('select_child_btn').addEventListener('click', function() {
    document.getElementById('child-modal').classList.remove('hidden');
});

document.getElementById('child_display').addEventListener('click', function() {
    document.getElementById('child-modal').classList.remove('hidden');
});

function closeChildModal() {
    document.getElementById('child-modal').classList.add('hidden');
}

function selectChild(childId, childName) {
    document.getElementById('child_id_input').value = childId;
    document.getElementById('child_display').value = childName;
    closeChildModal();
}

// Filter children based on search
document.getElementById('child-search').addEventListener('input', function() {
    const searchText = this.value.toLowerCase();
    document.querySelectorAll('.child-item').forEach(item => {
        const childName = item.querySelector('.font-medium').textContent.toLowerCase();
        if (childName.includes(searchText)) {
            item.style.display = 'block';
        } else {
            item.style.display = 'none';
        }
    });
});
</script>
@endif
@endsection