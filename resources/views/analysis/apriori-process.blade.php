@extends('layouts.app')

@section('title', 'Proses Algoritma Apriori (Support)')

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
@endpush

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Proses Algoritma Apriori (Support)</h1>
            <p class="text-gray-600 mt-2">Pemahaman langkah demi langkah algoritma Association Rule Mining</p>
        </div>
    </div>

    <!-- Algorithm Overview -->
    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg p-6 mb-8 border border-blue-200">
        <h2 class="text-xl font-semibold text-blue-800 mb-4">🎯 Tentang Algoritma Apriori</h2>
        <div class="grid md:grid-cols-2 gap-6">
            <div>
                <p class="text-gray-700 mb-4">
                    <strong>Apriori</strong> adalah algoritma klasik untuk association-rule mining yang bertujuan
                    menemukan frequent itemsets (kombinasi produk yang sering muncul bersamaan) dan menurunkan aturan
                    asosiasi seperti:
                </p>
                <div class="bg-white p-4 rounded-lg border-l-4 border-blue-500">
                    <em class="text-blue-700">"Jika pelanggan membeli Item A, mereka juga cenderung membeli Item
                        B."</em>
                </div>
            </div>
            <div>
                <h3 class="font-semibold text-gray-800 mb-2">📊 Input & Parameter:</h3>
                <ul class="text-gray-700 space-y-1">
                    <li><strong>Dataset:</strong> Daftar transaksi (setiap transaksi = set item yang dibeli bersama)
                    </li>
                    <li><strong>Tanggal Analisis:</strong>
                        @if($hasCalculation)
                        @if($selectedDate === 'all')
                        Semua tanggal ({{ count($sampleTransactions) }} transaksi)
                        @else
                        {{ \Carbon\Carbon::parse($selectedDate)->format('d M Y') }} ({{ count($sampleTransactions) }}
                        transaksi)
                        @endif
                        @else
                        <span class="text-gray-500 italic">Belum dipilih</span>
                        @endif
                    </li>
                    <li><strong>Minimum Support:</strong> {{ $minSupport }}% (ambang batas frekuensi itemset)</li>
                    <li><strong>Minimum Confidence:</strong> {{ $minConfidence }}% (ambang batas keandalan aturan
                        asosiasi)</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Parameters Control -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">🔧 Kontrol Parameter</h2>
        <form method="GET" action="{{ route('analysis.apriori-process') }}" class="flex flex-col lg:flex-row gap-4">
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 mb-2">Tanggal Transaksi *</label>
                <input type="date" name="transaction_date" value="{{ $selectedDate !== 'all' ? $selectedDate : '' }}"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500"
                    required>
                <div class="text-xs text-gray-500 mt-1">
                    @if(!empty($availableDates))
                    Rentang data: {{ \Carbon\Carbon::parse(min($availableDates))->format('d M Y') }} - {{
                    \Carbon\Carbon::parse(max($availableDates))->format('d M Y') }}
                    @else
                    Tidak ada data transaksi tersedia
                    @endif
                </div>
            </div>
            <div class="flex-none w-full lg:w-48">
                <label class="block text-sm font-medium text-gray-700 mb-2">Minimum Support (%)</label>
                <input type="number" name="min_support" value="{{ $minSupport }}" min="1" max="100"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="flex-none w-full lg:w-48">
                <label class="block text-sm font-medium text-gray-700 mb-2">Minimum Confidence (%)</label>
                <input type="number" name="min_confidence" value="{{ $minConfidence }}" min="1" max="100"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="flex flex-col lg:flex-row lg:items-end gap-3">
                <button type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-md whitespace-nowrap">
                    <i class="fas fa-calculator mr-2"></i>Hitung Apriori
                </button>
                @if($hasCalculation && $analysisSaved && $savedRulesCount > 0)
                <div class="text-sm text-green-600 bg-green-50 px-3 py-2 rounded-md border border-green-200">
                    <i class="fas fa-check-circle mr-1"></i>
                    {{ $savedRulesCount }} association rule{{ $savedRulesCount > 1 ? 's' : '' }} telah disimpan ke
                    database
                </div>
                @elseif($hasCalculation && !$analysisSaved)
                <div class="text-sm text-amber-600 bg-amber-50 px-3 py-2 rounded-md border border-amber-200">
                    <i class="fas fa-info-circle mr-1"></i>
                    Analisis selesai, tetapi tidak ada association rules yang dihasilkan
                </div>
                @endif
            </div>
        </form>
    </div>

    @if($hasCalculation)
    <!-- Sample Transaction Data -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-semibold text-gray-800">📊 Data Transaksi</h2>
            @if($selectedDate !== 'all')
            <div class="text-sm">
                <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full">
                    Difilter: {{ \Carbon\Carbon::parse($selectedDate)->format('d M Y') }}
                </span>
            </div>
            @else
            <div class="text-sm">
                <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full">
                    Menampilkan: Semua Tanggal
                </span>
            </div>
            @endif
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full table-auto">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">ID Transaksi</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Tanggal</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Items yang Dibeli
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($sampleTransactions as $transaction)
                    <tr>
                        <td class="px-4 py-2 text-sm font-medium text-gray-900">{{ $transaction['id'] }}</td>
                        <td class="px-4 py-2 text-sm text-gray-600">{{
                            \Carbon\Carbon::parse($transaction['date'])->format('d/m/Y') }}</td>
                        <td class="px-4 py-2 text-sm text-gray-900">
                            <div class="flex flex-wrap gap-1">
                                @foreach($transaction['items'] as $item)
                                <span class="inline-flex px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded-full">{{
                                    $item }}</span>
                                @endforeach
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" class="px-4 py-8 text-center text-gray-500">
                            Tidak ada transaksi pada tanggal yang dipilih
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4 flex justify-between items-center text-sm text-gray-600">
            <div>
                <strong>Total:</strong> {{ count($sampleTransactions) }} transaksi untuk analisis
                @if($selectedDate !== 'all')
                pada tanggal {{ \Carbon\Carbon::parse($selectedDate)->format('d M Y') }}
                @endif
            </div>
            @if($selectedDate !== 'all' && count($sampleTransactions) < 2) <div
                class="text-amber-600 bg-amber-50 px-3 py-1 rounded">
                ⚠️ Data terbatas - pertimbangkan menggunakan "Semua Tanggal" untuk analisis yang lebih akurat
        </div>
        @endif
    </div>
