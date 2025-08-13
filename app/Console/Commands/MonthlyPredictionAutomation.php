<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\StockPrediction;
use App\Models\OutgoingItem;
use App\Models\Item;
use App\Http\Controllers\StockPredictionController;
use Illuminate\Http\Request;
use Carbon\Carbon;

class MonthlyPredictionAutomation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'predictions:monthly-automation {--type=both : Type of automation (predict, calculate, both)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automated monthly prediction and calculation system';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $type = $this->option('type');

        $this->info('=== Monthly Prediction Automation ===');
        $this->info('Current Date: ' . now()->format('d F Y'));

        switch ($type) {
            case 'predict':
                $this->createMonthlyPredictions();
                break;
            case 'calculate':
                $this->calculateActualSales();
                break;
            case 'both':
            default:
                $this->createMonthlyPredictions();
                $this->newLine();
                $this->calculateActualSales();
                break;
        }
    }

    /**
     * Create predictions for next month (run at beginning of month)
     */
    private function createMonthlyPredictions()
    {
        $this->info('--- Creating Monthly Predictions ---');

        $currentMonth = now();
        $nextMonth = now()->addMonth();

        $this->info("Creating predictions for: {$nextMonth->format('F Y')}");

        // Get all items
        $items = Item::all();

        if ($items->isEmpty()) {
            $this->warn('No items found in database.');
            return;
        }

        $bar = $this->output->createProgressBar($items->count());
        $bar->start();

        $successCount = 0;
        $skipCount = 0;
        $errorCount = 0;

        foreach ($items as $item) {
            try {
                // Check if prediction already exists for this month
                $existingPrediction = StockPrediction::where('product', $item->item_name)
                    ->whereYear('month', $nextMonth->year)
                    ->whereMonth('month', $nextMonth->month)
                    ->first();

                if ($existingPrediction) {
                    $skipCount++;
                    $bar->advance();
                    continue;
                }

                // Get last month's sales data
                $lastMonth = $currentMonth->copy()->subMonth();
                $prevMonthTotal = OutgoingItem::where('item_id', $item->id)
                    ->whereYear('outgoing_date', $lastMonth->year)
                    ->whereMonth('outgoing_date', $lastMonth->month)
                    ->sum('quantity');

                if ($prevMonthTotal == 0) {
                    // If no sales last month, skip or use default prediction
                    $prediction = 0;
                } else {
                    // Call prediction algorithm (simplified)
                    $prediction = $this->callPredictionAlgorithm($item, $prevMonthTotal);
                }

                // Save prediction to database
                StockPrediction::create([
                    'prediction' => $prediction,
                    'actual' => null,
                    'product' => $item->item_name,
                    'month' => $nextMonth->startOfMonth()->format('Y-m-d')
                ]);

                $successCount++;
            } catch (\Exception $e) {
                $this->error("Error creating prediction for {$item->name}: " . $e->getMessage());
                $errorCount++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Prediction Results:");
        $this->line("  Created: {$successCount}");
        $this->line("  Skipped (already exists): {$skipCount}");
        $this->line("  Errors: {$errorCount}");
    }

    /**
     * Calculate actual sales for current month (run at end of month)
     */
    private function calculateActualSales()
    {
        $this->info('--- Calculating Actual Sales ---');

        $currentMonth = now();

        $this->info("Calculating actual sales for: {$currentMonth->format('F Y')}");

        // Get all predictions for current month that don't have actual data
        $predictions = StockPrediction::whereYear('month', $currentMonth->year)
            ->whereMonth('month', $currentMonth->month)
            ->whereNull('actual')
            ->get();

        if ($predictions->isEmpty()) {
            $this->warn('No predictions found for current month that need updating.');
            return;
        }

        $this->info("Found {$predictions->count()} predictions to update");

        $bar = $this->output->createProgressBar($predictions->count());
        $bar->start();

        $updatedCount = 0;
        $errors = [];

        foreach ($predictions as $prediction) {
            try {
                // Find item by name (using correct column)
                $item = Item::where('item_name', $prediction->product)->first();

                if (!$item) {
                    $errors[] = "Item not found for product: {$prediction->product}";
                    $bar->advance();
                    continue;
                }

                // Calculate actual sales for this item in current month
                $actualSales = OutgoingItem::where('item_id', $item->id)
                    ->whereYear('outgoing_date', $currentMonth->year)
                    ->whereMonth('outgoing_date', $currentMonth->month)
                    ->sum('quantity');

                // Update prediction with actual data
                $prediction->update([
                    'actual' => $actualSales
                ]);

                $updatedCount++;
            } catch (\Exception $e) {
                $errors[] = "Error updating prediction ID {$prediction->id}: " . $e->getMessage();
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Successfully updated {$updatedCount} predictions");

        if (!empty($errors)) {
            $this->error("Errors encountered:");
            foreach ($errors as $error) {
                $this->error("  " . $error);
            }
        }

        // Show accuracy statistics
        $this->showAccuracyStats($currentMonth->year, $currentMonth->month);
    }

    /**
     * Call prediction algorithm (simplified version)
     */
    private function callPredictionAlgorithm($item, $prevMonthTotal)
    {
        // Simplified prediction: use previous month + some variation
        // In real implementation, this would call your Python ML model

        // Simple moving average with trend
        $basePredicton = $prevMonthTotal;

        // Add some basic seasonality (increase in December, decrease in January)
        $month = now()->addMonth()->month;
        $seasonalFactor = 1.0;

        if ($month == 12) { // December - holiday season
            $seasonalFactor = 1.2;
        } elseif ($month == 1) { // January - post-holiday
            $seasonalFactor = 0.8;
        }

        $prediction = round($basePredicton * $seasonalFactor);

        return max(0, $prediction); // Ensure non-negative
    }

    /**
     * Show accuracy statistics
     */
    private function showAccuracyStats($year, $month)
    {
        $this->newLine();
        $this->info('=== Accuracy Statistics ===');

        $predictions = StockPrediction::whereYear('month', $year)
            ->whereMonth('month', $month)
            ->whereNotNull('actual')
            ->get();

        if ($predictions->isEmpty()) {
            $this->warn('No predictions with actual data found for accuracy calculation.');
            return;
        }

        $totalPredictions = $predictions->count();
        $accuracies = $predictions->map(function ($prediction) {
            return $prediction->accuracy;
        })->filter(); // Remove null values

        $averageAccuracy = $accuracies->avg();
        $minAccuracy = $accuracies->min();
        $maxAccuracy = $accuracies->max();

        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Predictions', $totalPredictions],
                ['Average Accuracy', round($averageAccuracy, 2) . '%'],
                ['Minimum Accuracy', round($minAccuracy, 2) . '%'],
                ['Maximum Accuracy', round($maxAccuracy, 2) . '%'],
                ['Month/Year', Carbon::create($year, $month)->format('F Y')],
            ]
        );

        // Show top 5 predictions
        $sortedByAccuracy = $predictions->sortByDesc('accuracy');

        $this->newLine();
        $this->info('Top 5 Most Accurate Predictions:');
        $topPredictions = $sortedByAccuracy->take(5);
        foreach ($topPredictions as $prediction) {
            $this->line("  {$prediction->product}: {$prediction->accuracy}% (Predicted: {$prediction->prediction}, Actual: {$prediction->actual})");
        }

        if ($predictions->count() > 5) {
            $this->newLine();
            $this->info('Bottom 5 Least Accurate Predictions:');
            $bottomPredictions = $sortedByAccuracy->reverse()->take(5);
            foreach ($bottomPredictions as $prediction) {
                $this->line("  {$prediction->product}: {$prediction->accuracy}% (Predicted: {$prediction->prediction}, Actual: {$prediction->actual})");
            }
        }
    }
}
