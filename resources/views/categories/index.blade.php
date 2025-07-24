@extends('layouts.app')

@section('title', 'Categories')

@section('content')
<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-3xl font-bold text-gray-700">Kategori</h1>
        <p class="text-gray-600">Manage your item categories</p>
    </div>
    <a href="{{ route('categories.create') }}"
        class="bg-primary hover:bg-primary-700 text-white px-4 py-2 rounded-md text-sm font-medium">
        Add Category
    </a>
</div>

<div class="rounded-2xl p-6">

    <!-- Categories Table -->
    <div class="bg-primary text-whiteshadow overflow-hidden sm:rounded-lg">
        <div class="p-4">
            <form method="GET" action="{{ route('categories.index') }}" class="flex flex-col sm:flex-row gap-4">
                <!-- Items per page -->
                <div class="flex items-center space-x-2">
                    <label for="per_page" class="text-sm text-white whitespace-nowrap">Show:</label>
                    <select name="per_page" id="per_page"
                        class="bg-white px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary">
                        <option value="10" {{ request('per_page', 10)==10 ? 'selected' : '' }}>10</option>
                        <option value="15" {{ request('per_page', 10)==15 ? 'selected' : '' }}>15</option>
                        <option value="25" {{ request('per_page', 10)==25 ? 'selected' : '' }}>25</option>
                        <option value="50" {{ request('per_page', 10)==50 ? 'selected' : '' }}>50</option>
                    </select>
                </div>
                <div class="w-fit">
                    <input type="text" name="search" id="search" value="{{ request('search') }}"
                        placeholder="Search categories by name or description..."
                        class="bg-white w-32 px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary">
                </div>
                <!-- Search Button -->
                <div class="flex space-x-2">
                    <button type="submit"
                        class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md text-sm font-medium whitespace-nowrap">
                        Search
                    </button>
                    @if(request('search'))
                    <a href="{{ route('categories.index') }}"
                        class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-md text-sm font-medium whitespace-nowrap">
                        Clear
                    </a>
                    @endif
                </div>
            </form>

            <!-- Results info -->
            @if($categories->total() > 0)
            <div class="mt-3 text-sm text-white">
                Showing {{ $categories->firstItem() }} to {{ $categories->lastItem() }} of {{ $categories->total() }}
                categories
            </div>
            @endif
        </div>
        @if($categories->count() > 0)
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-400">
                <thead class="">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-50 uppercase tracking-wider">
                            No
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-50 uppercase tracking-wider">
                            Nama Kategori
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-50 uppercase tracking-wider">
                            Jumlah Barang
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-50 uppercase tracking-wider">
                            Aksi
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-400">
                    @foreach($categories as $index => $category)
                    <tr class="hover:bg-white/10">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-50">
                            {{ ($categories->currentPage() - 1) * $categories->perPage() + $index + 1 }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-50">{{ $category->name }}</div>
                                    @if($category->description)
                                    <div class="text-sm text-gray-50">{{ Str::limit($category->description, 50) }}
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($category->items_count > 0)
                            <span
                                class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                            {{ $category->items_count >= 10 ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800' }}">
                                {{ $category->items_count }} {{ Str::plural('item', $category->items_count) }}
                            </span>
                            @else
                            <span
                                class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800">
                                0 items
                            </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex space-x-2">
                                <a href="{{ route('categories.show', $category) }}" class="text-white">View</a>
                                <a href="{{ route('categories.edit', $category) }}"
                                    class="text-yellow-600 hover:text-yellow-900">Edit</a>
                                <form action="{{ route('categories.destroy', $category) }}" method="POST" class="inline"
                                    onsubmit="return confirm('Yakin ingin menghapus kategori ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                        class="text-red-600 hover:text-red-900 {{ $category->items_count > 0 ? 'opacity-50 cursor-not-allowed' : '' }}"
                                        {{ $category->items_count > 0 ? 'disabled title="Tidak bisa menghapus kategori
                                        yang
                                        memiliki items"' : '' }}>
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-50">
                @if(request('search'))
                No categories found
                @else
                No categories
                @endif
            </h3>
            <p class="mt-1 text-sm text-gray-50">
                @if(request('search'))
                Try adjusting your search criteria.
                @else
                Get started by creating your first category.
                @endif
            </p>
            <div class="mt-6">
                @if(request('search'))
                <a href="{{ route('categories.index') }}"
                    class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    Clear Search
                </a>
                @else
                <a href="{{ route('categories.create') }}"
                    class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary hover:bg-primary-700">
                    <svg class="-ml-1 mr-2 h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z"
                            clip-rule="evenodd" />
                    </svg>
                    Add Category
                </a>
                @endif
            </div>
        </div>
        @endif
    </div>

    @if($categories->hasPages())
    <div class="mt-6">
        {{ $categories->links() }}
    </div>
    @endif
</div>

<!-- Simple JavaScript -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('search');
    const perPageSelect = document.getElementById('per_page');

    // Auto-submit on per page change
    if (perPageSelect) {
        perPageSelect.addEventListener('change', function() {
            this.closest('form').submit();
        });
    }

    // Add Enter key support for search
    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.closest('form').submit();
            }
        });

        // Focus on search input
        searchInput.focus();
    }

    // Highlight search terms in results
    const searchTerm = '{{ request("search") }}';
    if (searchTerm) {
        const regex = new RegExp(`(${searchTerm.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
        document.querySelectorAll('.text-gray-50').forEach(element => {
            if (element.textContent.toLowerCase().includes(searchTerm.toLowerCase())) {
                element.innerHTML = element.textContent.replace(regex, '<mark class="bg-yellow-200">$1</mark>');
            }
        });
    }
});
</script>
@endsection
