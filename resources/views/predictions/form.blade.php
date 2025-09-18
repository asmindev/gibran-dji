<!-- Prediction Form Component -->
<div class="bg-white shadow-xl rounded-xl mb-8 overflow-hidden">
    <div class="px-6 py-5 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-indigo-50">
        <h2 class="text-xl font-semibold text-gray-900 flex items-center">
            <div class="bg-blue-500 rounded-lg p-2 mr-3">
                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                    <path
                        d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3z" />
                </svg>
            </div>
            Buat Prediksi Baru
        </h2>
        <p class="mt-2 text-sm text-gray-600">Gunakan AI untuk menganalisis data historis dan membuat prediksi yang
            akurat</p>
    </div>
    <div class="p-6">
        <form id="prediction-form" class="space-y-6">
            @csrf
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Item Selection -->
                <div class="space-y-2">
                    <label for="item_id" class="flex items-center text-sm font-medium text-gray-700">
                        <svg class="w-4 h-4 mr-2 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                            <path
                                d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z" />
                        </svg>
                        Pilih Produk *
                    </label>
                    <select id="item_id" name="item_id" required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 bg-white">
                        <option value="">Pilih produk untuk diprediksi...</option>
                        @foreach($items as $item)
                        <option value="{{ $item->id }}" data-name="{{ $item->name }}" data-stock="{{ $item->stock }}"
                            data-category="{{ $item->category->name ?? 'No Category' }}">
                            {{ $item->name }} (Stok: {{ $item->stock ?? 0 }})
                        </option>
                        @endforeach
                    </select>
                    <div class="flex items-start mt-2 text-xs text-gray-500">
                        <svg class="w-3 h-3 mr-1 mt-0.5 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                clip-rule="evenodd" />
                        </svg>
                        Sistem akan menganalisis data penjualan historis untuk produk ini
                    </div>
                </div>

                <!-- Prediction Type Selection -->
                <div class="space-y-2">
                    <label for="prediction_type" class="flex items-center text-sm font-medium text-gray-700">
                        <svg class="w-4 h-4 mr-2 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"
                                clip-rule="evenodd" />
                        </svg>
                        Tipe Prediksi *
                    </label>
                    <select id="prediction_type" name="prediction_type" required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 bg-white">
                        <option value="">Pilih jenis prediksi...</option>
                        <option value="sales">📊 Prediksi Penjualan - Estimasi jumlah yang akan terjual</option>
                        <option value="restock">📦 Prediksi Restock - Estimasi jumlah yang perlu di-restock</option>
                    </select>
                    <div id="prediction-info" class="mt-2 text-sm transition-all duration-300"></div>
                </div>
            </div>

            <!-- Selected Item Info -->
            <div id="item-info"
                class="hidden bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-lg p-4 transition-all duration-300">
                <h3 class="text-lg font-medium text-blue-900 mb-3 flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                            clip-rule="evenodd" />
                    </svg>
                    Informasi Produk Terpilih
                </h3>
                <div id="item-details" class="bg-white rounded-lg p-4 shadow-sm"></div>
            </div>

            <!-- Submit Button -->
            <div class="flex justify-center pt-4">
                <button type="submit" id="predict-btn"
                    class="bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 disabled:from-gray-400 disabled:to-gray-500 disabled:cursor-not-allowed text-white font-medium py-4 px-8 rounded-lg shadow-lg transition-all duration-200 flex items-center space-x-3 min-w-[200px] justify-center">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path
                            d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3z" />
                    </svg>
                    <span id="btn-text">Generate Prediksi</span>
                    <div id="loading-spinner" class="hidden">
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
        </form>
    </div>
</div>

