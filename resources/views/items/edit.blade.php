@extends('layouts.app')

@section('title', 'Edit Item')

@section('content')
<div class="p-6">
    <!-- Breadcrumb -->
    <div class="mb-8">
        <nav class="flex mb-4" aria-label="Breadcrumb">
            <ol class="flex items-center space-x-4">
                <li>
                    <a href="{{ route('items.index') }}"
                        class="text-gray-400 hover:text-gray-500 transition-colors">Items</a>
                </li>
                <li>
                    <div class="flex items-center">
                        <svg class="flex-shrink-0 h-5 w-5 text-gray-300" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                                clip-rule="evenodd" />
                        </svg>
                        <span class="ml-4 text-sm font-medium text-gray-500">Edit {{ $item->item_name }}</span>
                    </div>
                </li>
            </ol>
        </nav>

        <div class="border-b border-gray-200 pb-6">
            <h1 class="text-3xl font-bold text-gray-900">Edit Item</h1>
            <p class="mt-2 text-gray-600">Update item information</p>
        </div>
    </div>

    <!-- Form Container -->
    <div class="max-w-5xl mx-auto">
        <form action="{{ route('items.update', $item) }}" method="POST" enctype="multipart/form-data" class="space-y-8">
            @csrf
            @method('PUT')

            <!-- Basic Information Section -->
            <div class="bg-white shadow-sm rounded-lg border border-gray-200">
                <div class="px-6 py-5 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Basic Information</h3>
                    <p class="mt-1 text-sm text-gray-500">Essential details about the item</p>
                </div>

                <div class="px-6 py-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Item Code -->
                        <div>
                            <label for="item_code" class="block text-sm font-medium text-gray-700 mb-2">
                                Item Code <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="item_code" id="item_code"
                                value="{{ old('item_code', $item->item_code) }}" required
                                placeholder="Enter unique item code"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                            @error('item_code')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Category -->
                        <div>
                            <label for="category_id" class="block text-sm font-medium text-gray-700 mb-2">
                                Category <span class="text-red-500">*</span>
                            </label>
                            <select name="category_id" id="category_id" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                                <option value="">Select a category</option>
                                @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ old('category_id', $item->category_id) ==
                                    $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                                @endforeach
                            </select>
                            @error('category_id')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Item Name -->
                        <div class="md:col-span-2">
                            <label for="item_name" class="block text-sm font-medium text-gray-700 mb-2">
                                Item Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="item_name" id="item_name"
                                value="{{ old('item_name', $item->item_name) }}" required placeholder="Enter item name"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                            @error('item_name')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Description -->
                        <div class="md:col-span-2">
                            <label for="description"
                                class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                            <textarea name="description" id="description" rows="4"
                                placeholder="Optional description for the item"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors resize-none">{{ old('description', $item->description) }}</textarea>
                            @error('description')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stock & Pricing Section -->
            <div class="bg-white shadow-sm rounded-lg border border-gray-200">
                <div class="px-6 py-5 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Stock & Pricing</h3>
                    <p class="mt-1 text-sm text-gray-500">Stock quantity and pricing information</p>
                </div>

                <div class="px-6 py-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Current Stock -->
                        <div>
                            <label for="stock" class="block text-sm font-medium text-gray-700 mb-2">
                                Current Stock <span class="text-red-500">*</span>
                            </label>
                            <input type="number" name="stock" id="stock" value="{{ old('stock', $item->stock) }}"
                                min="0" required placeholder="0"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                            <p class="mt-1 text-xs text-gray-500">Current stock will be adjusted to this value</p>
                            @error('stock')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Minimum Stock -->
                        <div>
                            <label for="minimum_stock" class="block text-sm font-medium text-gray-700 mb-2">
                                Minimum Stock <span class="text-red-500">*</span>
                            </label>
                            <input type="number" name="minimum_stock" id="minimum_stock"
                                value="{{ old('minimum_stock', $item->minimum_stock) }}" min="0" required
                                placeholder="10"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                            <p class="mt-1 text-xs text-gray-500">Alert when stock goes below this level</p>
                            @error('minimum_stock')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Purchase Price -->
                        <div>
                            <label for="purchase_price" class="block text-sm font-medium text-gray-700 mb-2">
                                Purchase Price <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="text-gray-500 text-sm">$</span>
                                </div>
                                <input type="number" name="purchase_price" id="purchase_price"
                                    value="{{ old('purchase_price', $item->purchase_price) }}" step="0.01" min="0"
                                    required placeholder="0.00"
                                    class="w-full pl-8 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                            </div>
                            @error('purchase_price')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Selling Price -->
                        <div class="md:col-span-2">
                            <label for="selling_price" class="block text-sm font-medium text-gray-700 mb-2">
                                Selling Price <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="text-gray-500 text-sm">$</span>
                                </div>
                                <input type="number" name="selling_price" id="selling_price"
                                    value="{{ old('selling_price', $item->selling_price) }}" step="0.01" min="0"
                                    required placeholder="0.00"
                                    class="w-full pl-8 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                            </div>
                            @error('selling_price')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
    </div>

    <!-- Item Image Section -->
    <div class="bg-white shadow-sm rounded-lg border border-gray-200">
        <div class="px-6 py-5 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Item Image</h3>
            <p class="mt-1 text-sm text-gray-500">Update the image for the item (optional)</p>
            @if($item->image_path)
            <div class="mt-4">
                <img src="{{ Storage::url($item->image_path) }}" alt="{{ $item->item_name }}"
                    class="h-24 w-24 object-cover rounded-lg shadow-sm">
                <p class="mt-2 text-xs text-gray-500">Current image</p>
            </div>
            @endif
        </div>

        <div class="px-6 py-6">
            <div
                class="flex justify-center px-6 py-10 border-2 border-gray-300 border-dashed rounded-lg hover:border-gray-400 transition-colors">
                <div class="text-center">
                    <svg class="mx-auto h-16 w-16 text-gray-400 mb-4" stroke="currentColor" fill="none"
                        viewBox="0 0 48 48">
                        <path
                            d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    <div class="flex text-sm text-gray-600 justify-center">
                        <label for="image"
                            class="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-blue-500">
                            <span class="px-2">Upload a new file</span>
                            <input id="image" name="image" type="file" class="sr-only" accept="image/*">
                        </label>
                        <p class="pl-1">or drag and drop</p>
                    </div>
                    <p class="text-xs text-gray-500 mt-2">PNG, JPG, GIF up to 2MB</p>
                </div>
            </div>
            @error('image')
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="flex justify-end space-x-4 pt-6 border-t border-gray-200">
        <a href="{{ route('items.index') }}"
            class="px-6 py-3 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
            Cancel
        </a>
        <button type="submit"
            class="px-6 py-3 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
            <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
            </svg>
            Update Item
        </button>
    </div>
    </form>
</div>
@endsection
