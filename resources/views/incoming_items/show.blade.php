@extends('layouts.app')

@section('title', 'Detail Barang Masuk')

@section('content')
<!-- Simple Breadcrumb -->
<div class="mb-6">
    <nav class="flex" aria-label="Breadcrumb">
        <ol class="flex items-center space-x-2">
            <li>
                <a href="{{ route('incoming_items.index') }}"
                    class="flex items-center text-primary hover:text-primary transition-colors">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M7 16l-4-4m0 0l4-4m-4 4h18" />
                    </svg>
                    Barang Masuk
                </a>
            </li>
            <li class="flex items-center">
                <svg class="w-4 h-4 text-gray-400 mx-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                        clip-rule="evenodd" />
                </svg>
                <span class="text-gray-500 font-medium">Detail Data</span>
            </li>
        </ol>
    </nav>

    <div class="mt-4">
        <h1 class="text-2xl font-bold text-gray-900">Detail Barang Masuk</h1>
        <p class="text-gray-600 mt-1">Informasi lengkap barang yang masuk ke inventory</p>
    </div>
</div>

<!-- Detail Card -->
<div class="w-full">
    <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="px-6 py-4 bg-primary text-white">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold">{{ $incomingItem->transaction_id }}</h2>
                <div class="flex space-x-2">
                    <a href="{{ route('incoming_items.edit', $incomingItem) }}"
                        class="bg-white/20 hover:bg-white/30 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                        Edit
                    </a>
                    <a href="{{ route('incoming_items.index') }}"
                        class="bg-white/20 hover:bg-white/30 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                        Kembali
                    </a>
                </div>
            </div>
        </div>

        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Detail Transaksi -->
                <div class="space-y-4">
                    <h3 class="text-lg font-semibold text-gray-800 border-b pb-2">Detail Transaksi</h3>

                    <div class="space-y-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-600">ID Transaksi</label>
                            <p class="text-gray-900 font-medium">{{ $incomingItem->transaction_id }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-600">Tanggal Masuk</label>
                            <p class="text-gray-900">{{ $incomingItem->incoming_date->format('d F Y') }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-600">Waktu Input</label>
                            <p class="text-gray-900">{{ $incomingItem->created_at->format('d F Y, H:i') }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-600">Jumlah</label>
                            <p class="font-semibold text-primary text-lg">+{{ number_format($incomingItem->quantity) }}
                            </p>
                        </div>

                        @if($incomingItem->unit_cost)
                        <div>
                            <label class="block text-sm font-medium text-gray-600">Harga Satuan</label>
                            <p class="text-gray-900">Rp {{ number_format($incomingItem->unit_cost, 0, ',', '.') }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-600">Total Nilai</label>
                            <p class="text-gray-900 font-semibold">Rp {{ number_format($incomingItem->unit_cost *
                                $incomingItem->quantity, 0, ',', '.') }}</p>
                        </div>
                        @endif

                        @if($incomingItem->notes)
                        <div>
                            <label class="block text-sm font-medium text-gray-600">Catatan</label>
                            <p class="text-gray-900 bg-gray-50 p-3 rounded-lg">{{ $incomingItem->notes }}</p>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Detail Barang -->
                <div class="space-y-4">
                    <h3 class="text-lg font-semibold text-gray-800 border-b pb-2">Detail Barang</h3>

                    <div class="bg-gray-50 p-4 rounded-lg">
                        <div class="text-center">
                            <h4 class="font-semibold text-lg text-gray-900">{{ $incomingItem->item->item_name }}</h4>
                            <p class="text-gray-600">{{ $incomingItem->item->category->category_name }}</p>
                            <p class="text-sm text-gray-500 mt-1">ID: {{ $incomingItem->item->id }}</p>
                        </div>

                        <div class="mt-4 text-center">
                            <div>
                                <p class="text-gray-600 text-sm">Stok Saat Ini</p>
                                <p
                                    class="font-semibold text-2xl {{ $incomingItem->item->stock <= $incomingItem->item->minimum_stock ? 'text-red-600' : 'text-primary' }}">
                                    {{ number_format($incomingItem->item->stock) }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stock Impact -->
            <div class="mt-6 bg-primar border border-primary rounded-lg p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-primary" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-primary">Stok Telah Diperbarui</h3>
                        <p class="text-sm text-primary">
                            Transaksi ini menambahkan <strong>{{ number_format($incomingItem->quantity) }}</strong> unit
                            ke inventory.
                            Stok saat ini: <strong>{{ number_format($incomingItem->item->stock) }}</strong> unit.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