</div>
@else
<!-- Placeholder when no calculation performed -->
<div class="bg-gradient-to-r from-gray-50 to-gray-100 rounded-lg p-8 mb-8 border border-gray-200">
    <div class="text-center">
        <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-blue-100 mb-4">
            <i class="fas fa-calculator text-blue-600 text-2xl"></i>
        </div>
        <h3 class="text-xl font-semibold text-gray-800 mb-2">Pilih Tanggal untuk Memulai Analisis</h3>
        <p class="text-gray-600 mb-4">
            Silakan pilih tanggal transaksi pada form di atas untuk memulai perhitungan algoritma Apriori.
        </p>
        <div class="bg-white rounded-lg p-4 max-w-md mx-auto">
            <h4 class="font-medium text-gray-800 mb-2">📋 Data Tersedia:</h4>
            <div class="text-sm text-gray-600">
                @if(!empty($availableDates))
                <p><strong>{{ count($availableDates) }}</strong> hari transaksi tersedia</p>
                <p>Dari {{ \Carbon\Carbon::parse(min($availableDates))->format('d M Y') }}
                    hingga {{ \Carbon\Carbon::parse(max($availableDates))->format('d M Y') }}</p>
                @else
                <p class="text-red-600">Tidak ada data transaksi yang tersedia</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endif

