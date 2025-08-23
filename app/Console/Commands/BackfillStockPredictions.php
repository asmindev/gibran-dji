<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\StockPrediction;
use App\Models\OutgoingItem;
use App\Models\IncomingItem;
use App\Models\Item;
use Carbon\Carbon;

class BackfillStockPredictions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'predictions:backfill
                            {--start-month=6 : Start month (1-12)}
                            {--start-year=2025 : Start year}
                            {--end-month=8 : End month (1-12)}
                            {--end-year=2025 : End year}
                            {--force : Force overwrite existing predictions}
                            {--dry-run : Show what would be done without actually doing it}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfill stock predictions using ML predictor and actual sales data for historical months';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $startMonth = $this->option('start-month');
        $startYear = $this->option('start-year');
        $endMonth = $this->option('end-month');
        $endYear = $this->option('end-year');
        $force = $this->option('force');
        $dryRun = $this->option('dry-run');

        $this->info('=== Stock Predictions Backfill (Using ML Predictor) ===');

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No data will be saved');
        }

        // Check if Python stock predictor models exist
        $this->checkPythonPredictor();

        // Auto-detect available data range if using default values
        $dataRange = $this->getAvailableDataRange();
        if ($dataRange) {
            $this->info("Available data range: {$dataRange['min_date']} to {$dataRange['max_date']}");

            $maxDate = Carbon::parse($dataRange['max_date']);
            $minDate = Carbon::parse($dataRange['min_date']);

            // Auto-adjust start month to be from the earliest data if using defaults
            if ($this->option('start-month') == 6 && $this->option('start-year') == 2025) {
                $this->info("Auto-adjusting start date to: {$minDate->format('F Y')} (earliest available data)");
                $startMonth = $minDate->month;
                $startYear = $minDate->year;
            }

            // Auto-adjust end month to be just the next month after last data
            if ($this->option('end-month') == 8 && $this->option('end-year') == 2025) {
                // Set end date to be exactly 1 month after the last data
                $suggestedEndDate = $maxDate->copy()->addMonth();

                $this->info("Auto-adjusting end date to: {$suggestedEndDate->format('F Y')} (1 month after last data)");
                $endMonth = $suggestedEndDate->month;
                $endYear = $suggestedEndDate->year;
            }
        }

        $startDate = Carbon::create($startYear, $startMonth, 1);
        $endDate = Carbon::create($endYear, $endMonth, 1);

        $this->info("Processing period: {$startDate->format('F Y')} to {$endDate->format('F Y')}");

        // Get all items
        $items = Item::all();

        if ($items->isEmpty()) {
            $this->error('No items found in database. Please seed some items first.');
            return 1;
        }

        $this->info("Found {$items->count()} items to process");

        // Clear all existing stock predictions before processing
        if (!$dryRun) {
            $existingCount = StockPrediction::count();
            if ($existingCount > 0) {
                $this->warn("🗑️  Clearing {$existingCount} existing stock predictions from database...");
                StockPrediction::truncate();
                $this->info("✅ Database cleared successfully");
            } else {
                $this->info("ℹ️  No existing predictions found to clear");
            }
        } else {
            $existingCount = StockPrediction::count();
            $this->warn("🗑️  [DRY RUN] Would clear {$existingCount} existing stock predictions from database");
        }

        $currentDate = $startDate->copy();
        $totalProcessed = 0;
        $totalSkipped = 0;
        $totalErrors = 0;

        while ($currentDate->lte($endDate)) {
            $this->newLine();
            $this->info("Processing month: {$currentDate->format('F Y')}");

            $monthResults = $this->processMonth($currentDate, $items, $force, $dryRun);

            $totalProcessed += $monthResults['processed'];
            $totalSkipped += $monthResults['skipped'];
            $totalErrors += $monthResults['errors'];

            $currentDate->addMonth();
        }

        $this->newLine();
        $this->info('=== Summary ===');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Processed', $totalProcessed],
                ['Total Skipped', $totalSkipped],
                ['Total Errors', $totalErrors],
            ]
        );

        if (!$dryRun && $totalProcessed > 0) {
            $this->info('✅ Backfill completed successfully!');

            // Show some statistics
            $this->showPredictionStats($startDate, $endDate);
        }

        return 0;
    }

    /**
     * Process predictions for a specific month
     */
    private function processMonth(Carbon $month, $items, $force, $dryRun)
    {
        $processed = 0;
        $skipped = 0;
        $errors = 0;

        $bar = $this->output->createProgressBar($items->count());
        $bar->start();

        foreach ($items as $item) {
            try {
                // Check if prediction already exists
                $existingPrediction = StockPrediction::where('item_id', $item->id)
                    ->whereYear('month', $month->year)
                    ->whereMonth('month', $month->month)
                    ->where('prediction_type', 'monthly')
                    ->first();

                if ($existingPrediction && !$force) {
                    $skipped++;
                    $bar->advance();
                    continue;
                }

                // Calculate prediction based on previous month's data
                $prediction = $this->calculatePrediction($item, $month);

                // Calculate actual sales for this month
                $actual = $this->calculateActualSales($item, $month);

                if (!$dryRun) {
                    if ($existingPrediction && $force) {
                        // Update existing
                        $existingPrediction->update([
                            'prediction' => $prediction,
                            'actual' => $actual,
                        ]);
                    } else {
                        // Create new
                        StockPrediction::create([
                            'item_id' => $item->id,
                            'prediction' => $prediction,
                            'actual' => $actual,
                            'product' => $item->item_name,
                            'month' => $month->startOfMonth()->format('Y-m-d'),
                            'prediction_type' => 'monthly', // ML-based monthly predictions
                        ]);
                    }
                }

                $processed++;
            } catch (\Exception $e) {
                $this->error("Error processing {$item->item_name}: " . $e->getMessage());
                $errors++;
            }

            $bar->advance();
        }

        $bar->finish();

        $this->newLine();
        $this->line("  📊 Results: Processed: {$processed}, Skipped: {$skipped}, Errors: {$errors}");

        return [
            'processed' => $processed,
            'skipped' => $skipped,
            'errors' => $errors
        ];
    }

    /**
     * Calculate prediction for an item using Python stock predictor
     */
    private function calculatePrediction($item, Carbon $targetMonth)
    {
        try {
            // Get previous month's total sales for monthly prediction
            $prevMonth = $targetMonth->copy()->subMonth();
            $prevMonthTotal = OutgoingItem::where('item_id', $item->id)
                ->whereYear('outgoing_date', $prevMonth->year)
                ->whereMonth('outgoing_date', $prevMonth->month)
                ->sum('quantity');

            // Use the same method as StockPredictionController
            $pythonResult = $this->callPythonPredict($item->id, 'bulan', [$prevMonthTotal]);

            // Handle both old integer format and new dictionary format
            if (is_array($pythonResult)) {
                $prediction = $pythonResult['prediction'];
                $this->line("  🤖 ML Prediction for {$item->item_name}: {$prediction} units (prev month: {$prevMonthTotal})");
            } else {
                $prediction = $pythonResult;
                $this->line("  🤖 ML Prediction for {$item->item_name}: {$prediction} units (prev month: {$prevMonthTotal})");
            }

            return max(0, (int) $prediction);
        } catch (\Exception $e) {
            $this->warn("  ❌ Error calling Python predictor for item {$item->item_name}: " . $e->getMessage());
            return $this->getFallbackPrediction($item, $targetMonth);
        }
    }

    /**
     * Call Python predictor using the same method as StockPredictionController
     */
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

        // Build command arguments for stock_predictor.py interface using item ID
        $escapedItemId = escapeshellarg((string) $itemId);
        $escapedType = escapeshellarg($predictionType);
        $escapedParams = array_map('escapeshellarg', $parameters);

        // Build command with virtual environment activation
        if (file_exists($pythonPath)) {
            // Virtual environment exists, use it
            if ($operatingSystem === 'Windows') {
                // Windows command with venv activation
                $command = "cd /d \"{$basePath}\\scripts\" && \".venv\\Scripts\\activate.bat\" && python stock_predictor.py predict {$escapedType} {$escapedItemId} " . implode(' ', $escapedParams) . " 2>&1";
            } else {
                // Linux/Unix command with venv activation
                $command = "cd \"{$basePath}/scripts\" && source .venv/bin/activate && python stock_predictor.py predict {$escapedType} {$escapedItemId} " . implode(' ', $escapedParams) . " 2>&1";
            }
        } else {
            // Fallback to system python if venv doesn't exist
            $this->warn("Virtual environment not found, using system Python");
            $pythonCmd = $operatingSystem === 'Windows' ? 'python' : 'python3';
            if ($operatingSystem === 'Windows') {
                $command = "cd /d \"{$basePath}\\scripts\" && {$pythonCmd} stock_predictor.py predict {$escapedType} {$escapedItemId} " . implode(' ', $escapedParams) . " 2>&1";
            } else {
                $command = "cd \"{$basePath}/scripts\" && {$pythonCmd} stock_predictor.py predict {$escapedType} {$escapedItemId} " . implode(' ', $escapedParams) . " 2>&1";
            }
        }

        // Execute command
        $output = [];
        $returnCode = 0;
        exec($command, $output, $returnCode);

        $outputString = implode("\n", $output);

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

    /**
     * Fallback prediction method when Python predictor fails
     */
    private function getFallbackPrediction($item, Carbon $targetMonth)
    {
        // Get previous 3 months data for better prediction
        $predictions = [];

        for ($i = 1; $i <= 3; $i++) {
            $prevMonth = $targetMonth->copy()->subMonths($i);

            $sales = OutgoingItem::where('item_id', $item->id)
                ->whereYear('outgoing_date', $prevMonth->year)
                ->whereMonth('outgoing_date', $prevMonth->month)
                ->sum('quantity');

            $predictions[] = $sales;
        }

        // Simple moving average with trend adjustment
        $nonZeroSales = array_filter($predictions, function ($sale) {
            return $sale > 0;
        });

        if (empty($nonZeroSales)) {
            // No historical data, use a conservative prediction
            return 5; // Default minimum prediction
        }

        $average = array_sum($nonZeroSales) / count($nonZeroSales);

        // Add seasonal factor based on month
        $seasonalFactor = $this->getSeasonalFactor($targetMonth->month);

        $prediction = round($average * $seasonalFactor);

        return max(0, $prediction);
    }

    /**
     * Calculate actual sales for an item in a specific month
     */
    private function calculateActualSales($item, Carbon $month)
    {
        return OutgoingItem::where('item_id', $item->id)
            ->whereYear('outgoing_date', $month->year)
            ->whereMonth('outgoing_date', $month->month)
            ->sum('quantity');
    }

    /**
     * Get seasonal factor for prediction adjustment
     */
    private function getSeasonalFactor($month)
    {
        $factors = [
            1 => 0.8,  // January - post holiday
            2 => 0.9,  // February
            3 => 1.0,  // March
            4 => 1.0,  // April
            5 => 1.1,  // May - spring
            6 => 1.2,  // June - summer
            7 => 1.2,  // July - summer
            8 => 1.1,  // August
            9 => 1.0,  // September
            10 => 1.0, // October
            11 => 1.1, // November - pre holiday
            12 => 1.3, // December - holiday season
        ];

        return $factors[$month] ?? 1.0;
    }

    /**
     * Show prediction statistics
     */
    private function showPredictionStats(Carbon $startDate, Carbon $endDate)
    {
        $this->newLine();
        $this->info('=== Prediction Statistics ===');

        $predictions = StockPrediction::whereBetween('month', [
            $startDate->format('Y-m-d'),
            $endDate->format('Y-m-d')
        ])->get();

        if ($predictions->isEmpty()) {
            $this->warn('No predictions found for the specified period.');
            return;
        }

        $totalPredictions = $predictions->count();
        $withActual = $predictions->whereNotNull('actual')->count();
        $totalPredicted = $predictions->sum('prediction');
        $totalActual = $predictions->sum('actual');

        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Records', $totalPredictions],
                ['Records with Actual', $withActual],
                ['Total Predicted', number_format($totalPredicted)],
                ['Total Actual', number_format($totalActual)],
                ['Period', $startDate->format('M Y') . ' - ' . $endDate->format('M Y')],
            ]
        );

        // Show accuracy for predictions with actual data
        $predictionsWithActual = $predictions->whereNotNull('actual');
        if ($predictionsWithActual->isNotEmpty()) {
            $accuracies = $predictionsWithActual->map(function ($prediction) {
                return $prediction->accuracy;
            })->filter();

            if ($accuracies->isNotEmpty()) {
                $avgAccuracy = $accuracies->avg();
                $this->newLine();
                $this->info("Average Prediction Accuracy: " . round($avgAccuracy, 2) . "%");
            }
        }

        // Show monthly breakdown
        $this->newLine();
        $this->info('Monthly Breakdown:');
        $monthlyData = $predictions->groupBy(function ($prediction) {
            return Carbon::parse($prediction->month)->format('M Y');
        });

        foreach ($monthlyData as $month => $monthPredictions) {
            $monthTotal = $monthPredictions->count();
            $monthPredicted = $monthPredictions->sum('prediction');
            $monthActual = $monthPredictions->sum('actual');

            $this->line("  {$month}: {$monthTotal} items, Predicted: {$monthPredicted}, Actual: {$monthActual}");
        }
    }

    /**
     * Get available data range from outgoing items
     */
    private function getAvailableDataRange()
    {
        $range = OutgoingItem::selectRaw('MIN(outgoing_date) as min_date, MAX(outgoing_date) as max_date')
            ->first();

        if ($range && $range->min_date && $range->max_date) {
            return [
                'min_date' => $range->min_date,
                'max_date' => $range->max_date
            ];
        }

        return null;
    }

    /**
     * Check if Python stock predictor is ready and train if necessary
     */
    private function checkPythonPredictor()
    {
        $this->info('🔍 Checking Python stock predictor...');

        $basePath = base_path();
        $scriptsPath = $basePath . '/scripts';
        $stockPredictorScript = $scriptsPath . '/stock_predictor.py';
        $monthlyModelPath = $scriptsPath . '/model/rf_stock_predictor_monthly.pkl';

        // Check if stock_predictor.py exists
        if (!file_exists($stockPredictorScript)) {
            $this->error("❌ Python stock predictor script not found: {$stockPredictorScript}");
            return false;
        }

        // Check if monthly model exists
        if (!file_exists($monthlyModelPath)) {
            $this->warn("⚠️ Monthly model not found. Training new model...");

            // First, export data to CSV
            $this->info("📤 Exporting outgoing data to CSV...");
            $exportResult = $this->exportOutgoingDataToCsv();

            if (!$exportResult['success']) {
                $this->error("❌ Data export failed: " . $exportResult['message']);
                return false;
            }

            $this->info("✅ Data export completed: {$exportResult['total_files']} files, {$exportResult['total_records']} records");

            if ($this->trainPythonModel()) {
                $this->info("✅ Model training completed successfully!");
            } else {
                $this->error("❌ Model training failed. Will use fallback predictions.");
                return false;
            }
        } else {
            $this->info("✅ Monthly model found: {$monthlyModelPath}");
        }

        return true;
    }

    /**
     * Train the Python stock prediction model
     */
    private function trainPythonModel()
    {
        $this->info('🚀 Training Python stock prediction model...');

        $basePath = base_path();
        $operatingSystem = PHP_OS_FAMILY;

        // Define paths based on operating system
        if ($operatingSystem === 'Windows') {
            $venvActivate = $basePath . '\\scripts\\.venv\\Scripts\\activate.bat';
            $pythonPath = $basePath . '\\scripts\\.venv\\Scripts\\python.exe';
        } else {
            $venvActivate = $basePath . '/scripts/.venv/bin/activate';
            $pythonPath = $basePath . '/scripts/.venv/bin/python';
        }

        // Build training command with virtual environment
        if (file_exists($pythonPath)) {
            // Virtual environment exists, use it
            if ($operatingSystem === 'Windows') {
                $trainCommand = "cd /d \"{$basePath}\\scripts\" && \".venv\\Scripts\\activate.bat\" && python stock_predictor.py train 2>&1";
            } else {
                $trainCommand = "cd \"{$basePath}/scripts\" && source .venv/bin/activate && python stock_predictor.py train 2>&1";
            }
        } else {
            // Fallback to system python if venv doesn't exist
            $this->warn("Virtual environment not found, using system Python");
            $pythonCmd = $operatingSystem === 'Windows' ? 'python' : 'python3';
            if ($operatingSystem === 'Windows') {
                $trainCommand = "cd /d \"{$basePath}\\scripts\" && {$pythonCmd} stock_predictor.py train 2>&1";
            } else {
                $trainCommand = "cd \"{$basePath}/scripts\" && {$pythonCmd} stock_predictor.py train 2>&1";
            }
        }

        $this->line("Executing training command...");

        // Execute training command
        $output = shell_exec($trainCommand);

        if ($output === null) {
            $this->error("Failed to execute training command");
            return false;
        }

        // Show training output
        $this->line("Training output:");
        $this->line($output);

        // Check if training was successful
        if (
            strpos($output, 'TRAINING_COMPLETED') !== false ||
            strpos($output, 'All models trained successfully') !== false ||
            strpos($output, 'training completed successfully') !== false
        ) {
            return true;
        }

        if (strpos($output, 'TRAINING_FAILED') !== false) {
            $this->error("Training failed as indicated by output");
            return false;
        }

        // Check if model file was created
        $monthlyModelPath = $basePath . '/scripts/models/monthly_model.pkl';
        if (file_exists($monthlyModelPath)) {
            $this->info("Model file created successfully");
            return true;
        }

        return false;
    }

    /**
     * Export outgoing data to CSV files for Python training
     */
    private function exportOutgoingDataToCsv()
    {
        try {
            $this->line('Starting CSV export of outgoing data');

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
                    'nama_barang' => $outgoing->item->item_name, // Item name
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

                $this->line("Generated CSV file: {$filename} with " . count($monthInfo['data']) . " records");
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
            $this->error('CSV export error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error saat export CSV: ' . $e->getMessage()
            ];
        }
    }
}
