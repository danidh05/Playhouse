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
            <label for="child_display" class="block text-gray-700 text-sm font-bold mb-2">Related Child (Optional)</label>
            <div class="relative">
                <input type="text" id="child_display" 
                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('child_id') border-red-500 @enderror"
                    placeholder="Click to select a child or leave empty" readonly
                    value="{{ old('child_id') ? $children->firstWhere('id', old('child_id'))->name : '' }}">
                <input type="hidden" name="child_id" id="child_id_input" value="{{ old('child_id') }}">
                <button type="button" id="select_child_btn" class="absolute right-0 top-0 h-full px-3 text-gray-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
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

<!-- Child Selection Modal -->
<div id="child-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-lg p-6 w-1/2 max-h-3/4 overflow-y-auto">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold">Select Child</h2>
            <button onclick="closeChildModal()" class="text-gray-500 hover:text-gray-700">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        
        <div class="mb-4">
            <input type="text" id="child-search" placeholder="Search children..." class="border border-gray-300 rounded w-full p-2 mb-4">
            
            <div class="mb-6">
                <button onclick="clearChildSelection()" class="w-full bg-gray-100 hover:bg-gray-200 p-3 rounded-lg mb-4 flex items-center justify-between">
                    <div>
                        <div class="font-medium">No Child Selected</div>
                        <div class="text-sm text-gray-500">Not related to a specific child</div>
                    </div>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
            
            <div id="children-list">
                @foreach($children as $child)
                <div class="child-item p-3 border-b hover:bg-gray-50 cursor-pointer" onclick="selectChild({{ $child->id }}, '{{ $child->name }}')">
                    <div class="font-medium">{{ $child->name }}</div>
                    @if(isset($child->guardian_name))
                    <div class="text-sm text-gray-500">Parent: {{ $child->guardian_name }}</div>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
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
    
    function clearChildSelection() {
        document.getElementById('child_id_input').value = '';
        document.getElementById('child_display').value = '';
        closeChildModal();
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
@endsection