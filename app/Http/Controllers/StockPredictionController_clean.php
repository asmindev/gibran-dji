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

            // DEBUG: Log simplified response building
            Log::info('SIMPLIFIED: Building clean response', [
                'item_name' => $item->name,
                'prediction' => $result['prediction'],
                'accuracy' => $result['details']['prediction_accuracy']['prediction_accuracy'] ?? 0
            ]);

            // Analyze stock status based on prediction type
            $stockAnalysis = $this->analyzeStockStatus(
                $result['prediction'],
                $item->stock ?? 0,
                $request->prediction_type,
                $item->name
            );

            // Clean and structure the response - simplified
            $cleanResponse = [
                'success' => true,
                'message' => 'Prediksi berhasil dibuat!',
                'data' => [
                    'product_name' => (string) $item->name,
                    'prediction_accuracy' => round($result['details']['prediction_accuracy']['prediction_accuracy'] ?? 0, 2),
                    'prediction_result' => round($result['prediction'], 2),
                    'current_stock' => (int) ($item->stock ?? 0),
                    'execution_time_ms' => round($result['execution_time_ms'] ?? 0, 2),
                    'stock_analysis' => $stockAnalysis,
                ],
            ];

            Log::info('SIMPLIFIED: About to return clean response', $cleanResponse);
            return response()->json($cleanResponse);
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
            // Simplified: Get outgoing items data for the last 3 months
            $outgoingData = OutgoingItem::where('item_id', $item->id)
                ->where('outgoing_date', '>=', Carbon::now()->subMonths(3))
                ->orderBy('outgoing_date', 'desc')
                ->get();

            if ($outgoingData->isEmpty()) {
                return [
                    'success' => false,
                    'message' => 'Tidak ada data penjualan dalam 3 bulan terakhir untuk prediksi.'
                ];
            }

            // Simple calculation: just get monthly average
            $totalQuantity = $outgoingData->sum('quantity');
            $monthlyData = $outgoingData->groupBy(function ($outgoingItem) {
                return $outgoingItem->outgoing_date->format('Y-m');
            });
            $totalMonths = $monthlyData->count();
            $avgMonthlySales = $totalMonths > 0 ? $totalQuantity / $totalMonths : 0;

            // Minimal parameters - just the essential one
            $params = [
                'avg_monthly_sales' => round($avgMonthlySales, 2),
            ];

            // Optional: Add simple sales velocity if enough data
            if ($totalMonths >= 2) {
                $monthlyTotals = $monthlyData->map->sum('quantity');
                $recentMonth = $monthlyTotals->values()->first();
                $previousMonth = $monthlyTotals->values()->get(1, 0);

                if ($previousMonth > 0) {
                    $salesVelocity = (($recentMonth - $previousMonth) / $previousMonth) * 100;
                    $params['sales_velocity'] = round($salesVelocity, 2);
                }
            }

            Log::info('Calculated simplified prediction parameters', [
                'item_id' => $item->id,
                'prediction_type' => $predictionType,
                'params' => $params,
                'data_months' => $totalMonths
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
     * Call Python prediction script using predict.py
     */
    private function callNewPythonPredict($item, $predictionType, $params)
    {
        try {
            $basePath = base_path();
            $scriptPath = $basePath . '/scripts/predict.py';

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
                $command = "cd {$basePath}/scripts && source .venv/bin/activate && python predict.py";
            } else {
                // Fallback to system Python
                Log::warning('Virtual environment not found, using system Python');
                $command = "cd {$basePath}/scripts && python predict.py";
            }

            $command .= " --product " . escapeshellarg((string) $item->id);
            $command .= " --type " . escapeshellarg($predictionType);

            // Add avg-monthly parameter (required by predict.py)
            $avgMonthly = $params['avg_monthly_sales'] ?? 0;
            $command .= " --avg-monthly " . escapeshellarg((string) $avgMonthly);

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

            foreach ($output as $line) {
                if (strpos($line, 'PREDICTION_RESULT:') !== false) {
                    $jsonStr = substr($line, strlen('PREDICTION_RESULT:'));
                    $predictionResult = json_decode(trim($jsonStr), true);
                    break;
                }
            }

            if (!$predictionResult || !isset($predictionResult['success'])) {
                return [
                    'success' => false,
                    'message' => 'Tidak dapat mengparse hasil prediksi dari output Python'
                ];
            }

            if (!$predictionResult['success']) {
                return [
                    'success' => false,
                    'message' => $predictionResult['message'] ?? 'Prediksi gagal'
                ];
            }

            // Extract prediction result
            $finalPrediction = $predictionResult['prediction'] ?? 0;
            $productInfo = $predictionResult['product_info'] ?? [];
            $modelPerformance = $predictionResult['model_performance'] ?? [];
            $executionTime = $predictionResult['execution_time_ms'] ?? null;
            $timingBreakdown = $predictionResult['timing_breakdown'] ?? [];

            return [
                'success' => true,
                'prediction' => $finalPrediction,
                'type' => $predictionType,
                'input_params' => $params,
                'product_info' => $productInfo,
                'model_performance' => $modelPerformance,
                'execution_time_ms' => $executionTime,
                'timing_breakdown' => $timingBreakdown,
                'features_used' => $predictionResult['features_used'] ?? [],
                'validation_message' => $predictionResult['validation_message'] ?? '',
                'period_start' => now()->format('Y-m-d'),
                'period_end' => now()->addMonth()->format('Y-m-d'),
                'details' => $predictionResult
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
            Log::info('Model training result', $result);

            if ($result['success']) {
                Cache::put('model_training_status', 'completed', 3600);
                Cache::put('last_training_date', now()->toDateTimeString(), 86400);

                // Extract model performance info if available
                $modelInfo = $result['model_info'] ?? [];
                $exportStats = $result['export_stats'] ?? [];

                return response()->json([
                    'success' => true,
                    'message' => 'Model berhasil ditraining!',
                    'data' => [
                        'training_date' => now()->toDateTimeString(),
                        'training_status' => 'completed',
                        'dataset_info' => [
                            'sales_records' => $exportStats['sales_records'] ?? 0,
                            'restock_records' => $exportStats['restock_records'] ?? 0,
                            'total_products' => $exportStats['total_products'] ?? 0
                        ],
                        'model_performance' => [
                            'sales_model' => [
                                'accuracy_r2' => round($modelInfo['sales']['test_r2'] ?? 0, 4),
                                'rmse' => round($modelInfo['sales']['test_rmse'] ?? 0, 2),
                                'training_samples' => $modelInfo['sales']['training_samples'] ?? 0
                            ],
                            'restock_model' => [
                                'accuracy_r2' => round($modelInfo['restock']['test_r2'] ?? 0, 4),
                                'rmse' => round($modelInfo['restock']['test_rmse'] ?? 0, 2),
                                'training_samples' => $modelInfo['restock']['training_samples'] ?? 0
                            ]
                        ]
                    ],
                    'metadata' => [
                        'timestamp' => now()->toISOString(),
                        'version' => '1.0',
                        'algorithm' => 'Random Forest'
                    ]
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
            'success' => true,
            'data' => [
                'training_status' => $status,
                'last_training_date' => $lastTraining,
                'status_message' => $this->getStatusMessage($status),
                'is_ready_for_prediction' => $status === 'completed'
            ],
            'metadata' => [
                'timestamp' => now()->toISOString(),
                'version' => '1.0'
            ]
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
            $scriptPath = $basePath . '/scripts/train_model.py';

            // Check if script exists
            if (!file_exists($scriptPath)) {
                return [
                    'success' => false,
                    'message' => 'Script training tidak ditemukan'
                ];
            }

            // First, export data for training
            $exportResult = $this->exportTrainingData();

            if ($exportResult['sales_records'] === 0 && $exportResult['restock_records'] === 0) {
                return [
                    'success' => false,
                    'message' => 'Tidak ada data untuk training. Pastikan ada data penjualan atau restock.'
                ];
            }

            // Build training command with virtual environment activation
            $venvPath = $basePath . '/scripts/.venv';

            if (is_dir($venvPath)) {
                // Use virtual environment
                Log::info('Using Python virtual environment for training: ' . $venvPath);
                $command = "cd {$basePath}/scripts && source .venv/bin/activate && python -u train_model.py";
            } else {
                // Fallback to system Python
                Log::warning('Virtual environment not found for training, using system Python');
                $command = "cd {$basePath}/scripts && python -u train_model.py";
            }

            Log::info('Running Python training command: ' . $command);

            // Execute command with timeout
            $descriptorspec = [
                0 => ["pipe", "r"],  // stdin
                1 => ["pipe", "w"],  // stdout
                2 => ["pipe", "w"],  // stderr
            ];

            $process = proc_open($command, $descriptorspec, $pipes);

            if (is_resource($process)) {
                // Close stdin
                fclose($pipes[0]);

                // Set a timeout for the process
                $timeout = 120; // 2 minutes
                $startTime = time();

                $output = '';
                $errorOutput = '';

                // Read output with timeout
                while (time() - $startTime < $timeout) {
                    $read = [$pipes[1], $pipes[2]];
                    $write = null;
                    $except = null;

                    if (stream_select($read, $write, $except, 1) > 0) {
                        if (in_array($pipes[1], $read)) {
                            $line = fgets($pipes[1]);
                            if ($line !== false) {
                                $output .= $line;
                            }
                        }
                        if (in_array($pipes[2], $read)) {
                            $line = fgets($pipes[2]);
                            if ($line !== false) {
                                $errorOutput .= $line;
                            }
                        }
                    }

    private function analyzeStockStatus($prediction, $currentStock, $predictionType, $productName)
    {
        $prediction = round($prediction, 2);
        $currentStock = (int) $currentStock;
        $minimumStock = 10; // Default minimum stock threshold

        if ($predictionType === 'sales') {
            // For sales prediction (barang keluar)
            if ($prediction > $currentStock) {
                // Understock: prediction exceeds current stock
                $shortage = $prediction - $currentStock;
                return [
                    'type' => 'sales',
                    'title' => '❌ Stok Tidak Mencukupi',
                    'message' => "Stok saat ini {$currentStock} unit tidak mencukupi untuk prediksi barang keluar sebesar {$prediction} unit.",
                    'category' => 'understock',
                    'status' => 'danger',
                    'shortage' => $shortage,
                    'details' => [
                        'current_stock' => $currentStock,
                        'minimum_stock' => $minimumStock,
                        'predicted_outgoing' => $prediction,
                        'status_text' => 'Understock'
                    ],
                    'summary' => "Kekurangan stok: {$shortage} unit | Status: Understock",
                    'icon' => '📤',
                    'color' => 'red'
                ];
            } else {
                // Stock sufficient for sales
                $surplus = $currentStock - $prediction;
                return [
                    'type' => 'sales',
                    'title' => '✅ Stok Mencukupi',
                    'message' => "Stok saat ini {$currentStock} unit mencukupi untuk prediksi barang keluar sebesar {$prediction} unit.",
                    'category' => 'sufficient',
                    'status' => 'success',
                    'surplus' => $surplus,
                    'details' => [
                        'current_stock' => $currentStock,
                        'minimum_stock' => $minimumStock,
                        'predicted_outgoing' => $prediction,
                        'status_text' => 'Aman'
                    ],
                    'summary' => "Surplus stok: {$surplus} unit | Status: Aman",
                    'icon' => '📤',
                    'color' => 'green'
                ];
            }
        } else {
            // For restock prediction (barang masuk)
            // We need to estimate potential sales to determine overstock
            // Assume average sales is 70% of current stock or use a reasonable estimate
            $estimatedSales = max(round($currentStock * 0.1), 40); // Estimate monthly sales
            $futureStock = $currentStock + $prediction;

            if ($futureStock > ($estimatedSales * 3)) {
                // Potential overstock: future stock is much higher than estimated demand
                return [
                    'type' => 'restock',
                    'title' => '⚠️ Stok Berlebih',
                    'message' => "Prediksi barang masuk sebesar {$prediction} unit akan menambah stok menjadi {$futureStock} unit.",
                    'category' => 'overstock',
                    'status' => 'warning',
                    'future_stock' => $futureStock,
                    'estimated_demand' => $estimatedSales,
                    'details' => [
                        'current_stock' => $currentStock,
                        'predicted_incoming' => $prediction,
                        'estimated_outgoing' => $estimatedSales,
                        'status_text' => 'Overstock/aman'
                    ],
                    'summary' => "Kondisi ini berpotensi Overstock karena permintaan hanya {$estimatedSales} unit.",
                    'icon' => '➕',
                    'color' => 'orange'
                ];
            } else {
                // Normal restock
                return [
                    'type' => 'restock',
                    'title' => '✅ Restock Normal',
                    'message' => "Prediksi barang masuk sebesar {$prediction} unit akan menambah stok menjadi {$futureStock} unit.",
                    'category' => 'normal',
                    'status' => 'success',
                    'future_stock' => $futureStock,
                    'estimated_demand' => $estimatedSales,
                    'details' => [
                        'current_stock' => $currentStock,
                        'predicted_incoming' => $prediction,
                        'estimated_outgoing' => $estimatedSales,
                        'status_text' => 'Normal'
                    ],
                    'summary' => "Restock normal dengan perkiraan permintaan {$estimatedSales} unit.",
                    'icon' => '➕',
                    'color' => 'green'
                ];
            }
        }
    }

    /**
