@extends('layouts.app')

@section('title', 'Outgoing Items')

@section('content')
<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-3xl font-bold text-gray-900">Outgoing Items</h1>
        <p class="text-gray-600">Track items dispatched from inventory</p>
    </div>
    <a href="{{ route('outgoing_items.create') }}"
        class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-medium">
        Record Outgoing
    </a>
</div>

<!-- Filter -->
<div class="bg-white p-4 rounded-lg shadow mb-6">
    <form method="GET" action="{{ route('outgoing_items.index') }}" class="flex flex-wrap gap-4">
        <div class="flex-1 min-w-0">
            <select name="item_id"
                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                <option value="">All Items</option>
                @foreach($items as $item)
                <option value="{{ $item->id }}" {{ request('item_id')==$item->id ? 'selected' : '' }}>
                    {{ $item->item_name }} ({{ $item->item_code }})
                </option>
                @endforeach
            </select>
        </div>
        <div class="w-48">
            <input type="date" name="start_date" value="{{ request('start_date') }}"
                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
        </div>
        <div class="w-48">
            <input type="date" name="end_date" value="{{ request('end_date') }}"
                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
        </div>
        <button type="submit" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md text-sm font-medium">
            Filter
        </button>
        @if(request()->hasAny(['item_id', 'start_date', 'end_date']))
        <a href="{{ route('outgoing_items.index') }}"
            class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-md text-sm font-medium">
            Clear
        </a>
        @endif
    </form>
</div>

@if($outgoingItems->count() > 0)
<div class="bg-white shadow overflow-hidden sm:rounded-md">
    <ul class="divide-y divide-gray-200">
        @foreach($outgoingItems as $outgoing)
        <li>
            <div class="px-4 py-4 sm:px-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17 8l4 4m0 0l-4 4m4-4H3" />
                                </svg>
                            </div>
                        </div>
                        <div class="ml-4">
                            <div class="text-sm font-medium text-gray-900">
                                {{ $outgoing->item->item_name }}
                            </div>
                            <div class="text-sm text-gray-500">
                                {{ $outgoing->item->item_code }} • To: {{ $outgoing->recipient }}
                            </div>
                            @if($outgoing->description)
                            <div class="text-sm text-gray-400">
                                {{ Str::limit($outgoing->description, 80) }}
                            </div>
                            @endif
                        </div>
                    </div>
                    <div class="flex items-center space-x-6">
                        <div class="text-right">
                            <div class="text-sm font-medium text-blue-600">-{{ $outgoing->quantity }}</div>
                            <div class="text-sm text-gray-500">{{ $outgoing->outgoing_date->format('M d, Y') }}</div>
                        </div>
                        <div class="flex items-center space-x-2">
                            <a href="{{ route('outgoing_items.show', $outgoing) }}"
                                class="text-indigo-600 hover:text-indigo-900 text-sm">View</a>
                            <a href="{{ route('outgoing_items.edit', $outgoing) }}"
                                class="text-yellow-600 hover:text-yellow-900 text-sm">Edit</a>
                            <form action="{{ route('outgoing_items.destroy', $outgoing) }}" method="POST" class="inline"
                                onsubmit="return confirm('Are you sure? This will adjust the item stock.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-900 text-sm">Delete</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </li>
        @endforeach
    </ul>
</div>
@else
<div class="text-center py-12">
    <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mx-auto">
        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
        </svg>
    </div>
    <h3 class="mt-2 text-sm font-medium text-gray-900">No outgoing items found</h3>
    @if(request()->hasAny(['item_id', 'start_date', 'end_date']))
    <p class="mt-1 text-sm text-gray-500">Try adjusting your filter criteria.</p>
    <div class="mt-6">
        <a href="{{ route('outgoing_items.index') }}" class="text-indigo-600 hover:text-indigo-500">Clear filters</a>
    </div>
    @else
    <p class="mt-1 text-sm text-gray-500">Get started by recording your first outgoing item.</p>
    <div class="mt-6">
        <a href="{{ route('outgoing_items.create') }}"
            class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
            <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
            </svg>
            Record Outgoing
        </a>
    </div>
    @endif
</div>
@endif

@if($outgoingItems->hasPages())
<div class="mt-6">
    {{ $outgoingItems->appends(request()->query())->links() }}
</div>
@endif
@endsection
