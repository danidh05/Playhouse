@extends('layouts.admin-layout')

@section('title', 'Add-Ons Management')

@section('content')
<div class="bg-white rounded-lg shadow-md p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Add-Ons Management</h1>
        <div class="flex gap-2">
            <a href="{{ route('admin.addons.index', ['show_discontinued' => request()->has('show_discontinued') ? '0' : '1']) }}"
                class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">
                {{ request()->has('show_discontinued') ? 'Hide Discontinued' : 'Show Discontinued' }}
            </a>
            <a href="{{ route('admin.addons.create') }}"
                class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                Add New Add-On
            </a>
        </div>
    </div>

    @if(session('warning'))
    <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-4">
        {{ session('warning') }}
    </div>
    @endif

    @if($addOns->isEmpty())
    <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-4">
        No add-ons found. Please add a new add-on.
    </div>
    @else
    <div class="overflow-x-auto">
        <table class="min-w-full bg-white">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price
                        (USD)</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price
                        (LBP)</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach($addOns as $addon)
                <tr class="{{ $addon->active ? '' : 'bg-gray-100' }}">
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($addon->active)
                        <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">Active</span>
                        @else
                        <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">Discontinued</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">{{ $addon->name }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">${{ number_format($addon->price, 2) }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        {{ number_format($addon->price * config('play.lbp_exchange_rate', 90000)) }} LBP</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <div class="flex space-x-2">
                            <a href="{{ route('admin.addons.edit', $addon) }}"
                                class="text-blue-600 hover:text-blue-900">Edit</a>
                            @if($addon->active)
                            <form action="{{ route('admin.addons.destroy', $addon) }}" method="POST"
                                class="inline-block"
                                onsubmit="return confirm('Are you sure you want to delete this add-on?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-900">Delete</button>
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
        {{ $addOns->appends(request()->query())->links() }}
    </div>
    @endif
</div>
@endsection