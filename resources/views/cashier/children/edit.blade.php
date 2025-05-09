@extends('layouts.cashier-layout')

@section('title', 'Edit Child Information')

@section('content')
<div class="p-6">
    <div class="mb-6 flex justify-between items-center">
        <div>
        <h1 class="text-2xl font-bold text-gray-800">Edit Child</h1>
            <p class="text-gray-600">Update child information in the system</p>
        </div>
        <a href="{{ route('cashier.children.index') }}"
            class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Back to List
        </a>
    </div>

    <div class="bg-white rounded-lg shadow-md">
        <div class="border-b border-gray-200 p-6">
            <div class="flex items-center">
                <div class="p-2 bg-blue-100 rounded-full mr-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-600" fill="none" 
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                </div>
                <h2 class="text-lg font-semibold text-gray-700">Child Information</h2>
            </div>
        </div>

        <form action="{{ route('cashier.children.update', $child->id) }}" method="POST" class="p-6">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="name" class="block text-gray-700 text-sm font-medium mb-2">Child's Full Name <span class="text-red-500">*</span></label>
                <input type="text" name="name" id="name"
                        class="shadow-sm border border-gray-300 rounded-md w-full py-2 px-3 text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('name') border-red-500 @enderror"
                    value="{{ old('name', $child->name) }}" required>
                @error('name')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

                <div>
                    <label for="birth_date" class="block text-gray-700 text-sm font-medium mb-2">Birth Date <span class="text-red-500">*</span></label>
                <input type="date" name="birth_date" id="birth_date"
                        class="shadow-sm border border-gray-300 rounded-md w-full py-2 px-3 text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('birth_date') border-red-500 @enderror"
                    value="{{ old('birth_date', $child->birth_date->format('Y-m-d')) }}" required>
                @error('birth_date')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

                <div>
                    <label for="guardian_name" class="block text-gray-700 text-sm font-medium mb-2">Guardian Name <span class="text-red-500">*</span></label>
                <input type="text" name="guardian_name" id="guardian_name"
                        class="shadow-sm border border-gray-300 rounded-md w-full py-2 px-3 text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('guardian_name') border-red-500 @enderror"
                    value="{{ old('guardian_name', $child->guardian_name) }}" required>
                @error('guardian_name')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

                <div>
                    <label for="guardian_phone" class="block text-gray-700 text-sm font-medium mb-2">Guardian Phone <span class="text-red-500">*</span></label>
                <input type="text" name="guardian_phone" id="guardian_phone"
                        class="shadow-sm border border-gray-300 rounded-md w-full py-2 px-3 text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('guardian_phone') border-red-500 @enderror"
                    value="{{ old('guardian_phone', $child->guardian_phone ?? $child->guardian_contact) }}" required>
                @error('guardian_phone')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
                </div>
            </div>

            <div class="mt-6">
                <label class="block text-gray-700 text-sm font-medium mb-3">How did you hear about us?</label>
                <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                        <div class="flex items-center">
                            <input type="checkbox" name="marketing_sources[]" id="source_facebook" value="facebook" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded" {{ in_array('facebook', old('marketing_sources', $child->marketing_sources ?? [])) ? 'checked' : '' }}>
                            <label for="source_facebook" class="ml-2 block text-sm text-gray-700">Facebook</label>
                        </div>
                        <div class="flex items-center">
                            <input type="checkbox" name="marketing_sources[]" id="source_instagram" value="instagram" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded" {{ in_array('instagram', old('marketing_sources', $child->marketing_sources ?? [])) ? 'checked' : '' }}>
                            <label for="source_instagram" class="ml-2 block text-sm text-gray-700">Instagram</label>
                        </div>
                        <div class="flex items-center">
                            <input type="checkbox" name="marketing_sources[]" id="source_tiktok" value="tiktok" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded" {{ in_array('tiktok', old('marketing_sources', $child->marketing_sources ?? [])) ? 'checked' : '' }}>
                            <label for="source_tiktok" class="ml-2 block text-sm text-gray-700">TikTok</label>
                        </div>
                        <div class="flex items-center">
                            <input type="checkbox" name="marketing_sources[]" id="source_passing_by" value="passing_by" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded" {{ in_array('passing_by', old('marketing_sources', $child->marketing_sources ?? [])) ? 'checked' : '' }}>
                            <label for="source_passing_by" class="ml-2 block text-sm text-gray-700">Saw it from outside</label>
                        </div>
                        <div class="flex items-center">
                            <input type="checkbox" name="marketing_sources[]" id="source_mascot" value="mascot" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded" {{ in_array('mascot', old('marketing_sources', $child->marketing_sources ?? [])) ? 'checked' : '' }}>
                            <label for="source_mascot" class="ml-2 block text-sm text-gray-700">Mascot outside</label>
                        </div>
                        <div class="flex items-center">
                            <input type="checkbox" name="marketing_sources[]" id="source_word_of_mouth" value="word_of_mouth" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded" {{ in_array('word_of_mouth', old('marketing_sources', $child->marketing_sources ?? [])) ? 'checked' : '' }}>
                            <label for="source_word_of_mouth" class="ml-2 block text-sm text-gray-700">Word of mouth</label>
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <label for="marketing_notes" class="block text-gray-700 text-sm font-medium mb-2">Additional marketing details (optional)</label>
                        <textarea name="marketing_notes" id="marketing_notes" rows="2" class="shadow-sm border border-gray-300 rounded-md w-full py-2 px-3 text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">{{ old('marketing_notes', $child->marketing_notes) }}</textarea>
                    </div>
                </div>
            </div>

            <div class="mt-6">
                <label for="notes" class="block text-gray-700 text-sm font-medium mb-2">Notes (Optional)</label>
                <textarea name="notes" id="notes" rows="3"
                    class="shadow-sm border border-gray-300 rounded-md w-full py-2 px-3 text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('notes') border-red-500 @enderror">{{ old('notes', $child->notes) }}</textarea>
                @error('notes')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mt-6 flex justify-end space-x-3">
                <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    Update Child
                </button>
                
                <button type="button" onclick="document.getElementById('delete-form').submit();" class="px-6 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                    Delete
                </button>
            </div>
        </form>
    </div>
    
    <form id="delete-form" action="{{ route('cashier.children.destroy', $child->id) }}" method="POST" class="hidden">
        @csrf
        @method('DELETE')
    </form>
</div>
@endsection