@extends('layouts.app')

@section('title', 'Prediksi Permintaan Produk')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Prediksi Permintaan Produk</h1>
            <p class="text-gray-600 mt-2">Berdasarkan analisis Random Forest Algorithm</p>
        </div>
        <div class="flex space-x-2">
            <a href="{{ route('analysis.index') }}"
                class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg">
                Kembali ke Dashboard
            </a>
            <a href="{{ route('analysis.recommendations') }}"
                class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg">
                Lihat Rekomendasi
            </a>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h3 class="text-lg font-semibold mb-4">Filter Prediksi</h3>
        <form method="GET" action="{{ route('analysis.predictions') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label for="search" class="block text-sm font-medium text-gray-700 mb-2">
                    Cari Nama Produk
                </label>
                <input type="text" id="search" name="search" value="{{ request('search') }}"
                    placeholder="Masukkan nama produk..."
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
            </div>
            <div>
                <label for="min_confidence" class="block text-sm font-medium text-gray-700 mb-2">
                    Minimum Confidence (%)
                </label>
                <input type="number" id="min_confidence" name="min_confidence" min="0" max="100" step="5"
                    value="{{ request('min_confidence', 50) }}"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
            </div>
            <div>
                <label for="min_demand" class="block text-sm font-medium text-gray-700 mb-2">
                    Minimum Predicted Demand
                </label>
                <input type="number" id="min_demand" name="min_demand" min="1" value="{{ request('min_demand', 1) }}"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
            </div>
            <div class="flex items-end space-x-2">
                <button type="submit" class="flex-1 bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-md">
                    Filter
                </button>
                <a href="{{ route('analysis.predictions') }}"
                    class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md">
                    Reset
                </a>
            </div>
        </form>

        <!-- Active Filters Display -->
        @if(request()->hasAny(['search', 'min_confidence', 'min_demand']))
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
                    @if(request('min_demand'))
                    <span
                        class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                        Demand ≥ {{ request('min_demand') }}
                    </span>
                    @endif
                </div>
                <a href="{{ route('analysis.predictions') }}" class="text-sm text-blue-600 hover:text-blue-800">
                    Hapus semua filter
                </a>
            </div>
        </div>
        @endif
    </div>

    <!-- Summary Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow-md p-4">
            <div class="text-sm text-gray-500">Total Prediksi</div>
            <div class="text-2xl font-bold text-green-600">{{ $predictions->total() }}</div>
        </div>
        <div class="bg-white rounded-lg shadow-md p-4">
            <div class="text-sm text-gray-500">Confidence Tinggi (>70%)</div>
            <div class="text-2xl font-bold text-blue-600">
                {{ $predictions->where('prediction_confidence', '>', 70)->count() }}
            </div>
        </div>
        <div class="bg-white rounded-lg shadow-md p-4">
            <div class="text-sm text-gray-500">Total Demand Forecast</div>
            <div class="text-2xl font-bold text-purple-600">
                {{ $predictions->sum('predicted_demand') }}
            </div>
        </div>
        <div class="bg-white rounded-lg shadow-md p-4">
            <div class="text-sm text-gray-500">Avg Confidence</div>
            <div class="text-2xl font-bold text-orange-600">
                {{ number_format($predictions->avg('prediction_confidence'), 1) }}%
            </div>
        </div>
    </div>

    <!-- Predictions Table -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold">Daftar Prediksi Permintaan</h3>
            <p class="text-sm text-gray-600 mt-1">
                Prediksi permintaan untuk 30 hari ke depan berdasarkan data historis
            </p>
        </div>

        @if($predictions->count() > 0)
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Nama Produk
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Prediksi Demand
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Confidence
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Periode
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Reliability
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($predictions as $prediction)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-gray-900">
                                {{ $prediction->item->item_name }}
                            </div>
                            <div class="text-xs text-gray-500">
                                {{ $prediction->item_code }}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-lg font-bold text-gray-900">
                                {{ $prediction->predicted_demand }}
                                <span class="text-sm font-normal text-gray-500">units</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                            @if($prediction->prediction_confidence > 80) bg-green-100 text-green-800
                                            @elseif($prediction->prediction_confidence > 70) bg-blue-100 text-blue-800
                                            @elseif($prediction->prediction_confidence > 60) bg-yellow-100 text-yellow-800
                                            @elseif($prediction->prediction_confidence > 50) bg-orange-100 text-orange-800
                                            @else bg-red-100 text-red-800
                                            @endif">
                                    {{ number_format($prediction->prediction_confidence, 1) }}%
                                </span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">
                                {{ \Carbon\Carbon::parse($prediction->prediction_period_start)->format('d/m/Y') }} -
                                {{ \Carbon\Carbon::parse($prediction->prediction_period_end)->format('d/m/Y') }}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                            $reliability = 'Very Low';
                            $reliabilityClass = 'bg-red-100 text-red-800';

                            if ($prediction->prediction_confidence > 80) {
                            $reliability = 'Very High';
                            $reliabilityClass = 'bg-green-100 text-green-800';
                            } elseif ($prediction->prediction_confidence > 70) {
                            $reliability = 'High';
                            $reliabilityClass = 'bg-blue-100 text-blue-800';
                            } elseif ($prediction->prediction_confidence > 60) {
                            $reliability = 'Medium';
                            $reliabilityClass = 'bg-yellow-100 text-yellow-800';
                            } elseif ($prediction->prediction_confidence > 50) {
                            $reliability = 'Low';
                            $reliabilityClass = 'bg-orange-100 text-orange-800';
                            }
                            @endphp
                            <span
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $reliabilityClass }}">
                                {{ $reliability }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $predictions->links() }}
        </div>
        @else
        <div class="px-6 py-8 text-center">
            <div class="text-gray-500">
                <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z">
                    </path>
                </svg>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Tidak Ada Prediksi</h3>
                <p class="text-gray-500">
                    Tidak ditemukan prediksi dengan filter yang dipilih.
                    Coba sesuaikan filter atau jalankan analisis ulang.
                </p>
            </div>
        </div>
        @endif
    </div>

    <!-- Charts Section -->
    @if($predictions->count() > 0)
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
        <!-- Top Products Chart -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold mb-4">Top 10 Prediksi Demand Tertinggi</h3>
            <div class="space-y-3">
                @foreach($predictions->sortByDesc('predicted_demand')->take(10) as $prediction)
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <div class="text-sm font-medium text-gray-900 truncate">
                            {{ Str::limit($prediction->item_name, 30) }}
                        </div>
                        <div class="text-xs text-gray-500">{{ $prediction->item_code }}</div>
                    </div>
                    <div class="flex items-center space-x-2">
                        <div class="text-sm font-bold text-gray-900">{{ $prediction->predicted_demand }}</div>
                        <div class="w-20 bg-gray-200 rounded-full h-2">
                            <div class="bg-green-500 h-2 rounded-full"
                                style="width: {{ min(100, ($prediction->predicted_demand / $predictions->max('predicted_demand')) * 100) }}%">
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Confidence Distribution -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold mb-4">Distribusi Confidence Level</h3>
            <div class="space-y-3">
                @php
                $confidenceRanges = [
                ['min' => 80, 'max' => 100, 'label' => 'Very High (80-100%)', 'color' => 'bg-green-500'],
                ['min' => 70, 'max' => 79, 'label' => 'High (70-79%)', 'color' => 'bg-blue-500'],
                ['min' => 60, 'max' => 69, 'label' => 'Medium (60-69%)', 'color' => 'bg-yellow-500'],
                ['min' => 50, 'max' => 59, 'label' => 'Low (50-59%)', 'color' => 'bg-orange-500'],
                ['min' => 0, 'max' => 49, 'label' => 'Very Low (<50%)', 'color'=> 'bg-red-500'],
                    ];
                    @endphp

                    @foreach($confidenceRanges as $range)
                    @php
                    $count = $predictions->filter(function($p) use ($range) {
                    return $p->prediction_confidence >= $range['min'] && $p->prediction_confidence <= $range['max'];
                        })->count();
                        $percentage = $predictions->count() > 0 ? ($count / $predictions->count()) * 100 : 0;
                        @endphp

                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-2">
                                <div class="w-4 h-4 rounded {{ $range['color'] }}"></div>
                                <span class="text-sm text-gray-700">{{ $range['label'] }}</span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span class="text-sm font-bold text-gray-900">{{ $count }}</span>
                                <div class="w-16 bg-gray-200 rounded-full h-2">
                                    <div class="h-2 rounded-full {{ $range['color'] }}"
                                        style="width: {{ $percentage }}%"></div>
                                </div>
                                <span class="text-xs text-gray-500 w-8">{{ number_format($percentage, 0) }}%</span>
                            </div>
                        </div>
                        @endforeach
            </div>
        </div>
    </div>
    @endif

    <!-- Info Section -->
    <div class="mt-6 bg-green-50 border border-green-200 rounded-lg p-4">
        <h4 class="text-sm font-medium text-green-800 mb-2">💡 Cara Membaca Prediksi:</h4>
        <ul class="text-sm text-green-700 space-y-1">
            <li><strong>Predicted Demand:</strong> Perkiraan jumlah unit yang akan terjual dalam 30 hari</li>
            <li><strong>Confidence:</strong> Tingkat kepercayaan model prediksi (semakin tinggi semakin akurat)</li>
            <li><strong>Reliability:</strong> Klasifikasi overall berdasarkan confidence level</li>
            <li><strong>High Confidence (>70%):</strong> Dapat digunakan untuk perencanaan inventory</li>
            <li><strong>Low Confidence (<50%):< /strong> Perlu validasi tambahan sebelum pengambilan keputusan</li>
        </ul>
    </div>
</div>
@endsection
