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

        contentContainer.innerHTML = `
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-blue-50 rounded-lg p-4">
                    <h4 class="font-semibold text-blue-900 mb-2">📦 Produk</h4>
                    <p class="text-blue-800">${prediction.name}</p>
                </div>

                <div class="bg-green-50 rounded-lg p-4">
                    <h4 class="font-semibold text-green-900 mb-2">📊 Prediksi Permintaan</h4>
                    <p class="text-2xl font-bold text-green-800">${Math.round(prediction.prediction)}</p>
                    <p class="text-sm text-green-600">Unit untuk ${prediction.type === 'daily' ? 'hari' : 'bulan'}</p>
                </div>

                <div class="bg-gray-50 rounded-lg p-4">
                    <h4 class="font-semibold text-gray-900 mb-2">${confidenceIcon} Confidence Level</h4>
                    <p class="text-2xl font-bold ${confidenceClass}">${confidence}%</p>
                    <div class="w-full bg-gray-200 rounded-full h-2 mt-2">
                        <div class="h-2 rounded-full bg-current" style="width: ${confidence}%"></div>
                    </div>
                </div>
            </div>
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