@if($hasCalculation && $algorithmSteps)
<!-- Algorithm Steps -->
<div class="space-y-8">
    <h2 class="text-2xl font-bold text-gray-800">🔄 Langkah-langkah Proses Algoritma</h2>

    @foreach($algorithmSteps['steps'] as $index => $step)
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 text-white p-6">
            <h3 class="text-xl font-semibold flex items-center">
                <span
                    class="bg-white text-blue-600 rounded-full w-8 h-8 flex items-center justify-center text-sm font-bold mr-3">
                    {{ $step['step'] }}
                </span>
                {{ $step['title'] }}
            </h3>
            <p class="mt-2 text-blue-100">{{ $step['description'] }}</p>
        </div>

        <div class="p-6">
            @if($step['step'] === 'A')
            <!-- Single items count -->
            <div class="overflow-x-auto">
                <table class="min-w-full table-auto">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Item</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Frekuensi
                            </th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Total
                                Transaksi</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Support (%)
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($step['data'] as $item)
                        <tr>
                            <td class="px-4 py-2 text-sm font-medium text-gray-900">{{ $item['item'] }}</td>
                            <td class="px-4 py-2 text-sm text-gray-900">{{ $item['count'] }}</td>
                            <td class="px-4 py-2 text-sm text-gray-600">{{ $item['total'] }}</td>
                            <td class="px-4 py-2 text-sm">
                                <span
                                    class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                        {{ $item['support'] >= $minSupport ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $item['support'] }}%
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @elseif($step['step'] === 'B')
            <!-- Pruned items -->
            <div class="mb-4 bg-blue-50 border-l-4 border-blue-500 p-4 rounded">
                <p class="text-sm text-blue-700">
                    <strong>📋 Pruning:</strong> Hanya items dengan support ≥ {{ $minSupport }}% yang dipertahankan
                    untuk tahap selanjutnya.
                </p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full table-auto">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Item</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Support (%)</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($step['data'] as $item)
                        @php
                        $isKept = ($item['status'] ?? '') === 'kept';
                        @endphp
                        <tr class="{{ $isKept ? 'bg-green-50' : 'bg-red-50' }}">
                            <td class="px-4 py-2 text-sm font-medium text-gray-900">{{ $item['item'] ?? 'Unknown' }}
                            </td>
                            <td class="px-4 py-2 text-sm">{{ $item['support'] ?? 0 }}%</td>
                            <td class="px-4 py-2 text-sm">
                                @if($isKept)
                                <span
                                    class="inline-flex items-center px-2 py-1 text-xs font-semibold bg-green-100 text-green-800 rounded-full">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    Dipertahankan
                                </span>
                                @else
                                <span
                                    class="inline-flex items-center px-2 py-1 text-xs font-semibold bg-red-100 text-red-800 rounded-full">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                    Dihapus (&lt; {{ $minSupport }}%)
                                </span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @elseif($step['step'] === 'C')
            <!-- Generated 2-itemsets -->
            <div class="grid md:grid-cols-2 gap-4">
                @foreach($step['data'] as $itemset)
                @if(isset($itemset['itemset']))
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <div class="flex flex-wrap gap-1 items-center">
                        @php
                        $items = explode(', ', trim($itemset['itemset'], '{}'));
                        @endphp
                        @foreach($items as $index => $item)
                        <span class="inline-flex px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded-full font-medium">
                            {{ trim($item) }}
                        </span>
                        @if($index < count($items) - 1) <span class="text-blue-400 font-bold">+</span>
                            @endif
                            @endforeach
                            <span
                                class="ml-2 text-xs bg-green-100 text-green-700 px-2 py-1 rounded font-medium">Generated</span>
                    </div>
                </div>
                @endif
                @endforeach
            </div>

            @elseif($step['step'] === 'D')
            <!-- Counted 2-itemsets -->
            <div class="overflow-x-auto">
                <table class="min-w-full table-auto">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Itemset</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Frekuensi
                            </th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Support (%)
                            </th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($step['data'] as $itemset)
                        @if(isset($itemset['status']) && isset($itemset['itemset']) && $itemset['status'] === 'kept')
                        <tr class="bg-green-50">
                            <td class="px-4 py-2 text-sm font-medium text-gray-900">
                                <div class="flex flex-wrap gap-1 items-center">
                                    @php
                                    $items = explode(', ', trim($itemset['itemset'], '{}'));
                                    @endphp
                                    @foreach($items as $index => $item)
                                    <span
                                        class="inline-flex px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded-full font-medium">
                                        {{ trim($item) }}
                                    </span>
                                    @if($index < count($items) - 1) <span class="text-gray-400 font-bold">+</span>
                                        @endif
                                        @endforeach
                                </div>
                            </td>
                            <td class="px-4 py-2 text-sm">{{ $itemset['count'] ?? 0 }}/{{
                                $algorithmSteps['summary']['total_transactions'] ?? 0 }}</td>
                            <td class="px-4 py-2 text-sm">{{ $itemset['support'] ?? 0 }}%</td>
                            <td class="px-4 py-2 text-sm">
                                <span
                                    class="inline-flex items-center px-2 py-1 text-xs font-semibold bg-green-100 text-green-800 rounded-full">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    Frequent
                                </span>
                            </td>
                        </tr>
                        @endif
                        @endforeach
                    </tbody>
                </table>
            </div>

            @elseif($step['step'] === 'E')
            <!-- 3-itemsets -->
            @php
            $frequentTriplets = collect($step['data'])->where('status', 'kept');
            $hasFrequentTriplets = $frequentTriplets->count() > 0;
            @endphp

            @if($hasFrequentTriplets)
            <!-- Tabel untuk 3-itemsets yang frequent -->
            <div class="overflow-x-auto">
                <table class="min-w-full table-auto">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Itemset</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Frekuensi</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Support (%)</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($step['data'] as $data)
                        @if(isset($data['itemset']) && $data['status'] === 'kept')
                        <tr class="bg-green-50">
                            <td class="px-4 py-2 text-sm font-medium text-gray-900">
                                <div class="flex flex-wrap gap-1 items-center">
                                    @php
                                    $items = explode(', ', trim($data['itemset'], '{}'));
                                    @endphp
                                    @foreach($items as $index => $item)
                                    <span
                                        class="inline-flex px-2 py-1 text-xs bg-purple-100 text-purple-800 rounded-full font-medium">
                                        {{ trim($item) }}
                                    </span>
                                    @if($index < count($items) - 1) <span class="text-gray-400 font-bold">+</span>
                                        @endif
                                        @endforeach
                                </div>
                            </td>
                            <td class="px-4 py-2 text-sm">{{ $data['count'] }}/{{
                                $algorithmSteps['summary']['total_transactions'] }}</td>
                            <td class="px-4 py-2 text-sm">{{ $data['support'] }}%</td>
                            <td class="px-4 py-2 text-sm">
                                <span
                                    class="inline-flex px-2 py-1 text-xs font-semibold bg-green-100 text-green-800 rounded-full">
                                    ✔️ Frequent
                                </span>
                            </td>
                        </tr>
                        @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <!-- Pesan jika tidak ada 3-itemsets yang frequent -->
            <div class="text-center py-8">
                @if(isset($step['data'][0]['note']))
                <!-- Jika tidak ada data untuk diproses -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 inline-block">
                    <h4 class="text-lg font-semibold text-blue-800 mb-2">Tidak Dapat Membentuk 3-Itemsets</h4>
                    <p class="text-blue-700 italic">{{ $step['data'][0]['note'] }}</p>
                </div>
                @else
                <!-- Jika ada data tapi semua tidak memenuhi minimum support -->
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 inline-block">
                    <h4 class="text-lg font-semibold text-yellow-800 mb-2">Tidak Ada 3-Itemset yang Frequent</h4>
                    <p class="text-yellow-700">
                        Tidak ada kombinasi 3-item yang memenuhi minimum support {{ $minSupport }}%
                    </p>
                    <div class="mt-3">
                        <span
                            class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-yellow-100 text-yellow-800">
                            📊 {{ count($step['data']) }} kombinasi telah dievaluasi
                        </span>
                    </div>
                </div>
                @endif
            </div>
            @endif

            @elseif($step['step'] === 'F')
            <!-- Algorithm termination -->
            <div class="text-center py-8">
                <div class="bg-green-50 border border-green-200 rounded-lg p-6 inline-block">
                    <h4 class="text-lg font-semibold text-green-800 mb-4">🏁 Algoritma Selesai</h4>
                    @foreach($step['data'] as $result)
                    <p class="text-green-700 mb-2">✅ {{ $result['result'] }}</p>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>
    @endforeach
