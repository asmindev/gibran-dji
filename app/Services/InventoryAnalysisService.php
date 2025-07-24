<?php

namespace App\Services;

use App\Models\OutgoingItem;
use App\Models\Item;
use App\Models\InventoryRecommendation;
use App\Models\StockPrediction;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Carbon\Carbon;
use Exception;

class InventoryAnalysisService
{
    private string $scriptPath;
    private string $storagePath;
    private string $outputPath;

    public function __construct()
    {
        $this->scriptPath = base_path('scripts/analyze_inventory.py');
        $this->storagePath = storage_path('app/inventory_analysis');
        $this->outputPath = $this->storagePath . '/output';

        // Ensure directories exist
        if (!file_exists($this->storagePath)) {
            mkdir($this->storagePath, 0775, true);
        }
        if (!file_exists($this->outputPath)) {
            mkdir($this->outputPath, 0775, true);
        }
    }

    /**
     * Export transaction data to CSV for Python analysis
     */
    public function exportTransactionData(): string
    {
        try {
            Log::info('Starting transaction data export for analysis');

            $transactions = OutgoingItem::with('item')
                ->get(['id', 'item_id', 'quantity', 'outgoing_date', 'customer'])
                ->map(function ($transaction) {
                    return [
                        'transaction_id' => $transaction->id,
                        'item_id' => $transaction->item_id,
                        'item_code' => $transaction->item->item_code ?? 'UNKNOWN',
                        'item_name' => $transaction->item->item_name ?? 'Unknown Item',
                        'quantity' => $transaction->quantity,
                        'outgoing_date' => $transaction->outgoing_date->format('Y-m-d'),
                        'customer' => $transaction->customer,
                    ];
                });

            if ($transactions->isEmpty()) {
                Log::warning('No transaction data found for export');
                throw new Exception('No transaction data available for analysis');
            }

            $csvFile = $this->storagePath . '/transactions.csv';
            $fp = fopen($csvFile, 'w');

            // Write header
            fputcsv($fp, ['transaction_id', 'item_id', 'item_code', 'item_name', 'quantity', 'outgoing_date', 'customer']);

            // Write data
            foreach ($transactions as $transaction) {
                fputcsv($fp, $transaction);
            }

            fclose($fp);

            Log::info("Exported {$transactions->count()} transactions to {$csvFile}");
            return $csvFile;
        } catch (Exception $e) {
            Log::error('Error exporting transaction data: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Run the Python inventory analysis script
     */
    public function runInventoryAnalysis(string $csvFile): array
    {
        try {
            Log::info('Starting Python inventory analysis');

            // Check if Python script exists
            if (!file_exists($this->scriptPath)) {
                throw new Exception("Python script not found at: {$this->scriptPath}");
            }

            // Check if CSV file exists
            if (!file_exists($csvFile)) {
                throw new Exception("CSV file not found at: {$csvFile}");
            }

            // Run the Python script
            $pythonPath = base_path('.venv/bin/python');
            $command = sprintf(
                '%s %s %s %s 2>&1',
                escapeshellarg($pythonPath),
                escapeshellarg($this->scriptPath),
                escapeshellarg($csvFile),
                escapeshellarg($this->outputPath)
            );

            Log::info("Executing command: {$command}");

            $result = Process::run($command);

            if (!$result->successful()) {
                Log::error('Python script failed: ' . $result->errorOutput());
                throw new Exception('Python analysis script failed: ' . $result->errorOutput());
            }

            Log::info('Python analysis completed successfully');
            Log::info('Python script output: ' . $result->output());

            // Check if output files were created
            $summaryFile = $this->outputPath . '/analysis_summary.json';
            if (file_exists($summaryFile)) {
                $summary = json_decode(file_get_contents($summaryFile), true);
                Log::info('Analysis summary: ' . json_encode($summary));
                return $summary;
            }

            return [
                'status' => 'completed',
                'analysis_date' => now()->toISOString(),
                'total_recommendations' => 0,
                'total_predictions' => 0,
            ];
        } catch (Exception $e) {
            Log::error('Error running inventory analysis: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Import analysis results back into the database
     */
    public function importAnalysisResults(): array
    {
        try {
            Log::info('Starting import of analysis results');

            $results = [
                'recommendations_imported' => 0,
                'predictions_imported' => 0,
                'errors' => [],
            ];

            // Import recommendations
            $recommendationsFile = $this->outputPath . '/recommendations.csv';
            if (file_exists($recommendationsFile)) {
                $results['recommendations_imported'] = $this->importRecommendations($recommendationsFile);
            }

            // Import predictions
            $predictionsFile = $this->outputPath . '/predictions.csv';
            if (file_exists($predictionsFile)) {
                $results['predictions_imported'] = $this->importPredictions($predictionsFile);
            }

            Log::info('Analysis results import completed', $results);
            return $results;
        } catch (Exception $e) {
            Log::error('Error importing analysis results: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Import recommendations from CSV
     */
    public function importRecommendations(string $csvFile): int
    {
        try {
            $count = 0;
            $analyzedAt = now();

            // Deactivate previous recommendations
            InventoryRecommendation::where('is_active', true)->update(['is_active' => false]);

            if (($handle = fopen($csvFile, 'r')) !== FALSE) {
                $header = fgetcsv($handle);

                while (($data = fgetcsv($handle)) !== FALSE) {
                    if (count($data) >= 5) {
                        $antecedents = explode(',', $data[0]);
                        $consequents = explode(',', $data[1]);

                        // Create rule description
                        $ruleDescription = sprintf(
                            'If customers buy %s, they are likely to also buy %s',
                            implode(', ', $antecedents),
                            implode(', ', $consequents)
                        );

                        InventoryRecommendation::create([
                            'antecedent_items' => $antecedents,
                            'consequent_items' => $consequents,
                            'support' => (float)$data[2],
                            'confidence' => (float)$data[3],
                            'lift' => (float)$data[4],
                            'rule_description' => $ruleDescription,
                            'is_active' => true,
                            'analyzed_at' => $analyzedAt,
                        ]);

                        $count++;
                    }
                }
                fclose($handle);
            }

            Log::info("Imported {$count} recommendations");
            return $count;
        } catch (Exception $e) {
            Log::error('Error importing recommendations: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Import predictions from CSV
     */
    public function importPredictions(string $csvFile): int
    {
        try {
            $count = 0;
            $analyzedAt = now();

            // Deactivate previous predictions
            StockPrediction::where('is_active', true)->update(['is_active' => false]);

            if (($handle = fopen($csvFile, 'r')) !== FALSE) {
                $header = fgetcsv($handle);

                while (($data = fgetcsv($handle)) !== FALSE) {
                    if (count($data) >= 6) {
                        // Find item by code
                        $item = Item::where('item_code', $data[0])->first();

                        if ($item) {
                            StockPrediction::create([
                                'item_id' => $item->id,
                                'predicted_demand' => (int)$data[2],
                                'prediction_confidence' => (float)$data[3],
                                'prediction_period_start' => Carbon::parse($data[4]),
                                'prediction_period_end' => Carbon::parse($data[5]),
                                'feature_importance' => json_decode($data[6] ?? '{}', true),
                                'is_active' => true,
                                'analyzed_at' => $analyzedAt,
                            ]);

                            $count++;
                        } else {
                            Log::warning("Item not found for code: {$data[0]}");
                        }
                    }
                }
                fclose($handle);
            }

            Log::info("Imported {$count} predictions");
            return $count;
        } catch (Exception $e) {
            Log::error('Error importing predictions: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Run the complete analysis pipeline
     */
    public function runCompleteAnalysis(): array
    {
        try {
            Log::info('Starting complete inventory analysis pipeline');

            // Step 1: Export transaction data
            $csvFile = $this->exportTransactionData();

            // Step 2: Run Python analysis
            $analysisSummary = $this->runInventoryAnalysis($csvFile);

            // Step 3: Import results
            $importResults = $this->importAnalysisResults();

            // Step 4: Cleanup temporary files
            if (file_exists($csvFile)) {
                unlink($csvFile);
            }

            $results = [
                'status' => 'completed',
                'analysis_date' => now()->toISOString(),
                'analysis_summary' => $analysisSummary,
                'import_results' => $importResults,
            ];

            Log::info('Complete analysis pipeline finished', $results);
            return $results;
        } catch (Exception $e) {
            Log::error('Error in complete analysis pipeline: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get analysis results summary
     */
    public function getAnalysisResultsSummary(): array
    {
        return [
            'active_recommendations' => InventoryRecommendation::active()->count(),
            'active_predictions' => StockPrediction::active()->count(),
            'high_confidence_recommendations' => InventoryRecommendation::active()->highConfidence(0.7)->count(),
            'high_confidence_predictions' => StockPrediction::active()->highConfidence(80)->count(),
            'last_analysis_date' => InventoryRecommendation::active()->max('analyzed_at') ?: 'Never',
        ];
    }

    /**
     * Clean up old analysis files
     */
    public function cleanupOldFiles(int $daysOld = 30): void
    {
        try {
            $cutoffDate = now()->subDays($daysOld);

            // Cleanup old recommendations
            InventoryRecommendation::where('analyzed_at', '<', $cutoffDate)
                ->where('is_active', false)
                ->delete();

            // Cleanup old predictions
            StockPrediction::where('analyzed_at', '<', $cutoffDate)
                ->where('is_active', false)
                ->delete();

            Log::info("Cleaned up analysis data older than {$daysOld} days");
        } catch (Exception $e) {
            Log::error('Error cleaning up old files: ' . $e->getMessage());
        }
    }
}
