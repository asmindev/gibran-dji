@extends('layouts.app')

@section('title', 'Analisis Inventori')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Analisis Inventori</h1>
        <div class="flex space-x-2">
            <a href="{{ route('analysis.apriori-process') }}"
                class="bg-indigo-500 hover:bg-indigo-600 text-white px-4 py-2 rounded-lg">
                Proses Algoritma Apriori
            </a>
            <a href="{{ route('analysis.recommendations') }}"
                class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg">
                Lihat Rekomendasi
            </a>
            <a href="{{ route('analysis.predictions') }}"
                class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg">
                Lihat Prediksi
            </a>
            <form action="{{ route('analysis.run') }}" method="POST" class="inline"
                onsubmit="return confirm('Apakah Anda yakin ingin menjalankan analisis? Proses ini mungkin memakan waktu beberapa menit.')">
                @csrf
                <button type="submit" class="bg-purple-500 hover:bg-purple-600 text-white px-4 py-2 rounded-lg">
                    Jalankan Analisis
                </button>
            </form>
        </div>
    </div>

    @if(session('success'))
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
        {{ session('success') }}
    </div>
    @endif

    @if(session('error'))
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
        {{ session('error') }}
    </div>
    @endif

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="text-3xl font-bold text-blue-600">{{ $summary['active_recommendations'] }}</div>
                <div class="ml-auto">
                    <svg class="w-8 h-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
            <p class="text-gray-500 text-sm mt-2">Rekomendasi Aktif</p>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="text-3xl font-bold text-green-600">{{ $summary['active_predictions'] }}</div>
                <div class="ml-auto">
                    <svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                    </svg>
                </div>
            </div>
            <p class="text-gray-500 text-sm mt-2">Prediksi Aktif</p>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="text-3xl font-bold text-purple-600">{{ $summary['high_confidence_recommendations'] }}</div>
                <div class="ml-auto">
                    <svg class="w-8 h-8 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.5 2.5L16 5.5 13.5 3M7 7l2.5 2.5L16 3.5 13.5 6M17 17l2.5 2.5L16 21.5 13.5 19">
                        </path>
                    </svg>
                </div>
            </div>
            <p class="text-gray-500 text-sm mt-2">Rekomendasi Tinggi</p>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="text-3xl font-bold text-orange-600">{{ $summary['high_confidence_predictions'] }}</div>
                <div class="ml-auto">
                    <svg class="w-8 h-8 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                        </path>
                    </svg>
                </div>
            </div>
            <p class="text-gray-500 text-sm mt-2">Prediksi Tinggi</p>
        </div>
    </div>

    <!-- Last Analysis Info -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Informasi Analisis Terakhir</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <span class="text-gray-600">Tanggal Analisis Terakhir:</span>
                <span class="font-semibold ml-2">
                    @if($summary['last_analysis_date'] === 'Never')
                    <span class="text-gray-500">Belum Pernah</span>
                    @else
                    {{ \Carbon\Carbon::parse($summary['last_analysis_date'])->format('d M Y H:i') }}
                    @endif
                </span>
            </div>
            <div>
                <span class="text-gray-600">Status:</span>
                <span class="font-semibold ml-2 text-green-600">
                    @if($summary['last_analysis_date'] === 'Never')
                    Perlu Analisis
                    @else
                    Selesai
                    @endif
                </span>
            </div>
        </div>
    </div>

    <!-- Recent Recommendations -->
    @if($recommendations->count() > 0)
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Rekomendasi Terbaru</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full table-auto">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jika
                            Dibeli</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Kemungkinan Dibeli</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Confidence</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Lift
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($recommendations as $recommendation)
                    <tr>
                        <td class="px-4 py-2 text-sm text-gray-900">
                            {{ $recommendation->antecedent_items_names }}
                        </td>
                        <td class="px-4 py-2 text-sm text-gray-900">
                            {{ $recommendation->consequent_items_names }}
                        </td>
                        <td class="px-4 py-2 text-sm">
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                @if($recommendation->confidence >= 0.8) bg-green-100 text-green-800
                                @elseif($recommendation->confidence >= 0.6) bg-yellow-100 text-yellow-800
                                @else bg-red-100 text-red-800 @endif">
                                {{ number_format($recommendation->confidence * 100, 1) }}%
                            </span>
                        </td>
                        <td class="px-4 py-2 text-sm text-gray-900">
                            {{ number_format($recommendation->lift, 2) }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4">
            <a href="{{ route('analysis.recommendations') }}" class="text-blue-600 hover:text-blue-800">
                Lihat semua rekomendasi →
            </a>
        </div>
    </div>
    @endif

    <!-- Recent Predictions -->
    @if($predictions->count() > 0)
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Prediksi Demand Terbaru</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full table-auto">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item
                        </th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Prediksi Demand</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Confidence</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Periode</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($predictions as $prediction)
                    <tr>
                        <td class="px-4 py-2 text-sm">
                            <div>
                                <div class="font-medium text-gray-900">{{ $prediction->item->item_name }}</div>
                                <div class="text-gray-500">{{ $prediction->item->item_code }}</div>
                            </div>
                        </td>
                        <td class="px-4 py-2 text-sm font-semibold text-gray-900">
                            {{ $prediction->predicted_demand }}
                        </td>
                        <td class="px-4 py-2 text-sm">
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                @if($prediction->prediction_confidence >= 80) bg-green-100 text-green-800
                                @elseif($prediction->prediction_confidence >= 60) bg-yellow-100 text-yellow-800
                                @else bg-red-100 text-red-800 @endif">
                                {{ number_format($prediction->prediction_confidence, 1) }}%
                            </span>
                        </td>
                        <td class="px-4 py-2 text-sm text-gray-900">
                            {{ $prediction->prediction_period_start->format('d M') }} -
                            {{ $prediction->prediction_period_end->format('d M Y') }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4">
            <a href="{{ route('analysis.predictions') }}" class="text-blue-600 hover:text-blue-800">
                Lihat semua prediksi →
            </a>
        </div>
    </div>
    @endif

    @if($recommendations->count() === 0 && $predictions->count() === 0)
    <div class="bg-gray-50 rounded-lg p-8 text-center">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
            </path>
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">Belum Ada Hasil Analisis</h3>
        <p class="mt-1 text-sm text-gray-500">
            Mulai dengan menjalankan analisis untuk mendapatkan rekomendasi dan prediksi inventori.
        </p>
        <div class="mt-6">
            <form action="{{ route('analysis.run') }}" method="POST" class="inline"
                onsubmit="return confirm('Apakah Anda yakin ingin menjalankan analisis?')">
                @csrf
                <button type="submit" class="bg-purple-500 hover:bg-purple-600 text-white px-4 py-2 rounded-lg">
                    Jalankan Analisis Pertama
                </button>
            </form>
        </div>
    </div>
    @endif
</div>
@endsection
