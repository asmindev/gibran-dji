<!-- Styles khusus untuk Predictions -->
<style>
    /* Loading spinner animation */
    .animate-spin {
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        from {
            transform: rotate(0deg);
        }

        to {
            transform: rotate(360deg);
        }
    }

    /* Pulse animation for worker status */
    .animate-pulse {
        animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    }

    @keyframes pulse {

        0%,
        100% {
            opacity: 1;
        }

        50% {
            opacity: 0.5;
        }
    }

    /* Alert transitions */
    .alert-enter {
        transform: translateX(100%);
        opacity: 0;
    }

    .alert-enter-active {
        transform: translateX(0);
        opacity: 1;
        transition: all 300ms ease-in-out;
    }

    /* Prediction form styling */
    .prediction-form-section {
        transition: all 0.3s ease-in-out;
    }

    .prediction-form-section:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    }

    /* Button hover effects */
    .btn-primary {
        transition: all 0.2s ease-in-out;
    }

    .btn-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15);
    }

    .btn-primary:active {
        transform: translateY(0);
    }

    /* Worker status indicator styles */
    .worker-status-running {
        background: linear-gradient(45deg, #10b981, #059669);
        box-shadow: 0 0 10px rgba(16, 185, 129, 0.3);
    }

    .worker-status-stopped {
        background: linear-gradient(45deg, #ef4444, #dc2626);
        box-shadow: 0 0 10px rgba(239, 68, 68, 0.3);
    }

    /* Results styling */
    .prediction-results {
        animation: slideInUp 0.5s ease-out;
    }

    @keyframes slideInUp {
        from {
            transform: translateY(30px);
            opacity: 0;
        }

        to {
            transform: translateY(0);
            opacity: 1;
        }
    }

    /* Hidden utility */
    .hidden {
        display: none !important;
    }

    /* Custom scrollbar for alerts */
    #alert-container::-webkit-scrollbar {
        width: 4px;
    }

    #alert-container::-webkit-scrollbar-track {
        background: transparent;
    }

    #alert-container::-webkit-scrollbar-thumb {
        background: rgba(156, 163, 175, 0.5);
        border-radius: 2px;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .prediction-form-grid {
            grid-template-columns: 1fr;
        }

        #alert-container {
            left: 1rem;
            right: 1rem;
            top: 1rem;
        }
    }
</style>
