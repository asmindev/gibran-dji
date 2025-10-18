@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<!-- Simple Header -->
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
    <p class="text-gray-600 mt-1">Ringkasan sistem manajemen inventory Anda</p>
</div>
<div class="bg-primary rounded-2xl overflow-hidden p-4">


    <!-- Row 2: Charts Section - 2 Column Layout -->
    <div class="space-y-5">
        <!-- Apriori Analysis Chart -->
        <div class="bg-white shadow rounded-lg">
            <div class="p-6 border-b border-gray-200">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Analisis Apriori</h3>
                    <p class="text-sm text-gray-600 mt-1">Top 10 aturan asosiasi berdasarkan Support & Confidence</p>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Statistik Apriori</h3>
                        <div class="w-12 h-12 bg-blue-500 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                                </path>
                            </svg>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-blue-50 p-4 rounded-lg">
                            <p class="text-sm text-blue-600 font-medium mb-1">Total Aturan</p>
                            <p class="text-3xl font-bold text-blue-700">{{ $totalAprioriRules }}</p>
                        </div>
                        <div class="bg-purple-50 p-4 rounded-lg">
                            <p class="text-sm text-purple-600 font-medium mb-1">Rata-rata Confidence</p>
                            <p class="text-3xl font-bold text-purple-700">{{ $avgAprioriConfidence ?
                                number_format($avgAprioriConfidence, 1) : 0 }}%</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="p-6">
                @if(!empty($aprioriChartData['labels']))
                <div class="relative" style="height: 500px;">
                    <canvas id="aprioriChart"></canvas>
                </div>
                @else
                <div class="text-center py-8">
                    <p class="text-gray-500 mb-2">Tidak ada data Apriori tersedia</p>
                    <p class="text-sm text-gray-400">Silakan jalankan analisis Apriori terlebih dahulu</p>
                </div>
                @endif
            </div>
        </div>

        <!-- FP-Growth Analysis Chart -->
        <div class="bg-white shadow rounded-lg">
            <div class="p-6 border-b border-gray-200">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Analisis FP-Growth</h3>
                    <p class="text-sm text-gray-600 mt-1">Top 10 aturan asosiasi berdasarkan Support & Confidence</p>
                </div>
                <!-- FP-Growth Stats Card -->
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Statistik FP-Growth</h3>
                        <div class="w-12 h-12 bg-green-500 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z">
                                </path>
                            </svg>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-green-50 p-4 rounded-lg">
                            <p class="text-sm text-green-600 font-medium mb-1">Total Aturan</p>
                            <p class="text-3xl font-bold text-green-700">{{ $totalFpGrowthRules }}</p>
                        </div>
                        <div class="bg-indigo-50 p-4 rounded-lg">
                            <p class="text-sm text-indigo-600 font-medium mb-1">Rata-rata Confidence</p>
                            <p class="text-3xl font-bold text-indigo-700">{{ $avgFpGrowthConfidence ?
                                number_format($avgFpGrowthConfidence, 1) : 0 }}%</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="p-6">
                @if(!empty($fpGrowthChartData['labels']))
                <div class="relative" style="height: 500px;">
                    <canvas id="fpGrowthChart"></canvas>
                </div>
                @else
                <div class="text-center py-8">
                    <p class="text-gray-500 mb-2">Tidak ada data FP-Growth tersedia</p>
                    <p class="text-sm text-gray-400">Silakan jalankan analisis FP-Growth terlebih dahulu</p>
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
    // Common chart options for bar charts
    const commonBarOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: true,
                position: 'top',
                labels: {
                    padding: 20,
                    font: {
                        size: 13
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
                padding: 12,
                titleFont: {
                    size: 14
                },
                bodyFont: {
                    size: 13
                },
                callbacks: {
                    label: function(context) {
                        const label = context.dataset.label || '';
                        const value = context.parsed.y;
                        return `${label}: ${value.toFixed(2)}%`;
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                max: 100,
                ticks: {
                    callback: function(value) {
                        return value + '%';
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
                    text: 'Persentase (%)',
                    color: '#374151',
                    font: {
                        size: 13,
                        weight: '600'
                    },
                    padding: {top: 0, bottom: 10}
                }
            },
            x: {
                ticks: {
                    color: '#6b7280',
                    font: {
                        size: 11
                    },
                    maxRotation: 45,
                    minRotation: 45,
                    autoSkip: false
                },
                grid: {
                    display: false
                },
                title: {
                    display: true,
                    text: 'Aturan Asosiasi',
                    color: '#374151',
                    font: {
                        size: 13,
                        weight: '600'
                    },
                    padding: {top: 10, bottom: 0}
                }
            }
        },
        layout: {
            padding: {
                top: 20,
                bottom: 10,
                left: 10,
                right: 10
            }
        }
    };

    // Apriori Chart
    @if(!empty($aprioriChartData['labels']))
        const aprioriCtx = document.getElementById('aprioriChart');
        if (aprioriCtx) {
            const aprioriData = {
                labels: {!! json_encode($aprioriChartData['labels']) !!},
                datasets: [
                    {
                        label: 'Support (%)',
                        data: {!! json_encode($aprioriChartData['support']) !!},
                        backgroundColor: 'rgba(59, 130, 246, 0.7)',
                        borderColor: 'rgba(59, 130, 246, 1)',
                        borderWidth: 2,
                        borderRadius: 4
                    },
                    {
                        label: 'Confidence (%)',
                        data: {!! json_encode($aprioriChartData['confidence']) !!},
                        backgroundColor: 'rgba(147, 51, 234, 0.7)',
                        borderColor: 'rgba(147, 51, 234, 1)',
                        borderWidth: 2,
                        borderRadius: 4
                    }
                ]
            };

            new Chart(aprioriCtx.getContext('2d'), {
                type: 'bar',
                data: aprioriData,
                options: commonBarOptions
            });
        }
    @endif

    // FP-Growth Chart
    @if(!empty($fpGrowthChartData['labels']))
        const fpGrowthCtx = document.getElementById('fpGrowthChart');
        if (fpGrowthCtx) {
            const fpGrowthData = {
                labels: {!! json_encode($fpGrowthChartData['labels']) !!},
                datasets: [
                    {
                        label: 'Support (%)',
                        data: {!! json_encode($fpGrowthChartData['support']) !!},
                        backgroundColor: 'rgba(34, 197, 94, 0.7)',
                        borderColor: 'rgba(34, 197, 94, 1)',
                        borderWidth: 2,
                        borderRadius: 4
                    },
                    {
                        label: 'Confidence (%)',
                        data: {!! json_encode($fpGrowthChartData['confidence']) !!},
                        backgroundColor: 'rgba(99, 102, 241, 0.7)',
                        borderColor: 'rgba(99, 102, 241, 1)',
                        borderWidth: 2,
                        borderRadius: 4
                    }
                ]
            };

            new Chart(fpGrowthCtx.getContext('2d'), {
                type: 'bar',
                data: fpGrowthData,
                options: commonBarOptions
            });
        }
    @endif
});
</script>
@endsection
