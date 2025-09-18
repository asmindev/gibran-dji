@extends('layouts.app')



@section('title', 'Stock Predictions')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <!-- Header Component -->
    @include('predictions.header')

    <!-- Worker Status Component -->
    {{-- @include('predictions.worker-status') --}}

    <!-- Prediction Form Component -->
    @include('predictions.form')

    <!-- Results Component -->
    @include('predictions.results')

</div>

<!-- Styles Component -->
@include('predictions.styles')

<!-- JavaScript Component -->
@push('scripts')
<script>
    // Define global functions first to avoid undefined errors
    window.showPredictionResults = function(prediction) {
        console.log('Displaying prediction results:', prediction);
        const resultsContainer = document.getElementById('prediction-results');
        const contentContainer = document.getElementById('prediction-content');

        // Extract data from simplified response format
        const data = prediction.data || prediction;
        const stockAnalysis = data.stock_analysis || {};

        // Get prediction accuracy directly from simplified response
        const confidence = data.prediction_accuracy || 0;

        // Ensure confidence is within valid range
        const normalizedConfidence = Math.min(Math.max(confidence, 0), 100);

        // Enhanced confidence styling based on accuracy level
        let confidenceClass = 'text-red-600';
        let confidenceIcon = '⚠️';
        let confidenceText = 'Rendah';
        let confidenceBgClass = 'bg-red-500';
        let accuracyLevel = 'Poor';

        // Determine accuracy level and styling based on percentage
        if (normalizedConfidence >= 85) {
            confidenceClass = 'text-green-600';
            confidenceIcon = '🏆';
            confidenceText = 'Sangat Tinggi';
            confidenceBgClass = 'bg-green-500';
            accuracyLevel = 'Excellent';
        } else if (normalizedConfidence >= 70) {
            confidenceClass = 'text-green-500';
            confidenceIcon = '✅';
            confidenceText = 'Tinggi';
            confidenceBgClass = 'bg-green-400';
            accuracyLevel = 'Good';
        } else if (normalizedConfidence >= 50) {
            confidenceClass = 'text-yellow-600';
            confidenceIcon = '⚡';
            confidenceText = 'Sedang';
            confidenceBgClass = 'bg-yellow-500';
            accuracyLevel = 'Fair';
        }

        // Stock Analysis Styling
        let analysisCardClass = 'bg-white border-2';
        let analysisTitleClass = 'text-gray-900';
        let analysisStatusClass = 'text-gray-600';
        let analysisIconBg = 'bg-gray-100';

        if (stockAnalysis.status === 'warning') {
            analysisCardClass = 'bg-red-50 border-2 border-red-200';
            analysisTitleClass = 'text-red-900';
            analysisStatusClass = 'text-red-700';
            analysisIconBg = 'bg-red-100';
        } else if (stockAnalysis.status === 'good') {
            analysisCardClass = 'bg-green-50 border-2 border-green-200';
            analysisTitleClass = 'text-green-900';
            analysisStatusClass = 'text-green-700';
            analysisIconBg = 'bg-green-100';
        }

        // Determine prediction type display
        const predictionTypeText = stockAnalysis.type === 'sales' ? 'Barang Keluar' : 'Barang Masuk';
        const predictionTypeIcon = stockAnalysis.type === 'sales' ? '📤' : '➕';

        contentContainer.innerHTML = `
            <!-- Compact Prediction Header -->
            <div class="bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-lg p-4 mb-4">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-lg font-bold">${stockAnalysis.type === 'sales' ? '📤 Prediksi Barang Keluar' : '➕ Prediksi Barang Masuk'}</h2>
                    <span class="text-xs bg-opacity-20 px-2 py-1 rounded">
                        ${normalizedConfidence.toFixed(0)}% akurat
                    </span>
                </div>
                <div class="grid grid-cols-3 gap-3 text-sm">
                    <div class="text-center">
                        <div class="text-xs opacity-80">Produk</div>
                        <div class="font-semibold truncate">${data.product_name || 'N/A'}</div>
                    </div>
                    <div class="text-center">
                        <div class="text-xs opacity-80">Prediksi</div>
                        <div class="font-semibold">${Math.round(data.prediction_result || 0)} unit</div>
                    </div>
                    <div class="text-center">
                        <div class="text-xs opacity-80">Stok</div>
                        <div class="font-semibold">${data.current_stock || 0} unit</div>
                    </div>
                </div>
            </div>

            <!-- Compact Stock Analysis -->
            <div class="${analysisCardClass} rounded-lg p-4 mb-4 shadow-sm">
                <div class="flex items-center space-x-3 mb-3">
                    <span class="text-xl">${stockAnalysis.status_emoji || '📊'}</span>
                    <div class="flex-1">
                        <h3 class="${analysisTitleClass} text-base font-semibold">
                            ${stockAnalysis.title || 'Status Stok'}
                        </h3>
                        <p class="${analysisStatusClass} text-sm">
                            ${stockAnalysis.summary || stockAnalysis.message || 'Tidak ada informasi'}
                        </p>
                    </div>
                    <span class="px-2 py-1 rounded text-xs font-medium ${
                        stockAnalysis.priority === 'high' ? 'priority-high' :
                        stockAnalysis.priority === 'medium' ? 'priority-medium' :
                        'priority-low'
                    }">
                        ${stockAnalysis.priority ? stockAnalysis.priority.toUpperCase() : 'NORMAL'}
                    </span>
                </div>

                ${stockAnalysis.formatted_status ? `
                <!-- Compact Status Grid -->
                <div class="grid grid-cols-2 gap-2 mb-3 text-xs">
                    ${Object.entries(stockAnalysis.formatted_status).map(([key, value]) => `
                        <div class="bg-gray-50 rounded p-2 text-center">
                            <div class="text-gray-600">${key}</div>
                            <div class="font-semibold ${analysisStatusClass}">${value}</div>
                        </div>
                    `).join('')}
                </div>
                ` : ''}

                ${stockAnalysis.recommendation ? `
                <!-- Compact Recommendation -->
                <div class="bg-blue-50 border-l-4 border-blue-400 p-3 rounded">
                    <div class="flex items-start space-x-2">
                        <span class="text-blue-500">💡</span>
                        <p class="text-blue-800 text-sm">${stockAnalysis.recommendation}</p>
                    </div>
                </div>
                ` : ''}
            </div>

            <!-- Compact Performance Metrics -->
            <div class="bg-white border border-gray-200 rounded-lg p-4">
                <div class="flex items-center justify-between mb-2">
                    <h4 class="text-sm font-semibold text-gray-900">🎯 Performa Model</h4>
                    <span class="text-xs text-gray-500">${Math.round(data.execution_time_ms || 0)}ms</span>
                </div>

                <!-- Compact Accuracy Bar -->
                <div class="mb-2">
                    <div class="flex justify-between text-xs mb-1">
                        <span class="text-gray-600">Akurasi</span>
                        <span class="${confidenceClass} font-medium">${normalizedConfidence.toFixed(1)}%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="${confidenceBgClass} h-2 rounded-full transition-all duration-500"
                             style="width: ${normalizedConfidence}%"></div>
                    </div>
                </div>

                <!-- Compact Level Badge -->
                <div class="flex items-center justify-center">
                    <span class="px-3 py-1 rounded-full text-xs font-medium ${confidenceClass} bg-opacity-10">
                        ${confidenceIcon} ${accuracyLevel}
                    </span>
                </div>
            </div>
        `;

        resultsContainer.classList.remove('hidden');
        resultsContainer.scrollIntoView({ behavior: 'smooth' });
    };

    window.showAlert = function(message, type = 'info') {
        const alertContainer = document.getElementById('alert-container');
        const alertId = 'alert-' + Date.now();

        let bgColor, textColor, icon;
        switch(type) {
            case 'success':
                bgColor = 'bg-green-100 border-green-400';
                textColor = 'text-green-700';
                icon = '✅';
                break;
            case 'error':
                bgColor = 'bg-red-100 border-red-400';
                textColor = 'text-red-700';
                icon = '❌';
                break;
            case 'warning':
                bgColor = 'bg-yellow-100 border-yellow-400';
                textColor = 'text-yellow-700';
                icon = '⚠️';
                break;
            default:
                bgColor = 'bg-blue-100 border-blue-400';
                textColor = 'text-blue-700';
                icon = 'ℹ️';
        }

        const alertHTML = `
            <div id="${alertId}" class="max-w-sm w-full ${bgColor} border ${textColor} px-4 py-3 rounded-lg shadow-lg transform transition-all duration-300 ease-in-out translate-x-full opacity-0">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <span class="mr-2">${icon}</span>
                        <span class="text-sm font-medium">${message}</span>
                    </div>
                    <button onclick="removeAlert('${alertId}')" class="ml-2 ${textColor} hover:opacity-75">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                        </svg>
                    </button>
                </div>
            </div>
        `;

        alertContainer.insertAdjacentHTML('beforeend', alertHTML);

        // Trigger animation
        setTimeout(() => {
            const alert = document.getElementById(alertId);
            if (alert) {
                alert.classList.remove('translate-x-full', 'opacity-0');
            }
        }, 10);

        // Auto remove after 5 seconds
        setTimeout(() => {
            removeAlert(alertId);
        }, 5000);
    };

    window.removeAlert = function(alertId) {
        const alert = document.getElementById(alertId);
        if (alert) {
            alert.classList.add('translate-x-full', 'opacity-0');
            setTimeout(() => {
                alert.remove();
            }, 300);
        }
    };
</script>
@endpush

@endsection
