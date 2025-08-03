<?php

namespace App\Jobs;

use App\Models\Item;
use App\Models\OutgoingItem;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class TrainStockPredictionModel implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public $timeout = 10 * 60; // 10 minutes
    public $tries = 3; // Retry 3 times if failed
    public $backoff = 3 * 10; // Wait 30 seconds between retries

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        // Set queue priority
        $this->onQueue('model-training');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Starting background model training job');

        // Set cache key for training status
        Cache::put('model_training_status', 'in_progress', 3600); // 1 hour
        Cache::put('model_training_started_at', now(), 3600);

        try {
            // Step 1: Export CSV data
            $csvResult = $this->exportOutgoingDataToCsv();
            Log::info('Job CSV export result', $csvResult);

            if (!$csvResult['success']) {
                Cache::put('model_training_status', 'failed', 3600);
                Cache::put('model_training_error', $csvResult['message'], 3600);
                throw new \Exception('CSV export failed: ' . $csvResult['message']);
            }

            // Step 2: Train Python model
            $trainingResult = $this->trainPythonModel();
            Log::info('Job model training result', $trainingResult);

            if (!$trainingResult['success']) {
                Cache::put('model_training_status', 'failed', 3600);
                Cache::put('model_training_error', $trainingResult['message'], 3600);
                throw new \Exception('Model training failed: ' . $trainingResult['message']);
            }

            // Success - update cache
            Cache::put('model_training_status', 'completed', 3600);
            Cache::put('model_training_completed_at', now(), 3600);
            Cache::put('model_training_result', [
                'csv_export' => $csvResult,
                'model_training' => $trainingResult
            ], 3600);

            Log::info('Background model training completed successfully');
        } catch (\Exception $e) {
            Log::error('Background model training failed: ' . $e->getMessage());
            Log::error('Training job stack trace: ' . $e->getTraceAsString());

            Cache::put('model_training_status', 'failed', 3600);
            Cache::put('model_training_error', $e->getMessage(), 3600);
            Cache::put('model_training_failed_at', now(), 3600);

            throw $e; // Re-throw to trigger retry mechanism
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Model training job failed permanently: ' . $exception->getMessage());

        Cache::put('model_training_status', 'failed', 3600);
        Cache::put('model_training_error', $exception->getMessage(), 3600);
        Cache::put('model_training_failed_at', now(), 3600);
    }

    private function exportOutgoingDataToCsv()
    {
        try {
            Log::info('Job: Starting CSV export of outgoing data');

            $outgoingData = OutgoingItem::with(['item'])
                ->orderBy('outgoing_date', 'asc')
                ->get();

            if ($outgoingData->isEmpty()) {
                return [
                    'success' => false,
                    'message' => 'Tidak ada data outgoing untuk di-export'
                ];
            }

            // Group data by month for CSV files
            $monthlyData = [];
            foreach ($outgoingData as $outgoing) {
                $dateValue = (string) $outgoing->outgoing_date;
                $carbonDate = Carbon::parse($dateValue);

                $monthKey = $carbonDate->format('Y-m');
                $monthName = $carbonDate->locale('id')->format('F');

                if (!isset($monthlyData[$monthKey])) {
                    $monthlyData[$monthKey] = [
                        'month_name' => $monthName,
                        'year' => $carbonDate->year,
                        'data' => []
                    ];
                }

                $monthlyData[$monthKey]['data'][] = [
                    'no' => count($monthlyData[$monthKey]['data']) + 1,
                    'id_trx' => $outgoing->id,
                    'tgl' => $carbonDate->format('d F Y'),
                    'id_item' => $outgoing->item->id,
                    'nama_barang' => $outgoing->item->name,
                    'kategori' => 'Barang Keluar',
                    'jumlah' => $outgoing->quantity
                ];
            }

            // Create scripts/data directory if it doesn't exist
            $dataPath = base_path('scripts/data');
            if (!file_exists($dataPath)) {
                mkdir($dataPath, 0755, true);
            }

            // Clear existing CSV files
            $existingFiles = glob($dataPath . '/*.csv');
            foreach ($existingFiles as $file) {
                unlink($file);
            }

            // Generate CSV files for each month
            $generatedFiles = [];
            foreach ($monthlyData as $monthKey => $monthInfo) {
                $filename = ucfirst($monthInfo['month_name']) . '.csv';
                $filepath = $dataPath . '/' . $filename;

                $file = fopen($filepath, 'w');
                fputcsv($file, ['no', 'id_trx', 'tgl', 'id_item', 'nama_barang', 'kategori', 'jumlah'], ',', '"', '\\');

                foreach ($monthInfo['data'] as $row) {
                    fputcsv($file, $row, ',', '"', '\\');
                }

                fclose($file);
                $generatedFiles[] = $filename;

                Log::info("Job: Generated CSV file: {$filename} with " . count($monthInfo['data']) . " records");
            }

            return [
                'success' => true,
                'message' => 'Data berhasil di-export ke CSV',
                'files_generated' => $generatedFiles,
                'total_files' => count($generatedFiles),
                'total_records' => $outgoingData->count(),
                'data_path' => $dataPath
            ];
        } catch (\Exception $e) {
            Log::error('Job CSV export error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error saat export CSV: ' . $e->getMessage()
            ];
        }
    }

    private function trainPythonModel()
    {
        try {
            Log::info('Job: Starting Python model training');

            $basePath = base_path();
            $operatingSystem = PHP_OS_FAMILY;

            // Build training command
            if (file_exists($basePath . '/scripts/.venv/bin/python')) {
                $command = "cd \"{$basePath}/scripts\" && source .venv/bin/activate && python stock_predictor.py train 2>&1";
            } else {
                $pythonCmd = $operatingSystem === 'Windows' ? 'python' : 'python3';
                $command = "cd \"{$basePath}/scripts\" && {$pythonCmd} stock_predictor.py train 2>&1";
            }

            Log::info('Job: Running model training command: ' . $command);

            $output = [];
            $returnCode = 0;
            exec($command, $output, $returnCode);

            $outputString = implode("\n", $output);
            Log::info('Job: Model training output: ' . $outputString);

            if ($returnCode !== 0) {
                return [
                    'success' => false,
                    'message' => 'Training gagal: ' . $outputString
                ];
            }

            // Check if model files were created
            $dailyModelExists = file_exists($basePath . '/scripts/model/rf_stock_predictor_daily.pkl');
            $monthlyModelExists = file_exists($basePath . '/scripts/model/rf_stock_predictor_monthly.pkl');

            Log::info('Job: Model files status', [
                'daily_model_exists' => $dailyModelExists,
                'monthly_model_exists' => $monthlyModelExists
            ]);

            return [
                'success' => true,
                'message' => 'Model berhasil di-training',
                'models_created' => [
                    'daily_model' => $dailyModelExists,
                    'monthly_model' => $monthlyModelExists
                ],
                'training_output' => $outputString
            ];
        } catch (\Exception $e) {
            Log::error('Job: Model training error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error saat training model: ' . $e->getMessage()
            ];
        }
    }
}
