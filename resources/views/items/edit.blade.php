@extends('layouts.app')

@section('title', 'Edit Item')

@section('content')
<div class="mb-8">
    <!-- Modern Breadcrumb -->
    <nav class="flex mb-6" aria-label="Breadcrumb">
        <ol class="flex items-center space-x-2">
            <li>
                <a href="{{ route('items.index') }}"
                    class="flex items-center text-indigo-600 hover:text-indigo-700 transition-colors">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M20 7l-8-4-8 4m16 0l-8 4-8-4m16 0v10l-8 4-8-4V7" />
                    </svg>
                    Items
                </a>
            </li>
            <li class="flex items-center">
                <svg class="w-4 h-4 text-gray-400 mx-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                        clip-rule="evenodd" />
                </svg>
                <span class="text-gray-500 font-medium">Edit {{ $item->item_name }}</span>
            </li>
        </ol>
    </nav>
</div>

<div class="bg-primary p-8 rounded-xl">
    <!-- Modern Form Container -->
    <div class="w-full mx-auto">
        <form action="{{ route('items.update', $item) }}" method="POST" enctype="multipart/form-data" class="space-y-8">
            @csrf
            @method('PUT')

            <div class="flex gap-x-4 flex-col lg:flex-row">

                <!-- Basic Information Card -->
                <div
                    class="w-full lg:w-1/2 bg-white rounded-2xl shadow-lg hover:shadow-xl transition-shadow duration-300 overflow-hidden">
                    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 px-6 py-4 border-b border-gray-100">
                        <div class="flex items-center">
                            <div class="bg-indigo-100 p-2 rounded-lg mr-3">
                                <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">Informasi Dasar</h3>
                                <p class="text-sm text-gray-600">Detail penting tentang item</p>
                            </div>
                        </div>
                    </div>

                    <div class="p-6">
                        <div class="">
                            <!-- Category -->
                            <div class="space-y-2">
                                <label for="category_id" class="block text-sm font-semibold text-gray-700">
                                    Kategori <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <select name="category_id" id="category_id" required
                                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-200 bg-gray-50 focus:bg-white appearance-none">
                                        <option value="">Pilih kategori</option>
                                        @foreach($categories as $category)
                                        <option value="{{ $category->id }}" {{ old('category_id', $item->category_id) ==
                                            $category->id ? 'selected' : '' }}>
                                            {{ $category->name }}
                                        </option>
                                        @endforeach
                                    </select>
                                    <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </div>
                                </div>
                                @error('category_id')
                                <p class="text-sm text-red-600 flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    {{ $message }}
                                </p>
                                @enderror
                            </div>
                        </div>

                        <!-- Item Name -->
                        <div class="mt-6 space-y-2">
                            <label for="item_name" class="block text-sm font-semibold text-gray-700">
                                Nama Item <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <input type="text" name="item_name" id="item_name"
                                    value="{{ old('item_name', $item->item_name) }}" required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-200 bg-gray-50 focus:bg-white"
                                    placeholder="Masukkan nama item">
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M20 7l-8-4-8 4m16 0l-8 4-8-4m16 0v10l-8 4-8-4V7" />
                                    </svg>
                                </div>
                            </div>
                            @error('item_name')
                            <p class="text-sm text-red-600 flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                        clip-rule="evenodd" />
                                </svg>
                                {{ $message }}
                            </p>
                            @enderror
                        </div>

                        <!-- Description -->
                        <div class="mt-6 space-y-2">
                            <label for="description" class="block text-sm font-semibold text-gray-700">Deskripsi</label>
                            <textarea name="description" id="description" rows="4"
                                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-200 bg-gray-50 focus:bg-white resize-none"
                                placeholder="Deskripsi opsional untuk item ini...">{{ old('description', $item->description) }}</textarea>
                            @error('description')
                            <p class="text-sm text-red-600 flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                        clip-rule="evenodd" />
                                </svg>
                                {{ $message }}
                            </p>
                            @enderror
                        </div>
                    </div>
                    <div
                        class="bg-white rounded-2xl shadow-lg hover:shadow-xl transition-shadow duration-300 overflow-hidden">
                        <div class="p-6">
                            <!-- Current Image Display -->
                            @if($item->image_path)
                            <div class="mb-6 p-4 bg-gray-50 rounded-xl">
                                <div class="flex items-center space-x-4">
                                    <img src="{{ Storage::url($item->image_path) }}" alt="{{ $item->item_name }}"
                                        class="w-20 h-20 object-cover rounded-lg shadow-sm border border-gray-200">
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">Gambar Saat Ini</p>
                                        <p class="text-xs text-gray-500 mt-1">Upload gambar baru untuk mengganti</p>
                                    </div>
                                </div>
                            </div>
                            @endif

                            <div class="border-2 border-dashed border-gray-300 rounded-2xl p-8 text-center hover:border-indigo-400 transition-colors duration-200"
                                id="drop-zone">
                                <!-- Default upload area -->
                                <div id="upload-area">
                                    <div class="space-y-4">
                                        <div class="flex justify-center">
                                            <div class="bg-gray-100 p-4 rounded-full">
                                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                                </svg>
                                            </div>
                                        </div>
                                        <div>
                                            <label for="image" class="cursor-pointer">
                                                <span
                                                    class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white px-6 py-2 rounded-full text-sm font-medium hover:from-indigo-700 hover:to-purple-700 transition-all duration-200 inline-block">
                                                    {{ $item->image_path ? 'Ganti Gambar' : 'Pilih File' }}
                                                </span>
                                                <input id="image" name="image" type="file" class="hidden"
                                                    accept="image/*" onchange="handleImageSelect(this)">
                                            </label>
                                            <p class="mt-2 text-sm text-gray-500">atau drag and drop file di sini</p>
                                        </div>
                                        <p class="text-xs text-gray-400">PNG, JPG, GIF hingga 2MB</p>
                                    </div>
                                </div>

                                <!-- Image preview area -->
                                <div id="preview-area" class="hidden">
                                    <div class="relative inline-block">
                                        <img id="image-preview" src="" alt="Preview"
                                            class="max-w-xs max-h-48 rounded-lg shadow-md">
                                        <button type="button" onclick="removeImage()"
                                            class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full p-1 hover:bg-red-600 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M6 18L18 6M6 6l12 12"></path>
                                            </svg>
                                        </button>
                                    </div>
                                    <div class="mt-3">
                                        <p id="file-name" class="text-sm text-gray-600"></p>
                                        <p id="file-size" class="text-xs text-gray-500"></p>
                                    </div>
                                </div>
                            </div>

                            @error('image')
                            <p class="mt-3 text-sm text-red-600 flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                        clip-rule="evenodd" />
                                </svg>
                                {{ $message }}
                            </p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Stock & Pricing Card -->
                <div
                    class="w-full h-fit lg:w-1/2 bg-white rounded-2xl shadow-lg hover:shadow-xl transition-shadow duration-300 overflow-hidden">
                    <div class="bg-gradient-to-r from-green-50 to-emerald-50 px-6 py-4 border-b border-gray-100">
                        <div class="flex items-center">
                            <div class="bg-green-100 p-2 rounded-lg mr-3">
                                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">Stok & Harga</h3>
                                <p class="text-sm text-gray-600">Informasi stok dan harga item</p>
                            </div>
                        </div>
                    </div>

                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Current Stock -->
                            <div class="space-y-2">
                                <label for="stock" class="block text-sm font-semibold text-gray-700">
                                    Stok Saat Ini <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <input type="number" name="stock" id="stock"
                                        value="{{ old('stock', $item->stock) }}" min="0" required
                                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-200 bg-gray-50 focus:bg-white"
                                        placeholder="0">

                                </div>
                                <p class="text-xs text-gray-500">Stok akan diupdate ke nilai ini</p>
                                @error('stock')
                                <p class="text-sm text-red-600 flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    {{ $message }}
                                </p>
                                @enderror
                            </div>

                            <!-- Minimum Stock -->
                            <div class="space-y-2">
                                <label for="minimum_stock" class="block text-sm font-semibold text-gray-700">
                                    Stok Minimum <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <input type="number" name="minimum_stock" id="minimum_stock"
                                        value="{{ old('minimum_stock', $item->minimum_stock ?? 5) }}" min="0" required
                                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-200 bg-gray-50 focus:bg-white"
                                        placeholder="5">

                                </div>
                                <p class="text-xs text-gray-500">Batas minimum stok untuk peringatan understock</p>
                                @error('minimum_stock')
                                <p class="text-sm text-red-600 flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    {{ $message }}
                                </p>
                                @enderror
                            </div>

                            <!-- Purchase Price -->
                            {{-- <div class="space-y-2">
                                <label for="purchase_price" class="block text-sm font-semibold text-gray-700">
                                    Harga Beli <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <span class="text-gray-500 font-medium">Rp</span>
                                    </div>
                                    <input type="number" name="purchase_price" id="purchase_price"
                                        value="{{ old('purchase_price', $item->purchase_price) }}" step="1000" min="0"
                                        required
                                        class="w-full pl-12 pr-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-200 bg-gray-50 focus:bg-white"
                                        placeholder="0">
                                </div>
                                @error('purchase_price')
                                <p class="text-sm text-red-600 flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    {{ $message }}
                                </p>
                                @enderror
                            </div> --}}

                            <!-- Selling Price -->
                            <div class="space-y-2">
                                <label for="selling_price" class="block text-sm font-semibold text-gray-700">
                                    Harga Jual <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <span class="text-gray-500 font-medium">Rp</span>
                                    </div>
                                    <input type="number" name="selling_price" id="selling_price"
                                        value="{{ old('selling_price', $item->selling_price) }}" step="1000" min="0"
                                        required
                                        class="w-full pl-12 pr-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-200 bg-gray-50 focus:bg-white"
                                        placeholder="0">
                                </div>
                                @error('selling_price')
                                <p class="text-sm text-red-600 flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    {{ $message }}
                                </p>
                                @enderror
                            </div>
                        </div>

                        <!-- Profit Margin Display -->

                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-col sm:flex-row gap-4 justify-end pt-6">
                <a href="{{ route('items.index') }}"
                    class="bg-red-600 text-white flex items-center justify-center px-6 py-3 rounded-xl transition-all duration-200 font-medium">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    Batal
                </a>
                <button type="submit"
                    class="bg-blue-500 flex items-center justify-center px-8 py-3 text-white rounded-xl hover:from-yellow-600 transition-all duration-200 font-medium shadow-lg hover:shadow-xl">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                    </svg>
                    Update Item
                </button>
            </div>
        </form>
    </div>

