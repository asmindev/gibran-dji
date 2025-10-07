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
        <!-- Stock Analysis Chart -->
        <div class="bg-white shadow rounded-lg">
            <div class="p-6 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Analisis Stok Bulanan</h3>
                        <p class="text-sm text-gray-600 mt-1">Perbandingan prediksi vs aktual untuk bulan {{
                            \Carbon\Carbon::parse($selectedPredictionMonth . '-01')->format('F Y') }}</p>
                    </div>
                    @if($availablePredictionMonths->count() > 0)
                    <div class="flex items-center space-x-2">
                        <label for="predictionMonthFilter" class="text-sm font-medium text-gray-700">Bulan:</label>
                        <select id="predictionMonthFilter" name="prediction_month"
                            class="text-sm border border-gray-300 rounded-md px-3 py-1 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            onchange="filterByPredictionMonth()">
                            @foreach($availablePredictionMonths as $month)
                            <option value="{{ $month['value'] }}" {{ $selectedPredictionMonth===$month['value']
                                ? 'selected' : '' }}>
                                {{ $month['label'] }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                </div>
            </div>
            <div class="p-6">
                @if($availablePredictionMonths->count() > 0)
                @if(!empty($lineChartData['labels']))
                <div class="relative h-64">
                    <canvas id="monthlyAnalysisChart"></canvas>
                </div>
                <div class="mt-4">
                    <div class="grid grid-cols-2 gap-4 text-center">
                        <div class="bg-blue-50 p-3 rounded-lg">
                            <p class="text-xs text-blue-600 font-medium">Total Prediksi (Sales + Restock)</p>
                            <p class="text-lg font-bold text-blue-700">{{ number_format($totalPrediction ?? 0) }} unit
                            </p>
                            <p class="text-xs text-gray-500 mt-1">Prediksi: {{ number_format($totalPredictedSales ?? 0)
                                }} sales + {{ number_format($totalPredictedRestock ?? 0) }} restock</p>
                        </div>
                        <div class="bg-red-50 p-3 rounded-lg">
                            <p class="text-xs text-red-600 font-medium">Total Aktual</p>
                            <p class="text-lg font-bold text-red-700">{{ number_format(($totalActualSales ?? 0) +
                                ($totalActualRestock ?? 0)) }} unit</p>
                            <p class="text-xs text-gray-500 mt-1">{{ number_format($totalActualSales ?? 0) }} penjualan
                                + {{ number_format($totalActualRestock ?? 0) }} restock</p>
                        </div>
                    </div>
                    @if($overallAccuracy !== null)
                    <div
                        class="mt-4 bg-gradient-to-r from-green-50 to-emerald-50 p-4 rounded-lg border border-green-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-green-700 font-medium">Akurasi Keseluruhan</p>
                                <p class="text-xs text-green-600 mt-1">Tingkat akurasi prediksi vs aktual</p>
                            </div>
                            <div class="text-right">
                                <p class="text-3xl font-bold text-green-700">{{ number_format($overallAccuracy, 1) }}%
                                </p>
                                <p class="text-xs text-green-600 mt-1">
                                    @if($overallAccuracy >= 85)
                                    <span
                                        class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        Sangat Baik
                                    </span>
                                    @elseif($overallAccuracy >= 70)
                                    <span
                                        class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        Baik
                                    </span>
                                    @elseif($overallAccuracy >= 50)
                                    <span
                                        class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                        Cukup
                                    </span>
                                    @else
                                    <span
                                        class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        Perlu Ditingkatkan
                                    </span>
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>
                    @else
                    <div class="mt-4 bg-gray-50 p-4 rounded-lg border border-gray-200">
                        <p class="text-sm text-gray-600 text-center">Akurasi belum dapat dihitung. Data aktual belum
                            tersedia.</p>
                    </div>
                    @endif
                    @if($totalPrediction == 0)
                    <p class="text-xs text-gray-400 mt-2">Tidak ada data prediksi untuk bulan ini.</p>
                    @endif
                </div>
                @else
                <div class="text-center py-8">
                    <p class="text-gray-500 mb-2">Tidak ada data untuk bulan ini</p>
                </div>
                @endif
                @else
                <div class="text-center py-8">
                    <p class="text-gray-500 mb-2">Tidak ada data transaksi (incoming atau outgoing) tersedia</p>
                </div>
                @endif
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
                display: true,
                position: 'top',
                labels: {
                    padding: 20,
                    font: {
                        size: 12
                    }
                }
            },
            tooltip: {
                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                titleColor: '#fff',
                bodyColor: '#fff',
                borderColor: 'rgba(255, 255, 255, 0.1)',
                borderWidth: 1,
                cornerRadius: 8,
                displayColors: true,
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
                },
                title: {
                    display: true,
                    text: 'Jumlah Unit',
                    color: '#6b7280',
                    font: {
                        size: 12,
                        weight: 'normal'
                    }
                }
            },
            x: {
                ticks: {
                    color: '#6b7280',
                    font: {
                        size: 11
                    },
                    maxRotation: 45,
                    minRotation: 0
                },
                grid: {
                    display: false
                },
                title: {
                    display: true,
                    text: 'Produk',
                    color: '#6b7280',
                    font: {
                        size: 12,
                        weight: 'normal'
                    }
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

    // Monthly Analysis Line Chart
    @if($availablePredictionMonths->count() > 0 && !empty($lineChartData['labels']))
        const monthlyAnalysisCtx = document.getElementById('monthlyAnalysisChart');
        if (monthlyAnalysisCtx) {
            const chartData = {!! json_encode($lineChartData) !!};

            // Configure datasets
            chartData.datasets.forEach((dataset, index) => {
                dataset.borderWidth = 2;
                dataset.fill = false;
                dataset.tension = 0.3;
                dataset.pointRadius = 4;
                dataset.pointHoverRadius = 6;
                dataset.pointBorderWidth = 2;
                dataset.pointHoverBorderWidth = 2;

                if (dataset.label === 'Total Prediksi') {
                    dataset.pointBackgroundColor = '#3b82f6';
                    dataset.pointBorderColor = '#ffffff';
                    dataset.pointHoverBackgroundColor = '#2563eb';
                    dataset.pointHoverBorderColor = '#ffffff';
                } else if (dataset.label === 'Sales Aktual') {
                    dataset.pointBackgroundColor = '#ef4444';
                    dataset.pointBorderColor = '#ffffff';
                    dataset.pointHoverBackgroundColor = '#dc2626';
                    dataset.pointHoverBorderColor = '#ffffff';
                }
            });

            const monthlyAnalysisChart = new Chart(monthlyAnalysisCtx.getContext('2d'), {
                type: 'line',
                data: chartData,
                options: {
                    ...commonOptions,
                    plugins: {
                        ...commonOptions.plugins,
                        tooltip: {
                            ...commonOptions.plugins.tooltip,
                            callbacks: {
                                title: function(context) {
                                    return `Produk: ${context[0].label}`;
                                },
                                label: function(context) {
                                    const value = context.parsed.y;
                                    return `${context.dataset.label}: ${value} unit`;
                                }
                            }
                        }
                    }
                }
            });
        }
    @endif
});

// Function to filter prediction chart by month
function filterByPredictionMonth() {
    const selectedMonth = document.getElementById('predictionMonthFilter').value;
    const currentUrl = new URL(window.location);
    currentUrl.searchParams.set('prediction_month', selectedMonth);
    window.location.href = currentUrl.toString();
}
</script>
@endsection
