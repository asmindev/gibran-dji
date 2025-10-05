<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Item;
use App\Models\IncomingItem;
use App\Models\OutgoingItem;
use App\Models\StockPrediction;
use App\Services\PlatformCompatibilityService;
use Carbon\Carbon;

class PredictBackfill extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'predict:backfill {--force : Force re-prediction even if data exists}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate predictions for all products for all months that have transaction data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting backfill prediction process...');

        $force = $this->option('force');

        // Get all unique months from incoming and outgoing items
        $months = $this->getAvailableMonths();

        if ($months->isEmpty()) {
            $this->warn('No transaction data found. Please add incoming or outgoing items first.');
            return 1;
        }

        $this->info("Found {$months->count()} month(s) with transaction data.");

        // Get all items
        $items = Item::all();

        if ($items->isEmpty()) {
            $this->warn('No items found in database.');
            return 1;
        }

        $this->info("Found {$items->count()} item(s) to process.");

        $totalPredictions = 0;
        $skippedPredictions = 0;
        $failedPredictions = 0;

        $progressBar = $this->output->createProgressBar($months->count() * $items->count() * 2); // *2 for sales and restock

        foreach ($months as $month) {
            $monthDate = Carbon::createFromFormat('Y-m', $month);

            foreach ($items as $item) {
                // Process sales prediction
                if ($this->processPrediction($item, $monthDate, 'sales', $force)) {
                    $totalPredictions++;
                } else {
                    if (!$force && $this->predictionExists($item, $monthDate, 'sales')) {
                        $skippedPredictions++;
                    } else {
                        $failedPredictions++;
                    }
                }
                $progressBar->advance();

                // Process restock prediction
                if ($this->processPrediction($item, $monthDate, 'restock', $force)) {
                    $totalPredictions++;
                } else {
                    if (!$force && $this->predictionExists($item, $monthDate, 'restock')) {
                        $skippedPredictions++;
                    } else {
                        $failedPredictions++;
                    }
                }
                $progressBar->advance();
            }
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->info("Backfill prediction completed!");
        $this->table(
            ['Status', 'Count'],
            [
                ['New Predictions', $totalPredictions],
                ['Skipped (Already exists)', $skippedPredictions],
                ['Failed', $failedPredictions],
                ['Total Processed', $totalPredictions + $skippedPredictions + $failedPredictions],
            ]
        );

        return 0;
    }

    /**
     * Get all available months from transaction data
     */
    private function getAvailableMonths()
    {
        return DB::table('incoming_items')
            ->selectRaw("DATE_FORMAT(incoming_date, '%Y-%m') as month")
            ->union(
                DB::table('outgoing_items')->selectRaw("DATE_FORMAT(outgoing_date, '%Y-%m') as month")
            )
            ->distinct()
            ->orderBy('month', 'asc')
            ->get()
            ->pluck('month');
    }

    /**
     * Check if prediction already exists
     */
    private function predictionExists($item, $monthDate, $predictionType)
    {
        return StockPrediction::where('item_id', $item->id)
            ->where('product', $item->item_name)
            ->whereYear('month', $monthDate->year)
            ->whereMonth('month', $monthDate->month)
            ->where('prediction_type', $predictionType)
            ->exists();
    }

    /**
     * Process prediction for a single item and month
     */
    private function processPrediction($item, $monthDate, $predictionType, $force = false)
    {
        // Check if prediction already exists
        if (!$force && $this->predictionExists($item, $monthDate, $predictionType)) {
            return false;
        }

        // Calculate average monthly quantity for the item up to this month
        $avgMonthly = $this->calculateAverageMonthly($item, $monthDate, $predictionType);

        if ($avgMonthly === null) {
            return false; // Skip if no historical data
        }

        // Build Python command with virtual environment support
        $scriptsPath = base_path('scripts');
        $args = [
            '--product',
            $item->id,
            '--type',
            $predictionType,
            '--avg-monthly',
            $avgMonthly
        ];

        $pythonCommandInfo = PlatformCompatibilityService::buildPythonCommand(
            $scriptsPath,
            'predict.py',
            $args
        );

        // Execute command
        $result = PlatformCompatibilityService::executeCommand(
            $pythonCommandInfo['command'],
            $pythonCommandInfo['workingDirectory']
        );

        if (!$result['success']) {
            return false;
        }

        // Parse prediction result
        $predictionResult = $this->parsePredictionResult($result['output']);

        if (!$predictionResult || !isset($predictionResult['prediction'])) {
            return false;
        }

        // Get actual data for this month if available
        $actual = $this->getActualData($item, $monthDate, $predictionType);

        // Save or update prediction to database
        StockPrediction::updateOrCreate(
            [
                'item_id' => $item->id,
                'product' => $item->item_name,
                'month' => $monthDate->format('Y-m-01'),
                'prediction_type' => $predictionType,
            ],
            [
                'prediction' => round($predictionResult['prediction'], 2),
                'actual' => $actual,
            ]
        );

        return true;
    }

    /**
     * Calculate average monthly quantity for an item
     */
    private function calculateAverageMonthly($item, $upToMonth, $predictionType)
    {
        $table = $predictionType === 'sales' ? 'outgoing_items' : 'incoming_items';
        $dateColumn = $predictionType === 'sales' ? 'outgoing_date' : 'incoming_date';

        $avg = DB::table($table)
            ->where('item_id', $item->id)
            ->where($dateColumn, '<', $upToMonth->format('Y-m-01'))
            ->selectRaw("AVG(quantity) as avg_quantity")
            ->value('avg_quantity');

        return $avg ? round($avg, 2) : null;
    }

    /**
     * Get actual data for a specific month
     */
    private function getActualData($item, $monthDate, $predictionType)
    {
        if ($predictionType === 'sales') {
            return OutgoingItem::where('item_id', $item->id)
                ->whereYear('outgoing_date', $monthDate->year)
                ->whereMonth('outgoing_date', $monthDate->month)
                ->sum('quantity');
        } else {
            return IncomingItem::where('item_id', $item->id)
                ->whereYear('incoming_date', $monthDate->year)
                ->whereMonth('incoming_date', $monthDate->month)
                ->sum('quantity');
        }
    }

    /**
     * Parse prediction result from Python script output
     */
    private function parsePredictionResult($output)
    {
        foreach ($output as $line) {
            if (strpos($line, 'PREDICTION_RESULT:') !== false) {
                $jsonString = substr($line, strpos($line, '{'));
                return json_decode($jsonString, true);
            }
        }

        return null;
    }
}
