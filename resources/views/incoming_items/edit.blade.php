@extends('layouts.app')

@section('title', 'Edit Barang Masuk')

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
                <span class="text-gray-500 font-medium">Edit Data</span>
            </li>
        </ol>
    </nav>

    <div class="mt-4">
        <h1 class="text-2xl font-bold text-gray-900">Edit Barang Masuk</h1>
        <p class="text-gray-600 mt-1">Update data barang yang masuk ke inventory</p>
    </div>
</div>

<!-- Simple Form -->
<div class="w-full">
    <form action="{{ route('incoming_items.update', $incomingItem) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="bg-white shadow rounded-lg p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- ID Transaksi -->
                <div>
                    <label for="transaction_id" class="block text-sm font-medium text-gray-700 mb-2">
                        ID Transaksi <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="transaction_id" id="transaction_id"
                        value="{{ old('transaction_id', $incomingItem->transaction_id) }}" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                        placeholder="Contoh: TRX20250811001">
                    @error('transaction_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Tanggal Masuk -->
                <div>
                    <label for="incoming_date" class="block text-sm font-medium text-gray-700 mb-2">
                        Tanggal Masuk <span class="text-red-500">*</span>
                    </label>
                    <input type="date" name="incoming_date" id="incoming_date"
                        value="{{ old('incoming_date', $incomingItem->incoming_date->format('Y-m-d')) }}" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                    @error('incoming_date')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Pilih Barang -->
                <div>
                    <label for="item_id" class="block text-sm font-medium text-gray-700 mb-2">
                        Pilih Barang <span class="text-red-500">*</span>
                    </label>
                    <select name="item_id" id="item_id" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                        <option value="">Pilih barang</option>
                        @foreach($items as $item)
                        <option value="{{ $item->id }}" {{ old('item_id', $incomingItem->item_id) == $item->id ?
                            'selected' : '' }}>
                            {{ $item->item_name }} - Stok: {{ $item->stock }}
                        </option>
                        @endforeach
                    </select>
                    @error('item_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Jumlah -->
                <div>
                    <label for="quantity" class="block text-sm font-medium text-gray-700 mb-2">
                        Jumlah <span class="text-red-500">*</span>
                    </label>
                    <input type="number" name="quantity" id="quantity"
                        value="{{ old('quantity', $incomingItem->quantity) }}" min="1" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                        placeholder="Masukkan jumlah barang">
                    @error('quantity')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Harga Satuan -->
                <div>
                    <label for="unit_cost" class="block text-sm font-medium text-gray-700 mb-2">Harga Satuan</label>
                    <input type="number" name="unit_cost" id="unit_cost"
                        value="{{ old('unit_cost', $incomingItem->unit_cost) }}" min="0" step="0.01"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                        placeholder="Masukkan harga satuan">
                    @error('unit_cost')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Catatan -->
                <div class="md:col-span-2">
                    <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">Catatan</label>
                    <textarea name="notes" id="notes" rows="3"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary resize-none"
                        placeholder="Catatan opsional tentang barang masuk ini">{{ old('notes', $incomingItem->notes) }}</textarea>
                    @error('notes')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Warning Box -->
        <div class="bg-primar border border-primary rounded-lg p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-primary" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                            clip-rule="evenodd"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-primary">Pemberitahuan Penting</h3>
                    <p class="text-sm text-primary mt-1">
                        Mengedit data ini akan menyesuaikan stok barang secara otomatis.
                        Selisih antara jumlah lama dan baru akan diterapkan pada stok saat ini.
                    </p>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex justify-end space-x-3">
            <a href="{{ route('incoming_items.index') }}"
                class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 font-medium transition-colors">
                Batal
            </a>
            <button type="submit"
                class="px-6 py-2 bg-primary hover:bg-primary text-white rounded-lg font-medium transition-colors">
                Update Barang Masuk
            </button>
        </div>
    </form>
</div>
@endsection
