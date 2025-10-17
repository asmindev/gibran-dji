@extends('layouts.app')

@section('title', 'Perbandingan Apriori vs FP-Growth')

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
@endpush

@section('content')
<div class="container mx-auto px-4 py-6">
    <h1 class="text-3xl font-bold text-gray-800 mb-2">⚖️ Perbandingan Algoritma: Apriori vs FP-Growth</h1>
    <p class="text-gray-600 mb-6">Analisis mendalam dan perbandingan side-by-side kedua algoritma Association Rule
        Mining</p>

    <!-- Parameters Control -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">🔧 Kontrol Parameter</h2>

        @if($hasCalculation && empty($aprioriSteps['association_rules']) && empty($fpGrowthSteps['association_rules']))
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-triangle text-yellow-400"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-yellow-700">
                        <strong>Tidak ada association rules yang ditemukan!</strong> Ini terjadi karena:
                    </p>
                    <ul class="mt-2 text-sm text-yellow-700 list-disc list-inside">
                        <li>Data transaksi terlalu sedikit ({{ count($sampleTransactions) }} transaksi)</li>
                        <li>Threshold terlalu tinggi (Support: {{ $minSupport }}%, Confidence: {{ $minConfidence }}%)
                        </li>
                    </ul>
                    <p class="mt-2 text-sm text-yellow-700">
                        <strong>💡 Solusi:</strong> Turunkan Support ke <strong>20%</strong> dan Confidence ke
                        <strong>50%</strong>, atau pilih tanggal dengan lebih banyak transaksi.
                    </p>
                </div>
            </div>
        </div>
        @endif

        <form method="GET" action="{{ route('analysis.compare') }}" class="flex flex-col lg:flex-row gap-4">
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 mb-2">Tanggal Transaksi *</label>
                <input type="date" name="transaction_date" value="{{ $selectedDate !== 'all' ? $selectedDate : '' }}"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-purple-500"
                    required>
                <div class="text-xs text-gray-500 mt-1">
                    @if(!empty($availableDates))
                    📅 Data tersedia: {{ \Carbon\Carbon::parse(min($availableDates))->format('d M Y') }} - {{
                    \Carbon\Carbon::parse(max($availableDates))->format('d M Y') }}
                    @endif
                </div>
            </div>
            <div class="flex-none w-full lg:w-48">
                <label class="block text-sm font-medium text-gray-700 mb-2">Minimum Support (%)</label>
                <input type="number" name="min_support" value="{{ $minSupport }}" min="1" max="100"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md">
                <div class="text-xs text-blue-600 mt-1">💡 Rekomendasi: 20-30%</div>
            </div>
            <div class="flex-none w-full lg:w-48">
                <label class="block text-sm font-medium text-gray-700 mb-2">Minimum Confidence (%)</label>
                <input type="number" name="min_confidence" value="{{ $minConfidence }}" min="1" max="100"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md">
                <div class="text-xs text-blue-600 mt-1">💡 Rekomendasi: 50-60%</div>
            </div>
            <div class="flex flex-col lg:items-end">
                <label class="block text-sm font-medium text-gray-700 mb-2">&nbsp;</label>
                <button type="submit"
                    class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 rounded-md whitespace-nowrap">
                    <i class="fas fa-sync-alt mr-2"></i>Bandingkan Algoritma
                </button>
            </div>
        </form>

        <!-- Quick Test Buttons -->
        <div class="mt-4 p-4 bg-blue-50 rounded-lg border border-blue-200">
            <h4 class="font-semibold text-blue-800 mb-2">🚀 Quick Test - Tanggal Terbaik</h4>
            <p class="text-sm text-blue-700 mb-3">Coba tanggal dengan 20 transaksi:</p>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('analysis.compare', ['transaction_date' => '2025-09-27', 'min_support' => 20, 'min_confidence' => 50]) }}"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm">
                    📅 27 Sep 2025 (20 tx) - Support 20%, Conf 50%
                </a>
                <a href="{{ route('analysis.compare', ['transaction_date' => '2025-10-15', 'min_support' => 20, 'min_confidence' => 50]) }}"
                    class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm">
                    📅 15 Oct 2025 (20 tx) - Support 20%, Conf 50%
                </a>
                <a href="{{ route('analysis.compare', ['transaction_date' => '2025-10-30', 'min_support' => 25, 'min_confidence' => 55]) }}"
                    class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 text-sm">
                    📅 30 Oct 2025 (20 tx) - Support 25%, Conf 55%
                </a>
            </div>
        </div>
    </div>

    @if($hasCalculation)
    <!-- Performance Summary -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-semibold text-gray-800">⚡ Ringkasan Performa</h2>
            <div class="text-sm">
                <span class="bg-purple-100 text-purple-800 px-3 py-1 rounded-full">
                    📅 {{ \Carbon\Carbon::parse($selectedDate)->format('d M Y') }} |
                    📊 {{ count($sampleTransactions) }} transaksi
                </span>
            </div>
        </div>
        <div class="grid md:grid-cols-4 gap-4">
            <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                <div class="text-sm text-blue-600 font-medium">Apriori Time</div>
                <div class="text-2xl font-bold text-blue-700">{{ $aprioriSteps['summary']['execution_time_ms'] ?? 0 }}
                    ms</div>
            </div>
            <div class="bg-green-50 p-4 rounded-lg border border-green-200">
                <div class="text-sm text-green-600 font-medium">FP-Growth Time</div>
                <div class="text-2xl font-bold text-green-700">{{ $fpGrowthSteps['summary']['execution_time_ms'] ?? 0 }}
                    ms</div>
            </div>
            <div class="bg-purple-50 p-4 rounded-lg border border-purple-200">
                <div class="text-sm text-purple-600 font-medium">Speedup</div>
                <div class="text-2xl font-bold text-purple-700">
                    @php
                    $apt = $aprioriSteps['summary']['execution_time_ms'] ?? 1;
                    $fpt = $fpGrowthSteps['summary']['execution_time_ms'] ?? 1;
                    echo $apt > 0 && $fpt > 0 ? round($apt / $fpt, 2) : 0;
                    @endphp x
                </div>
            </div>
            <div class="bg-orange-50 p-4 rounded-lg border border-orange-200">
                <div class="text-sm text-orange-600 font-medium">Total Transaksi</div>
                <div class="text-2xl font-bold text-orange-700">{{ count($sampleTransactions) }}</div>
            </div>
        </div>
    </div>

    <!-- Algorithm Comparison Info -->
    <div class="bg-gradient-to-r from-purple-50 to-indigo-50 rounded-lg p-6 mb-8 border border-purple-200">
        <h3 class="text-lg font-semibold text-purple-800 mb-4">🔍 Perbandingan Algoritma: Persamaan & Perbedaan</h3>
        <div class="grid md:grid-cols-2 gap-6">
            <div class="bg-white rounded-lg p-4 border border-purple-100">
                <h4 class="font-semibold text-blue-700 mb-3 flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                            clip-rule="evenodd" />
                    </svg>
                    Persamaan (Step A-B)
                </h4>
                <ul class="space-y-2 text-sm text-gray-700">
                    <li class="flex items-start">
                        <span class="text-green-600 mr-2">✓</span>
                        <span><strong>Step A:</strong> Scan database untuk hitung frekuensi setiap item</span>
                    </li>
                    <li class="flex items-start">
                        <span class="text-green-600 mr-2">✓</span>
                        <span><strong>Step B:</strong> Filter items dengan support ≥ {{ $minSupport }}%</span>
                    </li>
                    <li class="flex items-start">
                        <span class="text-green-600 mr-2">✓</span>
                        <span><strong>Hasil Step B PASTI SAMA</strong> karena menggunakan threshold yang sama!</span>
                    </li>
                </ul>
            </div>
            <div class="bg-white rounded-lg p-4 border border-purple-100">
                <h4 class="font-semibold text-green-700 mb-3 flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                            clip-rule="evenodd" />
                    </svg>
                    Perbedaan (Step C-E)
                </h4>
                <ul class="space-y-2 text-sm text-gray-700">
                    <li class="flex items-start">
                        <span class="text-blue-600 mr-2">→</span>
                        <span><strong>Apriori:</strong> Generate candidate → Join → Scan DB (berulang per
                            k-itemset)</span>
                    </li>
                    <li class="flex items-start">
                        <span class="text-green-600 mr-2">→</span>
                        <span><strong>FP-Growth:</strong> Build FP-Tree → Pattern growth (tanpa generate
                            candidate)</span>
                    </li>
                    <li class="flex items-start">
                        <span class="text-purple-600 mr-2">⚡</span>
                        <span><strong>FP-Growth lebih cepat</strong> karena hanya scan database 2x!</span>
                    </li>
                </ul>
            </div>
        </div>
        <div class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
            <p class="text-sm text-yellow-800">
                <strong>💡 Catatan:</strong> Jika hasil Step B sama, berarti kedua algoritma bekerja dengan benar!
                Perbedaan performa dan efisiensi muncul di langkah-langkah selanjutnya (Step C, D, E).
            </p>
        </div>
    </div>

    <!-- Two Column Comparison -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- LEFT COLUMN: APRIORI -->
        <div class="min-w-0">
            <div class="bg-gradient-to-r from-blue-600 to-blue-700 text-white p-6 rounded-t-lg">
                <h2 class="text-2xl font-bold flex items-center">
                    <span
                        class="bg-white text-blue-600 rounded-full w-10 h-10 flex items-center justify-center text-lg font-bold mr-3">A</span>
                    Algoritma Apriori
                </h2>
                <p class="mt-2 text-blue-100">Generate-and-test approach dengan multiple database scans</p>
            </div>

            <!-- Apriori Steps -->
            @if($aprioriSteps && isset($aprioriSteps['steps']))
            <div class="bg-white rounded-b-lg shadow-md p-6 mb-6">
                <h3 class="text-xl font-semibold text-gray-800 mb-4">🔄 Langkah-langkah Proses</h3>
                <div class="space-y-6">
                    @foreach($aprioriSteps['steps'] as $step)
                    <div class="border border-blue-200 rounded-lg overflow-hidden">
                        <div class="bg-blue-50 px-4 py-3 border-b border-blue-200">
                            <h4 class="font-semibold text-blue-800 flex items-center">
                                <span
                                    class="bg-blue-600 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs mr-2">{{
                                    $step['step'] }}</span>
                                {{ $step['title'] }}
                            </h4>
                            <p class="text-sm text-blue-600 mt-1">{{ $step['description'] }}</p>
                        </div>
                        <div class="p-4 bg-white">
                            @if($step['step'] === 'A')
                            <!-- Single items -->
                            <div class="overflow-x-auto">
                                <table class="min-w-full text-sm">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Item</th>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Count</th>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Support
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach(array_slice($step['data'], 0, 10) as $item)
                                        <tr class="border-b">
                                            <td class="px-3 py-2 text-xs">{{ $item['item'] ?? 'N/A' }}</td>
                                            <td class="px-3 py-2 text-xs">{{ $item['count'] ?? 0 }}/{{ $item['total'] ??
                                                0 }}</td>
                                            <td class="px-3 py-2 text-xs">
                                                <span
                                                    class="px-2 py-1 rounded-full text-xs {{ ($item['support'] ?? 0) >= $minSupport ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                    {{ $item['support'] ?? 0 }}%
                                                </span>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                @if(count($step['data']) > 10)
                                <p class="text-xs text-gray-500 mt-2 text-center">... dan {{ count($step['data']) - 10
                                    }} item lainnya</p>
                                @endif
                            </div>

                            @elseif($step['step'] === 'B')
                            <!-- Pruned items -->
                            <div class="overflow-x-auto">
                                <table class="min-w-full text-sm">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Item</th>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Support
                                            </th>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Status
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach(array_slice($step['data'], 0, 10) as $item)
                                        <tr
                                            class="border-b {{ ($item['status'] ?? '') === 'kept' ? 'bg-green-50' : 'bg-red-50' }}">
                                            <td class="px-3 py-2 text-xs font-medium">{{ $item['item'] ?? 'N/A' }}</td>
                                            <td class="px-3 py-2 text-xs">
                                                <span
                                                    class="px-2 py-1 rounded-full text-xs font-semibold {{ ($item['status'] ?? '') === 'kept' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                    {{ $item['support'] ?? 0 }}%
                                                </span>
                                            </td>
                                            <td class="px-3 py-2 text-xs">
                                                <span
                                                    class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold {{ ($item['status'] ?? '') === 'kept' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                    @if(($item['status'] ?? '') === 'kept')
                                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd"
                                                            d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                            clip-rule="evenodd" />
                                                    </svg>
                                                    Kept
                                                    @else
                                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd"
                                                            d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                                            clip-rule="evenodd" />
                                                    </svg>
                                                    Pruned (< {{ $minSupport }}%) @endif </span>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                @if(count($step['data']) > 10)
                                <div class="mt-3 text-center">
                                    <span class="text-xs text-gray-500 bg-gray-100 px-3 py-1 rounded-full">
                                        ... dan {{ count($step['data']) - 10 }} item lainnya
                                    </span>
                                </div>
                                @endif
                                <div class="mt-3 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                                    <p class="text-xs text-blue-700">
                                        <strong>💡 Info:</strong> Hanya items dengan support ≥ {{ $minSupport }}% yang
                                        dipertahankan. Items yang di-prune tidak akan digunakan dalam langkah
                                        selanjutnya.
                                    </p>
                                </div>
                            </div>

                            @elseif($step['step'] === 'D')
                            <!-- 2-itemsets -->
                            <div class="space-y-2">
                                @php
                                $kept = collect($step['data'])->where('status', 'kept')->take(8);
                                @endphp
                                @foreach($kept as $itemset)
                                <div class="bg-green-50 border border-green-200 rounded px-3 py-2">
                                    <div class="flex justify-between items-center">
                                        <span class="text-xs font-medium">{{ $itemset['itemset'] ?? 'N/A' }}</span>
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs text-gray-600">{{ $itemset['count'] ?? 0 }} tx</span>
                                            <span class="text-xs bg-green-100 text-green-800 px-2 py-1 rounded">{{
                                                $itemset['support'] ?? 0 }}%</span>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                                @if($kept->count() < collect($step['data'])->where('status', 'kept')->count())
                                    <p class="text-xs text-gray-500 text-center">... {{
                                        collect($step['data'])->where('status', 'kept')->count() - $kept->count() }}
                                        lainnya</p>
                                    @endif
                            </div>

                            @elseif($step['step'] === 'E')
                            <!-- 3-itemsets -->
                            @php
                            $triplets = collect($step['data'])->where('status', 'kept');
                            @endphp
                            @if($triplets->count() > 0)
                            <div class="space-y-2">
                                @foreach($triplets->take(5) as $itemset)
                                <div class="bg-purple-50 border border-purple-200 rounded px-3 py-2">
                                    <div class="flex justify-between items-center">
                                        <span class="text-xs font-medium">{{ $itemset['itemset'] ?? 'N/A' }}</span>
                                        <span class="text-xs bg-purple-100 text-purple-800 px-2 py-1 rounded">{{
                                            $itemset['support'] ?? 0 }}%</span>
                                    </div>
                                </div>
                                @endforeach
                                @if($triplets->count() > 5)
                                <p class="text-xs text-gray-500 text-center">... {{ $triplets->count() - 5 }} lainnya
                                </p>
                                @endif
                            </div>
                            @else
                            <div class="text-center py-4 bg-yellow-50 border border-yellow-200 rounded">
                                <p class="text-xs text-yellow-700">Tidak ada 3-itemset yang memenuhi minimum support</p>
                            </div>
                            @endif

                            @else
                            <div class="text-xs text-gray-600">{{ json_encode($step['data']) }}</div>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Apriori Association Rules -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
                <div class="bg-gradient-to-r from-blue-600 to-blue-700 text-white p-4">
                    <h3 class="text-lg font-semibold">🎯 Association Rules</h3>
                    <p class="text-sm text-blue-100 mt-1">{{ count($aprioriSteps['association_rules'] ?? []) }} rules
                        ditemukan</p>
                </div>
                <div class="p-4">
                    @forelse($aprioriSteps['association_rules'] ?? [] as $rule)
                    <div class="bg-blue-50 border border-blue-200 rounded p-3 mb-3">
                        <div class="font-medium text-sm text-blue-900">{{ $rule['rule'] }}</div>
                        <div class="text-xs text-gray-600 mt-1">
                            Support: {{ $rule['support'] }}% | Confidence: {{ $rule['confidence'] }}% | Lift: {{
                            $rule['lift'] ?? 'N/A' }}
                        </div>
                        <div class="text-xs text-blue-700 italic mt-1">{{ $rule['description'] }}</div>
                    </div>
                    @empty
                    <p class="text-sm text-gray-500 text-center py-4">Tidak ada association rules yang dihasilkan</p>
                    @endforelse
                </div>
            </div>

            <!-- Apriori Summary -->
            <div class="bg-white rounded-lg shadow-md p-4">
                <h3 class="text-lg font-semibold text-gray-800 mb-3">📊 Ringkasan</h3>
                <div class="grid grid-cols-2 gap-3">
                    <div class="text-center p-3 bg-blue-50 rounded">
                        <div class="text-2xl font-bold text-blue-600">{{ $aprioriSteps['summary']['total_transactions']
                            ?? 0 }}</div>
                        <div class="text-xs text-gray-600">Transaksi</div>
                    </div>
                    <div class="text-center p-3 bg-green-50 rounded">
                        <div class="text-2xl font-bold text-green-600">{{
                            $aprioriSteps['summary']['frequent_1_itemsets'] ?? 0 }}</div>
                        <div class="text-xs text-gray-600">1-Itemsets</div>
                    </div>
                    <div class="text-center p-3 bg-purple-50 rounded">
                        <div class="text-2xl font-bold text-purple-600">{{
                            $aprioriSteps['summary']['frequent_2_itemsets'] ?? 0 }}</div>
                        <div class="text-xs text-gray-600">2-Itemsets</div>
                    </div>
                    <div class="text-center p-3 bg-orange-50 rounded">
                        <div class="text-2xl font-bold text-orange-600">{{ $aprioriSteps['summary']['strong_rules'] ?? 0
                            }}</div>
                        <div class="text-xs text-gray-600">Strong Rules</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT COLUMN: FP-GROWTH -->
        <div class="min-w-0">
            <div class="bg-gradient-to-r from-green-600 to-green-700 text-white p-6 rounded-t-lg">
                <h2 class="text-2xl font-bold flex items-center">
                    <span
                        class="bg-white text-green-600 rounded-full w-10 h-10 flex items-center justify-center text-lg font-bold mr-3">F</span>
                    Algoritma FP-Growth
                </h2>
                <p class="mt-2 text-green-100">Pattern-growth approach dengan FP-Tree (hanya 2 database scans)</p>
            </div>

            <!-- FP-Growth Steps -->
            @if($fpGrowthSteps && isset($fpGrowthSteps['steps']))
            <div class="bg-white rounded-b-lg shadow-md p-6 mb-6">
                <h3 class="text-xl font-semibold text-gray-800 mb-4">🔄 Langkah-langkah Proses</h3>
                <div class="space-y-6">
                    @foreach($fpGrowthSteps['steps'] as $step)
                    <div class="border border-green-200 rounded-lg overflow-hidden">
                        <div class="bg-green-50 px-4 py-3 border-b border-green-200">
                            <h4 class="font-semibold text-green-800 flex items-center">
                                <span
                                    class="bg-green-600 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs mr-2">{{
                                    $step['step'] }}</span>
                                {{ $step['title'] }}
                            </h4>
                            <p class="text-sm text-green-600 mt-1">{{ $step['description'] }}</p>
                        </div>
                        <div class="p-4 bg-white">
                            @if($step['step'] === 'A')
                            <!-- Scan items -->
                            <div class="overflow-x-auto">
                                <table class="min-w-full text-sm">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Item</th>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Count</th>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Support
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach(array_slice($step['data'], 0, 10) as $item)
                                        <tr class="border-b">
                                            <td class="px-3 py-2 text-xs">{{ $item['item'] ?? 'N/A' }}</td>
                                            <td class="px-3 py-2 text-xs">{{ $item['count'] ?? 0 }}</td>
                                            <td class="px-3 py-2 text-xs">
                                                <span
                                                    class="px-2 py-1 rounded-full text-xs {{ ($item['support'] ?? 0) >= $minSupport ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                    {{ $item['support'] ?? 0 }}%
                                                </span>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                @if(count($step['data']) > 10)
                                <p class="text-xs text-gray-500 mt-2 text-center">... dan {{ count($step['data']) - 10
                                    }} item lainnya</p>
                                @endif
                            </div>

                            @elseif($step['step'] === 'B')
                            <!-- F-List -->
                            <div class="overflow-x-auto">
                                <table class="min-w-full text-sm">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Item</th>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Support
                                            </th>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Status
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach(array_slice($step['data'], 0, 10) as $index => $item)
                                        @php
                                        $support = is_array($item) && isset($item['support']) ? $item['support'] : 0;
                                        $isKept = $support >= $minSupport;
                                        @endphp
                                        <tr class="border-b {{ $isKept ? 'bg-green-50' : 'bg-red-50' }}">
                                            <td class="px-3 py-2 text-xs font-medium">
                                                {{ is_array($item) && isset($item['item']) ? $item['item'] :
                                                (is_string($item) ? $item : 'N/A') }}
                                            </td>
                                            <td class="px-3 py-2 text-xs">
                                                <span
                                                    class="px-2 py-1 rounded-full text-xs font-semibold {{ $isKept ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                    {{ $support }}%
                                                </span>
                                            </td>
                                            <td class="px-3 py-2 text-xs">
                                                <span
                                                    class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold {{ $isKept ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                    @if($isKept)
                                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd"
                                                            d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                            clip-rule="evenodd" />
                                                    </svg>
                                                    Kept in F-List
                                                    @else
                                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd"
                                                            d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                                            clip-rule="evenodd" />
                                                    </svg>
                                                    Pruned (< {{ $minSupport }}%) @endif </span>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                @if(count($step['data']) > 10)
                                <div class="mt-3 text-center">
                                    <span class="text-xs text-gray-500 bg-gray-100 px-3 py-1 rounded-full">
                                        ... dan {{ count($step['data']) - 10 }} item lainnya
                                    </span>
                                </div>
                                @endif
                                <div class="mt-3 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                                    <p class="text-xs text-blue-700">
                                        <strong>💡 Info:</strong> Hanya items dengan support ≥ {{ $minSupport }}% yang
                                        dipertahankan dalam F-List. Items yang di-prune tidak akan digunakan untuk
                                        membangun FP-Tree.
                                    </p>
                                </div>
                            </div>
                            berdasarkan frekuensi (descending).
                            Items ini akan digunakan untuk membangun FP-Tree.
                            </p>
                        </div>
                    </div>

                    @elseif($step['step'] === 'C')
                    <!-- Sort Transactions -->
                    <div class="space-y-2">
                        @php
                        $sortedData = is_string($step['data']) ? json_decode($step['data'], true) :
                        $step['data'];
                        @endphp
                        @if(is_array($sortedData))
                        @foreach(array_slice($sortedData, 0, 8) as $transaction)
                        <div class="bg-blue-50 border border-blue-200 rounded px-3 py-2">
                            @if(is_array($transaction))
                            <div class="text-xs">
                                <div class="font-medium text-gray-700 mb-1">
                                    Sorted: <span class="text-blue-700">{{ $transaction['sorted'] ?? 'N/A'
                                        }}</span>
                                </div>
                                @if(isset($transaction['count']))
                                <div class="text-gray-500 text-xs">
                                    Items: {{ $transaction['count'] ?? 0 }}
                                </div>
                                @endif
                            </div>
                            @else
                            <div class="text-xs">{{ $transaction }}</div>
                            @endif
                        </div>
                        @endforeach
                        @if(count($sortedData) > 8)
                        <p class="text-xs text-gray-500 text-center">... {{ count($sortedData) - 8 }} transaksi
                            lainnya</p>
                        @endif
                        @else
                        <p class="text-xs text-gray-500">Data tidak valid</p>
                        @endif
                    </div>

                    @elseif($step['step'] === 'D')
                    <!-- FP-Tree paths -->
                    <div class="space-y-2">
                        @foreach(array_slice($step['data'], 0, 6) as $path)
                        <div class="bg-blue-50 border border-blue-200 rounded px-3 py-2">
                            <div class="text-xs font-mono">{{ is_array($path) ? implode(' → ', $path) : $path }}
                            </div>
                        </div>
                        @endforeach
                        @if(count($step['data']) > 6)
                        <p class="text-xs text-gray-500 text-center">... {{ count($step['data']) - 6 }} paths
                            lainnya</p>
                        @endif
                    </div>

                    @elseif($step['step'] === 'E')
                    <!-- Mined patterns -->
                    <div class="space-y-2">
                        @foreach(array_slice($step['data'], 0, 8) as $pattern)
                        <div class="bg-purple-50 border border-purple-200 rounded px-3 py-2">
                            <div class="flex justify-between items-center">
                                <span class="text-xs font-medium">{{ is_array($pattern) &&
                                    isset($pattern['pattern']) ? $pattern['pattern'] : (is_string($pattern) ?
                                    $pattern : json_encode($pattern)) }}</span>
                                @if(is_array($pattern) && isset($pattern['support']))
                                <span class="text-xs bg-purple-100 text-purple-800 px-2 py-1 rounded">{{
                                    $pattern['support'] }}%</span>
                                @endif
                            </div>
                        </div>
                        @endforeach
                        @if(count($step['data']) > 8)
                        <p class="text-xs text-gray-500 text-center">... {{ count($step['data']) - 8 }} patterns
                            lainnya</p>
                        @endif
                    </div>

                    @else
                    <div class="text-xs text-gray-600">
                        @if(is_string($step['data']))
                        {{ $step['data'] }}
                        @else
                        {{ json_encode($step['data']) }}
                        @endif
                    </div>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- FP-Growth Association Rules -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
        <div class="bg-gradient-to-r from-green-600 to-green-700 text-white p-4">
            <h3 class="text-lg font-semibold">🎯 Association Rules</h3>
            <p class="text-sm text-green-100 mt-1">{{ count($fpGrowthSteps['association_rules'] ?? []) }} rules
                ditemukan</p>
        </div>
        <div class="p-4">
            @forelse($fpGrowthSteps['association_rules'] ?? [] as $rule)
            <div class="bg-green-50 border border-green-200 rounded p-3 mb-3">
                <div class="font-medium text-sm text-green-900">{{ $rule['rule'] }}</div>
                <div class="text-xs text-gray-600 mt-1">
                    Support: {{ $rule['support'] }}% | Confidence: {{ $rule['confidence'] }}% | Lift: {{
                    $rule['lift'] ?? 'N/A' }}
                </div>
                <div class="text-xs text-green-700 italic mt-1">{{ $rule['description'] }}</div>
            </div>
            @empty
            <p class="text-sm text-gray-500 text-center py-4">Tidak ada association rules yang dihasilkan</p>
            @endforelse
        </div>
    </div>

    <!-- FP-Growth Summary -->
    <div class="bg-white rounded-lg shadow-md p-4">
        <h3 class="text-lg font-semibold text-gray-800 mb-3">📊 Ringkasan</h3>
        <div class="grid grid-cols-2 gap-3">
            <div class="text-center p-3 bg-green-50 rounded">
                <div class="text-2xl font-bold text-green-600">{{
                    $fpGrowthSteps['summary']['total_transactions'] ?? 0 }}</div>
                <div class="text-xs text-gray-600">Transaksi</div>
            </div>
            <div class="text-center p-3 bg-blue-50 rounded">
                <div class="text-2xl font-bold text-blue-600">{{ $fpGrowthSteps['summary']['frequent_items'] ??
                    0 }}</div>
                <div class="text-xs text-gray-600">Frequent Items</div>
            </div>
            <div class="text-center p-3 bg-purple-50 rounded">
                <div class="text-2xl font-bold text-purple-600">{{ $fpGrowthSteps['summary']['fp_tree_paths'] ??
                    0 }}</div>
                <div class="text-xs text-gray-600">FP-Tree Paths</div>
            </div>
            <div class="text-center p-3 bg-orange-50 rounded">
                <div class="text-2xl font-bold text-orange-600">{{ $fpGrowthSteps['summary']['strong_rules'] ??
                    0 }}</div>
                <div class="text-xs text-gray-600">Strong Rules</div>
            </div>
        </div>
    </div>
</div>
</div>

@else
<!-- No Calculation Placeholder -->
<div class="bg-gradient-to-r from-gray-50 to-gray-100 rounded-lg p-12 text-center border border-gray-200">
    <div class="mx-auto flex items-center justify-center h-20 w-20 rounded-full bg-purple-100 mb-4">
        <i class="fas fa-balance-scale text-purple-600 text-3xl"></i>
    </div>
    <h3 class="text-2xl font-semibold text-gray-800 mb-2">Pilih Tanggal untuk Memulai Perbandingan</h3>
    <p class="text-gray-600 mb-6">
        Gunakan form di atas untuk memilih tanggal dan parameter, kemudian klik "Bandingkan Algoritma"
    </p>
    <div class="bg-white rounded-lg p-6 max-w-md mx-auto">
        <h4 class="font-medium text-gray-800 mb-3">📋 Data Tersedia:</h4>
        <div class="text-sm text-gray-600">
            @if(!empty($availableDates))
            <p class="mb-2"><strong>{{ count($availableDates) }}</strong> hari transaksi tersedia</p>
            <p>Dari {{ \Carbon\Carbon::parse(min($availableDates))->format('d M Y') }}
                hingga {{ \Carbon\Carbon::parse(max($availableDates))->format('d M Y') }}</p>
            @else
            <p class="text-red-600">Tidak ada data transaksi yang tersedia</p>
            @endif
        </div>
    </div>
</div>
@endif
</div>
@endsection
