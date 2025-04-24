@extends('layouts.cashier-layout')

@section('title', 'Complaints')

@section('toolbar')
<!-- Complaints specific toolbar -->
<div class="px-4 py-2 bg-white border-b flex justify-between items-center">
    <div class="flex space-x-2">
        <a href="{{ route('cashier.complaints.index', ['filter' => 'today']) }}" class="px-3 py-1 text-xs rounded {{ request()->input('filter', 'today') === 'today' ? 'bg-primary text-white' : 'bg-gray-200 text-gray-700' }}">Today</a>
        <a href="{{ route('cashier.complaints.index', ['filter' => 'week']) }}" class="px-3 py-1 text-xs rounded {{ request()->input('filter') === 'week' ? 'bg-primary text-white' : 'bg-gray-200 text-gray-700' }}">This Week</a>
        <a href="{{ route('cashier.complaints.index', ['filter' => 'month']) }}" class="px-3 py-1 text-xs rounded {{ request()->input('filter') === 'month' ? 'bg-primary text-white' : 'bg-gray-200 text-gray-700' }}">This Month</a>
        <a href="{{ route('cashier.complaints.index', ['filter' => 'all']) }}" class="px-3 py-1 text-xs rounded {{ request()->input('filter') === 'all' ? 'bg-primary text-white' : 'bg-gray-200 text-gray-700' }}">All Time</a>
    </div>
    
    <div class="flex space-x-2">
        <a href="{{ route('cashier.complaints.create') }}" class="px-3 py-1 text-xs bg-primary text-white rounded flex items-center">
            <span class="mr-1">New Complaint</span>
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
        </a>
    </div>
</div>
@endsection

@section('content')
<div>
    <!-- Complaints Table -->
    <div class="w-full overflow-x-auto">
        <table class="min-w-full bg-white border-collapse">
            <thead>
                <tr class="bg-gray-50 border-b">
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">#ID</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Child</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Date & Time</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody>
                @if(count($complaints) > 0)
                    @foreach($complaints as $complaint)
                    <tr class="border-b hover:bg-gray-50">
                        <td class="px-4 py-2 whitespace-nowrap">#{{ $complaint->id }}</td>
                        <td class="px-4 py-2 whitespace-nowrap">
                            @if($complaint->child)
                                <span class="font-medium text-primary">{{ $complaint->child->name }}</span>
                                @if($complaint->child->guardian_name)
                                    <div class="text-xs text-gray-500">Parent: {{ $complaint->child->guardian_name }}</div>
                                @endif
                            @else
                                <span class="text-gray-500 italic">Child record unavailable</span>
                            @endif
                        </td>
                        <td class="px-4 py-2 whitespace-nowrap text-sm">
                            {{ $complaint->created_at->format('d M Y, H:i') }}
                        </td>
                        <td class="px-4 py-2 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                {{ $complaint->type }}
                            </span>
                        </td>
                        <td class="px-4 py-2">
                            <div class="max-w-xs text-sm text-gray-500 truncate">
                                {{ \Illuminate\Support\Str::limit($complaint->description, 50) }}
                            </div>
                        </td>
                        <td class="px-4 py-2 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                {{ $complaint->resolved ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $complaint->resolved ? 'Resolved' : 'Open' }}
                            </span>
                        </td>
                        <td class="px-4 py-2 whitespace-nowrap">
                            <a href="{{ route('cashier.complaints.show', $complaint) }}" class="text-primary hover:text-primary-dark mr-2">
                                View
                            </a>
                        </td>
                    </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                            <div class="flex flex-col items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-gray-400 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                <p>No complaints found for the selected period</p>
                            </div>
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
    
    <!-- Pagination -->
    @if(count($complaints) > 0)
    <div class="p-4">
        {{ $complaints->links() }}
    </div>
    @endif
</div>
@endsection 