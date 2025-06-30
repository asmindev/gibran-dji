@extends('layouts.app')

@section('title', 'Categories')

@section('content')
<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-3xl font-bold text-gray-900">Categories</h1>
        <p class="text-gray-600">Manage your item categories</p>
    </div>
    <a href="{{ route('categories.create') }}"
        class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-medium">
        Add Category
    </a>
</div>

<div class="bg-white shadow overflow-hidden sm:rounded-md">
    @if($categories->count() > 0)
    <ul class="divide-y divide-gray-200">
        @foreach($categories as $category)
        <li>
            <div class="px-4 py-4 flex items-center justify-between">
                <div class="flex items-center">
                    <div class="flex-shrink-0 h-10 w-10">
                        <div class="h-10 w-10 rounded-full bg-indigo-100 flex items-center justify-center">
                            <span class="text-indigo-600 font-medium text-sm">{{ substr($category->name, 0, 2) }}</span>
                        </div>
                    </div>
                    <div class="ml-4">
                        <div class="text-sm font-medium text-gray-900">
                            {{ $category->name }}
                        </div>
                        @if($category->description)
                        <div class="text-sm text-gray-500">
                            {{ Str::limit($category->description, 60) }}
                        </div>
                        @endif
                        <div class="text-xs text-gray-400">
                            {{ $category->items_count ?? 0 }} items
                        </div>
                    </div>
                </div>
                <div class="flex items-center space-x-2">
                    <a href="{{ route('categories.show', $category) }}"
                        class="text-indigo-600 hover:text-indigo-900 text-sm">View</a>
                    <a href="{{ route('categories.edit', $category) }}"
                        class="text-yellow-600 hover:text-yellow-900 text-sm">Edit</a>
                    <form action="{{ route('categories.destroy', $category) }}" method="POST" class="inline"
                        onsubmit="return confirm('Are you sure?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-red-600 hover:text-red-900 text-sm">Delete</button>
                    </form>
                </div>
            </div>
        </li>
        @endforeach
    </ul>
    @else
    <div class="text-center py-12">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">No categories</h3>
        <p class="mt-1 text-sm text-gray-500">Get started by creating your first category.</p>
        <div class="mt-6">
            <a href="{{ route('categories.create') }}"
                class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                <svg class="-ml-1 mr-2 h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z"
                        clip-rule="evenodd" />
                </svg>
                Add Category
            </a>
        </div>
    </div>
    @endif
</div>

@if($categories->hasPages())
<div class="mt-6">
    {{ $categories->links() }}
</div>
@endif
@endsection