</div>


<!-- Interactive JavaScript -->
<script>
    // Image upload handling
    function handleImageSelect(input) {
        const file = input.files[0];
        if (!file) return;

        console.log('File selected:', file.name, file.size, file.type);

        // Validate file type
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        if (!allowedTypes.includes(file.type)) {
            alert('Tipe file tidak diizinkan. Gunakan JPG, PNG, atau GIF.');
            input.value = '';
            return;
        }

        // Validate file size (2MB = 2 * 1024 * 1024 bytes)
        const maxSize = 2 * 1024 * 1024;
        if (file.size > maxSize) {
            alert('Ukuran file terlalu besar. Maksimal 2MB.');
            input.value = '';
            return;
        }

        // Show preview
        showImagePreview(file);
    }

    function showImagePreview(file) {
        const reader = new FileReader();

        reader.onload = function(e) {
            document.getElementById('upload-area').classList.add('hidden');
            document.getElementById('preview-area').classList.remove('hidden');
            document.getElementById('image-preview').src = e.target.result;
            document.getElementById('file-name').textContent = file.name;
            document.getElementById('file-size').textContent = formatFileSize(file.size);
        };

        reader.readAsDataURL(file);
    }

    function removeImage() {
        document.getElementById('image').value = '';
        document.getElementById('upload-area').classList.remove('hidden');
        document.getElementById('preview-area').classList.add('hidden');
        document.getElementById('image-preview').src = '';
        console.log('Image removed');
    }

    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';

        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));

        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Profit calculation
        const purchaseInput = document.getElementById('purchase_price');
        const sellingInput = document.getElementById('selling_price');
        const profitAmount = document.getElementById('profit-amount');
        const profitPercentage = document.getElementById('profit-percentage');

        function calculateProfit() {
            if (!purchaseInput || !sellingInput) return;

            const purchase = parseFloat(purchaseInput.value) || 0;
            const selling = parseFloat(sellingInput.value) || 0;
            const profit = selling - purchase;
            const percentage = purchase > 0 ? ((profit / purchase) * 100) : 0;

            if (profitAmount) {
                profitAmount.textContent = 'Rp ' + profit.toLocaleString('id-ID');
            }
            if (profitPercentage) {
                profitPercentage.textContent = percentage.toFixed(1) + '%';
            }

            // Color coding
            if (profitAmount) {
                if (profit > 0) {
                    profitAmount.className = 'text-lg font-bold text-green-600';
                } else if (profit < 0) {
                    profitAmount.className = 'text-lg font-bold text-red-600';
                } else {
                    profitAmount.className = 'text-lg font-bold text-gray-600';
                }
            }
        }

        if (purchaseInput && sellingInput) {
            purchaseInput.addEventListener('input', calculateProfit);
            sellingInput.addEventListener('input', calculateProfit);
        }

        // Drag and drop functionality
        const dropZone = document.getElementById('drop-zone');
        const fileInput = document.getElementById('image');

        // Prevent default drag behaviors
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, preventDefaults, false);
            document.body.addEventListener(eventName, preventDefaults, false);
        });

        // Highlight drop zone when item is dragged over it
        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, () => {
                dropZone.classList.add('border-blue-400', 'bg-blue-50');
            }, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, () => {
                dropZone.classList.remove('border-blue-400', 'bg-blue-50');
            }, false);
        });

        // Handle dropped files
        dropZone.addEventListener('drop', handleDrop, false);

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;

            if (files.length > 0) {
                fileInput.files = files;
                handleImageSelect(fileInput);
            }
        }

        // Form validation enhancement
        const form = document.querySelector('form');
        if (form) {
            form.addEventListener('submit', function(e) {
                const requiredFields = form.querySelectorAll('[required]');
                let isValid = true;

                requiredFields.forEach(field => {
                    if (!field.value.trim()) {
                        isValid = false;
                        field.classList.add('border-red-500');
                        field.addEventListener('input', function() {
                            this.classList.remove('border-red-500');
                        });
                    }
                });

                if (!isValid) {
                    e.preventDefault();
                    alert('Mohon lengkapi semua field yang wajib diisi');
                }
            });
        }
    });
</script>
@endsection
