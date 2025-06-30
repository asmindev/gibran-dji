@extends('layouts.app')

@section('title', $category->name)

@section('content')
<div class="mb-6">
    <nav class="flex" aria-label="Breadcrumb">
        <ol class="flex items-center space-x-4">
            <li>
                <a href="{{ route('categories.index') }}" class="text-gray-400 hover:text-gray-500">Categories</a>
            </li>
            <li>
                <div class="flex items-center">
                    <svg class="flex-shrink-0 h-5 w-5 text-gray-300" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                            clip-rule="evenodd" />
                    </svg>
                    <span class="ml-4 text-sm font-medium text-gray-500">{{ $category->name }}</span>
                </div>
            </li>
        </ol>
    </nav>

    <div class="flex justify-between items-center">
        <div>
            <h1 class="mt-4 text-3xl font-bold text-gray-900">{{ $category->name }}</h1>
            @if($category->description)
            <p class="text-gray-600">{{ $category->description }}</p>
            @endif
        </div>
        <div class="flex space-x-3">
            <a href="{{ route('categories.edit', $category) }}"
                class="bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                Edit Category
            </a>
            <form action="{{ route('categories.destroy', $category) }}" method="POST" class="inline"
                onsubmit="return confirm('Are you sure? This will also delete all items in this category.')">
                @csrf
                @method('DELETE')
                <button type="submit"
                    class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                    Delete Category
                </button>
            </form>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Category Stats -->
    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-indigo-500 rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M20 7l-8-4-8 4m16 0l-8 4-8-4m16 0v10l-8 4-8-4V7"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Total Items</dt>
                        <dd class="text-lg font-medium text-gray-900">{{ $category->items->count() }}</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                            </path>
                        </svg>
                    </div>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Total Stock</dt>
                        <dd class="text-lg font-medium text-gray-900">{{ $category->items->sum('stock') }}</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-yellow-500 rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1">
                            </path>
                        </svg>
                    </div>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Total Value</dt>
                        <dd class="text-lg font-medium text-gray-900">${{
                            number_format($category->items->sum(function($item) { return $item->stock *
                            $item->purchase_price; }), 2) }}</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Items in this Category -->
<div class="mt-8">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-semibold text-gray-900">Items in this Category</h2>
        <a href="{{ route('items.create') }}?category_id={{ $category->id }}"
            class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-medium">
            Add Item to Category
        </a>
    </div>

    @if($category->items->count() > 0)
    <div class="bg-white shadow overflow-hidden sm:rounded-md">
        <ul class="divide-y divide-gray-200">
            @foreach($category->items as $item)
            <li>
                <div class="px-4 py-4 flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 h-10 w-10">
                            @if($item->image_path)
                            <img class="h-10 w-10 rounded-full object-cover" src="{{ Storage::url($item->image_path) }}"
                                alt="{{ $item->item_name }}">
                            @else
                            <div class="h-10 w-10 rounded-full bg-gray-100 flex items-center justify-center">
                                <span class="text-gray-600 font-medium text-sm">{{ substr($item->item_name, 0, 2)
                                    }}</span>
                            </div>
                            @endif
                        </div>
                        <div class="ml-4">
                            <div class="text-sm font-medium text-gray-900">
                                {{ $item->item_name }}
                            </div>
                            <div class="text-sm text-gray-500">
                                {{ $item->item_code }}
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center space-x-6">
                        <div class="text-right">
                            <div class="text-sm font-medium text-gray-900">Stock: {{ $item->stock }}</div>
                            <div class="text-sm text-gray-500">${{ number_format($item->selling_price, 2) }}</div>
                        </div>
                        <div class="flex items-center space-x-2">
                            <a href="{{ route('items.show', $item) }}"
                                class="text-indigo-600 hover:text-indigo-900 text-sm">View</a>
                            <a href="{{ route('items.edit', $item) }}"
                                class="text-yellow-600 hover:text-yellow-900 text-sm">Edit</a>
                        </div>
                    </div>
                </div>
            </li>
            @endforeach
        </ul>
    </div>
    @else
    <div class="text-center py-12 bg-white rounded-lg shadow">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M20 7l-8-4-8 4m16 0l-8 4-8-4m16 0v10l-8 4-8-4V7" />
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">No items in this category</h3>
        <p class="mt-1 text-sm text-gray-500">Get started by adding your first item to this category.</p>
        <div class="mt-6">
            <a href="{{ route('items.create') }}?category_id={{ $category->id }}"
                class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                <svg class="-ml-1 mr-2 h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z"
                        clip-rule="evenodd" />
                </svg>
                Add Item
            </a>
        </div>
    </div>
    @endif
</div>
@endsection