</div>

<!-- Association Rules -->
<div class="bg-white rounded-lg shadow-md overflow-hidden mt-8">
    <div class="bg-gradient-to-r from-green-600 to-green-700 text-white p-6">
        <h3 class="text-xl font-semibold">🎯 Aturan Asosiasi yang Dihasilkan</h3>
        <p class="mt-2 text-green-100">Dari frequent itemsets, dibentuk aturan asosiasi dengan confidence ≥ {{
            $minConfidence }}%</p>
    </div>

    <div class="p-6">
        <div class="overflow-x-auto">
            <table class="min-w-full table-auto">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Aturan</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Deskripsi</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Support (%)</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Confidence (%)</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Action</th>
                        {{-- tambahkan action detail untuk kedua produk --}}
                        {{-- <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Lift</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th> --}}
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($algorithmSteps['association_rules'] as $index => $rule)
                    @php
                    $ruleItems = explode(' → ', $rule['rule']);
                    $antecedent = trim($ruleItems[0]);
                    $consequent = trim($ruleItems[1]);
                    @endphp
                    <tr class="{{ $rule['status'] === 'weak' ? 'bg-red-50' : 'bg-green-50' }}">
                        <td class="px-4 py-2 text-sm font-medium text-gray-900">{{ $rule['rule'] }}</td>
                        <td class="px-4 py-2 text-sm text-blue-700 italic">{{ $rule['description'] }}</td>
                        <td class="px-4 py-2 text-sm">{{ $rule['support'] }}%</td>
                        <td class="px-4 py-2 text-sm">{{ $rule['confidence'] }}%</td>
                        <td class="px-4 py-2 text-sm">
                            <button
                                onclick="openProductDetailModal('{{ addslashes($antecedent) }}', '{{ addslashes($consequent) }}', '{{ addslashes($rule['rule']) }}', {{ $rule['support'] }}, {{ $rule['confidence'] }})"
                                class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded-lg text-xs font-medium transition-colors">
                                <i class="fas fa-eye mr-1"></i>Detail
                            </button>
                        </td>
                        {{-- <td class="px-4 py-2 text-sm">{{ $rule['lift'] }}</td>
                        <td class="px-4 py-2 text-sm">
                            @if($rule['status'] === 'strong')
                            <span
                                class="inline-flex px-2 py-1 text-xs font-semibold bg-green-100 text-green-800 rounded-full">
                                💪 Strong (≥ {{ $minConfidence }}%)
                            </span>
                            @else
                            <span
                                class="inline-flex px-2 py-1 text-xs font-semibold bg-red-100 text-red-800 rounded-full">
                                ⚠️ Weak (< {{ $minConfidence }}%) </span>
                                    @endif
                        </td> --}}
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Algorithm Summary -->
<div class="bg-white rounded-lg shadow-md p-6 mt-8">
    <h3 class="text-xl font-semibold text-gray-800 mb-4">📈 Ringkasan Hasil Algoritma</h3>
    <div class="grid md:grid-cols-5 gap-6">
        <div class="text-center">
            <div class="text-3xl font-bold text-blue-600">{{ $algorithmSteps['summary']['total_transactions'] ?? 0 }}
            </div>
            <div class="text-sm text-gray-600">Total Transaksi</div>
        </div>
        <div class="text-center">
            <div class="text-3xl font-bold text-green-600">{{ $algorithmSteps['summary']['frequent_1_itemsets'] ?? 0 }}
            </div>
            <div class="text-sm text-gray-600">Frequent 1-Itemsets</div>
        </div>
        <div class="text-center">
            <div class="text-3xl font-bold text-purple-600">{{ $algorithmSteps['summary']['frequent_2_itemsets'] ?? 0 }}
            </div>
            <div class="text-sm text-gray-600">Frequent 2-Itemsets</div>
        </div>
        <div class="text-center">
            <div class="text-3xl font-bold text-orange-600">{{ $algorithmSteps['summary']['strong_rules'] ?? 0 }}</div>
            <div class="text-sm text-gray-600">Strong Rules</div>
        </div>
        <div class="text-center">
            <div class="text-3xl font-bold text-red-600">
                @if(isset($algorithmSteps['summary']['execution_time_ms']))
                @if($algorithmSteps['summary']['execution_time_ms'] > 1000)
                {{ round($algorithmSteps['summary']['execution_time_ms'] / 1000, 2) }}s
                @else
                {{ $algorithmSteps['summary']['execution_time_ms'] }}ms
                @endif
                @else
                N/A
                @endif
            </div>
            <div class="text-sm text-gray-600">Waktu Eksekusi</div>
        </div>
    </div>
