@extends('layouts.app')

@section('title', 'Tambah Barang')

@section('content')
<!-- Simple Breadcrumb -->
<div class="mb-6">
    <nav class="flex" aria-label="Breadcrumb">
        <ol class="flex items-center space-x-2">
            <li>
                <a href="{{ route('items.index') }}"
                    class="flex items-center text-primary hover:text-primary-700 transition-colors">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M20 7l-8-4-8 4m16 0l-8 4-8-4m16 0v10l-8 4-8-4V7" />
                    </svg>
                    Barang
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
        <h1 class="text-2xl font-bold text-gray-900">Tambah Barang Baru</h1>
        <p class="text-gray-600 mt-1">Lengkapi form di bawah untuk menambahkan barang ke inventory</p>
    </div>
</div>

<div class="bg-primary p-8 rounded-xl">
    <!-- Simple Form -->
    <div class="w-full mx-auto">
        <form action="{{ route('items.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Basic Information Card -->
                <div class="bg-white shadow rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Informasi Dasar</h3>

                    <div class="space-y-4">
                        <!-- Item Code -->
                        <div>
                            <label for="item_code" class="block text-sm font-medium text-gray-700 mb-2">
                                Kode Barang <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="item_code" id="item_code" value="{{ old('item_code') }}" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                                placeholder="Contoh: ITM001">
                            @error('item_code')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Item Name -->
                        <div>
                            <label for="item_name" class="block text-sm font-medium text-gray-700 mb-2">
                                Nama Barang <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="item_name" id="item_name" value="{{ old('item_name') }}" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                                placeholder="Masukkan nama barang">
                            @error('item_name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Category -->
                        <div>
                            <label for="category_id" class="block text-sm font-medium text-gray-700 mb-2">
                                Kategori <span class="text-red-500">*</span>
                            </label>
                            <select name="category_id" id="category_id" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                                <option value="">Pilih kategori</option>
                                @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ old('category_id',
                                    request('category_id'))==$category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                                @endforeach
                            </select>
                            @error('category_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Description -->
                        <div>
                            <label for="description"
                                class="block text-sm font-medium text-gray-700 mb-2">Deskripsi</label>
                            <textarea name="description" id="description" rows="3"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary resize-none"
                                placeholder="Deskripsi opsional untuk barang ini">{{ old('description') }}</textarea>
                            @error('description')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Stock & Pricing Card -->
                <div class="bg-white shadow rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Stok & Harga</h3>

                    <div class="space-y-4">
                        <!-- Initial Stock -->
                        <div>
                            <label for="stock" class="block text-sm font-medium text-gray-700 mb-2">
                                Stok Awal <span class="text-red-500">*</span>
                            </label>
                            <input type="number" name="stock" id="stock" value="{{ old('stock', 0) }}" min="0" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                                placeholder="0">
                            @error('stock')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Purchase Price -->
                        <div>
                            <label for="purchase_price" class="block text-sm font-medium text-gray-700 mb-2">
                                Harga Beli <span class="text-red-500">*</span>
                            </label>
                            <input type="number" name="purchase_price" id="purchase_price"
                                value="{{ old('purchase_price') }}" min="0" step="0.01" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                                placeholder="0">
                            @error('purchase_price')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Selling Price -->
                        <div>
                            <label for="selling_price" class="block text-sm font-medium text-gray-700 mb-2">
                                Harga Jual <span class="text-red-500">*</span>
                            </label>
                            <input type="number" name="selling_price" id="selling_price"
                                value="{{ old('selling_price') }}" min="0" step="0.01" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                                placeholder="0">
                            @error('selling_price')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Min Stock -->
                        <div>
                            <label for="min_stock" class="block text-sm font-medium text-gray-700 mb-2">Stok
                                Minimum</label>
                            <input type="number" name="min_stock" id="min_stock" value="{{ old('min_stock', 5) }}"
                                min="0"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                                placeholder="5">
                            @error('min_stock')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <!-- Image Upload -->
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Gambar Barang</h3>

                <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center">
                    <input type="file" name="image" id="image" class="hidden" accept="image/*">
                    <label for="image" class="cursor-pointer">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <div class="mt-4">
                            <p class="text-sm text-gray-600">Klik untuk memilih gambar atau drag and drop</p>
                            <p class="text-xs text-gray-500 mt-1">PNG, JPG, GIF hingga 2MB</p>
                        </div>
                    </label>
                </div>
                @error('image')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Info Box -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800">Tips</h3>
                        <div class="mt-2 text-sm text-blue-700">
                            <p>Pastikan data yang dimasukkan akurat. Stok awal akan dicatat sebagai transaksi barang
                                masuk.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex justify-end space-x-3">
                <a href="{{ route('items.index') }}"
                    class="bg-red-600 px-4 py-2 rounded-lg text-gray-50  font-medium transition-colors">
                    Batal
                </a>
                <button type="submit"
                    class="bg-blue-500 px-6 py-2 hover:bg-primary-700 text-white rounded-lg font-medium transition-colors">
                    Simpan Barang
                </button>
            </div>
        </form>
    </div>
</div>

@endsection
