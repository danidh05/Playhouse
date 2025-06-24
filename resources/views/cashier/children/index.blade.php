@extends('layouts.cashier-layout')

@section('title', 'Children')

@section('content')
<div class="p-6">
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">
                @if(request()->has('select_for_sale') && request()->select_for_sale)
                    Select a Child for Sale
                @else
                    Children
                @endif
            </h1>
            <p class="text-gray-600">
                @if(request()->has('select_for_sale') && request()->select_for_sale)
                    Choose which child to create a sale for
                @else
                    Manage and track registered children
                @endif
            </p>
        </div>
        
        @if(request()->has('select_for_sale') && request()->select_for_sale)
            <div class="flex space-x-2">
                <a href="{{ route('cashier.sales.create') }}?type=walkin" 
                   class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 flex items-center">
                    Walk-in Sale
                </a>
                <a href="{{ route('cashier.children.create') }}?redirect_to_sale=true" 
                   class="px-4 py-2 bg-primary text-white rounded-md hover:bg-primary-dark flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Register New Child
                </a>
            </div>
        @else
            <a href="{{ route('cashier.children.create') }}"
                class="px-4 py-2 bg-primary text-white rounded-md hover:bg-primary-dark flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Add New Child
            </a>
        @endif
    </div>
    
    <!-- Filter Bar -->
    <div class="bg-white p-4 rounded-xl shadow-sm mb-6">
        <form action="{{ route('cashier.children.index') }}" method="GET" class="flex flex-wrap gap-4 items-end">
            <div class="flex-1 min-w-[200px]">
                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                <input type="text" name="search" id="search" placeholder="Search by name or guardian..."
                    value="{{ request('search') }}"
                    class="w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-primary focus:border-primary">
            </div>
            
            <div class="w-40">
                <label for="age" class="block text-sm font-medium text-gray-700 mb-1">Age Range</label>
                <select name="age" id="age" class="w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-primary focus:border-primary">
                    <option value="">All Ages</option>
                    <option value="0-3" {{ request('age') == '0-3' ? 'selected' : '' }}>0-3 years</option>
                    <option value="4-6" {{ request('age') == '4-6' ? 'selected' : '' }}>4-6 years</option>
                    <option value="7-10" {{ request('age') == '7-10' ? 'selected' : '' }}>7-10 years</option>
                    <option value="11+" {{ request('age') == '11+' ? 'selected' : '' }}>11+ years</option>
                </select>
            </div>
            
            @if(request()->has('select_for_sale'))
                <input type="hidden" name="select_for_sale" value="{{ request('select_for_sale') }}">
            @endif
            
            <div>
                <button type="submit" class="bg-primary text-white py-2 px-4 rounded-md hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-primary">
                    Filter
                </button>
                <a href="{{ route('cashier.children.index') }}{{ request()->has('select_for_sale') ? '?select_for_sale='.request('select_for_sale') : '' }}" class="ml-2 text-gray-600 hover:text-gray-900">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Children List -->
    <div class="bg-white rounded-xl shadow-sm">
        <div class="border-b border-gray-200 p-6">
            <div class="flex items-center">
                <div class="p-2 bg-primary-light rounded-full mr-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                </div>
                <h2 class="text-lg font-semibold text-gray-700">Registered Children ({{ $children->total() }})</h2>
            </div>
                </div>
                
        <div class="p-6">
            @if(count($children) > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Child</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Guardian</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Age</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Play Sessions</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Visit</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($children as $child)
                            <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10 rounded-full bg-primary-light flex items-center justify-center">
                                        <span class="text-primary font-medium">{{ substr($child->name, 0, 1) }}</span>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900">{{ $child->name }}</div>
                                        <div class="text-sm text-gray-500">ID: #{{ $child->id }}</div>
                                    </div>
                                </div>
                                </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $child->guardian_name }}</div>
                                <div class="text-sm text-gray-500">{{ $child->guardian_phone }}</div>
                                </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">
                                    {{ $child->birth_date ? $child->birth_date->age : $child->age }} years
                                </div>
                                @if($child->birth_date)
                                    <div class="text-sm text-gray-500">{{ $child->birth_date->format('M d, Y') }}</div>
                                @else
                                    <div class="text-sm text-gray-500">Age provided</div>
                                @endif
                                </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">
                                    <span class="bg-primary-light text-primary px-2 py-1 rounded-full font-medium">
                                        {{ $child->play_sessions_count ?? 0 }}
                                    </span>
                                </div>
                                <div class="text-xs text-gray-500 mt-1">Total sessions</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($child->last_visit)
                                <div class="text-sm text-gray-900">{{ $child->last_visit->format('M d, Y') }}</div>
                                <div class="text-sm text-gray-500">{{ $child->last_visit->format('h:i A') }}</div>
                                @else
                                <div class="text-sm text-gray-500">No visits yet</div>
                                @endif
                                </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex space-x-3">
                                    @if(request()->has('select_for_sale') && request()->select_for_sale)
                                        <a href="{{ route('cashier.sales.create', ['child_id' => $child->id]) }}" 
                                           class="text-white bg-primary hover:bg-primary-dark py-1 px-3 rounded-md">
                                            Create Sale
                                        </a>
                                    @else
                                        <a href="{{ route('cashier.sessions.create', ['child_id' => $child->id]) }}" 
                                           class="text-white bg-green-600 hover:bg-green-700 py-1 px-3 rounded-md">
                                            New Session
                                        </a>
                                        <a href="{{ route('cashier.children.edit', $child->id) }}" 
                                           class="text-primary hover:text-primary-dark">
                                            Edit
                                        </a>
                                        <form action="{{ route('cashier.children.destroy', $child->id) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this child?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900 ml-2">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            
            <div class="mt-4">
                {{ $children->links() }}
            </div>
            @else
            <div class="text-center py-8">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-gray-400 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                <h3 class="text-lg font-medium text-gray-900 mb-1">No Children Found</h3>
                <p class="text-gray-500">No children match your search criteria.</p>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection