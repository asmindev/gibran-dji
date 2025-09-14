<!-- Prediction Form Component -->
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
                        <option value="{{ $item->id }}" data-name="{{ $item->name }}" data-stock="{{ $item->stock }}"
                            data-category="{{ $item->category->name ?? 'No Category' }}">
                            {{ $item->name }}
                        </option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-sm text-gray-500">Sistem akan otomatis menggunakan data penjualan historis
                    </p>
                </div>

                <!-- Prediction Type Selection -->
                <div>
                    <label for="prediction_type" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="bi bi-calendar-range text-green-600"></i> Tipe Prediksi
                    </label>
                    <select id="prediction_type" name="prediction_type" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Pilih tipe prediksi...</option>
                        <option value="sales">� Prediksi Penjualan - Prediksi jumlah yang akan terjual</option>
                        <option value="restock">� Prediksi Restock - Prediksi jumlah yang perlu di-restock</option>
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
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                            </circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 714 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
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

                if (type === 'sales') {
                    infoDiv.innerHTML = '<span class="text-blue-600">� Akan memprediksi berapa banyak produk yang akan terjual berdasarkan pola penjualan historis</span>';
                } else if (type === 'restock') {
                    infoDiv.innerHTML = '<span class="text-green-600">� Akan memprediksi berapa banyak produk yang perlu di-restock berdasarkan data penjualan dan ketersediaan stok</span>';
                } else {
                    infoDiv.innerHTML = '';
                }
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
                    const itemCode = selectedOption.dataset.code;
                    const currentStock = selectedOption.dataset.stock;
                    const category = selectedOption.dataset.category;

                    itemDetails.innerHTML = `
                        <div class="grid grid-cols-2 gap-4">
                            <div><strong>Nama:</strong> ${itemName}</div>
                            <div><strong>Kode:</strong> ${itemCode || 'N/A'}</div>
                            <div><strong>Stok Saat Ini:</strong> ${currentStock || '0'}</div>
                            <div><strong>Kategori:</strong> ${category || 'No Category'}</div>
                        </div>
                    `;
                    itemInfo.classList.remove('hidden');
                } else {
                    itemInfo.classList.add('hidden');
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
                        // merge data.results and data.prediction
                        const results = Object.assign({}, data.results, data.prediction);
                        results.product = data.product || {};
                        window.showPredictionResults(results);
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
