@extends('layouts.app')

@section('title', 'Items')


@section('content')
<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-3xl font-bold text-gray-900">Barang</h1>
        <p class="text-gray-600">Kelola dan lacak barang</p>
    </div>
    <a href="{{ route('items.create') }}" class="bg-primary text-white px-4 py-2 rounded-md text-sm font-medium">
        Tambah Barang
    </a>
</div>

<div class="rounded-md">


    <!-- Items Table -->
    @if($items->count() > 0)
    <div class="bg-primary shadow overflow-hidden sm:rounded-lg">
        <div class="p-4">
            <form method="GET" action="{{ route('items.index') }}" class="flex flex-col sm:flex-row gap-4">
                <!-- Search -->
                <div class="w-fit">
                    <input type="text" name="search" id="search" value="{{ request('search') }}"
                        placeholder="Search items by name, code, or category..."
                        class="bg-white w-32 px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none">
                </div>

                <!-- Items per page -->
                <div class="flex items-center space-x-2">
                    <label for="per_page" class="text-sm text-white whitespace-nowrap">Show:</label>
                    <select name="per_page" id="per_page"
                        class="bg-white px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="10" {{ request('per_page', 10)==10 ? 'selected' : '' }}>10</option>
                        <option value="15" {{ request('per_page', 10)==15 ? 'selected' : '' }}>15</option>
                        <option value="25" {{ request('per_page', 10)==25 ? 'selected' : '' }}>25</option>
                        <option value="50" {{ request('per_page', 10)==50 ? 'selected' : '' }}>50</option>
                    </select>
                </div>

                <!-- Search Button -->
                <div class="flex space-x-2">
                    <button type="submit"
                        class="bg-blue-500 text-white px-4 py-2 rounded-md text-sm font-medium whitespace-nowrap">
                        Search
                    </button>
                    @if(request('search'))
                    <a href="{{ route('items.index') }}"
                        class="bg-white text-gray-600 px-4 py-2 rounded-md text-sm font-medium whitespace-nowrap">
                        Clear
                    </a>
                    @endif
                </div>
            </form>

            <!-- Results info -->
            @if($items->total() > 0)
            <div class="mt-3 text-sm text-gray-50">
                Showing {{ $items->firstItem() }} to {{ $items->lastItem() }} of {{ $items->total() }} items
            </div>
            @endif
        </div>
        <div class="overflow-x-auto">
            <table class="bg-primary min-w-full divide-y divide-gray-400">
                <thead class="">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-50 uppercase tracking-wider">
                            No
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-50 uppercase tracking-wider">
                            ID Barang
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-50 uppercase tracking-wider">
                            Nama Barang
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-50 uppercase tracking-wider">
                            Kategori
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-50 uppercase tracking-wider">
                            Harga Jual
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-50 uppercase tracking-wider">
                            Aksi
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-400">
                    @foreach($items as $index => $item)
                    <tr class="hover:bg-white/10">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-50">
                            {{ ($items->currentPage() - 1) * $items->perPage() + $index + 1 }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-50">
                            {{ $item->item_code }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-50">
                            {{ $item->item_name }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-50">
                            {{ $item->category->name }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-green-600">
                            Rp {{ number_format($item->selling_price, 0, ',', '.') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex space-x-2">
                                <a href="{{ route('items.show', $item) }}" class="text-white hover:text-white">View</a>
                                <a href="{{ route('items.edit', $item) }}"
                                    class="text-yellow-600 hover:text-yellow-900">Edit</a>
                                <form action="{{ route('items.destroy', $item) }}" method="POST" class="inline"
                                    onsubmit="return confirm('Yakin ingin menghapus item ini?')">
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
    </div>
    @else
    <div class="bg-primary shadow rounded-lg">
        <div class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M20 7l-8-4-8 4m16 0l-8 4-8-4m16 0v10l-8 4-8-4V7" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-50">
                @if(request('search'))
                No items found
                @else
                No items
                @endif
            </h3>
            <p class="mt-1 text-sm text-gray-100">
                @if(request('search'))
                Try adjusting your search criteria.
                @else
                Get started by adding your first item.
                @endif
            </p>
            <div class="mt-6">
                @if(request('search'))
                <a href="{{ route('items.index') }}"
                    class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    Clear Search
                </a>
                @else
                <a href="{{ route('items.create') }}"
                    class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                    <svg class="-ml-1 mr-2 h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z"
                            clip-rule="evenodd" />
                    </svg>
                    Add Item
                </a>
                @endif
            </div>
        </div>
    </div>
    @endif

    @if($items->hasPages())
    <div class="mt-6">
        {{ $items->links() }}
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
