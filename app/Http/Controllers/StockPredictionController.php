<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\IncomingItem;
use App\Models\OutgoingItem;
use App\Models\StockPrediction;
use Illuminate\Http\Request;
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

        // Validate request
        $validator = Validator::make($request->all(), [
            'item_id' => 'required|exists:items,id',
            'prediction_type' => 'required|in:sales,restock',
        ], [
            'item_id.required' => 'Silakan pilih produk',
            'item_id.exists' => 'Produk yang dipilih tidak valid',
            'prediction_type.required' => 'Silakan pilih tipe prediksi',
            'prediction_type.in' => 'Tipe prediksi harus sales atau restock',
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
                'item_name' => $item->name,
                'prediction_type' => $request->prediction_type
            ]);

            // Calculate prediction parameters based on historical data
            $predictionParams = $this->calculatePredictionParameters($item, $request->prediction_type);

            if (!$predictionParams['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $predictionParams['message']
                ], 400);
            }

            // Call new Python prediction script
            $result = $this->callNewPythonPredict($item, $request->prediction_type, $predictionParams['params']);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 500);
            }

            // Save prediction to database
            $stockPrediction = $this->savePredictionToDatabase($item, $result, $request->prediction_type);

            return response()->json([
                'success' => true,
                'message' => 'Prediksi berhasil dibuat!',
                'results' => $result,
                'prediction_id' => $stockPrediction->id,
                'product' => [
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

    /**
     * Calculate prediction parameters from historical data
     */
    private function calculatePredictionParameters($item, $predictionType)
    {
        try {
            // Get outgoing items data for the last 30 days
            $outgoingData = OutgoingItem::where('item_id', $item->id)
                ->where('outgoing_date', '>=', Carbon::now()->subDays(30))
                ->orderBy('outgoing_date', 'desc')
                ->get();

            if ($outgoingData->isEmpty()) {
                return [
                    'success' => false,
                    'message' => 'Tidak ada data penjualan dalam 30 hari terakhir untuk prediksi.'
                ];
            }

            // Calculate basic metrics
            $totalQuantity = $outgoingData->sum('quantity');
            $totalDays = $outgoingData->groupBy(function ($item) {
                return $item->outgoing_date->format('Y-m-d');
            })->count();

            $totalTransactions = $outgoingData->count();
            $avgDailySales = $totalDays > 0 ? $totalQuantity / $totalDays : 0;

            // Calculate sales velocity (trend)
            $recentWeekData = $outgoingData->where('outgoing_date', '>=', Carbon::now()->subDays(7));
            $olderWeekData = $outgoingData->where('outgoing_date', '<', Carbon::now()->subDays(7))
                ->where('outgoing_date', '>=', Carbon::now()->subDays(14));

            $recentWeekTotal = $recentWeekData->sum('quantity');
            $olderWeekTotal = $olderWeekData->sum('quantity');

            $salesVelocity = 0;
            if ($olderWeekTotal > 0) {
                $salesVelocity = (($recentWeekTotal - $olderWeekTotal) / $olderWeekTotal) * 100;
            }

            // Calculate recent averages and totals
            $last7DaysData = $outgoingData->where('outgoing_date', '>=', Carbon::now()->subDays(7));
            $recentAvg = $last7DaysData->groupBy(function ($item) {
                return $item->outgoing_date->format('Y-m-d');
            })->map->sum('quantity')->avg() ?? 0;

            $recentTotal = $last7DaysData->sum('quantity');

            // Calculate sales consistency/volatility
            $dailyTotals = $outgoingData->groupBy(function ($item) {
                return $item->outgoing_date->format('Y-m-d');
            })->map->sum('quantity')->values()->toArray();

            $salesConsistency = 1.0;
            $salesVolatility = 1.0;

            if (count($dailyTotals) > 1) {
                $mean = array_sum($dailyTotals) / count($dailyTotals);
                $variance = 0;
                foreach ($dailyTotals as $value) {
                    $variance += pow($value - $mean, 2);
                }
                $variance /= count($dailyTotals);
                $stdDev = sqrt($variance);

                $salesVolatility = $mean > 0 ? $stdDev / $mean : 1.0;
                $salesConsistency = 1 / (1 + $salesVolatility);
            }

            // Prepare parameters based on prediction type
            $params = [
                'avg_daily_sales' => round($avgDailySales, 2),
                'sales_velocity' => round($salesVelocity, 2),
                'transaction_count' => $totalTransactions,
            ];

            if ($predictionType === 'sales') {
                $params['sales_consistency'] = round($salesConsistency, 2);
                $params['recent_avg'] = round($recentAvg, 2);
            } else { // restock
                $params['sales_volatility'] = round($salesVolatility, 2);
                $params['recent_total'] = round($recentTotal, 2);
            }

            Log::info('Calculated prediction parameters', [
                'item_id' => $item->id,
                'prediction_type' => $predictionType,
                'params' => $params,
                'data_days' => $totalDays
            ]);

            return [
                'success' => true,
                'params' => $params
            ];
        } catch (\Exception $e) {
            Log::error('Error calculating prediction parameters: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error menghitung parameter prediksi: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Call new Python prediction script using main.py interface
     */
    private function callNewPythonPredict($item, $predictionType, $params)
    {
        try {
            $basePath = base_path();
            $scriptPath = $basePath . '/scripts/main.py';

            // Check if script exists
            if (!file_exists($scriptPath)) {
                return [
                    'success' => false,
                    'message' => 'Script prediksi tidak ditemukan'
                ];
            }

            // Build command arguments with virtual environment activation
            $venvPath = $basePath . '/scripts/.venv';

            if (is_dir($venvPath)) {
                // Use virtual environment
                Log::info('Using Python virtual environment: ' . $venvPath);
                $command = "cd {$basePath}/scripts && source .venv/bin/activate && python main.py predict";
            } else {
                // Fallback to system Python
                Log::warning('Virtual environment not found, using system Python');
                $command = "cd {$basePath}/scripts && python main.py predict";
            }

            $command .= " --type " . escapeshellarg($predictionType);
            $command .= " --product " . escapeshellarg((string) $item->id);

            // Add parameters
            foreach ($params as $key => $value) {
                $paramName = str_replace('_', '-', $key);
                $command .= " --{$paramName} " . escapeshellarg((string) $value);
            }

            Log::info('Running Python prediction command: ' . $command);

            // Execute command
            $output = [];
            $returnCode = 0;
            exec($command . " 2>&1", $output, $returnCode);

            $outputString = implode("\n", $output);
            Log::info('Python prediction output: ' . $outputString);

            if ($returnCode !== 0) {
                return [
                    'success' => false,
                    'message' => 'Error executing prediction: ' . $outputString
                ];
            }

            // Parse prediction result from output
            $predictionResult = null;
            $executionTime = null;

            foreach ($output as $line) {
                if (strpos($line, 'Predicted Quantity:') !== false) {
                    preg_match('/Predicted Quantity:\s*([\d.]+)/', $line, $matches);
                    if (isset($matches[1])) {
                        $predictionResult = floatval($matches[1]);
                    }
                }
                if (strpos($line, 'Execution Time:') !== false) {
                    preg_match('/Execution Time:\s*([\d.]+)ms/', $line, $matches);
                    if (isset($matches[1])) {
                        $executionTime = floatval($matches[1]);
                    }
                }
            }

            if ($predictionResult === null) {
                return [
                    'success' => false,
                    'message' => 'Tidak dapat mengparse hasil prediksi dari output Python'
                ];
            }

            return [
                'success' => true,
                'prediction' => $predictionResult,
                'type' => $predictionType,
                'input_params' => $params,
                'execution_time_ms' => $executionTime,
                'period_start' => now()->format('Y-m-d'),
                'period_end' => now()->format('Y-m-d')
            ];
        } catch (\Exception $e) {
            Log::error('Python prediction call error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error calling Python prediction: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Train prediction models
     */
    public function trainModel(Request $request)
    {
        try {
            // Check if training is already in progress
            $status = Cache::get('model_training_status', 'idle');
            if ($status === 'training') {
                return response()->json([
                    'success' => false,
                    'message' => 'Model training sudah sedang berjalan'
                ], 400);
            }

            // Set training status
            Cache::put('model_training_status', 'training', 3600); // 1 hour timeout

            // Call Python training script
            $result = $this->callPythonTrain();

            if ($result['success']) {
                Cache::put('model_training_status', 'completed', 3600);
                Cache::put('last_training_date', now()->toDateTimeString(), 86400);

                return response()->json([
                    'success' => true,
                    'message' => 'Model berhasil ditraining!',
                    'training_date' => now()->toDateTimeString()
                ]);
            } else {
                Cache::put('model_training_status', 'failed', 3600);
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 500);
            }
        } catch (\Exception $e) {
            Cache::put('model_training_status', 'failed', 3600);
            Log::error('Model training error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error training model: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get training status
     */
    public function getTrainingStatus(Request $request)
    {
        $status = Cache::get('model_training_status', 'idle');
        $lastTraining = Cache::get('last_training_date');

        $response = [
            'status' => $status,
            'last_training' => $lastTraining,
            'message' => $this->getStatusMessage($status)
        ];

        return response()->json($response);
    }

    /**
     * Call Python training script
     */
    private function callPythonTrain()
    {
        try {
            $basePath = base_path();
            $scriptPath = $basePath . '/scripts/main.py';

            // Check if script exists
            if (!file_exists($scriptPath)) {
                return [
                    'success' => false,
                    'message' => 'Script training tidak ditemukan'
                ];
            }

            // First, export data for training
            $this->exportTrainingData();

            // Build training command with virtual environment activation
            $venvPath = $basePath . '/scripts/.venv';

            if (is_dir($venvPath)) {
                // Use virtual environment
                Log::info('Using Python virtual environment for training: ' . $venvPath);
                $command = "cd {$basePath}/scripts && source .venv/bin/activate && python main.py train --data-folder stock/data";
            } else {
                // Fallback to system Python
                Log::warning('Virtual environment not found for training, using system Python');
                $command = "cd {$basePath}/scripts && python main.py train --data-folder stock/data";
            }

            Log::info('Running Python training command: ' . $command);

            // Execute command
            $output = [];
            $returnCode = 0;
            exec($command . " 2>&1", $output, $returnCode);

            $outputString = implode("\n", $output);
            Log::info('Python training output: ' . $outputString);

            if ($returnCode !== 0) {
                return [
                    'success' => false,
                    'message' => 'Error executing training: ' . $outputString
                ];
            }

            // Check for success indicators in output
            if (strpos($outputString, 'TRAINING_COMPLETED') !== false) {
                return ['success' => true];
            } else {
                return [
                    'success' => false,
                    'message' => 'Training tidak berhasil diselesaikan'
                ];
            }
        } catch (\Exception $e) {
            Log::error('Python training call error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error calling Python training: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Export training data to CSV
     */
    private function exportTrainingData()
    {
        $dataPath = base_path('scripts/stock/data');

        // Create data directory if it doesn't exist
        if (!is_dir($dataPath)) {
            mkdir($dataPath, 0755, true);
        }

        // Export outgoing items data for sales prediction
        $outgoingItems = OutgoingItem::with('item')
            ->orderBy('outgoing_date', 'desc')
            ->get();

        $salesData = [];
        // Python script expects: ["no", "id_trx", "tgl", "id_item", "nama_barang", "kategori", "jumlah"]
        $salesData[] = ['no', 'id_trx', 'tgl', 'id_item', 'nama_barang', 'kategori', 'jumlah'];

        foreach ($outgoingItems as $index => $outgoingItem) {
            $salesData[] = [
                $index + 1, // no
                'TRX-' . $outgoingItem->id, // id_trx
                Carbon::parse($outgoingItem->outgoing_date)->format('Y-m-d'), // tgl
                $outgoingItem->item_id, // id_item
                $outgoingItem->item->name ?? 'Unknown', // nama_barang
                'keluar', // kategori (sales transactions are "keluar")
                $outgoingItem->quantity // jumlah
            ];
        }

        // Export incoming items data for restock prediction
        $incomingItems = IncomingItem::with('item')
            ->orderBy('incoming_date', 'desc')
            ->get();

        $restockData = [];
        // Python script expects: ["no", "id_trx", "tgl", "id_item", "nama_barang", "kategori", "jumlah"]
        $restockData[] = ['no', 'id_trx', 'tgl', 'id_item', 'nama_barang', 'kategori', 'jumlah'];

        foreach ($incomingItems as $index => $incomingItem) {
            $restockData[] = [
                $index + 1, // no
                'TRX-' . $incomingItem->id, // id_trx
                Carbon::parse($incomingItem->incoming_date)->format('Y-m-d'), // tgl
                $incomingItem->item_id, // id_item
                $incomingItem->item->name ?? 'Unknown', // nama_barang
                'masuk', // kategori (restock transactions are "masuk")
                $incomingItem->quantity // jumlah
            ];
        }

        // Write CSV files
        $this->writeCSV($dataPath . '/sales_data.csv', $salesData);
        $this->writeCSV($dataPath . '/restock_data.csv', $restockData);

        Log::info('Training data exported', [
            'sales_records' => count($salesData) - 1,
            'restock_records' => count($restockData) - 1,
            'data_path' => $dataPath
        ]);
    }

    /**
     * Write data to CSV file
     */
    private function writeCSV($filename, $data)
    {
        $handle = fopen($filename, 'w');
        foreach ($data as $row) {
            fputcsv($handle, $row);
        }
        fclose($handle);
    }

    /**
     * Save prediction result to database
     */
    private function savePredictionToDatabase($item, $result, $predictionType)
    {
        $predictionData = [
            'item_id' => $item->id,
            'product' => $item->name,
            'prediction' => $result['prediction'],
            'prediction_type' => $predictionType,
            'month' => now()->startOfMonth(),
            'created_at' => now(),
            'updated_at' => now()
        ];

        $stockPrediction = StockPrediction::create($predictionData);

        Log::info('Prediction saved to database', [
            'prediction_id' => $stockPrediction->id,
            'item_id' => $item->id,
            'prediction_type' => $predictionType,
            'prediction_value' => $result['prediction']
        ]);

        return $stockPrediction;
    }

    /**
     * Get status message for training status
     */
    private function getStatusMessage($status)
    {
        switch ($status) {
            case 'training':
                return 'Model sedang ditraining...';
            case 'completed':
                return 'Training berhasil diselesaikan';
            case 'failed':
                return 'Training gagal';
            default:
                return 'Model siap untuk ditraining';
        }
    }
}