</div>

<!-- Performance Analysis -->
@if(isset($algorithmSteps['summary']['execution_time_ms']))
<div class="bg-gradient-to-r from-cyan-50 to-blue-50 rounded-lg p-6 mt-8 border border-cyan-200 hidden">
    <h3 class="text-xl font-semibold text-cyan-800 mb-4">⚡ Analisis Performa Eksekusi</h3>
    <div class="grid md:grid-cols-3 gap-6">
        <div class="bg-white rounded-lg p-4 shadow-sm">
            <h4 class="font-semibold text-gray-800 mb-2">🕒 Detail Waktu</h4>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-600">Waktu Total:</span>
                    <span class="font-medium" id="totalExecutionTime">
                        @if($algorithmSteps['summary']['execution_time_ms'] > 1000)
                        {{ round($algorithmSteps['summary']['execution_time_ms'] / 1000, 3) }} detik
                        @else
                        {{ $algorithmSteps['summary']['execution_time_ms'] }} ms
                        @endif
                    </span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Per Transaksi:</span>
                    <span class="font-medium">
                        @if($algorithmSteps['summary']['total_transactions'] > 0)
                        {{ round($algorithmSteps['summary']['execution_time_ms'] /
                        $algorithmSteps['summary']['total_transactions'], 2) }} ms
                        @else
                        0 ms
                        @endif
                    </span>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg p-4 shadow-sm">
            <h4 class="font-semibold text-gray-800 mb-2">📊 Kategori Performa</h4>
            <div class="text-center">
                @php
                $execTime = $algorithmSteps['summary']['execution_time_ms'];
                $transactionCount = $algorithmSteps['summary']['total_transactions'];

                if ($execTime < 100) { $category='🚀 Sangat Cepat' ; $color='text-green-600' ; $bgColor='bg-green-100' ;
                    } elseif ($execTime < 500) { $category='⚡ Cepat' ; $color='text-blue-600' ; $bgColor='bg-blue-100' ;
                    } elseif ($execTime < 2000) { $category='⏱️ Normal' ; $color='text-yellow-600' ;
                    $bgColor='bg-yellow-100' ; } else { $category='🐌 Lambat' ; $color='text-red-600' ;
                    $bgColor='bg-red-100' ; } @endphp <span
                    class="inline-flex px-3 py-2 text-sm font-medium rounded-full {{ $bgColor }} {{ $color }}">
                    {{ $category }}
                    </span>
            </div>
        </div>

        <div class="bg-white rounded-lg p-4 shadow-sm">
            <h4 class="font-semibold text-gray-800 mb-2">🎯 Efisiensi</h4>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-600">Kompleksitas:</span>
                    <span class="font-medium">
                        @if($algorithmSteps['summary']['total_transactions'] < 50) Rendah
                            @elseif($algorithmSteps['summary']['total_transactions'] < 200) Sedang @else Tinggi @endif
                            </span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Throughput:</span>
                    <span class="font-medium">
                        @if($algorithmSteps['summary']['execution_time_ms'] > 0 &&
                        $algorithmSteps['summary']['total_transactions'] > 0)
                        {{ round($algorithmSteps['summary']['total_transactions'] /
                        ($algorithmSteps['summary']['execution_time_ms'] / 1000), 0) }} txn/s
                        @else
                        0 txn/s
                        @endif
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed Step Timings -->
    @if(isset($algorithmSteps['summary']['step_timings']) && !empty($algorithmSteps['summary']['step_timings']))
    <div class="mt-6">
        <h4 class="font-semibold text-gray-800 mb-3">📋 Breakdown Waktu per Langkah</h4>
        <div class="bg-gray-50 rounded-lg p-4">
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-3">
                @if(isset($algorithmSteps['summary']['step_timings']['step1']))
                <div class="flex justify-between items-center bg-white p-2 rounded text-sm">
                    <span class="text-gray-600">Scan & Count Singles:</span>
                    <span class="font-medium text-blue-600">{{ $algorithmSteps['summary']['step_timings']['step1']
                        }}ms</span>
                </div>
                @endif

                @if(isset($algorithmSteps['summary']['step_timings']['step2']))
                <div class="flex justify-between items-center bg-white p-2 rounded text-sm">
                    <span class="text-gray-600">Prune Items:</span>
                    <span class="font-medium text-green-600">{{ $algorithmSteps['summary']['step_timings']['step2']
                        }}ms</span>
                </div>
                @endif

                @if(isset($algorithmSteps['summary']['step_timings']['step4']))
                <div class="flex justify-between items-center bg-white p-2 rounded text-sm">
                    <span class="text-gray-600">Count 2-Itemsets:</span>
                    <span class="font-medium text-purple-600">{{ $algorithmSteps['summary']['step_timings']['step4']
                        }}ms</span>
                </div>
                @endif

                @if(isset($algorithmSteps['summary']['step_timings']['step5']))
                <div class="flex justify-between items-center bg-white p-2 rounded text-sm">
                    <span class="text-gray-600">Generate 3-Itemsets:</span>
                    <span class="font-medium text-orange-600">{{ $algorithmSteps['summary']['step_timings']['step5']
                        }}ms</span>
                </div>
                @endif

                @if(isset($algorithmSteps['summary']['step_timings']['rules']))
                <div class="flex justify-between items-center bg-white p-2 rounded text-sm">
                    <span class="text-gray-600">Generate Rules:</span>
                    <span class="font-medium text-red-600">{{ $algorithmSteps['summary']['step_timings']['rules']
                        }}ms</span>
                </div>
                @endif

                @if(isset($algorithmSteps['summary']['algorithm_time_ms']))
                <div class="flex justify-between items-center bg-blue-100 p-2 rounded text-sm border-2 border-blue-200">
                    <span class="text-blue-800 font-medium">Total Algorithm:</span>
                    <span class="font-bold text-blue-800">{{ $algorithmSteps['summary']['algorithm_time_ms'] }}ms</span>
                </div>
                @endif
            </div>
        </div>
    </div>
    @endif
