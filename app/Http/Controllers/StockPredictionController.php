<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\OutgoingItem;
use App\Models\StockPrediction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class StockPredictionController extends Controller
{
    public function index()
    {
        $items = Item::with('category')->get();
        $predictions = StockPrediction::with('item')
            ->active()
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        return view('predictions.index', compact('items', 'predictions'));
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
            $prediction = $this->savePredictionResult($result, $item, $request->prediction_period);

            return response()->json([
                'success' => true,
                'message' => 'Prediksi berhasil dibuat!',
                'prediction' => $prediction,
                'results' => $result
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
            // Get last 3 days of outgoing items for this product
            // get today's date
            $today = Carbon::now()->format('Y-m-d');
            $yesterday = Carbon::yesterday()->format('Y-m-d');
            // $twoDaysAgo = Carbon::now()->subDays(2)->format('Y-m-d');
            $threeDaysAgo = Carbon::now()->subDays(3)->format('Y-m-d');

            $lastThreeDays = OutgoingItem::where('item_id', $item->id)
                ->whereBetween('outgoing_date', [$threeDaysAgo, $yesterday])
                ->get();


            if ($lastThreeDays->count() < 3) {
                return [
                    'success' => false,
                    'message' => 'Data penjualan harian tidak cukup untuk prediksi.'
                ];
            }
            Log::info('Daily prediction inputs', [
                'item_id' => $item->id,
                'item_name' => $item->item_name,
                'last_three_days' => $lastThreeDays->pluck('quantity')->toArray()
            ]);

            $dailyTotals = [];

            foreach ($lastThreeDays as $item) {
                $dateKey = $item->outgoing_date->format('Y-m-d');
                if (!isset($dailyTotals[$dateKey])) {
                    $dailyTotals[$dateKey] = 0;
                }
                $dailyTotals[$dateKey] += $item->quantity;
            }

            // dd($dailyTotals); // Debugging line to check daily totals

            // Sort by date descending to get proper lag order
            krsort($dailyTotals);
            $lags = array_values($dailyTotals);

            // Ensure we have exactly 3 lag values
            $lag1 = $lags[0] ?? 0; // Yesterday
            $lag2 = $lags[1] ?? 0; // 2 days ago
            $lag3 = $lags[2] ?? 0; // 3 days ago
            Log::info('Daily prediction lags', [
                'Item' => $item,
                'lag1' => $lag1,
                'lag2' => $lag2,
                'lag3' => $lag3
            ]);

            Log::info('Daily prediction inputs', [
                'item_id' => $item->item_id,
                'item_name' => $item->item_name,
                'lag1' => $lag1,
                'lag2' => $lag2,
                'lag3' => $lag3
            ]);

            // Call Python prediction script using item ID
            $pythonResult = $this->callPythonPredict($item->item_id, 'hari', [$lag1, $lag2, $lag3]);

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
                'confidence' => $this->calculateConfidence($lags),
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

    private function savePredictionResult($result, $item, $predictionPeriod)
    {
        // Deactivate previous predictions for this item
        StockPrediction::where('item_id', $item->id)->update(['is_active' => false]);

        // Create new prediction record
        $prediction = StockPrediction::create([
            'item_id' => $item->id,
            'predicted_demand' => $result['prediction'],
            'prediction_confidence' => $result['confidence'],
            'prediction_period_start' => $result['period_start'],
            'prediction_period_end' => $result['period_end'],
            'feature_importance' => json_encode([
                'prediction_type' => $result['type'],
                'input_data' => $result['input_data']
            ]),
            'is_active' => true,
            'analyzed_at' => now(),
        ]);

        return $prediction;
    }

    public function destroy(StockPrediction $prediction)
    {
        $prediction->delete();

        return response()->json([
            'success' => true,
            'message' => 'Prediksi berhasil dihapus'
        ]);
    }

    public function generateModel(Request $request)
    {
        Log::info('Model generation request received');

        try {
            // Step 1: Export outgoing data to CSV
            $csvResult = $this->exportOutgoingDataToCsv();

            if (!$csvResult['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $csvResult['message']
                ], 500);
            }

            // Step 2: Train the model using Python script
            $trainingResult = $this->trainPythonModel();

            if (!$trainingResult['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $trainingResult['message']
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'Model berhasil diperbarui!',
                'details' => [
                    'csv_export' => $csvResult,
                    'model_training' => $trainingResult
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Model generation error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat generate model: ' . $e->getMessage()
            ], 500);
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
                $date = Carbon::parse($outgoing->outgoing_date->format('Y-m-d')); // Convert to string then parse
                $monthKey = $date->format('Y-m'); // e.g., "2024-01"
                $monthName = $date->locale('id')->format('F'); // Indonesian month name

                if (!isset($monthlyData[$monthKey])) {
                    $monthlyData[$monthKey] = [
                        'month_name' => $monthName,
                        'year' => $date->year,
                        'data' => []
                    ];
                }

                $monthlyData[$monthKey]['data'][] = [
                    'no' => count($monthlyData[$monthKey]['data']) + 1,
                    'id_trx' => $outgoing->id,
                    'tgl' => $date->format('d F Y'), // e.g., "15 Januari 2024"
                    'nama_barang' => $outgoing->item->id, // Use item ID instead of name
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
                fputcsv($file, ['no', 'id_trx', 'tgl', 'nama_barang', 'kategori', 'jumlah'], ',', '"', '\\');

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
                $command = "cd \"{$basePath}/scripts\" && source .venv/bin/activate && python stock_predictor.py train 2>&1";
            } else {
                // Fallback to system python
                $pythonCmd = $operatingSystem === 'Windows' ? 'python' : 'python3';
                $command = "cd \"{$basePath}/scripts\" && {$pythonCmd} stock_predictor.py train 2>&1";
            }

            Log::info('Running model training command: ' . $command);

            // Execute training command
            $output = [];
            $returnCode = 0;
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
}
