@extends('layouts.app')

@section('title', 'Rekomendasi Asosiasi Produk')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Rekomendasi Asosiasi Produk</h1>
            <p class="text-gray-600 mt-2">Berdasarkan analisis Apriori Algorithm</p>
        </div>
        <div class="flex space-x-2">
            <a href="{{ route('analysis.index') }}"
                class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg">
                Kembali ke Dashboard
            </a>
            <a href="{{ route('analysis.predictions') }}"
                class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg">
                Lihat Prediksi
            </a>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h3 class="text-lg font-semibold mb-4">Filter Rekomendasi</h3>
        <form method="GET" action="{{ route('analysis.recommendations') }}"
            class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label for="search" class="block text-sm font-medium text-gray-700 mb-2">
                    Cari Nama Produk
                </label>
                <input type="text" id="search" name="search" value="{{ request('search') }}"
                    placeholder="Masukkan nama produk..."
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label for="min_confidence" class="block text-sm font-medium text-gray-700 mb-2">
                    Minimum Confidence (%)
                </label>
                <input type="number" id="min_confidence" name="min_confidence" min="0" max="100" step="5"
                    value="{{ request('min_confidence') }}"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label for="min_lift" class="block text-sm font-medium text-gray-700 mb-2">
                    Minimum Lift
                </label>
                <input type="number" id="min_lift" name="min_lift" min="1" step="0.5" value="{{ request('min_lift') }}"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="flex items-end space-x-2">
                <button type="submit" class="flex-1 bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md">
                    Filter
                </button>
                <a href="{{ route('analysis.recommendations') }}"
                    class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md">
                    Reset
                </a>
            </div>
        </form>

        <!-- Active Filters Display -->
        @if(request()->hasAny(['search', 'min_confidence', 'min_lift']))
        <div class="mt-4 p-3 bg-blue-50 border border-blue-200 rounded-md">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <span class="text-sm font-medium text-blue-800">Filter Aktif:</span>
                    @if(request('search'))
                    <span
                        class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                        Nama: "{{ request('search') }}"
                    </span>
                    @endif
                    @if(request('min_confidence'))
                    <span
                        class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                        Confidence ≥ {{ request('min_confidence') }}%
                    </span>
                    @endif
                    @if(request('min_lift'))
                    <span
                        class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                        Lift ≥ {{ request('min_lift') }}
                    </span>
                    @endif
                </div>
                <a href="{{ route('analysis.recommendations') }}" class="text-sm text-blue-600 hover:text-blue-800">
                    Hapus semua filter
                </a>
            </div>
        </div>
        @endif
    </div>

    <!-- Summary Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow-md p-4">
            <div class="text-sm text-gray-500">Total Rekomendasi</div>
            <div class="text-2xl font-bold text-blue-600">{{ $recommendations->total() }}</div>
        </div>
        <div class="bg-white rounded-lg shadow-md p-4">
            <div class="text-sm text-gray-500">Confidence Tinggi (>80%)</div>
            <div class="text-2xl font-bold text-green-600">
                {{ $recommendations->where('confidence', '>', 0.8)->count() }}
            </div>
        </div>
        <div class="bg-white rounded-lg shadow-md p-4">
            <div class="text-sm text-gray-500">Lift Tinggi (>10)</div>
            <div class="text-2xl font-bold text-purple-600">
                {{ $recommendations->where('lift', '>', 10)->count() }}
            </div>
        </div>
        <div class="bg-white rounded-lg shadow-md p-4">
            <div class="text-sm text-gray-500">Avg Confidence</div>
            <div class="text-2xl font-bold text-orange-600">
                {{ number_format($recommendations->avg('confidence') * 100, 1) }}%
            </div>
        </div>
    </div>

    <!-- Recommendations Table -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold">Daftar Rekomendasi</h3>
            <p class="text-sm text-gray-600 mt-1">
                Produk yang sering dibeli bersamaan berdasarkan data transaksi
            </p>
        </div>

        @if($recommendations->count() > 0)
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Jika Membeli
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Maka Akan Membeli
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Support
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Confidence
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Lift
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Kekuatan
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($recommendations as $recommendation)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">
                                {{ $recommendation->antecedent_items_names }}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">
                                {{ $recommendation->consequent_items_names }}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">
                                {{ number_format($recommendation->support * 100, 2) }}%
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                            @if($recommendation->confidence > 0.9) bg-green-100 text-green-800
                                            @elseif($recommendation->confidence > 0.8) bg-blue-100 text-blue-800
                                            @elseif($recommendation->confidence > 0.7) bg-yellow-100 text-yellow-800
                                            @else bg-red-100 text-red-800
                                            @endif">
                                    {{ number_format($recommendation->confidence * 100, 1) }}%
                                </span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">
                                {{ number_format($recommendation->lift, 2) }}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                            $strength = 'Lemah';
                            $strengthClass = 'bg-red-100 text-red-800';

                            if ($recommendation->confidence > 0.8 && $recommendation->lift > 5) {
                            $strength = 'Sangat Kuat';
                            $strengthClass = 'bg-green-100 text-green-800';
                            } elseif ($recommendation->confidence > 0.7 && $recommendation->lift > 3) {
                            $strength = 'Kuat';
                            $strengthClass = 'bg-blue-100 text-blue-800';
                            } elseif ($recommendation->confidence > 0.6 && $recommendation->lift > 2) {
                            $strength = 'Sedang';
                            $strengthClass = 'bg-yellow-100 text-yellow-800';
                            }
                            @endphp
                            <span
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $strengthClass }}">
                                {{ $strength }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $recommendations->links() }}
        </div>
        @else
        <div class="px-6 py-8 text-center">
            <div class="text-gray-500">
                <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                    </path>
                </svg>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Tidak Ada Rekomendasi</h3>
                <p class="text-gray-500">
                    Tidak ditemukan rekomendasi dengan filter yang dipilih.
                    Coba sesuaikan filter atau jalankan analisis ulang.
                </p>
            </div>
        </div>
        @endif
    </div>

    <!-- Info Section -->
    <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
        <h4 class="text-sm font-medium text-blue-800 mb-2">💡 Cara Membaca Rekomendasi:</h4>
        <ul class="text-sm text-blue-700 space-y-1">
            <li><strong>Support:</strong> Seberapa sering kombinasi produk muncul dalam transaksi</li>
            <li><strong>Confidence:</strong> Kemungkinan customer membeli produk B setelah membeli produk A</li>
            <li><strong>Lift:</strong> Seberapa kuat hubungan antara produk A dan B (>1 = positive association)</li>
            <li><strong>Kekuatan:</strong> Klasifikasi overall berdasarkan confidence dan lift</li>
        </ul>
    </div>
</div>
@endsection