</div>
@endif
@endif

<!-- Key Insights -->
{{-- <div class="bg-gradient-to-r from-indigo-50 to-purple-50 rounded-lg p-6 mt-8 border border-indigo-200">
    <h3 class="text-xl font-semibold text-indigo-800 mb-4">💡 Key Insights</h3>
    <div class="grid md:grid-cols-2 gap-6">
        <div>
            <h4 class="font-semibold text-gray-800 mb-2">🎯 Frequent Itemsets</h4>
            <ul class="text-gray-700 space-y-1">
                <li>• <strong>Sepatu bola ortus</strong> muncul di 70% transaksi</li>
                <li>• <strong>Kaos kaki avo</strong> muncul di 80% transaksi</li>
                <li>• Kombinasi keduanya muncul di 50% transaksi</li>
            </ul>
        </div>
        <div>
            <h4 class="font-semibold text-gray-800 mb-2">🔗 Association Rules</h4>
            <ul class="text-gray-700 space-y-1">
                <li>• Sepatu bola → Kaos kaki: 71.4% confidence</li>
                <li>• Kaos kaki → Sepatu bola: 62.5% confidence</li>
                <li>• Kedua aturan menunjukkan asosiasi yang kuat</li>
            </ul>
        </div>
    </div>
</div> --}}

