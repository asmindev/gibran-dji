<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\StockPrediction;
use Carbon\Carbon;

class PredictionReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'predictions:report
                            {--month= : Specific month (1-12)}
                            {--year= : Specific year}
                            {--product= : Specific product name}
                            {--sort=accuracy : Sort by (accuracy, product, month)}
                            {--limit=20 : Limit results}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate detailed prediction accuracy report';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $month = $this->option('month');
        $year = $this->option('year');
        $product = $this->option('product');
        $sort = $this->option('sort');
        $limit = $this->option('limit');

        $this->info('=== Stock Prediction Report ===');

        // Build query
        $query = StockPrediction::whereNotNull('actual');

        if ($month && $year) {
            $query->whereYear('month', $year)->whereMonth('month', $month);
            $this->info("Filtering by: " . Carbon::create($year, $month)->format('F Y'));
        } elseif ($year) {
            $query->whereYear('month', $year);
            $this->info("Filtering by year: {$year}");
        }

        if ($product) {
            $query->where('product', 'like', "%{$product}%");
            $this->info("Filtering by product: {$product}");
        }

        $predictions = $query->get();

        if ($predictions->isEmpty()) {
            $this->warn('No predictions found with the specified criteria.');
            return;
        }

        // Sort results
        switch ($sort) {
            case 'accuracy':
                $predictions = $predictions->sortByDesc('accuracy');
                break;
            case 'product':
                $predictions = $predictions->sortBy('product');
                break;
            case 'month':
                $predictions = $predictions->sortBy('month');
                break;
        }

        // Apply limit
        if ($limit) {
            $predictions = $predictions->take($limit);
        }

        // Show summary statistics
        $this->showSummaryStats($query->get());

        // Show detailed table
        $this->newLine();
        $this->info("=== Detailed Results (sorted by {$sort}) ===");

        $tableData = [];
        foreach ($predictions as $prediction) {
            $accuracy = $prediction->accuracy ? round($prediction->accuracy, 1) . '%' : 'N/A';
            $tableData[] = [
                $prediction->product,
                Carbon::parse($prediction->month)->format('M Y'),
                number_format($prediction->prediction),
                number_format($prediction->actual),
                $accuracy,
                $prediction->prediction > $prediction->actual ? 'Over' : 'Under'
            ];
        }

        $this->table(
            ['Product', 'Month', 'Predicted', 'Actual', 'Accuracy', 'Type'],
            $tableData
        );

        // Show accuracy distribution
        $this->showAccuracyDistribution($query->get());
    }

    /**
     * Show summary statistics
     */
    private function showSummaryStats($predictions)
    {
        $total = $predictions->count();
        $totalPredicted = $predictions->sum('prediction');
        $totalActual = $predictions->sum('actual');
        $accuracies = $predictions->map(function ($p) {
            return $p->accuracy;
        })->filter();

        $avgAccuracy = $accuracies->avg();
        $minAccuracy = $accuracies->min();
        $maxAccuracy = $accuracies->max();

        $this->newLine();
        $this->info('=== Summary Statistics ===');

        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Predictions', number_format($total)],
                ['Total Predicted Units', number_format($totalPredicted)],
                ['Total Actual Units', number_format($totalActual)],
                ['Overall Difference', number_format($totalPredicted - $totalActual)],
                ['Average Accuracy', round($avgAccuracy, 2) . '%'],
                ['Best Accuracy', round($maxAccuracy, 2) . '%'],
                ['Worst Accuracy', round($minAccuracy, 2) . '%'],
            ]
        );
    }

    /**
     * Show accuracy distribution
     */
    private function showAccuracyDistribution($predictions)
    {
        $this->newLine();
        $this->info('=== Accuracy Distribution ===');

        $accuracies = $predictions->map(function ($p) {
            return $p->accuracy;
        })->filter();

        $ranges = [
            '90-100%' => $accuracies->filter(function ($acc) {
                return $acc >= 90;
            })->count(),
            '80-89%' => $accuracies->filter(function ($acc) {
                return $acc >= 80 && $acc < 90;
            })->count(),
            '70-79%' => $accuracies->filter(function ($acc) {
                return $acc >= 70 && $acc < 80;
            })->count(),
            '60-69%' => $accuracies->filter(function ($acc) {
                return $acc >= 60 && $acc < 70;
            })->count(),
            '50-59%' => $accuracies->filter(function ($acc) {
                return $acc >= 50 && $acc < 60;
            })->count(),
            'Below 50%' => $accuracies->filter(function ($acc) {
                return $acc < 50;
            })->count(),
        ];

        $distributionData = [];
        foreach ($ranges as $range => $count) {
            $percentage = $accuracies->count() > 0 ? round(($count / $accuracies->count()) * 100, 1) : 0;
            $distributionData[] = [$range, $count, $percentage . '%'];
        }

        $this->table(
            ['Accuracy Range', 'Count', 'Percentage'],
            $distributionData
        );

        // Show product performance
        $this->showProductPerformance($predictions);
    }

    /**
     * Show product performance ranking
     */
    private function showProductPerformance($predictions)
    {
        $this->newLine();
        $this->info('=== Product Performance Ranking ===');

        $productStats = $predictions->groupBy('product')->map(function ($productPredictions, $product) {
            $accuracies = $productPredictions->map(function ($p) {
                return $p->accuracy;
            })->filter();
            $avgAccuracy = $accuracies->avg();
            $totalPredicted = $productPredictions->sum('prediction');
            $totalActual = $productPredictions->sum('actual');

            return [
                'product' => $product,
                'avg_accuracy' => $avgAccuracy,
                'predictions_count' => $productPredictions->count(),
                'total_predicted' => $totalPredicted,
                'total_actual' => $totalActual,
                'difference' => $totalPredicted - $totalActual
            ];
        })->sortByDesc('avg_accuracy');

        $productData = [];
        foreach ($productStats as $stats) {
            $productData[] = [
                $stats['product'],
                $stats['predictions_count'],
                round($stats['avg_accuracy'], 1) . '%',
                number_format($stats['total_predicted']),
                number_format($stats['total_actual']),
                number_format($stats['difference'])
            ];
        }

        $this->table(
            ['Product', 'Count', 'Avg Accuracy', 'Total Pred', 'Total Actual', 'Difference'],
            $productData
        );
    }
}
