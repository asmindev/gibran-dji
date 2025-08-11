@extends('layouts.app')

@section('title', 'Edit Barang Keluar')

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
                <span class="text-gray-500 font-medium">Edit Data</span>
            </li>
        </ol>
    </nav>

    <div class="mt-4">
        <h1 class="text-2xl font-bold text-gray-900">Edit Barang Keluar</h1>
        <p class="text-gray-600 mt-1">Update data barang yang keluar dari inventory</p>
    </div>
</div>

<!-- Simple Form -->
<div class="max-w-2xl">
    <form action="{{ route('outgoing_items.update', $outgoingItem) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="bg-white shadow rounded-lg p-6">
            <div class="space-y-6">
                <!-- ID Transaksi -->
                <div>
                    <label for="transaction_id" class="block text-sm font-medium text-gray-700 mb-2">
                        ID Transaksi <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="transaction_id" id="transaction_id"
                        value="{{ old('transaction_id', $outgoingItem->transaction_id) }}" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                        placeholder="Masukkan ID transaksi unik, contoh: OUT-2024-001">
                    <p class="mt-1 text-xs text-gray-500">
                        ID transaksi harus unik untuk setiap barang keluar
                    </p>
                    @error('transaction_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Tanggal Keluar -->
                <div>
                    <label for="outgoing_date" class="block text-sm font-medium text-gray-700 mb-2">
                        Tanggal Keluar <span class="text-red-500">*</span>
                    </label>
                    <input type="date" name="outgoing_date" id="outgoing_date"
                        value="{{ old('outgoing_date', $outgoingItem->outgoing_date->format('Y-m-d')) }}" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                    @error('outgoing_date')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Pilih Barang -->
                <div>
                    <label for="item_id" class="block text-sm font-medium text-gray-700 mb-2">
                        Pilih Barang <span class="text-red-500">*</span>
                    </label>
                    <select name="item_id" id="item_id" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                        <option value="">Pilih barang</option>
                        @foreach($items as $item)
                        <option value="{{ $item->id }}" {{ old('item_id', $outgoingItem->item_id) == $item->id ?
                            'selected' : '' }}
                            data-stock="{{ $item->stock }}" data-price="{{ $item->selling_price }}" data-min-stock="{{
                            $item->minimum_stock }}">
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
                    <input type="number" name="quantity" id="quantity" min="1"
                        value="{{ old('quantity', $outgoingItem->quantity) }}" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                        placeholder="Masukkan jumlah">
                    <div id="stock-warning" class="hidden mt-1 text-sm text-red-600">
                        ⚠️ Stok tidak mencukupi
                    </div>
                    <div id="low-stock-warning" class="hidden mt-1 text-sm text-primary-600">
                        ⚠️ Stok akan di bawah minimum
                    </div>
                    @error('quantity')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Keterangan -->
                <div>
                    <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">Keterangan</label>
                    <textarea name="notes" id="notes" rows="3"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 resize-none"
                        placeholder="Catatan tambahan...">{{ old('notes', $outgoingItem->notes) }}</textarea>
                    @error('notes')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Warning Box -->
        <div class="bg-primary/5 border border-primary/20 rounded-lg p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-primary-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                            clip-rule="evenodd"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-primary/80">Pemberitahuan Penting</h3>
                    <p class="text-sm text-primary/70 mt-1">
                        Mengedit transaksi ini akan menyesuaikan stok barang secara otomatis.
                        Selisih antara jumlah lama dan baru akan diterapkan pada stok saat ini.
                        Pastikan stok mencukupi.
                    </p>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex justify-end space-x-3 mt-2">
                <a href="{{ route('outgoing_items.show', $outgoingItem) }}"
                    class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 font-medium transition-colors">
                    Batal
                </a>
                <button type="submit"
                    class="px-6 py-2 bg-primary/60 hover:bg-primary/70 text-white rounded-lg font-medium transition-colors">
                    Update Barang Keluar
                </button>
            </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const itemSelect = document.getElementById('item_id');
        const quantityInput = document.getElementById('quantity');
        const stockWarning = document.getElementById('stock-warning');
        const lowStockWarning = document.getElementById('low-stock-warning');

        function updateCalculations() {
            const selectedOption = itemSelect.options[itemSelect.selectedIndex];
            const quantity = parseInt(quantityInput.value) || 0;

            // Check stock availability
            if (selectedOption.value && quantity > 0) {
                const availableStock = parseInt(selectedOption.dataset.stock);
                const minStock = parseInt(selectedOption.dataset.minStock);
                const newStock = availableStock - quantity;

                // Show/hide warnings
                if (quantity > availableStock) {
                    stockWarning.classList.remove('hidden');
                    lowStockWarning.classList.add('hidden');
                } else if (newStock < minStock) {
                    stockWarning.classList.add('hidden');
                    lowStockWarning.classList.remove('hidden');
                } else {
                    stockWarning.classList.add('hidden');
                    lowStockWarning.classList.add('hidden');
                }
            } else {
                stockWarning.classList.add('hidden');
                lowStockWarning.classList.add('hidden');
            }
        }

        itemSelect.addEventListener('change', updateCalculations);
        quantityInput.addEventListener('input', updateCalculations);

        // Initial calculation
        updateCalculations();
    });
</script>
@endsection