<!-- Product Detail Modal -->
<div id="productDetailModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <!-- Modal Header -->
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-900" id="modalTitle">Detail Produk Asosiasi</h3>
                <button onclick="closeProductDetailModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg>
                </button>
            </div>

            <!-- Association Rule Info -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                <h4 class="font-semibold text-blue-800 mb-2">📊 Informasi Aturan Asosiasi</h4>
                <div class="grid md:grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="font-medium text-gray-700">Aturan:</span>
                        <span id="modalRule" class="text-blue-600 font-medium ml-1"></span>
                    </div>
                    <div>
                        <span class="font-medium text-gray-700">Support:</span>
                        <span id="modalSupport" class="text-green-600 font-medium ml-1"></span>
                    </div>
                    <div class="md:col-span-2">
                        <span class="font-medium text-gray-700">Confidence:</span>
                        <span id="modalConfidence" class="text-purple-600 font-medium ml-1"></span>
                    </div>
                </div>
            </div>

            <!-- Products Cards -->
            <div class="grid md:grid-cols-2 gap-6">
                <!-- Antecedent Product -->
                <div class="bg-gradient-to-br from-green-50 to-green-100 border border-green-200 rounded-lg p-6">
                    <div class="flex items-center mb-4">
                        <div
                            class="bg-green-500 text-white rounded-full w-8 h-8 flex items-center justify-center text-sm font-bold mr-3">
                            A
                        </div>
                        <h5 class="text-lg font-semibold text-green-800">Produk Antecedent</h5>
                    </div>
                    <div class="space-y-3">
                        <div class="bg-white rounded-lg p-4 shadow-sm">
                            <!-- Product Image -->
                            <div class="flex justify-center mb-3">
                                <div class="relative w-20 h-20">
                                    <div id="antecedentLoader"
                                        class="absolute inset-0 flex items-center justify-center bg-green-50 rounded-lg border-2 border-green-200 z-10">
                                        <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-green-500">
                                        </div>
                                    </div>
                                    <img id="antecedentImage" src="" alt="Product Image"
                                        class="w-20 h-20 object-cover rounded-lg border-2 border-green-200 shadow-sm opacity-0 transition-opacity duration-500"
                                        style="display: none;">
                                </div>
                            </div>
                            <h6 id="antecedentName" class="font-medium text-gray-900 mb-2 text-center"></h6>
                            <div class="text-sm text-gray-600">
                                <p><span class="font-medium">Kategori:</span> <span class="text-green-600">Olahraga &
                                        Outdoor</span></p>
                                <p><span class="font-medium">Status:</span> <span class="text-green-600">Tersedia</span>
                                </p>
                                <p><span class="font-medium">Peran:</span> Produk yang dibeli terlebih dahulu</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Consequent Product -->
                <div class="bg-gradient-to-br from-blue-50 to-blue-100 border border-blue-200 rounded-lg p-6">
                    <div class="flex items-center mb-4">
                        <div
                            class="bg-blue-500 text-white rounded-full w-8 h-8 flex items-center justify-center text-sm font-bold mr-3">
                            C
                        </div>
                        <h5 class="text-lg font-semibold text-blue-800">Produk Consequent</h5>
                    </div>
                    <div class="space-y-3">
                        <div class="bg-white rounded-lg p-4 shadow-sm">
                            <!-- Product Image -->
                            <div class="flex justify-center mb-3">
                                <div class="relative w-20 h-20">
                                    <div id="consequentLoader"
                                        class="absolute inset-0 flex items-center justify-center bg-blue-50 rounded-lg border-2 border-blue-200 z-10">
                                        <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-500"></div>
                                    </div>
                                    <img id="consequentImage" src="" alt="Product Image"
                                        class="w-20 h-20 object-cover rounded-lg border-2 border-blue-200 shadow-sm opacity-0 transition-opacity duration-500"
                                        style="display: none;">
                                </div>
                            </div>
                            <h6 id="consequentName" class="font-medium text-gray-900 mb-2 text-center"></h6>
                            <div class="text-sm text-gray-600">
                                <p><span class="font-medium">Kategori:</span> <span class="text-blue-600">Olahraga &
                                        Outdoor</span></p>
                                <p><span class="font-medium">Status:</span> <span class="text-blue-600">Tersedia</span>
                                </p>
                                <p><span class="font-medium">Peran:</span> Produk yang direkomendasikan</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Business Insight -->
            <div class="mt-6 bg-amber-50 border border-amber-200 rounded-lg p-4">
                <h5 class="font-semibold text-amber-800 mb-2">💡 Insight Bisnis</h5>
                <p id="businessInsight" class="text-sm text-amber-700"></p>
            </div>

            <!-- Modal Footer -->
            <div class="flex justify-end mt-6">
                <button onclick="closeProductDetailModal()"
                    class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    // Product data from backend
    const products = @json($products);

    function openProductDetailModal(antecedent, consequent, rule, support, confidence) {
    // Set modal content
    document.getElementById('modalRule').textContent = rule;
    document.getElementById('modalSupport').textContent = support + '%';
    document.getElementById('modalConfidence').textContent = confidence + '%';
    document.getElementById('antecedentName').textContent = antecedent;
    document.getElementById('consequentName').textContent = consequent;

    // Get image elements and loaders
    const antecedentImage = document.getElementById('antecedentImage');
    const consequentImage = document.getElementById('consequentImage');
    const antecedentLoader = document.getElementById('antecedentLoader');
    const consequentLoader = document.getElementById('consequentLoader');

    // Reset and show loaders first
    antecedentLoader.style.display = 'flex';
    consequentLoader.style.display = 'flex';
    antecedentImage.style.display = 'none';
    consequentImage.style.display = 'none';
    antecedentImage.style.opacity = '0';
    consequentImage.style.opacity = '0';

    // Function to handle image loading with better error handling
    function loadImageSafe(imgElement, loaderElement, product, fallbackText, color) {
        return new Promise((resolve) => {
            // Determine image source
            let imageSource;
            if (product && product.image_path && product.image_path.trim() !== '') {
                imageSource = `/storage/${product.image_path}`;
            } else {
                imageSource = `https://via.placeholder.com/120x120/${color}/white?text=${encodeURIComponent(fallbackText)}`;
            }

            const tempImage = new Image();

            tempImage.onload = function() {
                // Image loaded successfully
                imgElement.src = this.src;
                imgElement.style.display = 'block';

                // Small delay to ensure image is rendered before showing
                requestAnimationFrame(() => {
                    imgElement.style.opacity = '1';
                    loaderElement.style.display = 'none';
                    resolve();
                });
            };

            tempImage.onerror = function() {
                // Fallback to placeholder
                const fallbackUrl = `https://via.placeholder.com/120x120/${color}/white?text=${encodeURIComponent(fallbackText)}`;
                imgElement.src = fallbackUrl;
                imgElement.style.display = 'block';

                requestAnimationFrame(() => {
                    imgElement.style.opacity = '1';
                    loaderElement.style.display = 'none';
                    resolve();
                });
            };

            // Start loading
            tempImage.src = imageSource;
        });
    }

    // Get product data
    const antecedentProduct = products[antecedent];
    const consequentProduct = products[consequent];

    // Set alt attributes
    antecedentImage.alt = `${antecedent} Product Image`;
    consequentImage.alt = `${consequent} Product Image`;

    // Load images sequentially to prevent race conditions
    loadImageSafe(
        antecedentImage,
        antecedentLoader,
        antecedentProduct,
        antecedent.substring(0, 2).toUpperCase(),
        '10B981'
    ).then(() => {
        return loadImageSafe(
            consequentImage,
            consequentLoader,
            consequentProduct,
            consequent.substring(0, 2).toUpperCase(),
            '3B82F6'
        );
    }).then(() => {
        console.log('All images loaded successfully');
    }).catch((error) => {
        console.error('Error loading images:', error);
    });

    // Set business insight
    const insight = `Pelanggan yang membeli "${antecedent}" memiliki kemungkinan ${confidence}% untuk juga membeli "${consequent}". Kombinasi kedua produk ini muncul dalam ${support}% dari total transaksi, menunjukkan pola pembelian yang kuat dan dapat digunakan untuk strategi cross-selling.`;
    document.getElementById('businessInsight').textContent = insight;

    // Show modal
    document.getElementById('productDetailModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeProductDetailModal() {
    // Reset image states completely
    const antecedentImage = document.getElementById('antecedentImage');
    const consequentImage = document.getElementById('consequentImage');
    const antecedentLoader = document.getElementById('antecedentLoader');
    const consequentLoader = document.getElementById('consequentLoader');

    // Completely reset images
    antecedentImage.src = '';
    consequentImage.src = '';
    antecedentImage.style.display = 'none';
    consequentImage.style.display = 'none';
    antecedentImage.style.opacity = '0';
    consequentImage.style.opacity = '0';

    // Show loaders for next time
    antecedentLoader.style.display = 'flex';
    consequentLoader.style.display = 'flex';

    // Hide modal
    document.getElementById('productDetailModal').classList.add('hidden');
    document.body.style.overflow = 'auto';
}

// Close modal when clicking outside
document.getElementById('productDetailModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeProductDetailModal();
    }
});

// Close modal with ESC key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeProductDetailModal();
    }
});

// Date input enhancement
document.addEventListener('DOMContentLoaded', function() {
    // Initialize hidden input if "all dates" is already checked
    const allDatesCheckbox = document.getElementById('allDatesCheckbox');
    if (allDatesCheckbox && allDatesCheckbox.checked) {
        toggleDateInput(allDatesCheckbox);
    }
    console.log('Apriori analysis page loaded');
});
</script>
</div>
@endsection
