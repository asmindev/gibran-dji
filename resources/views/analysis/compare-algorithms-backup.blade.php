@extends('layouts.app')

@section('title', 'Perbandingan Apriori vs FP-Growth')

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
    .comparison-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
    }
    @media (max-width: 768px) {
        .comparison-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
<div class="container mx-auto px-4 py-6">
    <h1 class="text-3xl font-bold text-gray-800 mb-2">⚖️ Perbandingan Algoritma: Apriori vs FP-Growth</h1>
    <p class="text-gray-600 mb-6">Analisis mendalam dan perbandingan side-by-side kedua algoritma</p>

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
                <div class="text-xs text-gray-500 mt-1">Rekomendasi: 20-30%</div>
            </div>
            <div class="flex-none w-full lg:w-48">
                <label class="block text-sm font-medium text-gray-700 mb-2">Minimum Confidence (%)</label>
                <input type="number" name="min_confidence" value="{{ $minConfidence }}" min="1" max="100"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md">
                <div class="text-xs text-gray-500 mt-1">Rekomendasi: 50-60%</div>
            </div>
            <div class="flex flex-col lg:items-end">
                <label class="block text-sm font-medium text-gray-700 mb-2">&nbsp;</label>
                <button type="submit"
                    class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 rounded-md whitespace-nowrap">
                    <i class="fas fa-sync-alt mr-2"></i>Bandingkan
                </button>
            </div>
        </form>

        <!-- Quick Test Buttons -->
        @if(!$hasCalculation)
        <div class="mt-4 pt-4 border-t border-gray-200">
            <div class="text-sm text-gray-600 mb-2">🚀 <strong>Quick Test:</strong></div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('analysis.compare') }}?transaction_date=2025-09-27&min_support=20&min_confidence=50"
                    class="text-xs bg-blue-100 text-blue-700 px-3 py-1 rounded hover:bg-blue-200">
                    27 Sep 2025 (20 tx, Support 20%, Confidence 50%)
                </a>
                <a href="{{ route('analysis.compare') }}?transaction_date=2025-10-15&min_support=25&min_confidence=55"
                    class="text-xs bg-green-100 text-green-700 px-3 py-1 rounded hover:bg-green-200">
                    15 Oct 2025 (20 tx, Support 25%, Confidence 55%)
                </a>
                <a href="{{ route('analysis.compare') }}?transaction_date=2025-09-08&min_support=30&min_confidence=60"
                    class="text-xs bg-purple-100 text-purple-700 px-3 py-1 rounded hover:bg-purple-200">
                    8 Sep 2025 (20 tx, Support 30%, Confidence 60%)
                </a>
            </div>
        </div>
        @endif
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
            <div class="bg-blue-50 p-4 rounded-lg">
                <div class="text-sm text-blue-600">Apriori Time</div>
                <div class="text-2xl font-bold text-blue-700">{{ $aprioriSteps['summary']['execution_time_ms'] ?? 0 }}
                    ms</div>
            </div>
            <div class="bg-green-50 p-4 rounded-lg">
                <div class="text-sm text-green-600">FP-Growth Time</div>
                <div class="text-2xl font-bold text-green-700">{{ $fpGrowthSteps['summary']['execution_time_ms'] ?? 0 }}
                    ms</div>
            </div>
            <div class="bg-purple-50 p-4 rounded-lg">
                <div class="text-sm text-purple-600">Speedup</div>
                <div class="text-2xl font-bold text-purple-700">
                    @php
                    $apt = $aprioriSteps['summary']['execution_time_ms'] ?? 1;
                    $fpt = $fpGrowthSteps['summary']['execution_time_ms'] ?? 1;
                    echo $apt > 0 && $fpt > 0 ? round($apt / $fpt, 2) : 0;
                    @endphp x
                </div>
            </div>
            <div class="bg-orange-50 p-4 rounded-lg">
                <div class="text-sm text-orange-600">Transaksi</div>
                <div class="text-2xl font-bold text-orange-700">{{ count($sampleTransactions) }}</div>
            </div>
        </div>
    </div>

    <!-- Association Rules Comparison -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">📋 Association Rules</h2>
        <div class="grid md:grid-cols-2 gap-6">
            <!-- Apriori -->
            <div>
                <h3 class="font-bold text-blue-700 mb-3 flex items-center justify-between">
                    <span>Apriori</span>
                    <span class="text-sm bg-blue-100 px-2 py-1 rounded">{{ count($aprioriSteps['association_rules'] ??
                        []) }} rules</span>
                </h3>
                <div class="space-y-2 max-h-96 overflow-y-auto">
                    @forelse($aprioriSteps['association_rules'] ?? [] as $rule)
                    <div class="bg-blue-50 border border-blue-200 rounded p-3">
                        <div class="font-medium">{{ $rule['rule'] }}</div>
                        <div class="text-xs text-gray-600 mt-1">
                            Support: {{ $rule['support'] }}% | Confidence: {{ $rule['confidence'] }}% | Lift: {{
                            $rule['lift'] ?? 'N/A' }}
                        </div>
                    </div>
                    @empty
                    <p class="text-gray-500">Tidak ada rules</p>
                    @endforelse
                </div>
            </div>
            <!-- FP-Growth -->
            <div>
                <h3 class="font-bold text-green-700 mb-3 flex items-center justify-between">
                    <span>FP-Growth</span>
                    <span class="text-sm bg-green-100 px-2 py-1 rounded">{{ count($fpGrowthSteps['association_rules'] ??
                        []) }} rules</span>
                </h3>
                <div class="space-y-2 max-h-96 overflow-y-auto">
                    @forelse($fpGrowthSteps['association_rules'] ?? [] as $rule)
                    <div class="bg-green-50 border border-green-200 rounded p-3">
                        <div class="font-medium">{{ $rule['rule'] }}</div>
                        <div class="text-xs text-gray-600 mt-1">
                            Support: {{ $rule['support'] }}% | Confidence: {{ $rule['confidence'] }}% | Lift: {{
                            $rule['lift'] ?? 'N/A' }}
                        </div>
                    </div>
                    @empty
                    <p class="text-gray-500">Tidak ada rules</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
    @else
    <div class="bg-gray-50 rounded-lg p-8 text-center">
        <i class="fas fa-info-circle text-gray-400 text-4xl mb-3"></i>
        <h3 class="text-lg font-semibold text-gray-700">Pilih tanggal untuk memulai perbandingan</h3>
    </div>
    @endif
</div>
@endsection
