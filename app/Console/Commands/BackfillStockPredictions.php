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
                            {--start-month=5 : Start month (1-12)}
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
    protected $description = 'Backfill stock predictions and actual sales data for historical months';

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

        $this->info('=== Stock Predictions Backfill ===');

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No data will be saved');
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
                $existingPrediction = StockPrediction::where('product', $item->item_name)
                    ->whereYear('month', $month->year)
                    ->whereMonth('month', $month->month)
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
                            'prediction' => $prediction,
                            'actual' => $actual,
                            'product' => $item->item_name,
                            'month' => $month->startOfMonth()->format('Y-m-d')
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
        $this->line("  Processed: {$processed}, Skipped: {$skipped}, Errors: {$errors}");

        return [
            'processed' => $processed,
            'skipped' => $skipped,
            'errors' => $errors
        ];
    }

    /**
     * Calculate prediction for an item based on historical data
     */
    private function calculatePrediction($item, Carbon $targetMonth)
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
}
