@extends('layouts.admin-layout')

@section('title', 'Expenses Management')

@section('content')
<div class="bg-white rounded-lg shadow-md p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Expenses Management</h1>
        <a href="{{ route('admin.expenses.create') }}"
            class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
            Add New Expense
        </a>
    </div>

    @if($expenses->isEmpty())
    <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-4">
        No expenses found. Please add a new expense.
    </div>
    @else
    <div class="overflow-x-auto">
        <table class="min-w-full bg-white">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Recorded By</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach($expenses as $expense)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap">{{ $expense->created_at->format('M d, Y H:i') }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">{{ $expense->item }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">${{ number_format($expense->amount, 2) }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">{{ $expense->user->name }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <form action="{{ route('admin.expenses.destroy', $expense) }}" method="POST"
                            class="inline-block"
                            onsubmit="return confirm('Are you sure you want to delete this expense?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-900">Delete</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-4">
        {{ $expenses->links() }}
    </div>
    @endif
</div>
@endsection 