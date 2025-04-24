@extends('layouts.cashier-layout')

@section('title', 'Complaint Details')

@section('toolbar')
<div class="px-4 py-2 bg-white border-b flex justify-between items-center">
    <div class="flex items-center space-x-2">
        <a href="{{ route('cashier.complaints.index') }}" class="px-3 py-1 text-xs bg-gray-200 text-gray-700 rounded flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Back to Complaints
        </a>
    </div>
    
    <div class="flex space-x-2">
        @if(!$complaint->resolved)
        <form action="{{ route('cashier.complaints.resolve', $complaint) }}" method="POST" class="inline">
            @csrf
            @method('PATCH')
            <button type="submit" class="px-3 py-1 text-xs bg-green-600 text-white rounded flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                Mark as Resolved
            </button>
        </form>
        @endif
    </div>
</div>
@endsection

@section('content')
<div class="max-w-4xl mx-auto p-4">
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <!-- Complaint Header -->
        <div class="p-4 bg-gray-50 border-b">
            <div class="flex justify-between items-center">
                <h1 class="text-xl font-medium text-gray-800">
                    Complaint #{{ $complaint->id }}
                </h1>
                <div>
                    <span class="px-3 py-1 text-xs inline-flex items-center rounded-full {{ $complaint->resolved ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                        {{ $complaint->resolved ? 'Resolved' : 'Open' }}
                    </span>
                </div>
            </div>
        </div>
        
        <!-- Complaint Details -->
        <div class="p-4 grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <h2 class="text-sm font-medium text-gray-500 mb-2">COMPLAINT INFORMATION</h2>
                <div class="border rounded-lg overflow-hidden">
                    <div class="grid grid-cols-3 border-b">
                        <div class="py-2 px-3 bg-gray-50 font-medium text-xs text-gray-600">Type</div>
                        <div class="py-2 px-3 col-span-2">
                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">{{ $complaint->type }}</span>
                        </div>
                    </div>
                    <div class="grid grid-cols-3 border-b">
                        <div class="py-2 px-3 bg-gray-50 font-medium text-xs text-gray-600">Created</div>
                        <div class="py-2 px-3 col-span-2">{{ $complaint->created_at->format('M d, Y h:i A') }}</div>
                    </div>
                    <div class="grid grid-cols-3 border-b">
                        <div class="py-2 px-3 bg-gray-50 font-medium text-xs text-gray-600">Reported by</div>
                        <div class="py-2 px-3 col-span-2">{{ $complaint->user->name }}</div>
                    </div>
                    <div class="grid grid-cols-3">
                        <div class="py-2 px-3 bg-gray-50 font-medium text-xs text-gray-600">During shift</div>
                        <div class="py-2 px-3 col-span-2">
                            {{ $complaint->shift->date->format('M d, Y') }} ({{ ucfirst($complaint->shift->type) }})
                        </div>
                    </div>
                </div>
            </div>
            
            <div>
                <h2 class="text-sm font-medium text-gray-500 mb-2">CHILD INFORMATION</h2>
                <div class="border rounded-lg overflow-hidden">
                    @if($complaint->child)
                    <div class="grid grid-cols-3 border-b">
                        <div class="py-2 px-3 bg-gray-50 font-medium text-xs text-gray-600">Name</div>
                        <div class="py-2 px-3 col-span-2 font-medium">{{ $complaint->child->name }}</div>
                    </div>
                    <div class="grid grid-cols-3 border-b">
                        <div class="py-2 px-3 bg-gray-50 font-medium text-xs text-gray-600">Age</div>
                        <div class="py-2 px-3 col-span-2">{{ $complaint->child->age }} years</div>
                    </div>
                    @if(isset($complaint->child->guardian_name))
                    <div class="grid grid-cols-3 border-b">
                        <div class="py-2 px-3 bg-gray-50 font-medium text-xs text-gray-600">Guardian</div>
                        <div class="py-2 px-3 col-span-2">{{ $complaint->child->guardian_name }}</div>
                    </div>
                    @endif
                    @if(isset($complaint->child->guardian_phone))
                    <div class="grid grid-cols-3">
                        <div class="py-2 px-3 bg-gray-50 font-medium text-xs text-gray-600">Contact</div>
                        <div class="py-2 px-3 col-span-2">{{ $complaint->child->guardian_phone }}</div>
                    </div>
                    @endif
                    @else
                    <div class="p-4 text-gray-500 text-center">
                        <p>No child information available</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        
        <!-- Complaint Description -->
        <div class="px-4 pb-4">
            <h2 class="text-sm font-medium text-gray-500 mb-2">DESCRIPTION</h2>
            <div class="border rounded-lg p-4 bg-gray-50">
                <p class="whitespace-pre-line text-gray-800">{{ $complaint->description }}</p>
            </div>
        </div>
    </div>
</div>
@endsection 