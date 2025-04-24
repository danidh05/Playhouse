@extends('layouts.admin-layout')

@section('title', 'Add-Ons Management')

@section('content')
<div class="bg-white rounded-lg shadow-md p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Add-Ons Management</h1>
        <a href="{{ route('admin.addons.create') }}"
            class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
            Add New Add-On
        </a>
    </div>

    @if($addOns->isEmpty())
    <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-4">
        No add-ons found. Please add a new add-on.
    </div>
    @else
    <div class="overflow-x-auto">
        <table class="min-w-full bg-white">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price (USD)</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price (LBP)</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach($addOns as $addon)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap">{{ $addon->name }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">${{ number_format($addon->price, 2) }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">{{ number_format($addon->price * config('play.lbp_exchange_rate', 90000)) }} L.L</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <div class="flex space-x-2">
                            <a href="{{ route('admin.addons.edit', $addon) }}"
                                class="text-blue-600 hover:text-blue-900">Edit</a>
                            <form action="{{ route('admin.addons.destroy', $addon) }}" method="POST"
                                class="inline-block"
                                onsubmit="return confirm('Are you sure you want to delete this add-on?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-900">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-4">
        {{ $addOns->links() }}
    </div>
    @endif
</div>
@endsection 