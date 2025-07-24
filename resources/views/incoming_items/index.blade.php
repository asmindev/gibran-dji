@extends('layouts.app')

@section('title', 'Incoming Items')

@section('content')
<div>
    <!-- Simple Header -->
    <div class="mb-6">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Barang Masuk</h1>
                <p class="text-gray-600 mt-1">Kelola dan lacak barang yang masuk ke inventory</p>
            </div>
            <div class="flex space-x-3">
                <!-- Export Dropdown -->
                <div class="relative inline-block text-left" x-data="{ open: false }">
                    <button @click="open = !open" type="button"
                        class="bg-green-600 hover:bg-green-700 text-white px-4 py-2.5 rounded-lg font-medium transition-colors inline-flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Export
                        <svg class="w-4 h-4 ml-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                clip-rule="evenodd" />
                        </svg>
                    </button>

                    <div x-show="open" @click.outside="open = false" x-transition
                        class="absolute right-0 mt-2 w-48 rounded-lg shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-10">
                        <div class="py-1">
                            <a href="{{ route('incoming_items.export', ['format' => 'excel'] + request()->query()) }}"
                                class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <svg class="w-4 h-4 mr-3 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        d="M4 4a2 2 0 00-2 2v8a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2H4zm2 2h8v8H6V6z" />
                                </svg>
                                Export Excel
                            </a>
                            <a href="{{ route('incoming_items.export', ['format' => 'csv'] + request()->query()) }}"
                                class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <svg class="w-4 h-4 mr-3 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z" />
                                    <path fill-rule="evenodd"
                                        d="M4 5a2 2 0 012-2v1a1 1 0 102 0V3a2 2 0 012 2v6a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm2.5 5a1.5 1.5 0 100-3 1.5 1.5 0 000 3z"
                                        clip-rule="evenodd" />
                                </svg>
                                Export CSV
                            </a>
                        </div>
                    </div>
                </div>

                <a href="{{ route('incoming_items.create') }}"
                    class="bg-primary text-white px-5 py-2.5 rounded-lg font-medium transition-colors">
                    Tambah Barang Masuk
                </a>
            </div>
        </div>
    </div>


    <div class="bg-primary rounded-xl p-8">
        @if($incomingItems->count() > 0)
        <div class="bg-white shadow rounded-lg overflow-hidden">
            <!-- Simple Filter -->
            <div class="bg-white p-4">
                <form method="GET" action="{{ route('incoming_items.index') }}" class="flex flex-wrap gap-4">
                    <div class="flex-1 min-w-48">
                        <input type="text" name="search" value="{{ request('search') }}"
                            placeholder="Cari nama barang..."
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                    </div>
                    <div class="flex-1 min-w-48">
                        <select name="item_id"
                            class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                            <option value="">Semua Item</option>
                            @foreach($items as $item)
                            <option value="{{ $item->id }}" {{ request('item_id')==$item->id ? 'selected' : '' }}>
                                {{ $item->item_name }} ({{ $item->item_code }})
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <input type="date" name="start_date" value="{{ request('start_date') }}"
                            placeholder="Dari Tanggal"
                            class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                    </div>
                    <div>
                        <input type="date" name="end_date" value="{{ request('end_date') }}"
                            placeholder="Sampai Tanggal"
                            class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                    </div>
                    <button type="submit"
                        class="bg-primary text-white px-4 py-2 rounded-lg font-medium transition-colors">
                        Filter
                    </button>
                    @if(request()->hasAny(['search', 'item_id', 'start_date', 'end_date']))
                    <a href="{{ route('incoming_items.index') }}"
                        class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg font-medium transition-colors">
                        Clear
                    </a>
                    @endif
                </form>
            </div>
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama
                            Barang
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Tanggal
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Jumlah
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($incomingItems as $index => $incoming)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ ($incomingItems->currentPage() - 1) * $incomingItems->perPage() + $index + 1 }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div>
                                <div class="text-sm font-medium text-gray-900">{{ $incoming->item->item_name }}</div>
                                <div class="text-sm text-gray-500">{{ $incoming->item->item_code }}</div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $incoming->incoming_date->format('d/m/Y') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm font-medium text-primary-600">+{{ $incoming->quantity }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                            <a href="{{ route('incoming_items.show', $incoming) }}"
                                class="text-primary-600 hover:text-primary-700">Detail</a>
                            <a href="{{ route('incoming_items.edit', $incoming) }}"
                                class="text-primary-600 hover:text-primary-700">Edit</a>
                            <form action="{{ route('incoming_items.destroy', $incoming) }}" method="POST" class="inline"
                                onsubmit="return confirm('Yakin ingin menghapus? Ini akan menyesuaikan stok barang.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-700">Hapus</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="bg-white rounded-lg shadow p-12 text-center">
            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M7 16l-4-4m0 0l4-4m-4 4h18" />
                </svg>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">Belum ada data barang masuk</h3>
            @if(request()->hasAny(['search', 'item_id', 'start_date', 'end_date']))
            <p class="text-gray-500 mb-4">Coba sesuaikan kriteria filter Anda.</p>
            <a href="{{ route('incoming_items.index') }}"
                class="text-primary-600 hover:text-primary-700 font-medium">Hapus
                filter</a>
            @else
            <p class="text-gray-500 mb-6">Mulai dengan mencatat barang masuk pertama Anda.</p>
            <a href="{{ route('incoming_items.create') }}"
                class="inline-flex items-center px-4 py-2 bg-primary text-white font-medium rounded-lg transition-colors">
                <svg class="-ml-1 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Tambah Barang Masuk
            </a>
            @endif
        </div>
        @endif

        @if($incomingItems->hasPages())
        <div class="mt-6">
            {{ $incomingItems->appends(request()->query())->links() }}
        </div>
        @endif
    </div>

</div>
@endsection
