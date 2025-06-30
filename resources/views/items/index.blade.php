@extends('layouts.app')

@section('title', 'Items')

@section('content')
<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-3xl font-bold text-gray-900">Items</h1>
        <p class="text-gray-600">Manage your inventory items</p>
    </div>
    <a href="{{ route('items.create') }}"
        class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-medium">
        Add Item
    </a>
</div>

<!-- Search and Filter -->
<div class="bg-white p-4 rounded-lg shadow mb-6">
    <form method="GET" action="{{ route('items.index') }}" class="flex flex-wrap gap-4">
        <div class="flex-1 min-w-0">
            <input type="text" name="search" value="{{ request('search') }}"
                placeholder="Search items by name or code..."
                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm py-3 px-2">
        </div>
        <div class="w-48">
            <select name="category_id"
                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm py-3 px-2 bg-white">
                <option value="">All Categories</option>
                @foreach($categories as $category)
                <option value="{{ $category->id }}" {{ request('category_id')==$category->id ? 'selected' : '' }}
                    class="bg-white">
                    {{ $category->name }}
                </option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md text-sm font-medium">
            Search
        </button>
        @if(request('search') || request('category_id'))
        <a href="{{ route('items.index') }}"
            class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-md text-sm font-medium">
            Clear
        </a>
        @endif
    </form>
</div>

@if($items->count() > 0)
<div class="bg-white shadow overflow-hidden sm:rounded-md">
    <ul class="divide-y divide-gray-200">
        @foreach($items as $item)
        <li>
            <div class="px-4 py-4 sm:px-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 h-12 w-12">
                            @if($item->image_path)
                            <img class="h-12 w-12 rounded-full object-cover" src="{{ Storage::url($item->image_path) }}"
                                alt="{{ $item->item_name }}">
                            @else
                            <div class="h-12 w-12 rounded-full bg-gray-100 flex items-center justify-center">
                                <span class="text-gray-600 font-medium text-sm">{{ substr($item->item_name, 0, 2)
                                    }}</span>
                            </div>
                            @endif
                        </div>
                        <div class="ml-4">
                            <div class="flex items-center">
                                <div class="text-sm font-medium text-gray-900">
                                    {{ $item->item_name }}
                                </div>
                                @if($item->isLowStock())
                                <span
                                    class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    Low Stock
                                </span>
                                @endif
                            </div>
                            <div class="text-sm text-gray-500">
                                {{ $item->item_code }} • {{ $item->category->name }}
                            </div>
                            @if($item->description)
                            <div class="text-sm text-gray-400">
                                {{ Str::limit($item->description, 80) }}
                            </div>
                            @endif
                        </div>
                    </div>
                    <div class="flex items-center space-x-6">
                        <div class="text-right">
                            <div class="text-sm font-medium text-gray-900">Stock: {{ $item->stock }}</div>
                            <div class="text-sm text-gray-500">Buy: ${{ number_format($item->purchase_price, 2) }}</div>
                            <div class="text-sm text-gray-500">Sell: ${{ number_format($item->selling_price, 2) }}</div>
                        </div>
                        <div class="flex items-center space-x-2">
                            <a href="{{ route('items.show', $item) }}"
                                class="text-indigo-600 hover:text-indigo-900 text-sm">View</a>
                            <a href="{{ route('items.edit', $item) }}"
                                class="text-yellow-600 hover:text-yellow-900 text-sm">Edit</a>
                            <form action="{{ route('items.destroy', $item) }}" method="POST" class="inline"
                                onsubmit="return confirm('Are you sure?')">
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
    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M20 7l-8-4-8 4m16 0l-8 4-8-4m16 0v10l-8 4-8-4V7" />
    </svg>
    <h3 class="mt-2 text-sm font-medium text-gray-900">No items found</h3>
    @if(request('search') || request('category_id'))
    <p class="mt-1 text-sm text-gray-500">Try adjusting your search or filter criteria.</p>
    <div class="mt-6">
        <a href="{{ route('items.index') }}" class="text-indigo-600 hover:text-indigo-500">Clear filters</a>
    </div>
    @else
    <p class="mt-1 text-sm text-gray-500">Get started by adding your first item.</p>
    <div class="mt-6">
        <a href="{{ route('items.create') }}"
            class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
            <svg class="-ml-1 mr-2 h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd"
                    d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z"
                    clip-rule="evenodd" />
            </svg>
            Add Item
        </a>
    </div>
    @endif
</div>
@endif

@if($items->hasPages())
<div class="mt-6">
    {{ $items->appends(request()->query())->links() }}
</div>
@endif
@endsection
