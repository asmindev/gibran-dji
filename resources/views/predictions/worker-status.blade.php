<!-- Worker Status Widget -->
<div class="mb-6 bg-white shadow-sm rounded-lg border border-gray-200">
    <div class="px-4 py-3 border-b border-gray-200">
        <h3 class="text-sm font-medium text-gray-900 flex items-center">
            <i class="bi bi-gear-wide-connected text-purple-600 mr-2"></i>
            Status Queue Worker
        </h3>
    </div>
    <div class="px-4 py-3">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div id="worker-indicator" class="w-3 h-3 rounded-full bg-gray-400"></div>
                <span id="worker-status-text" class="text-sm text-gray-600">Checking worker status...</span>
            </div>
            <div id="queue-info" class="text-xs text-gray-500"></div>
        </div>
        <div class="mt-2 text-xs text-gray-400">
            Status diperbarui setiap 10 detik • Worker diperlukan untuk memproses training model
        </div>
    </div>
</div>

@push('scripts')
<script>
    // ======== WORKER STATUS POLLING ========
    function initializeWorkerStatusPolling() {
        updateWorkerStatus(); // Initial check
        setInterval(updateWorkerStatus, 10000); // Check every 10 seconds
    }

    async function updateWorkerStatus() {
        try {
            const response = await axios.get('{{ route("predictions.worker-status") }}');
            const { worker_running: is_running, processes: { length: queue_length } } = response.data;

            const workerIndicator = document.getElementById('worker-indicator');
            const workerStatusText = document.getElementById('worker-status-text');
            const queueInfo = document.getElementById('queue-info');

            if (is_running) {
                workerIndicator.className = 'w-3 h-3 rounded-full bg-green-400 animate-pulse';
                workerStatusText.textContent = 'Queue worker berjalan';
                workerStatusText.className = 'text-sm text-green-600 font-medium';
            } else {
                workerIndicator.className = 'w-3 h-3 rounded-full bg-red-400';
                workerStatusText.textContent = 'Queue worker tidak berjalan';
                workerStatusText.className = 'text-sm text-red-600 font-medium';
            }

            if (queue_length > 0) {
                queueInfo.textContent = `${queue_length} job dalam antrian`;
                queueInfo.className = 'text-xs text-blue-600';
            } else {
                queueInfo.textContent = 'Tidak ada job';
                queueInfo.className = 'text-xs text-gray-500';
            }

        } catch (error) {
            console.error('Worker status check failed:', error);
            const workerIndicator = document.getElementById('worker-indicator');
            const workerStatusText = document.getElementById('worker-status-text');

            workerIndicator.className = 'w-3 h-3 rounded-full bg-gray-400';
            workerStatusText.textContent = 'Status tidak diketahui';
            workerStatusText.className = 'text-sm text-gray-500';
        }
    }
</script>
@endpush
