<?php

namespace App\Http\Controllers;

use App\Jobs\TrainStockPredictionModel;
use App\Models\Item;
use App\Models\OutgoingItem;
use App\Models\StockPrediction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class StockPredictionController extends Controller
{
    public function index()
    {
        $items = Item::with('category')->get();
        return view('predictions.index', compact('items'));
    }

    public function predict(Request $request)
    {
        Log::info('Stock prediction request received', [
            'request' => $request->all()
        ]);

        // Validate request - simplified for direct prediction
        $validator = Validator::make($request->all(), [
            'item_id' => 'required|exists:items,id',
            'prediction_period' => 'required|in:daily,monthly',
        ], [
            'item_id.required' => 'Silakan pilih produk',
            'item_id.exists' => 'Produk yang dipilih tidak valid',
            'prediction_period.required' => 'Silakan pilih periode prediksi',
            'prediction_period.in' => 'Periode prediksi harus daily atau monthly',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $item = Item::findOrFail($request->item_id);
            Log::info('Item found for prediction', [
                'item_id' => $item->id,
                'item_name' => $item->name
            ]);

            // Determine prediction type and calculate required data
            if ($request->prediction_period === 'daily') {
                $result = $this->makeDailyPrediction($item);
            } else {
                $result = $this->makeMonthlyPrediction($item);
            }

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 500);
            }

            // Save prediction to database
            $stockPrediction = $this->savePredictionToDatabase($item, $result, $request->prediction_period);

            return response()->json([
                'success' => true,
                'message' => 'Prediksi berhasil dibuat!',
                'results' => $result,
                'prediction_id' => $stockPrediction->id,
                "product" => [
                    'id' => $item->id,
                    'name' => $item->name,
                    'category' => $item->category ? $item->category->name : 'Uncategorized',
                    'current_stock' => $item->stock ?? 0,
                    'minimum_stock' => $item->minimum_stock ?? 5
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Stock prediction error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat melakukan prediksi: ' . $e->getMessage()
            ], 500);
        }
    }

    private function makeDailyPrediction($item)
    {
        try {
            // Get last 3 days of transactions for this product (latest 3 days with data)
            $lastThreeDays = OutgoingItem::where('item_id', $item->id)
                ->select('outgoing_date', 'quantity')
                ->orderBy('outgoing_date', 'desc')
                ->limit(100) // Get recent records to work with
                ->get()
                ->groupBy(function ($item) {
                    return $item->outgoing_date->format('Y-m-d');
                })
                ->take(3) // Take only the 3 most recent days with data
                ->map(function ($dayItems) {
                    return $dayItems->sum('quantity');
                });

            Log::info('Daily prediction inputs', [
                'item_id' => $item->id,
                'item_name' => $item->name,
                'last_three_days_data' => $lastThreeDays->toArray(),
                'available_days' => $lastThreeDays->keys()->toArray()
            ]);


            if ($lastThreeDays->count() < 3) {
                Log::info("Daily prediction not enough data", [
                    'item_id' => $item->id,
                    'item_name' => $item->name,
                    'count' => $lastThreeDays->count(),
                ]);
                return [
                    'success' => false,
                    'message' => 'Data penjualan harian tidak cukup untuk prediksi (minimal 3 hari data transaksi).'
                ];
            }

            // Convert collection to array and get values in reverse chronological order
            $dailyTotals = $lastThreeDays->values()->toArray();

            // Data is already sorted in descending order (most recent first)
            // So we use direct indexing for lag values
            $lag1 = $dailyTotals[0] ?? 0; // Most recent day
            $lag2 = $dailyTotals[1] ?? 0; // 2nd most recent day
            $lag3 = $dailyTotals[2] ?? 0; // 3rd most recent day

            Log::info('Daily prediction lags', [
                'item_id' => $item->id,
                'item_name' => $item->name,
                'lag1' => $lag1,
                'lag2' => $lag2,
                'lag3' => $lag3,
                'available_dates' => $lastThreeDays->keys()->toArray()
            ]);

            // Call Python prediction script using item ID
            $pythonResult = $this->callPythonPredict($item->id, 'hari', [$lag1, $lag2, $lag3]);

            // Handle both old integer format and new dictionary format
            if (is_array($pythonResult)) {
                $prediction = $pythonResult['prediction'];
                $executionTime = $pythonResult['execution_time_ms'] ?? null;
                $modelTime = $pythonResult['model_prediction_time_ms'] ?? null;
            } else {
                $prediction = $pythonResult;
                $executionTime = null;
                $modelTime = null;
            }

            $result = [
                'success' => true,
                'prediction' => $prediction,
                'type' => 'daily',
                'input_data' => [
                    'lag1' => $lag1,
                    'lag2' => $lag2,
                    'lag3' => $lag3
                ],
                'confidence' => $this->calculateConfidence([$lag1, $lag2, $lag3]),
                'period_start' => now()->format('Y-m-d'),
                'period_end' => now()->format('Y-m-d')
            ];

            // Add execution time information if available
            if ($executionTime !== null) {
                $result['execution_time_ms'] = $executionTime;
                $result['model_prediction_time_ms'] = $modelTime;
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('Daily prediction error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error dalam prediksi harian: ' . $e->getMessage()
            ];
        }
    }

    private function makeMonthlyPrediction($item)
    {
        try {
            // Get last month's total outgoing items for this product
            $lastMonth = Carbon::now()->subMonth();
            $prevMonthTotal = OutgoingItem::where('item_id', $item->id)
                ->whereYear('outgoing_date', $lastMonth->year)
                ->whereMonth('outgoing_date', $lastMonth->month)
                ->sum('quantity');

            if ($prevMonthTotal == 0) {
                return [
                    'success' => false,
                    'message' => 'Data penjualan bulan lalu tidak ditemukan untuk prediksi bulanan.'
                ];
            }

            Log::info('Monthly prediction inputs', [
                'item_id' => $item->id,
                'item_name' => $item->name,
                'prev_month_total' => $prevMonthTotal,
                'last_month' => $lastMonth->format('Y-m')
            ]);

            // Call Python prediction script using item ID
            $pythonResult = $this->callPythonPredict($item->id, 'bulan', [$prevMonthTotal]);

            // Handle both old integer format and new dictionary format
            if (is_array($pythonResult)) {
                $prediction = $pythonResult['prediction'];
                $executionTime = $pythonResult['execution_time_ms'] ?? null;
                $modelTime = $pythonResult['model_prediction_time_ms'] ?? null;
            } else {
                $prediction = $pythonResult;
                $executionTime = null;
                $modelTime = null;
            }

            $result = [
                'success' => true,
                'prediction' => $prediction,
                'type' => 'monthly',
                'input_data' => [
                    'prev_month_total' => $prevMonthTotal
                ],
                'confidence' => $this->calculateMonthlyConfidence($prevMonthTotal),
                'period_start' => now()->startOfMonth()->format('Y-m-d'),
                'period_end' => now()->endOfMonth()->format('Y-m-d')
            ];

            // Add execution time information if available
            if ($executionTime !== null) {
                $result['execution_time_ms'] = $executionTime;
                $result['model_prediction_time_ms'] = $modelTime;
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('Monthly prediction error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error dalam prediksi bulanan: ' . $e->getMessage()
            ];
        }
    }

    private function callPythonPredict($itemId, $type, $parameters)
    {
        $basePath = base_path();
        $operatingSystem = PHP_OS_FAMILY;

        // Define paths based on operating system
        if ($operatingSystem === 'Windows') {
            $scriptPath = $basePath . '\\scripts\\stock_predictor.py';
            $venvActivate = $basePath . '\\scripts\\.venv\\Scripts\\activate.bat';
            $pythonPath = $basePath . '\\scripts\\.venv\\Scripts\\python.exe';
        } else {
            $scriptPath = $basePath . '/scripts/stock_predictor.py';
            $venvActivate = $basePath . '/scripts/.venv/bin/activate';
            $pythonPath = $basePath . '/scripts/.venv/bin/python';
        }

        // Map type from Indonesian to English
        $predictionType = ($type === 'hari') ? 'daily' : 'monthly';

        // Build command arguments for new stock_predictor.py interface using item ID
        $escapedItemId = escapeshellarg((string) $itemId);
        $escapedType = escapeshellarg($predictionType);
        $escapedParams = array_map('escapeshellarg', $parameters);

        // Build command with virtual environment activation
        if (file_exists($pythonPath)) {
            // Virtual environment exists, use it
            if ($operatingSystem === 'Windows') {
                // Windows command with venv activation
                // Format: python stock_predictor.py predict <type> <item_id> <params...>
                $command = "cd /d \"{$basePath}\\scripts\" && \".venv\\Scripts\\activate.bat\" && python stock_predictor.py predict {$escapedType} {$escapedItemId} " . implode(' ', $escapedParams) . " 2>&1";
            } else {
                // Linux/Unix command with venv activation
                // Format: python stock_predictor.py predict <type> <item_id> <params...>
                $command = "cd \"{$basePath}/scripts\" && source .venv/bin/activate && python stock_predictor.py predict {$escapedType} {$escapedItemId} " . implode(' ', $escapedParams) . " 2>&1";
            }
        } else {
            // Fallback to system python if venv doesn't exist
            Log::warning('Virtual environment not found, using system Python');
            $pythonCmd = $operatingSystem === 'Windows' ? 'python' : 'python3';
            if ($operatingSystem === 'Windows') {
                $command = "cd /d \"{$basePath}\\scripts\" && {$pythonCmd} stock_predictor.py predict {$escapedType} {$escapedItemId} " . implode(' ', $escapedParams) . " 2>&1";
            } else {
                $command = "cd \"{$basePath}/scripts\" && {$pythonCmd} stock_predictor.py predict {$escapedType} {$escapedItemId} " . implode(' ', $escapedParams) . " 2>&1";
            }
        }

        Log::info('Running Python prediction command: ' . $command);

        // Execute command
        $output = [];
        $returnCode = 0;
        exec($command, $output, $returnCode);

        $outputString = implode("\n", $output);
        Log::info('Python prediction output: ' . $outputString);

        if ($returnCode !== 0) {
            throw new \Exception('Python script failed: ' . $outputString);
        }

        // Extract prediction result from output
        $predictionResult = null;
        $predictionDetails = null;

        foreach ($output as $line) {
            if (strpos($line, 'PREDICTION_RESULT:') === 0) {
                $predictionResult = (int) substr($line, strlen('PREDICTION_RESULT:'));
            } elseif (strpos($line, 'PREDICTION_FULL:') === 0) {
                $jsonString = substr($line, strlen('PREDICTION_FULL:'));
                $predictionDetails = json_decode($jsonString, true);
            }
        }

        if ($predictionResult === null) {
            throw new \Exception('Could not extract prediction result from Python output');
        }

        // Return enhanced data if available, otherwise just the prediction value
        if ($predictionDetails !== null) {
            // Add the basic prediction for backward compatibility
            $predictionDetails['prediction_value'] = $predictionResult;
            return $predictionDetails;
        }

        return $predictionResult;
    }

    private function calculateConfidence($lags)
    {
        // Simple confidence calculation based on data consistency
        if (empty($lags)) return 0.5;

        $mean = array_sum($lags) / count($lags);
        $variance = 0;
        foreach ($lags as $lag) {
            $variance += pow($lag - $mean, 2);
        }
        $variance /= count($lags);
        $stdDev = sqrt($variance);

        // Lower standard deviation = higher confidence
        $coefficient = $stdDev / ($mean + 1); // Add 1 to avoid division by zero
        return max(0.3, min(0.95, 1 - $coefficient));
    }

    private function calculateMonthlyConfidence($prevMonthTotal)
    {
        // Simple confidence based on data availability
        if ($prevMonthTotal > 50) return 0.85;
        if ($prevMonthTotal > 20) return 0.75;
        if ($prevMonthTotal > 5) return 0.65;
        return 0.55;
    }

    public function generateModel(Request $request)
    {
        try {
            // Check if training is already in progress
            $currentStatus = Cache::get('model_training_status');

            Log::info('Model generation request received', [
                'current_status' => $currentStatus,
                'user_ip' => $request->ip(),
                'timestamp' => now(),
                'user_agent' => $request->userAgent()
            ]);

            if ($currentStatus === 'in_progress') {
                $startedAt = Cache::get('model_training_started_at');
                $elapsedMinutes = $startedAt ? now()->diffInMinutes($startedAt) : 0;

                Log::info('Model training already in progress', [
                    'started_at' => $startedAt,
                    'elapsed_minutes' => $elapsedMinutes
                ]);

                return response()->json([
                    'success' => false,
                    'message' => "Model training sedang berjalan (dimulai {$elapsedMinutes} menit yang lalu). Silakan tunggu hingga selesai.",
                    'status' => 'in_progress',
                    'elapsed_minutes' => $elapsedMinutes
                ], 200);
            }

            // Additional check: Prevent rapid-fire requests (within 30 seconds)
            $lastRequestTime = Cache::get('last_training_request_time');
            if ($lastRequestTime && now()->diffInSeconds($lastRequestTime) < 1) {
                Log::warning('Rapid training request detected', [
                    'last_request' => $lastRequestTime,
                    'current_time' => now(),
                    'seconds_diff' => now()->diffInSeconds($lastRequestTime)
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Permintaan training terlalu cepat. Silakan tunggu 30 detik sebelum mencoba lagi.',
                    'status' => 'rate_limited'
                ], 429);
            }

            // Mark this request time
            Cache::put('last_training_request_time', now(), 60); // Store for 1 minute

            // Dispatch job to queue
            Log::info('Dispatching model training job to queue');
            TrainStockPredictionModel::dispatch();

            // Clear previous results
            Cache::forget('model_training_result');
            Cache::forget('model_training_error');

            return response()->json([
                'success' => true,
                'message' => 'Model training telah dimulai di background. Anda akan menerima notifikasi saat selesai.',
                'status' => 'queued',
                'details' => [
                    'message' => 'Job telah ditambahkan ke queue untuk diproses',
                    'queue_name' => 'model-training',
                    'estimated_time' => '3-5 menit'
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('Model generation queue dispatch error: ' . $e->getMessage());
            Log::error('Queue dispatch stack trace: ' . $e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memulai training: ' . $e->getMessage(),
                'error_details' => [
                    'exception' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]
            ], 200);
        }
    }

    public function getTrainingStatus(Request $request)
    {
        $status = Cache::get('model_training_status', 'idle');

        $response = [
            'status' => $status,
            'message' => $this->getStatusMessage($status)
        ];

        switch ($status) {
            case 'in_progress':
                $startedAt = Cache::get('model_training_started_at');
                $elapsedMinutes = $startedAt ? now()->diffInMinutes($startedAt) : 0;
                $response['elapsed_minutes'] = $elapsedMinutes;
                $response['started_at'] = $startedAt;
                break;

            case 'completed':
                $completedAt = Cache::get('model_training_completed_at');
                $result = Cache::get('model_training_result');
                $response['completed_at'] = $completedAt;
                $response['result'] = $result;
                break;

            case 'failed':
                $failedAt = Cache::get('model_training_failed_at');
                $error = Cache::get('model_training_error');
                $response['failed_at'] = $failedAt;
                $response['error'] = $error;
                break;
        }

        return response()->json($response);
    }

    private function getStatusMessage($status)
    {
        switch ($status) {
            case 'idle':
                return 'Model training belum dimulai';
            case 'in_progress':
                return 'Model training sedang berjalan di background';
            case 'completed':
                return 'Model training telah selesai dengan sukses';
            case 'failed':
                return 'Model training gagal';
            default:
                return 'Status tidak diketahui';
        }
    }

    private function exportOutgoingDataToCsv()
    {
        try {
            Log::info('Starting CSV export of outgoing data');

            // Get all outgoing items with item details
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
                // Cast the date field to a string and then parse it
                $dateValue = (string) $outgoing->outgoing_date;
                $carbonDate = Carbon::parse($dateValue);

                $monthKey = $carbonDate->format('Y-m'); // e.g., "2024-01"
                $monthName = $carbonDate->locale('id')->format('F'); // Indonesian month name

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
                    'tgl' => $carbonDate->format('d F Y'), // e.g., "15 Januari 2024"
                    'id_item' => $outgoing->item->id, // Item ID
                    'nama_barang' => $outgoing->item->name, // Item name
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

                // Write CSV header
                fputcsv($file, ['no', 'id_trx', 'tgl', 'id_item', 'nama_barang', 'kategori', 'jumlah'], ',', '"', '\\');

                // Write data rows
                foreach ($monthInfo['data'] as $row) {
                    fputcsv($file, $row, ',', '"', '\\');
                }

                fclose($file);
                $generatedFiles[] = $filename;

                Log::info("Generated CSV file: {$filename} with " . count($monthInfo['data']) . " records");
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
            Log::error('CSV export error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error saat export CSV: ' . $e->getMessage()
            ];
        }
    }

    private function trainPythonModel()
    {
        try {
            Log::info('Starting Python model training');

            $basePath = base_path();
            $operatingSystem = PHP_OS_FAMILY;

            // Build training command
            if (file_exists($basePath . '/scripts/.venv/bin/python')) {
                // Use virtual environment
                $command = "source \"{$basePath}/scripts/.venv/bin/activate\" && python \"{$basePath}/scripts/stock_predictor.py\" train";
            } else {
                // Fallback to system python
                $pythonCmd = $operatingSystem === 'Windows' ? 'python' : 'python3';

                $command = "{$pythonCmd} \"{$basePath}/scripts/stock_predictor.py\" train";
            }

            Log::info('Running model training command: ' . $command);

            // Execute training command
            $output = [];
            $returnCode = 0;
            Log::info('Executing command: ' . $command);
            exec($command, $output, $returnCode);

            $outputString = implode("\n", $output);
            Log::info('Model training output: ' . $outputString);

            if ($returnCode !== 0) {
                return [
                    'success' => false,
                    'message' => 'Training gagal: ' . $outputString
                ];
            }

            // Check if model files were created
            $dailyModelExists = file_exists($basePath . '/scripts/model/rf_stock_predictor_daily.pkl');
            $monthlyModelExists = file_exists($basePath . '/scripts/model/rf_stock_predictor_monthly.pkl');
            Log::info('Model files status', [
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
            Log::error('Model training error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error saat training model: ' . $e->getMessage()
            ];
        }
    }

    public function getWorkerStatus(Request $request)
    {
        try {
            // Check if queue worker is running
            $output = [];
            exec('ps aux | grep -E "queue:work.*model-training" | grep -v grep', $output);

            $isRunning = !empty($output);
            $processes = [];

            foreach ($output as $line) {
                if (preg_match('/(\d+).*?(\d{2}:\d{2}:\d{2}).*?(php.*queue:work.*)/', $line, $matches)) {
                    $processes[] = [
                        'pid' => $matches[1],
                        'time' => $matches[2],
                        'command' => $matches[3]
                    ];
                }
            }

            // Get queue size
            $queueSize = \Illuminate\Support\Facades\Queue::size('model-training');

            // Get failed jobs count
            $failedJobs = \Illuminate\Support\Facades\Queue::size('failed');

            return response()->json([
                'worker_running' => $isRunning,
                'processes' => $processes,
                'queue_size' => $queueSize,
                'failed_jobs' => $failedJobs,
                'message' => $isRunning ? 'Queue worker is running' : 'Queue worker is not running',
                'status' => $isRunning ? 'active' : 'inactive'
            ]);
        } catch (\Exception $e) {
            Log::error('Worker status check error: ' . $e->getMessage());
            return response()->json([
                'worker_running' => false,
                'error' => $e->getMessage(),
                'message' => 'Error checking worker status'
            ], 500);
        }
    }

    /**
     * Save prediction result to database
     */
    private function savePredictionToDatabase($item, $result, $predictionPeriod)
    {
        // Determine prediction month based on type
        if ($predictionPeriod === 'daily') {
            // For daily predictions, we use current month
            $predictionMonth = now()->startOfMonth();
        } else {
            // For monthly predictions, we use next month
            $predictionMonth = now()->addMonth()->startOfMonth();
        }

        // Check if prediction for this item and month already exists
        $existingPrediction = StockPrediction::where('item_id', $item->id)
            ->where('month', $predictionMonth->format('Y-m-d'))
            ->where('prediction_type', $predictionPeriod)
            ->first();

        $predictionData = [
            'item_id' => $item->id,
            'predicted_quantity' => $result['prediction'],
            'prediction_type' => $predictionPeriod,
            'prediction_month' => $predictionMonth->format('Y-m-d'),
            'input_data' => $result['input_data'] ?? null,
            'confidence' => $result['confidence'] ?? null,
            'execution_time_ms' => $result['execution_time_ms'] ?? null,
            'model_prediction_time_ms' => $result['model_prediction_time_ms'] ?? null,
            'prediction' => $result['prediction'] ?? null,
            'product' => $item->name,
            'month' => $predictionMonth->format('Y-m-d'),
            'predicted_at' => now(),
        ];

        if ($existingPrediction) {
            // Update existing prediction
            $existingPrediction->update($predictionData);
            Log::info('Updated existing stock prediction', [
                'prediction_id' => $existingPrediction->id,
                'item_id' => $item->id,
                'prediction_month' => $predictionMonth->format('Y-m-d')
            ]);
            return $existingPrediction;
        } else {
            // Create new prediction
            // json prediction


            Log::info('Creating new stock prediction', $predictionData);
            $stockPrediction = StockPrediction::create($predictionData);
            Log::info('Created new stock prediction', [
                'prediction_id' => $stockPrediction->id,
                'item_id' => $item->id,
                'prediction_month' => $predictionMonth->format('Y-m-d')
            ]);
            return $stockPrediction;
        }
    }

    /**
     * Update actual sales data for predictions
     */
    public function updateActualData(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'year' => 'required|integer|min:2020|max:2030',
                'month' => 'required|integer|min:1|max:12',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $year = $request->year;
            $month = $request->month;
            $updatedCount = 0;

            // Get all predictions for the specified month
            $predictions = StockPrediction::whereYear('prediction_month', $year)
                ->whereMonth('prediction_month', $month)
                ->whereNull('actual_quantity')
                ->get();

            foreach ($predictions as $prediction) {
                // Calculate actual sales for this item in the specified month
                $actualSales = OutgoingItem::where('item_id', $prediction->item_id)
                    ->whereYear('outgoing_date', $year)
                    ->whereMonth('outgoing_date', $month)
                    ->sum('quantity');

                // Update prediction with actual data
                $prediction->update([
                    'actual_quantity' => $actualSales,
                    'actual_updated_at' => now()
                ]);

                $updatedCount++;

                Log::info('Updated actual data for prediction', [
                    'prediction_id' => $prediction->id,
                    'item_id' => $prediction->item_id,
                    'actual_quantity' => $actualSales,
                    'predicted_quantity' => $prediction->predicted_quantity
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => "Data aktual berhasil diperbarui untuk {$updatedCount} prediksi",
                'updated_count' => $updatedCount,
                'month' => Carbon::create($year, $month)->locale('id')->format('F Y')
            ]);
        } catch (\Exception $e) {
            Log::error('Update actual data error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memperbarui data aktual: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get prediction history and accuracy
     */
    public function getPredictionHistory(Request $request)
    {
        try {
            $itemId = $request->get('item_id');
            $limit = $request->get('limit', 10);

            $query = StockPrediction::with('item.category')
                ->orderBy('prediction_month', 'desc');

            if ($itemId) {
                $query->where('item_id', $itemId);
            }

            $predictions = $query->limit($limit)->get();

            $formattedPredictions = $predictions->map(function ($prediction) {
                return [
                    'id' => $prediction->id,
                    'item_name' => $prediction->item->name,
                    'category' => $prediction->item->category ? $prediction->item->category->name : 'Uncategorized',
                    'prediction_month' => $prediction->formatted_month,
                    'prediction_type' => $prediction->prediction_type,
                    'predicted_quantity' => $prediction->predicted_quantity,
                    'actual_quantity' => $prediction->actual_quantity,
                    'accuracy' => $prediction->accuracy,
                    'confidence' => $prediction->confidence,
                    'predicted_at' => $prediction->predicted_at->format('d/m/Y H:i'),
                    'actual_updated_at' => $prediction->actual_updated_at ? $prediction->actual_updated_at->format('d/m/Y H:i') : null
                ];
            });

            // Calculate overall accuracy statistics
            $predictionsWithActual = $predictions->filter(function ($prediction) {
                return $prediction->actual_quantity !== null;
            });

            $averageAccuracy = $predictionsWithActual->avg('accuracy');
            $totalPredictions = $predictions->count();
            $predictionsWithActualCount = $predictionsWithActual->count();

            return response()->json([
                'success' => true,
                'predictions' => $formattedPredictions,
                'statistics' => [
                    'total_predictions' => $totalPredictions,
                    'predictions_with_actual' => $predictionsWithActualCount,
                    'average_accuracy' => $averageAccuracy ? round($averageAccuracy, 2) : null,
                    'completion_rate' => $totalPredictions > 0 ? round(($predictionsWithActualCount / $totalPredictions) * 100, 2) : 0
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Get prediction history error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengambil riwayat prediksi: ' . $e->getMessage()
            ], 500);
        }
    }
}
