@extends('layouts.admin-layout')

@section('title', 'Complaints Management')

@section('content')
<div class="bg-white rounded-lg shadow-md p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Complaints Management</h1>
        <div>
            <form action="{{ route('admin.complaints.index') }}" method="GET" class="flex items-center">
                <select name="shift" id="shift" class="border rounded py-2 px-3 mr-2">
                    <option value="">All Shifts</option>
                    @foreach($shifts as $shift)
                    <option value="{{ $shift->id }}" {{ request('shift') == $shift->id ? 'selected' : '' }}>
                        {{ $shift->date->format('M d, Y') }} ({{ ucfirst($shift->type) }})
                    </option>
                    @endforeach
                </select>
                <button type="submit" class="bg-blue-600 text-white py-2 px-4 rounded hover:bg-blue-700">
                    Filter
                </button>
            </form>
        </div>
    </div>

    @if($complaints->isEmpty())
    <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-4">
        No complaints found.
    </div>
    @else
    <div class="overflow-x-auto">
        <table class="min-w-full bg-white">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Shift Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Child</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reported By</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach($complaints as $complaint)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap">
                        {{ $complaint->shift->date->format('M d, Y') }} ({{ ucfirst($complaint->shift->type) }})
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">{{ $complaint->type }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        {{ $complaint->child ? $complaint->child->name : 'N/A' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">{{ $complaint->user->name }}</td>
                    <td class="px-6 py-4">
                        <div class="truncate max-w-xs" title="{{ $complaint->description }}">
                            {{ $complaint->description }}
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                            {{ $complaint->resolved ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ $complaint->resolved ? 'Resolved' : 'Unresolved' }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <form action="{{ route('admin.complaints.toggle-resolved', $complaint) }}" method="POST"
                            class="inline-block">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="text-blue-600 hover:text-blue-900">
                                {{ $complaint->resolved ? 'Mark Unresolved' : 'Mark Resolved' }}
                            </button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-4">
        {{ $complaints->links() }}
    </div>
    @endif
</div>
@endsection 