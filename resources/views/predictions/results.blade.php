<!-- Results Section -->
<div id="prediction-results" class="hidden">
    <div class="bg-white shadow-lg rounded-lg mb-8">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-xl font-semibold text-gray-900">
                <i class="bi bi-graph-up text-green-600"></i> Hasil Prediksi
            </h3>
        </div>
        <div class="p-6">
            <div id="prediction-content" class="space-y-6">
                <!-- Content will be populated dynamically -->
            </div>
        </div>
    </div>
</div>

<!-- Alert Messages -->
<div id="alert-container" class="fixed top-4 right-4 z-50 space-y-2" style="z-index: 9999;">
    <!-- Alerts will be inserted here -->
</div>

@push('scripts')
<script>
    // ======== RESULTS DISPLAY FUNCTIONS ========
    window.showPredictionResults = function(prediction) {
        console.log('Displaying prediction results:', prediction);
        const resultsContainer = document.getElementById('prediction-results');
        const contentContainer = document.getElementById('prediction-content');

        let confidence = prediction.confidence
        confidence = parseFloat(confidence).toFixed(2) * 100; // Convert to percentage

        let confidenceClass = 'text-red-600';
        let confidenceIcon = '⚠️';

        if (confidence >= 80) {
            confidenceClass = 'text-green-600';
            confidenceIcon = '✅';
        } else if (confidence >= 60) {
            confidenceClass = 'text-yellow-600';
            confidenceIcon = '⚡';
        }

        // Format execution time display
        const executionTime = prediction.execution_time_ms || 0;
        let executionDisplay = `${executionTime} ms`;
        let executionClass = 'text-purple-800';

        if (executionTime > 1000) {
            executionDisplay = `${(executionTime / 1000).toFixed(2)} detik`;
            executionClass = 'text-orange-800';
        } else if (executionTime > 100) {
            executionClass = 'text-yellow-800';
        } else {
            executionClass = 'text-green-800';
        }

        // Performance indicator
        let performanceIcon = '🚀';
        let performanceText = 'Sangat Cepat';
        if (executionTime > 1000) {
            performanceIcon = '🐌';
            performanceText = 'Lambat';
        } else if (executionTime > 500) {
            performanceIcon = '⚡';
            performanceText = 'Normal';
        } else if (executionTime > 100) {
            performanceIcon = '⚡';
            performanceText = 'Cepat';
        }

        // Handle missing execution time data
        if (executionTime === 0 || executionTime === null || executionTime === undefined) {
            executionDisplay = 'Tidak tersedia';
            executionClass = 'text-gray-500';
            performanceIcon = '❓';
            performanceText = 'Data tidak tersedia';
        }

                // Check for understock warning
        const currentStock = prediction.product?.current_stock || 0;
        const minimumStock = prediction.product?.minimum_stock || 5;
        const predictedDemand = Math.round(prediction.prediction);

        // Check multiple understock conditions
        const isUnderstockByPrediction = predictedDemand > currentStock;
        const isUnderstockByMinimum = currentStock <= minimumStock;
        const isUnderstock = isUnderstockByPrediction || isUnderstockByMinimum;

        const stockDifference = predictedDemand - currentStock;
        const minimumDifference = minimumStock - currentStock;

        // Stock status with enhanced warnings
        let stockWarning = '';
        if (isUnderstock) {
            let warningMessage = '';
            let recommendations = [];

            if (isUnderstockByPrediction && isUnderstockByMinimum) {
                warningMessage = '🚨 Peringatan Kritis: Stok di bawah minimum DAN tidak cukup untuk prediksi!';
                const requiredStock = Math.max(predictedDemand, minimumStock);
                recommendations.push(`Tambah stok minimal <strong>${requiredStock - currentStock} unit</strong> untuk memenuhi kebutuhan.`);
            } else if (isUnderstockByPrediction) {
                warningMessage = '⚠️ Peringatan: Stok tidak cukup untuk prediksi permintaan!';
                recommendations.push(`Tambah stok <strong>${stockDifference} unit</strong> untuk memenuhi prediksi permintaan.`);
            } else if (isUnderstockByMinimum) {
                warningMessage = '📉 Peringatan: Stok di bawah batas minimum!';
                recommendations.push(`Tambah stok minimal <strong>${Math.abs(minimumDifference)} unit</strong> untuk mencapai minimum stock.`);
            }

            stockWarning = `
                <div class="mt-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-lg">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-red-800">${warningMessage}</h3>
                            <div class="mt-2 text-sm text-red-700">
                                ${recommendations.map(rec => `<p class="mb-1">${rec}</p>`).join('')}
                            </div>
                            <div class="mt-3">
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-2 text-xs">
                                    <div class="bg-red-100 p-2 rounded">
                                        <span class="font-medium text-red-800">📦 Stok Saat Ini</span><br>
                                        <span class="text-red-600">${currentStock} unit</span>
                                    </div>
                                    <div class="bg-orange-100 p-2 rounded">
                                        <span class="font-medium text-orange-800">📉 Minimum Stock</span><br>
                                        <span class="text-orange-600">${minimumStock} unit</span>
                                    </div>
                                    <div class="bg-blue-100 p-2 rounded">
                                        <span class="font-medium text-blue-800">📈 Prediksi Demand</span><br>
                                        <span class="text-blue-600">${predictedDemand} unit</span>
                                    </div>
                                    <div class="bg-purple-100 p-2 rounded">
                                        <span class="font-medium text-purple-800">🎯 Status</span><br>
                                        <span class="text-purple-600">${isUnderstockByPrediction && isUnderstockByMinimum ? 'Kritis' : 'Perlu Perhatian'}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        } else {
            const surplus = currentStock - predictedDemand;
            const aboveMinimum = currentStock - minimumStock;

            stockWarning = `
                <div class="mt-6 bg-green-50 border-l-4 border-green-500 p-4 rounded-lg">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-green-800">✅ Stok Mencukupi</h3>
                            <div class="mt-2 text-sm text-green-700">
                                <p>Stok saat ini <strong>${currentStock} unit</strong> mencukupi untuk prediksi permintaan <strong>${predictedDemand} unit</strong>.</p>
                                <p class="mt-1">Surplus stok: <strong class="text-green-800">${surplus} unit</strong> | Di atas minimum: <strong class="text-green-800">${aboveMinimum} unit</strong></p>
                            </div>
                            <div class="mt-3">
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-2 text-xs">
                                    <div class="bg-green-100 p-2 rounded">
                                        <span class="font-medium text-green-800">📦 Stok Saat Ini</span><br>
                                        <span class="text-green-600">${currentStock} unit</span>
                                    </div>
                                    <div class="bg-blue-100 p-2 rounded">
                                        <span class="font-medium text-blue-800">📉 Minimum Stock</span><br>
                                        <span class="text-blue-600">${minimumStock} unit</span>
                                    </div>
                                    <div class="bg-purple-100 p-2 rounded">
                                        <span class="font-medium text-purple-800">📈 Prediksi Demand</span><br>
                                        <span class="text-purple-600">${predictedDemand} unit</span>
                                    </div>
                                    <div class="bg-emerald-100 p-2 rounded">
                                        <span class="font-medium text-emerald-800">🎯 Status</span><br>
                                        <span class="text-emerald-600">Aman</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }

        contentContainer.innerHTML = `
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6">
                <div class="bg-blue-50 rounded-lg p-4">
                    <h4 class="font-semibold text-blue-900 mb-2">📦 Produk</h4>
                    <p class="text-blue-800">${prediction.product.name || 'Tidak diketahui'}</p>
                </div>

                <div class="bg-green-50 rounded-lg p-4">
                    <h4 class="font-semibold text-green-900 mb-2">📊 Prediksi Penjualan</h4>
                    <p class="text-2xl font-bold text-green-800">${Math.round(prediction.prediction)}</p>
                    <p class="text-sm text-green-600">Unit untuk ${prediction.type === 'daily' ? 'hari' : 'bulan'}</p>
                </div>

                <div class="bg-indigo-50 rounded-lg p-4">
                    <h4 class="font-semibold text-indigo-900 mb-2">📦 Stok Saat Ini</h4>
                    <p class="text-2xl font-bold ${isUnderstock ? 'text-red-600' : 'text-indigo-800'}">${currentStock}</p>
                    <p class="text-sm ${isUnderstock ? 'text-red-600' : 'text-indigo-600'}">Unit tersedia</p>
                </div>

                <div class="bg-gray-50 rounded-lg p-4">
                    <h4 class="font-semibold text-gray-900 mb-2">${confidenceIcon} Akurasi Model</h4>
                    <p class="text-2xl font-bold ${confidenceClass}">${confidence}%</p>
                    <div class="w-full bg-gray-200 rounded-full h-2 mt-2">
                        <div class="h-2 rounded-full bg-current" style="width: ${confidence}%"></div>
                    </div>
                </div>

                <div class="bg-purple-50 rounded-lg p-4">
                    <h4 class="font-semibold text-purple-900 mb-2">${performanceIcon} Waktu Eksekusi</h4>
                    <p class="text-xl font-bold ${executionClass}">${prediction.model_prediction_time_ms} ms</p>
                    <p class="text-sm text-purple-600">${performanceText}</p>
                </div>
            </div>

            ${stockWarning}

            ${prediction.input_parameters ? `
                <div class="mt-6 bg-gray-50 rounded-lg p-4">
                    <h4 class="font-semibold text-gray-900 mb-3">📋 Detail Parameter Input</h4>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4 text-sm">
                        ${Object.entries(prediction.input_parameters).map(([key, value]) => `
                            <div class="flex justify-between">
                                <span class="text-gray-600">${key}:</span>
                                <span class="font-medium text-gray-900">${value}</span>
                            </div>
                        `).join('')}
                    </div>
                </div>
            ` : ''}

            ${prediction.timestamp ? `
                <div class="mt-4 text-center text-sm text-gray-500">
                    <i class="bi bi-clock"></i> Prediksi dibuat pada: ${new Date(prediction.timestamp).toLocaleString('id-ID')}
                </div>
            ` : ''}
        `;

        resultsContainer.classList.remove('hidden');
        resultsContainer.scrollIntoView({ behavior: 'smooth' });
    };

    // ======== ALERT SYSTEM ========
    window.showAlert = function(message, type = 'info') {
        const alertContainer = document.getElementById('alert-container');
        const alertId = 'alert-' + Date.now();

        let bgColor, textColor, icon;
        switch (type) {
            case 'success':
                bgColor = 'bg-green-500';
                textColor = 'text-white';
                icon = '✅';
                break;
            case 'error':
                bgColor = 'bg-red-500';
                textColor = 'text-white';
                icon = '❌';
                break;
            case 'warning':
                bgColor = 'bg-yellow-500';
                textColor = 'text-white';
                icon = '⚠️';
                break;
            default:
                bgColor = 'bg-blue-500';
                textColor = 'text-white';
                icon = 'ℹ️';
        }

        const alertHTML = `
            <div id="${alertId}" class="${bgColor} ${textColor} px-4 py-3 rounded-lg shadow-lg flex items-center space-x-2 max-w-md transform transition-all duration-300 ease-in-out">
                <span>${icon}</span>
                <span class="flex-1">${message}</span>
                <button onclick="removeAlert('${alertId}')" class="ml-2 ${textColor} hover:opacity-75">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                    </svg>
                </button>
            </div>
        `;

        alertContainer.insertAdjacentHTML('beforeend', alertHTML);

        // Auto remove after 5 seconds
        setTimeout(() => {
            removeAlert(alertId);
        }, 5000);
    };

    window.removeAlert = function(alertId) {
        const alert = document.getElementById(alertId);
        if (alert) {
            alert.style.transform = 'translateX(100%)';
            alert.style.opacity = '0';
            setTimeout(() => {
                alert.remove();
            }, 300);
        }
    };
</script>
@endpush
