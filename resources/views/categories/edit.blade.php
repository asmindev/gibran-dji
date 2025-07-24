@extends('layouts.app')

@section('title', 'Edit Kategori')

@section('content')
<!-- Simple Breadcrumb -->
<div class="mb-6">
    <nav class="flex" aria-label="Breadcrumb">
        <ol class="flex items-center space-x-2">
            <li>
                <a href="{{ route('categories.index') }}"
                    class="flex items-center text-primary hover:text-primary-700 transition-colors">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                    Kategori
                </a>
            </li>
            <li class="flex items-center">
                <svg class="w-4 h-4 text-gray-400 mx-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                        clip-rule="evenodd" />
                </svg>
                <span class="text-gray-500 font-medium">Edit {{ $category->name }}</span>
            </li>
        </ol>
    </nav>

    <div class="mt-4">
        <h1 class="text-2xl font-bold text-gray-900">Edit Kategori</h1>
        <p class="text-gray-600 mt-1">Update informasi kategori</p>
    </div>
</div>

<!-- Simple Form -->
<div class="max-w-2xl">
    <form action="{{ route('categories.update', $category) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="bg-white shadow rounded-lg p-6">
            <div class="space-y-6">
                <!-- Nama Kategori -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                        Nama Kategori <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" id="name" value="{{ old('name', $category->name) }}" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary @error('name') border-red-300 @enderror"
                        placeholder="Masukkan nama kategori">
                    @error('name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Deskripsi -->
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Deskripsi</label>
                    <textarea name="description" id="description" rows="3"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary resize-none @error('description') border-red-300 @enderror"
                        placeholder="Deskripsi opsional untuk kategori">{{ old('description', $category->description) }}</textarea>
                    @error('description')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Warning Box -->
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                            clip-rule="evenodd"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-yellow-800">Pemberitahuan Penting</h3>
                    <p class="text-sm text-yellow-700 mt-1">
                        Perubahan pada kategori ini akan mempengaruhi semua barang yang terkait dengan kategori ini.
                    </p>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex justify-end space-x-3">
            <a href="{{ route('categories.index') }}"
                class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 font-medium transition-colors">
                Batal
            </a>
            <button type="submit"
                class="px-6 py-2 bg-primary hover:bg-primary-700 text-white rounded-lg font-medium transition-colors">
                Update Kategori
            </button>
        </div>
    </form>
</div>
@endsection
