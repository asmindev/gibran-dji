@extends('layouts.app')

@section('title', 'Tambah Barang Keluar')

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
                <span class="text-gray-500 font-medium">Tambah Baru</span>
            </li>
        </ol>
    </nav>

    <div class="mt-4">
        <h1 class="text-2xl font-bold text-gray-900">Tambah Barang Keluar</h1>
        <p class="text-gray-600 mt-1">Catat barang yang keluar dari inventory</p>
    </div>
</div>

<!-- Simple Form -->
<div class="max-w-2xl">
    <form action="{{ route('outgoing_items.store') }}" method="POST" class="space-y-6">
        @csrf

        <div class="bg-white shadow rounded-lg p-6">
            <div class="space-y-6">
                <!-- Tanggal Keluar -->
                <div>
                    <label for="outgoing_date" class="block text-sm font-medium text-gray-700 mb-2">
                        Tanggal Keluar <span class="text-red-500">*</span>
                    </label>
                    <input type="date" name="outgoing_date" id="outgoing_date"
                        value="{{ old('outgoing_date', date('Y-m-d')) }}" required
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
                        <option value="{{ $item->id }}" data-stock="{{ $item->stock }}" {{ old('item_id')==$item->id ?
                            'selected' : '' }}>
                            {{ $item->item_name }} ({{ $item->item_code }}) - Stok: {{ $item->stock }}
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
                    <input type="number" name="quantity" id="quantity" value="{{ old('quantity') }}" min="1" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                        placeholder="Masukkan jumlah barang">
                    <p id="stock-warning" class="mt-1 text-sm text-red-600 hidden">Stok tidak mencukupi!</p>
                    @error('quantity')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Penerima -->
                <div>
                    <label for="recipient" class="block text-sm font-medium text-gray-700 mb-2">
                        Penerima <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="recipient" id="recipient" value="{{ old('recipient') }}" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                        placeholder="Nama penerima">
                    @error('recipient')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Keterangan -->
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Keterangan</label>
                    <textarea name="description" id="description" rows="3"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 resize-none"
                        placeholder="Catatan opsional tentang barang keluar ini">{{ old('description') }}</textarea>
                    @error('description')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Warning Box -->
        <div class="bg-primary-50 border border-primary-200 rounded-lg p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-primary-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                            clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-primary-800">Stok akan diperbarui otomatis</h3>
                    <div class="mt-2 text-sm text-primary-700">
                        <p>Ketika Anda mencatat barang keluar ini, stok barang akan berkurang secara otomatis.
                            Pastikan stok mencukupi sebelum melanjutkan.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex justify-end space-x-3">
            <a href="{{ route('outgoing_items.index') }}"
                class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 font-medium transition-colors">
                Batal
            </a>
            <button type="submit" id="submit-btn"
                class="px-6 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg font-medium transition-colors">
                Simpan Barang Keluar
            </button>
        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const itemSelect = document.getElementById('item_id');
        const quantityInput = document.getElementById('quantity');
        const stockWarning = document.getElementById('stock-warning');
        const submitBtn = document.getElementById('submit-btn');

        function checkStock() {
            const selectedOption = itemSelect.options[itemSelect.selectedIndex];
            const availableStock = selectedOption.getAttribute('data-stock') || 0;
            const requestedQuantity = parseInt(quantityInput.value) || 0;

            if (requestedQuantity > availableStock && availableStock > 0) {
                stockWarning.classList.remove('hidden');
                stockWarning.textContent = `Stok tidak mencukupi! Hanya tersedia ${availableStock} unit.`;
                submitBtn.disabled = true;
                submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
            } else {
                stockWarning.classList.add('hidden');
                submitBtn.disabled = false;
                submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
            }
        }

        itemSelect.addEventListener('change', checkStock);
        quantityInput.addEventListener('input', checkStock);
    });
</script>
@endsection
