@extends('layouts.app')

@section('title', 'Stock Predictions')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Prediksi Stok</h1>
                <p class="mt-2 text-gray-600">Gunakan AI untuk memprediksi kebutuhan stok berdasarkan data penjualan
                    historis</p>
            </div>
            <div>
                <button id="generate-model-btn"
                    class="bg-gradient-to-r from-purple-600 to-purple-700 hover:from-purple-700 hover:to-purple-800 text-white font-medium py-3 px-6 rounded-lg shadow-sm transition duration-200 flex items-center space-x-2">
                    <i class="bi bi-gear-fill"></i>
                    <span id="generate-btn-text">Generate/Retrain Model</span>
                    <div id="generate-loading-spinner" class="hidden ml-2">
                        <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none"
                            viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                            </circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                            </path>
                        </svg>
                    </div>
                </button>
            </div>
        </div>
    </div>

    <!-- Prediction Form -->
    <div class="bg-white shadow-lg rounded-lg mb-8">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-900">
                <i class="bi bi-robot text-blue-600"></i> Buat Prediksi Baru
            </h2>
        </div>
        <div class="p-6">
            <form id="prediction-form" class="space-y-6">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Item Selection -->
                    <div>
                        <label for="item_id" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="bi bi-box-seam text-blue-600"></i> Pilih Produk
                        </label>
                        <select id="item_id" name="item_id" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Pilih produk...</option>
                            @foreach($items as $item)
                            <option value="{{ $item->id }}" data-name="{{ $item->name }}"
                                data-code="{{ $item->item_code }}" data-stock="{{ $item->stock }}"
                                data-category="{{ $item->category->name ?? 'No Category' }}">
                                {{ $item->name }} {{ $item->item_code ? '(' . $item->item_code . ')' : '' }}
                            </option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-sm text-gray-500">Sistem akan otomatis menggunakan data penjualan historis
                        </p>
                    </div>

                    <!-- Period Selection -->
                    <div>
                        <label for="prediction_period" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="bi bi-calendar-range text-green-600"></i> Tipe Prediksi
                        </label>
                        <select id="prediction_period" name="prediction_period" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Pilih tipe prediksi...</option>
                            <option value="daily">📅 Prediksi Harian - Prediksi untuk hari ini</option>
                            <option value="monthly">📊 Prediksi Bulanan - Prediksi untuk bulan ini</option>
                        </select>
                        <div id="prediction-info" class="mt-2 text-sm text-gray-600"></div>
                    </div>
                </div>

                <!-- Selected Item Info -->
                <div id="item-info" class="hidden bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <h3 class="text-lg font-medium text-blue-900 mb-2">Informasi Produk Terpilih</h3>
                    <div id="item-details" class="text-sm text-blue-800"></div>
                </div>

                <!-- Submit Button -->
                <div class="flex justify-center">
                    <button type="submit" id="predict-btn"
                        class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-8 rounded-lg shadow-sm transition duration-200 flex items-center space-x-2">
                        <i class="bi bi-cpu"></i>
                        <span id="btn-text">Generate Prediksi</span>
                        <div id="loading-spinner" class="hidden ml-2">
                            <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none"
                                viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                    stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                        </div>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Alert Container -->
    <div id="alert-container"></div>


    <!-- Results Section -->
    <div id="results-section" class="hidden">
        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-8">
            <div class="bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-lg p-6">
                <div class="text-3xl font-bold" id="total-demand">-</div>
                <div class="text-blue-100">Total Prediksi Demand</div>
            </div>
            <div class="bg-gradient-to-r from-green-500 to-green-600 text-white rounded-lg p-6">
                <div class="text-3xl font-bold" id="confidence-score">-</div>
                <div class="text-green-100">Confidence Score</div>
            </div>
            <div class="bg-gradient-to-r from-purple-500 to-purple-600 text-white rounded-lg p-6">
                <div class="text-3xl font-bold" id="avg-daily">-</div>
                <div class="text-purple-100">Rata-rata Harian</div>
            </div>
            <div class="bg-gradient-to-r from-orange-500 to-orange-600 text-white rounded-lg p-6">
                <div class="text-3xl font-bold" id="stock-days">-</div>
                <div class="text-orange-100">Hari Stok Tersisa</div>
            </div>
            <div class="bg-gradient-to-r from-indigo-500 to-indigo-600 text-white rounded-lg p-6">
                <div class="text-3xl font-bold" id="execution-time">-</div>
                <div class="text-indigo-100">Waktu Eksekusi (ms)</div>
            </div>
        </div>

        <!-- Results Details -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Predictions Table -->
            <div class="bg-white shadow-lg rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Detail Prediksi</h3>
                </div>
                <div class="p-6">
                    <div id="predictions-table" class="overflow-x-auto"></div>
                </div>
            </div>

            <!-- Feature Importance -->
            <div class="bg-white shadow-lg rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Feature Importance</h3>
                    <p class="text-sm text-gray-600">Faktor yang mempengaruhi prediksi</p>
                </div>
                <div class="p-6">
                    <div id="feature-importance"></div>
                </div>
            </div>
        </div>

        <!-- Recommendations -->
        <div class="mt-8 bg-white shadow-lg rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Rekomendasi</h3>
            </div>
            <div class="p-6">
                <div id="recommendations"></div>
            </div>
        </div>
    </div>

    <!-- Recent Predictions -->
    {{-- @if($predictions->count() > 0)
    <div class="mt-8 bg-white shadow-lg rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Prediksi Terbaru</h3>
        </div>
        <div class="p-6">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Produk</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Prediksi</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Confidence</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Periode</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($predictions as $prediction)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ $prediction->item->item_name }}</div>
                                <div class="text-sm text-gray-500">{{ $prediction->item->item_code }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span
                                    class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                    {{ number_format($prediction->predicted_demand, 0) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="w-16 bg-gray-200 rounded-full h-2 mr-2">
                                        <div class="h-2 rounded-full
                                            @if($prediction->prediction_confidence >= 80) bg-green-500
                                            @elseif($prediction->prediction_confidence >= 60) bg-yellow-500
                                            @else bg-red-500
                                            @endif" style="width: {{ $prediction->prediction_confidence }}%"></div>
                                    </div>
                                    <span class="text-sm text-gray-900">{{
                                        number_format($prediction->prediction_confidence, 1) }}%</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $prediction->prediction_period_start->format('d/m/Y') }} -
                                {{ $prediction->prediction_period_end->format('d/m/Y') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($prediction->is_active)
                                <span
                                    class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Active</span>
                                @else
                                <span
                                    class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">Inactive</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <button onclick="deletePrediction({{ $prediction->id }})"
                                    class="text-red-600 hover:text-red-900">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif --}}
</div>

<!-- Include Bootstrap Icons -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">

<script>
    document.addEventListener('DOMContentLoaded', function() {
    // Show prediction info when type is selected
    document.getElementById('prediction_period').addEventListener('change', function() {
        const infoDiv = document.getElementById('prediction-info');

        if (this.value === 'daily') {
            infoDiv.innerHTML = '<i class="bi bi-info-circle text-blue-500"></i> Menggunakan data penjualan 3 hari terakhir untuk prediksi hari ini';
        } else if (this.value === 'monthly') {
            infoDiv.innerHTML = '<i class="bi bi-info-circle text-blue-500"></i> Menggunakan data penjualan bulan lalu untuk prediksi bulan ini';
        } else {
            infoDiv.innerHTML = '';
        }
    });

    // Show item details when item is selected
    document.getElementById('item_id').addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const itemInfo = document.getElementById('item-info');
        const itemDetails = document.getElementById('item-details');

        if (this.value) {
            const itemData = {
                name: selectedOption.dataset.name,
                code: selectedOption.dataset.code,
                stock: selectedOption.dataset.stock,
                category: selectedOption.dataset.category
            };

            itemDetails.innerHTML = `
                <div class="grid grid-cols-2 gap-4">
                    <div><strong>Nama:</strong> ${itemData.name}</div>
                    <div><strong>Kode:</strong> ${itemData.code}</div>
                    <div><strong>Kategori:</strong> ${itemData.category}</div>
                    <div><strong>Stok Saat Ini:</strong> <span class="font-semibold">${itemData.stock}</span></div>
                </div>
            `;
            itemInfo.classList.remove('hidden');
        } else {
            itemInfo.classList.add('hidden');
        }
    });

    // Form submission
    document.getElementById('prediction-form').addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        const predictBtn = document.getElementById('predict-btn');
        const btnText = document.getElementById('btn-text');
        const loadingSpinner = document.getElementById('loading-spinner');

        // Show loading state
        predictBtn.disabled = true;
        btnText.textContent = 'Memproses...';
        loadingSpinner.classList.remove('hidden');

        fetch('{{ route("predictions.predict") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('Prediksi berhasil dibuat!', 'success');
                displayResults(data.results);
            } else {
                // Handle validation errors
                if (data.errors) {
                    let errorMessage = 'Kesalahan validasi:<br>';
                    Object.values(data.errors).forEach(errors => {
                        errors.forEach(error => {
                            errorMessage += `• ${error}<br>`;
                        });
                    });
                    showAlert(errorMessage, 'error');
                } else {
                    showAlert(data.message || 'Prediksi gagal', 'error');
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Terjadi kesalahan saat melakukan prediksi', 'error');
        })
        .finally(() => {
            // Reset button state
            predictBtn.disabled = false;
            btnText.textContent = 'Generate Prediksi';
            loadingSpinner.classList.add('hidden');
        });
    });

    // Generate Model button event listener
    document.getElementById('generate-model-btn').addEventListener('click', function() {
        const generateBtn = document.getElementById('generate-model-btn');
        const generateBtnText = document.getElementById('generate-btn-text');
        const generateLoadingSpinner = document.getElementById('generate-loading-spinner');

        // Confirm action
        if (!confirm('Generate/retrain model akan memperbarui model AI berdasarkan data terbaru. Proses ini mungkin memakan waktu beberapa menit. Lanjutkan?')) {
            return;
        }

        // Show loading state
        generateBtn.disabled = true;
        generateBtnText.textContent = 'Generating...';
        generateLoadingSpinner.classList.remove('hidden');

        fetch('{{ route("predictions.generate-model") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({})
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('Model berhasil diperbarui! Model AI telah dilatih ulang dengan data terbaru.', 'success');
            } else {
                showAlert(data.message || 'Gagal memperbarui model', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Terjadi kesalahan saat memperbarui model', 'error');
        })
        .finally(() => {
            // Reset button state
            generateBtn.disabled = false;
            generateBtnText.textContent = 'Generate/Retrain Model';
            generateLoadingSpinner.classList.add('hidden');
        });
    });
});

