@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<!-- Simple Header -->
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
    <p class="text-gray-600 mt-1">Ringkasan sistem manajemen inventory Anda</p>
</div>
<div class="bg-primary rounded-2xl overflow-hidden p-4">

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <!-- Total Items Card -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-600">Total Items</p>
                    <p class="text-xl font-bold text-gray-900">{{ $totalItems }}</p>
                </div>
            </div>
        </div>

        <!-- Total Categories Card -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="w-10 h-10 bg-green-500 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10">
                        </path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-600">Total Kategori</p>
                    <p class="text-xl font-bold text-gray-900">{{ $totalCategories }}</p>
                </div>
            </div>
        </div>

        <!-- Low Stock Items Card -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="w-10 h-10 bg-red-500 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z">
                        </path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-600">Stok Menipis</p>
                    <p class="text-xl font-bold text-red-600">{{ $lowStockItems->count() }}</p>
                </div>
            </div>
        </div>

        <!-- Total Stock Card -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="w-10 h-10 bg-indigo-500 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                        </path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-600">Total Stok</p>
                    <p class="text-xl font-bold text-gray-900">{{ $latestItems->sum('stock') }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 gap-y-6">
        <!-- Apriori Analysis Chart -->
        <div class="bg-white shadow rounded-lg">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Analisis Apriori</h3>
                <p class="text-sm text-gray-600 mt-1">Association rules dengan confidence dan support tertinggi</p>
            </div>
            <div class="p-6">
                @if($aprioriData->count() > 0)
                <div class="relative h-64">
                    <canvas id="aprioriAnalysisChart"></canvas>
                </div>
                @else
                <div class="text-center py-8">
                    <p class="text-gray-500 mb-2">Belum ada data analisis Apriori</p>
                    <a href="{{ route('analysis.apriori-process') }}"
                        class="text-blue-600 hover:text-blue-700 text-sm font-medium">
                        Mulai analisis Apriori
                    </a>
                </div>
                @endif
            </div>
        </div>

        <!-- Monthly Transactions Chart -->
        <div class="bg-white shadow rounded-lg">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Transaksi Bulanan</h3>
                <p class="text-sm text-gray-600 mt-1">Barang masuk vs keluar bulan ini</p>
            </div>
            <div class="p-6">
                <div class="relative h-64">
                    <canvas id="monthlyTransactionsChart"></canvas>
                </div>

                <!-- Net Change Summary -->
                <div class="mt-6 pt-4 border-t border-gray-200">
                    @php
                    $netChange = $monthlyIncoming - $monthlyOutgoing;
                    @endphp
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-700">Perubahan Bersih Bulan Ini</span>
                        <span class="text-sm font-semibold {{ $netChange >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ $netChange >= 0 ? '+' : '' }}{{ number_format($netChange) }}
                        </span>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">
                        {{ now()->format('F Y') }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>


<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
    // Common chart options
    const commonOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                titleColor: '#fff',
                bodyColor: '#fff',
                borderColor: 'rgba(255, 255, 255, 0.1)',
                borderWidth: 1,
                cornerRadius: 8,
                displayColors: false,
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1,
                    callback: function(value) {
                        return Number.isInteger(value) ? value : '';
                    },
                    color: '#6b7280',
                    font: {
                        size: 12
                    }
                },
                grid: {
                    color: 'rgba(229, 231, 235, 0.8)',
                    drawBorder: false
                }
            },
            x: {
                ticks: {
                    color: '#6b7280',
                    font: {
                        size: 12
                    }
                },
                grid: {
                    display: false
                }
            }
        },
        layout: {
            padding: {
                top: 10,
                bottom: 10,
                left: 10,
                right: 10
            }
        }
    };

    // Apriori Analysis Chart
    @if($aprioriData->count() > 0)
    const aprioriAnalysisCtx = document.getElementById('aprioriAnalysisChart').getContext('2d');

    const aprioriAnalysisChart = new Chart(aprioriAnalysisCtx, {
        type: 'bar',
        data: {
            labels: {!! json_encode($aprioriData->pluck('label')) !!},
            datasets: [
                {
                    label: 'Confidence',
                    data: {!! json_encode($aprioriData->pluck('confidence')) !!},
                    backgroundColor: '#ef444420',
                    borderColor: '#ef4444',
                    borderWidth: 2,
                    borderRadius: 6,
                    borderSkipped: false,
                },
                {
                    label: 'Support',
                    data: {!! json_encode($aprioriData->pluck('support')) !!},
                    backgroundColor: '#3b82f620',
                    borderColor: '#3b82f6',
                    borderWidth: 2,
                    borderRadius: 6,
                    borderSkipped: false,
                }
            ]
        },
        options: {
            ...commonOptions,
            plugins: {
                ...commonOptions.plugins,
                legend: {
                    display: true,
                    position: 'top',
                    labels: {
                        usePointStyle: true,
                        padding: 20,
                        font: {
                            size: 12
                        }
                    }
                },
                tooltip: {
                    ...commonOptions.plugins.tooltip,
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + context.parsed.y + '%';
                        }
                    }
                }
            },
            scales: {
                ...commonOptions.scales,
                y: {
                    ...commonOptions.scales.y,
                    max: 100,
                    ticks: {
                        ...commonOptions.scales.y.ticks,
                        callback: function(value) {
                            return value + '%';
                        }
                    }
                }
            }
        }
    });
    @endif

    // Monthly Transactions Chart
    const monthlyTransactionsCtx = document.getElementById('monthlyTransactionsChart').getContext('2d');
    const monthlyTransactionsChart = new Chart(monthlyTransactionsCtx, {
        type: 'bar',
        data: {
            labels: ['Incoming', 'Outgoing'],
            datasets: [{
                label: 'Transactions',
                data: [{{ $monthlyIncoming }}, {{ $monthlyOutgoing }}],
                backgroundColor: ['#10b98120', '#ef444420'],
                borderColor: ['#10b981', '#ef4444'],
                borderWidth: 2,
                borderRadius: 6,
                borderSkipped: false,
            }]
        },
        options: {
            ...commonOptions,
            plugins: {
                ...commonOptions.plugins,
                tooltip: {
                    ...commonOptions.plugins.tooltip,                callbacks: {
                    label: function(context) {
                        const label = context.label;
                        const value = context.parsed.y;
                        return `${label}: ${value} item`;
                    }
                }
                }
            }
        }
    });
});
</script>
@endsection