@push('scripts')
<script>
    // ======== PREDICTION FORM HANDLERS ========
    function initializePredictionForm() {
        // Prediction type selection change handler
        const typeSelect = document.getElementById('prediction_type');
        if (typeSelect) {
            typeSelect.addEventListener('change', function() {
                const infoDiv = document.getElementById('prediction-info');
                const type = this.value;

                infoDiv.style.opacity = '0';
                setTimeout(() => {
                    if (type === 'sales') {
                        infoDiv.innerHTML = `
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 flex items-start">
                                <svg class="w-5 h-5 text-blue-600 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"/>
                                </svg>
                                <div>
                                    <div class="text-blue-800 font-medium">Prediksi Penjualan</div>
                                    <div class="text-blue-600 text-xs mt-1">AI akan menganalisis pola penjualan historis untuk memprediksi berapa unit yang akan terjual berdasarkan tren dan seasonality</div>
                                </div>
                            </div>
                        `;
                    } else if (type === 'restock') {
                        infoDiv.innerHTML = `
                            <div class="bg-green-50 border border-green-200 rounded-lg p-3 flex items-start">
                                <svg class="w-5 h-5 text-green-600 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM6.293 6.707a1 1 0 010-1.414l3-3a1 1 0 011.414 0l3 3a1 1 0 01-1.414 1.414L11 5.414V13a1 1 0 11-2 0V5.414L7.707 6.707a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                                </svg>
                                <div>
                                    <div class="text-green-800 font-medium">Prediksi Restock</div>
                                    <div class="text-green-600 text-xs mt-1">AI akan menganalisis data penjualan dan stok untuk memprediksi berapa unit yang perlu di-restock agar tidak kehabisan</div>
                                </div>
                            </div>
                        `;
                    } else {
                        infoDiv.innerHTML = '';
                    }
                    infoDiv.style.opacity = '1';
                }, 150);
            });
        }

        // Item selection change handler
        const itemSelect = document.getElementById('item_id');
        if (itemSelect) {
            itemSelect.addEventListener('change', function() {
                const itemInfo = document.getElementById('item-info');
                const itemDetails = document.getElementById('item-details');

                if (this.value) {
                    const selectedOption = this.options[this.selectedIndex];
                    const itemName = selectedOption.dataset.name;
                    const currentStock = selectedOption.dataset.stock;
                    const category = selectedOption.dataset.category;

                    itemDetails.innerHTML = `
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="bg-blue-50 p-3 rounded-lg border border-blue-100">
                                <div class="text-xs font-medium text-blue-600 uppercase tracking-wide mb-1">Nama Produk</div>
                                <div class="text-sm font-semibold text-blue-900">${itemName}</div>
                            </div>
                            <div class="bg-green-50 p-3 rounded-lg border border-green-100">
                                <div class="text-xs font-medium text-green-600 uppercase tracking-wide mb-1">Stok Tersedia</div>
                                <div class="text-sm font-semibold text-green-900">${currentStock || '0'} unit</div>
                            </div>
                            <div class="bg-purple-50 p-3 rounded-lg border border-purple-100">
                                <div class="text-xs font-medium text-purple-600 uppercase tracking-wide mb-1">Kategori</div>
                                <div class="text-sm font-semibold text-purple-900">${category || 'No Category'}</div>
                            </div>
                        </div>
                    `;
                    itemInfo.classList.remove('hidden');
                    // Smooth transition
                    setTimeout(() => {
                        itemInfo.style.opacity = '1';
                        itemInfo.style.transform = 'translateY(0)';
                    }, 10);
                } else {
                    itemInfo.style.opacity = '0';
                    itemInfo.style.transform = 'translateY(-10px)';
                    setTimeout(() => {
                        itemInfo.classList.add('hidden');
                    }, 300);
                }
            });
        }

        // Form submission handler
        const predictionForm = document.getElementById('prediction-form');
        if (predictionForm) {
            predictionForm.addEventListener('submit', async function(e) {
                e.preventDefault();

                const formData = new FormData(this);
                const predictBtn = document.getElementById('predict-btn');
                const btnText = document.getElementById('btn-text');
                const loadingSpinner = document.getElementById('loading-spinner');

                // Show loading state
                predictBtn.disabled = true;
                btnText.textContent = 'Generating...';
                loadingSpinner.classList.remove('hidden');

                try {
                    // Make prediction request
                    const response = await axios.post('{{ route("predictions.predict") }}', formData, {
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        }
                    });

                    console.log('Prediction response:', response.data);
                    const data = response.data;

                    if (data.success) {
                        // Handle the new simplified response structure
                        const predictionData = data; // Use the data directly as it's already simplified

                        window.showPredictionResults(predictionData);
                        window.showAlert('Prediksi berhasil dibuat!', 'success');
                    } else {
                        window.showAlert(data.message || 'Gagal membuat prediksi', 'error');
                    }
                } catch (error) {
                    console.error('Prediction error:', error);
                    let errorMessage = 'Terjadi kesalahan saat membuat prediksi';
                    if (error.response && error.response.data && error.response.data.message) {
                        errorMessage = error.response.data.message;
                    }
                    window.showAlert(errorMessage, 'error');
                } finally {
                    // Reset button state
                    predictBtn.disabled = false;
                    btnText.textContent = 'Generate Prediksi';
                    loadingSpinner.classList.add('hidden');
                }
            });
        }
    }
    document.addEventListener('DOMContentLoaded', initializePredictionForm);
</script>
@endpush
