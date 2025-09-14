<!-- Header Section dengan Generate Model Button -->
<div class="mb-8">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Prediksi Stok</h1>
            <p class="mt-2 text-gray-600">Gunakan AI untuk memprediksi kebutuhan stok berdasarkan data penjualan
                historis</p>
        </div>
        <div>
            <form id="generate-model-form">
                @csrf
                <button id="generate-model-btn" type="submit"
                    class="bg-gradient-to-r from-purple-600 to-purple-700 hover:from-purple-700 hover:to-purple-800 text-white font-medium py-3 px-6 rounded-lg shadow-sm transition duration-200 flex items-center space-x-2">
                    <i class="bi bi-gear-fill"></i>
                    <span id="generate-btn-text">Train Model</span>
                    <div id="generate-loading-spinner" class="hidden ml-2">
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
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // ======== GENERATE MODEL BUTTON HANDLER ========
    function initializeGenerateModelButton() {
        const generateModelForm = document.getElementById('generate-model-form');
        const generateModelBtn = document.getElementById('generate-model-btn');

        if (generateModelForm && generateModelBtn) {
            console.log('Initializing Generate Model button handler');

            // Remove any existing onclick handlers
            generateModelBtn.onclick = null;

            // Handle form submission
            generateModelForm.addEventListener('submit', async function(event) {
                console.log('Generate Model form submitted', {
                    isTrainingInProgress: window.isTrainingInProgress,
                    buttonDisabled: generateModelBtn.disabled,
                    eventType: event.type,
                    timestamp: new Date().toISOString()
                });
                // CRITICAL: Prevent any form submission or page navigation
                event.preventDefault();
                event.stopPropagation();
                event.stopImmediatePropagation();

                console.log('Generate Model form submitted', {
                    isTrainingInProgress: window.isTrainingInProgress,
                    buttonDisabled: generateModelBtn.disabled,
                    eventType: event.type,
                    timestamp: new Date().toISOString()
                });

                // Call the generate model function
                await handleGenerateModelClick(event, generateModelBtn);
            });

            // Handle button click as backup
            generateModelBtn.addEventListener('click', async function(event) {
                // CRITICAL: Prevent any form submission or page navigation
                event.preventDefault();
                event.stopPropagation();
                event.stopImmediatePropagation();

                console.log('Generate Model button clicked', {
                    isTrainingInProgress: window.isTrainingInProgress,
                    buttonDisabled: generateModelBtn.disabled,
                    eventType: event.type,
                    timestamp: new Date().toISOString()
                });

                // Call the generate model function
                await handleGenerateModelClick(event, generateModelBtn);
            });
        }
    }

    // Extract the main logic into a separate function
    async function handleGenerateModelClick(event, generateModelBtn) {
        // Prevent multiple clicks
        if (window.isTrainingInProgress) {
            console.log('Training already in progress, ignoring click');
            window.showAlert('Training sudah berjalan, mohon tunggu...', 'warning');
            return false;
        }

        // Check if button is already disabled
        if (generateModelBtn.disabled) {
            console.log('Button already disabled, ignoring click');
            return false;
        }        // Immediately disable the button to prevent any further clicks
        generateModelBtn.disabled = true;

        const generateBtnText = document.getElementById('generate-btn-text');
        const generateLoadingSpinner = document.getElementById('generate-loading-spinner');

        // Set flag and update UI
        window.isTrainingInProgress = true;
        generateBtnText.textContent = 'Memulai training...';
        generateLoadingSpinner.classList.remove('hidden');

        try {
            console.log('Sending training request...');
            const response = await axios.post('{{ route("predictions.train-model") }}', {}, {
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json'
                },
                timeout: 30000 // 30 seconds for initial request
            });

            console.log('Training request response:', response.data);

            if (response.data.success) {
                // Training started successfully, start polling for status
                window.showAlert('Model training dimulai di background. Silakan tunggu...', 'info');
                generateBtnText.textContent = 'Training di background...';

                // Start polling status
                window.startTrainingStatusPolling(generateModelBtn, generateBtnText, generateLoadingSpinner);

            } else {
                // Handle failure to start training
                window.isTrainingInProgress = false;
                generateModelBtn.disabled = false;
                generateBtnText.textContent = 'Train Model';
                generateLoadingSpinner.classList.add('hidden');

                window.showAlert(response.data.message || 'Gagal memulai training', 'error');
            }

        } catch (error) {
            console.error('Training request failed:', error);

            // Reset state on error
            window.isTrainingInProgress = false;
            generateModelBtn.disabled = false;
            generateBtnText.textContent = 'Train Model';
            generateLoadingSpinner.classList.add('hidden');

            let errorMessage = 'Terjadi kesalahan saat memulai training';
            if (error.response) {
                console.error('Error response:', error.response);
                errorMessage = error.response.data.message || `Server error (${error.response.status})`;
            } else if (error.request) {
                console.error('Error request:', error.request);
                errorMessage = 'Tidak dapat terhubung ke server';
            } else {
                console.error('Error:', error.message);
                errorMessage = 'Terjadi kesalahan: ' + error.message;
            }

            window.showAlert(errorMessage, 'error');
        }
    }

    // ======== TRAINING STATUS POLLING ========
    window.startTrainingStatusPolling = function(generateBtn, generateBtnText, generateLoadingSpinner) {
        const startTime = Date.now();

        const pollInterval = setInterval(async () => {
            try {
                const statusResponse = await axios.get('{{ route("predictions.training-status") }}');
                const status = statusResponse.data.status;
                const elapsedSeconds = Math.round((Date.now() - startTime) / 1000);

                console.log('Training status:', status, 'Elapsed:', elapsedSeconds + 's');

                switch (status) {
                    case 'in_progress':
                        const minutes = Math.floor(elapsedSeconds / 60);
                        const seconds = elapsedSeconds % 60;
                        generateBtnText.textContent = `Training... (${minutes}:${seconds.toString().padStart(2, '0')})`;
                        break;

                    case 'completed':
                        clearInterval(pollInterval);
                        window.isTrainingInProgress = false; // Reset flag
                        generateBtn.disabled = false;
                        generateBtnText.textContent = 'Train Model';
                        generateLoadingSpinner.classList.add('hidden');

                        const completedMinutes = Math.floor(elapsedSeconds / 60);
                        const completedSeconds = elapsedSeconds % 60;
                        const durationText = completedMinutes > 0 ? `${completedMinutes}m ${completedSeconds}s` : `${completedSeconds}s`;

                        window.showAlert(`Model berhasil diperbarui dalam ${durationText}! Training selesai di background.`, 'success');
                        break;

                    case 'failed':
                        clearInterval(pollInterval);
                        window.isTrainingInProgress = false; // Reset flag
                        generateBtn.disabled = false;
                        generateBtnText.textContent = 'Train Model';
                        generateLoadingSpinner.classList.add('hidden');

                        const errorMessage = statusResponse.data.error || 'Training gagal';
                        window.showAlert(`Training gagal: ${errorMessage}`, 'error');
                        break;

                    case 'idle':
                        // Training hasn't started yet or was reset
                        generateBtnText.textContent = `Menunggu job dimulai... (${elapsedSeconds}s)`;
                        break;
                }

                // Timeout after 10 minutes
                if (elapsedSeconds > 600) {
                    clearInterval(pollInterval);
                    window.isTrainingInProgress = false; // Reset flag
                    generateBtn.disabled = false;
                    generateBtnText.textContent = 'Train Model';
                    generateLoadingSpinner.classList.add('hidden');
                    window.showAlert('Training timeout. Periksa status queue worker.', 'error');
                }

            } catch (error) {
                console.error('Status polling error:', error);
                // Continue polling even if there's an error
            }
        }, 2000); // Poll every 2 seconds
    };
    initializeGenerateModelButton();
</script>
@endpush
