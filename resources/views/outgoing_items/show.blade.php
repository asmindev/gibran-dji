@extends('layouts.app')

@section('title', 'Detail Barang Keluar')

@section('content')
<!-- Simple Breadcrumb -->
<div class="mb-6">
    <nav class="flex" aria-label="Breadcrumb">
        <ol class="flex items-center space-x-2">
            <li>
                <a href="{{ route('outgoing_items.index') }}"
                    class="flex items-center text-primary-600 hover:text-primary-700 transition-colors">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M7 16l-4-4m0 0l4-4m-4 4h18" />
                    </svg>
                    Barang Keluar
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
        <h1 class="text-2xl font-bold text-gray-900">Detail Barang Keluar</h1>
        <p class="text-gray-600 mt-1">Informasi lengkap barang yang keluar dari inventory</p>
    </div>
</div>

<!-- Detail Card -->
<div class="max-w-4xl">
    <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="px-6 py-4 bg-primary/60 text-white">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold">ID Transaksi: #{{ str_pad($outgoingItem->id, 6, '0', STR_PAD_LEFT) }}
                </h2>
                <div class="flex space-x-2">
                    <a href="{{ route('outgoing_items.edit', $outgoingItem) }}"
                        class="bg-white/20 hover:bg-white/30 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                        Edit
                    </a>
                    <a href="{{ route('outgoing_items.index') }}"
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
                            <label class="block text-sm font-medium text-gray-600">Tanggal Keluar</label>
                            <p class="text-gray-900">{{ $outgoingItem->outgoing_date->format('d F Y') }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-600">Waktu Input</label>
                            <p class="text-gray-900">{{ $outgoingItem->created_at->format('d F Y, H:i') }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-600">Jumlah</label>
                            <p class="font-semibold text-primary-600 text-lg">-{{ number_format($outgoingItem->quantity)
                                }}</p>
                        </div>

                        @if($outgoingItem->customer)
                        <div>
                            <label class="block text-sm font-medium text-gray-600">Penerima</label>
                            <p class="text-gray-900">{{ $outgoingItem->customer }}</p>
                        </div>
                        @endif

                        @if($outgoingItem->purpose)
                        <div>
                            <label class="block text-sm font-medium text-gray-600">Tujuan</label>
                            <p class="text-gray-900">{{ $outgoingItem->purpose }}</p>
                        </div>
                        @endif

                        @if($outgoingItem->notes)
                        <div>
                            <label class="block text-sm font-medium text-gray-600">Keterangan</label>
                            <p class="text-gray-900 bg-gray-50 p-3 rounded-lg">{{ $outgoingItem->notes }}</p>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Detail Barang -->
                <div class="space-y-4">
                    <h3 class="text-lg font-semibold text-gray-800 border-b pb-2">Detail Barang</h3>

                    <div class="bg-gray-50 p-4 rounded-lg">
                        <div class="text-center">
                            <h4 class="font-semibold text-lg text-gray-900">{{ $outgoingItem->item->item_name }}</h4>
                            <p class="text-gray-600">{{ $outgoingItem->item->category->category_name }}</p>
                            <p class="text-sm text-gray-500 mt-1">ID: {{ $outgoingItem->item->id }}</p>
                        </div>

                        <div class="mt-4 text-center">
                            <div>
                                <p class="text-gray-600 text-sm">Stok Saat Ini</p>
                                <p
                                    class="font-semibold text-2xl {{ $outgoingItem->item->stock <= $outgoingItem->item->minimum_stock ? 'text-red-600' : 'text-primary-600' }}">
                                    {{ number_format($outgoingItem->item->stock) }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stock Impact -->
            <div class="mt-6 bg-primary/5 border border-primary/20 rounded-lg p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-primary-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-primary">Stok Telah Diperbarui</h3>
                        <p class="text-sm text-primary/70">
                            Transaksi ini mengurangi <strong>{{ number_format($outgoingItem->quantity) }}</strong> unit
                            dari inventory.
                            Stok saat ini: <strong>{{ number_format($outgoingItem->item->stock) }}</strong> unit.
                            @if($outgoingItem->item->stock <= $outgoingItem->item->minimum_stock)
                                <span class="font-semibold text-red-600">⚠️ Stok di bawah minimum!</span>
                                @endif
                        </p>
                    </div>
                </div>
            </div>

            <!-- Transaction Value -->
            @if($outgoingItem->unit_price)
            <div class="mt-4 bg-primary/5 border border-primary/20 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-medium text-primary/80">Nilai Transaksi</h3>
                        <p class="text-sm text-primary/70">
                            Harga Satuan: Rp {{ number_format($outgoingItem->unit_price, 0, ',', '.') }} × {{
                            number_format($outgoingItem->quantity) }} unit
                        </p>
                    </div>
                    <div class="text-right">
                        <p class="text-2xl font-bold text-primary/90">
                            Rp {{ number_format($outgoingItem->unit_price * $outgoingItem->quantity, 0, ',', '.') }}
                        </p>
                        <p class="text-sm text-primary/60">Total Nilai</p>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