function showAlert(message, type = 'success') {
    const alertContainer = document.getElementById('alert-container');
    const alertClass = type === 'success' ? 'bg-green-100 border-green-400 text-green-700' : 'bg-red-100 border-red-400 text-red-700';

    const alert = document.createElement('div');
    alert.className = `${alertClass} px-4 py-3 rounded border mb-4`;
    alert.innerHTML = `
        <div class="flex justify-between items-start">
            <div>${message}</div>
            <button onclick="this.parentElement.parentElement.remove()" class="text-lg font-semibold ml-4">&times;</button>
        </div>
    `;
    alertContainer.appendChild(alert);

    setTimeout(() => {
        if (alert.parentNode) {
            alert.remove();
        }
    }, 5000);
}

function displayResults(results) {
    const resultsSection = document.getElementById('results-section');

    // Update summary cards based on new response format
    const prediction = results.prediction || 0;
    const confidence = Math.round((results.confidence || 0) * 100);
    const predictionType = results.type || 'unknown';
    const executionTime = results.execution_time_ms || 0;

    document.getElementById('total-demand').textContent = prediction;
    document.getElementById('confidence-score').textContent = confidence + '%';

    // For single prediction, show the same value
    document.getElementById('avg-daily').textContent = predictionType === 'daily' ? prediction : Math.round(prediction / 30);

    // Calculate stock days (assuming we have item stock info)
    const selectedOption = document.getElementById('item_id').options[document.getElementById('item_id').selectedIndex];
    const currentStock = parseInt(selectedOption.dataset.stock) || 0;
    const dailyAvg = predictionType === 'daily' ? prediction : Math.round(prediction / 30);
    const stockDays = currentStock > 0 && dailyAvg > 0 ? Math.round(currentStock / dailyAvg) : 0;
    document.getElementById('stock-days').textContent = stockDays + ' hari';

    // Display execution time
    document.getElementById('execution-time').textContent = Math.round(executionTime);

    // Display single prediction result
    const tableHtml = `
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipe</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Periode</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Prediksi</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Waktu</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full ${predictionType === 'daily' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800'}">
                            ${predictionType === 'daily' ? '📅 Harian' : '📊 Bulanan'}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        ${predictionType === 'daily' ? 'Hari ini' : 'Bulan ini'}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full bg-blue-100 text-blue-800">
                            ${prediction} unit
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        ${Math.round(executionTime)}ms
                        ${results.model_prediction_time_ms ? `<br><small class="text-xs text-gray-400">Model: ${Math.round(results.model_prediction_time_ms)}ms</small>` : ''}
                    </td>
                </tr>
            </tbody>
        </table>
    `;
    document.getElementById('predictions-table').innerHTML = tableHtml;

    // Display input data as "feature importance"
    const inputData = results.input_data || {};
    let featureHtml = '';

    if (predictionType === 'daily') {
        featureHtml = `
            <div class="space-y-3">
                <div class="mb-3">
                    <div class="flex justify-between items-center mb-1">
                        <span class="text-sm font-medium text-gray-700">Penjualan Kemarin</span>
                        <span class="text-sm text-gray-500">${inputData.lag1 || 0} unit</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-blue-500 h-2 rounded-full" style="width: 90%"></div>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="flex justify-between items-center mb-1">
                        <span class="text-sm font-medium text-gray-700">2 Hari Lalu</span>
                        <span class="text-sm text-gray-500">${inputData.lag2 || 0} unit</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-green-500 h-2 rounded-full" style="width: 70%"></div>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="flex justify-between items-center mb-1">
                        <span class="text-sm font-medium text-gray-700">3 Hari Lalu</span>
                        <span class="text-sm text-gray-500">${inputData.lag3 || 0} unit</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-purple-500 h-2 rounded-full" style="width: 50%"></div>
                    </div>
                </div>
            </div>
        `;
    } else {
        featureHtml = `
            <div class="space-y-3">
                <div class="mb-3">
                    <div class="flex justify-between items-center mb-1">
                        <span class="text-sm font-medium text-gray-700">Total Bulan Lalu</span>
                        <span class="text-sm text-gray-500">${inputData.prev_month_total || 0} unit</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-blue-500 h-2 rounded-full" style="width: 100%"></div>
                    </div>
                </div>
                <div class="text-sm text-gray-600">
                    <i class="bi bi-info-circle"></i> Prediksi berdasarkan pola penjualan historis
                </div>
            </div>
        `;
    }
    document.getElementById('feature-importance').innerHTML = featureHtml;

    // Display recommendations
    let recommendationHtml = '';
    const selectedItem = document.getElementById('item_id').options[document.getElementById('item_id').selectedIndex];
    const itemName = selectedItem.dataset.name || 'Produk ini';

    if (stockDays < 7) {
        const neededStock = Math.max(0, (dailyAvg * 30) - currentStock);
        recommendationHtml = `
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                <strong>⚠️ Stok Kritis:</strong> Stok ${itemName} saat ini hanya akan bertahan ${stockDays} hari.
                <br><strong>Tindakan:</strong> Segera lakukan pemesanan minimal ${Math.round(neededStock)} unit untuk bulan ini.
            </div>
        `;
    } else if (stockDays < 14) {
        const recommendedOrder = Math.round(prediction * 0.5);
        recommendationHtml = `
            <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded">
                <strong>⚡ Peringatan Stok Rendah:</strong> Stok ${itemName} akan bertahan ${stockDays} hari.
                <br><strong>Rekomendasi:</strong> Pertimbangkan untuk memesan ${recommendedOrder} unit dalam waktu dekat.
            </div>
        `;
    } else {
        recommendationHtml = `
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                <strong>✅ Stok Aman:</strong> Stok ${itemName} saat ini akan bertahan ${stockDays} hari.
                <br><strong>Status:</strong> Monitor tingkat stok secara berkala dan lakukan pemesanan sesuai jadwal normal.
            </div>
        `;
    }

    // Add prediction accuracy info
    let accuracyInfo = '';
    if (confidence >= 80) {
        accuracyInfo = `
            <div class="mt-4 bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded">
                <strong>🎯 Akurasi Tinggi (${confidence}%):</strong> Prediksi ini memiliki tingkat kepercayaan yang tinggi berdasarkan pola data historis.
            </div>
        `;
    } else if (confidence >= 60) {
        accuracyInfo = `
            <div class="mt-4 bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded">
                <strong>📊 Akurasi Sedang (${confidence}%):</strong> Gunakan prediksi ini sebagai panduan, namun pertimbangkan faktor eksternal lainnya.
            </div>
        `;
    } else {
        accuracyInfo = `
            <div class="mt-4 bg-orange-100 border border-orange-400 text-orange-700 px-4 py-3 rounded">
                <strong>⚠️ Akurasi Rendah (${confidence}%):</strong> Data historis terbatas. Gunakan prediksi ini dengan hati-hati dan pertimbangkan analisis manual.
            </div>
        `;
    }

    document.getElementById('recommendations').innerHTML = recommendationHtml + accuracyInfo;

    // Show results section
    resultsSection.classList.remove('hidden');
    resultsSection.scrollIntoView({ behavior: 'smooth' });
}

function deletePrediction(id) {
    if (confirm('Yakin ingin menghapus prediksi ini?')) {
        fetch(`/predictions/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert(data.message, 'success');
                setTimeout(() => {
                    location.reload();
                }, 1000);
            } else {
                showAlert(data.message || 'Terjadi kesalahan', 'error');
            }
        })
        .catch(error => {
            showAlert('Terjadi kesalahan saat menghapus prediksi', 'error');
        });
    }
}
</script>
@endsection
