<?php

namespace App\Http\Controllers;

use App\Models\InventoryRecommendation;
use App\Models\StockPrediction;
use App\Models\Item;
use App\Models\OutgoingItem;
use App\Models\AprioriAnalysis;
use App\Models\FpGrowthAnalysis;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AnalysisController extends Controller
{

    /**
     * Display comparison of both Apriori and FP-Growth algorithms
     */
    public function compareAlgorithms(Request $request)
    {
        // Get algorithm parameters - keep as percentage
        $minSupport = $request->get('min_support', 50); // Keep as percentage (50)
        $minConfidence = $request->get('min_confidence', 70); // Keep as percentage (70)
        $selectedDate = $request->get('transaction_date', ''); // Default empty, no calculation

        // Get real transaction data from database for available dates
        $allTransactions = $this->getTransactionDataFromDatabase();
        $availableDates = array_unique(array_column($allTransactions, 'date'));
        sort($availableDates);

        // Get available months from transactions (format: YYYY-MM)
        $availableMonths = [];
        foreach ($availableDates as $date) {
            $monthKey = Carbon::parse($date)->format('Y-m');
            if (!in_array($monthKey, $availableMonths)) {
                $availableMonths[] = $monthKey;
            }
        }
        sort($availableMonths);

        // Only perform calculation if a date is selected
        $sampleTransactions = [];
        $aprioriSteps = null;
        $fpGrowthSteps = null;
        $hasCalculation = false;
        $aprioriSaved = false;
        $fpGrowthSaved = false;
        $aprioriSavedCount = 0;
        $fpGrowthSavedCount = 0;

        if (!empty($selectedDate)) {
            $hasCalculation = true;

            // Start timing execution
            $startTime = microtime(true);

            // Filter transactions by selected date
            if ($selectedDate === 'all') {
                $sampleTransactions = $allTransactions;
            } else {
                $sampleTransactions = array_filter($allTransactions, function ($transaction) use ($selectedDate) {
                    // Use Carbon for proper date comparison
                    $transactionDate = Carbon::parse($transaction['date'])->format('Y-m-d');
                    $filterDate = Carbon::parse($selectedDate)->format('Y-m-d');
                    return $transactionDate === $filterDate;
                });
                // Reset array keys after filtering
                $sampleTransactions = array_values($sampleTransactions);
            }

            Log::info('Algorithm comparison filtering result', [
                'selected_date' => $selectedDate,
                'total_transactions' => count($allTransactions),
                'filtered_transactions' => count($sampleTransactions)
            ]);

            // If no transactions found for selected date, show message
            if (empty($sampleTransactions)) {
                $aprioriSteps = $this->getEmptyAlgorithmSteps($minSupport, $minConfidence);
                $fpGrowthSteps = $this->getEmptyAlgorithmSteps($minSupport, $minConfidence);
            } else {
                // Run Apriori algorithm
                $aprioriStart = microtime(true);
                $aprioriSteps = $this->simulateAprioriSteps($sampleTransactions, $minSupport, $minConfidence);
                $aprioriEnd = microtime(true);
                $aprioriSteps['summary']['execution_time_ms'] = round(($aprioriEnd - $aprioriStart) * 1000, 2);

                // Run FP-Growth algorithm
                $fpGrowthStart = microtime(true);
                $fpGrowthSteps = $this->simulateFpGrowthSteps($sampleTransactions, $minSupport, $minConfidence);
                $fpGrowthEnd = microtime(true);
                $fpGrowthSteps['summary']['execution_time_ms'] = round(($fpGrowthEnd - $fpGrowthStart) * 1000, 2);

                // Save Apriori results
                try {
                    $aprioriSavedCount = AprioriAnalysis::saveAnalysis(
                        $aprioriSteps,
                        $selectedDate,
                        $minSupport,
                        $minConfidence,
                        $sampleTransactions,
                        Auth::user()->name ?? 'System'
                    );

                    if ($aprioriSavedCount && $aprioriSavedCount > 0) {
                        $aprioriSaved = true;
                        Log::info('Apriori analysis saved successfully', [
                            'rules_saved' => $aprioriSavedCount,
                            'selected_date' => $selectedDate
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error('Failed to save Apriori analysis', ['error' => $e->getMessage()]);
                }

                // Save FP-Growth results
                try {
                    $fpGrowthSavedCount = FpGrowthAnalysis::saveAnalysis(
                        $fpGrowthSteps,
                        $selectedDate,
                        $minSupport,
                        $minConfidence,
                        $sampleTransactions,
                        Auth::user()->name ?? 'System'
                    );

                    if ($fpGrowthSavedCount && $fpGrowthSavedCount > 0) {
                        $fpGrowthSaved = true;
                        Log::info('FP-Growth analysis saved successfully', [
                            'rules_saved' => $fpGrowthSavedCount,
                            'selected_date' => $selectedDate
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error('Failed to save FP-Growth analysis', ['error' => $e->getMessage()]);
                }
            }
        }

        // Get product data with images for modal
        $products = Item::select('item_name', 'image_path', 'id')
            ->get()
            ->keyBy('item_name')
            ->toArray();

        return view('analysis.compare-algorithms', compact(
            'sampleTransactions',
            'aprioriSteps',
            'fpGrowthSteps',
            'selectedDate',
            'availableDates',
            'availableMonths',
            'products',
            'hasCalculation',
            'aprioriSaved',
            'fpGrowthSaved',
            'aprioriSavedCount',
            'fpGrowthSavedCount'
        ))->with([
            'minSupport' => $minSupport,
            'minConfidence' => $minConfidence
        ]);
    }

    /**
     * Display FP-Growth algorithm process page
     */
    public function fpGrowthProcess(Request $request)
    {
        // Get algorithm parameters - keep as percentage
        $minSupport = $request->get('min_support', 50); // Keep as percentage (50)
        $minConfidence = $request->get('min_confidence', 70); // Keep as percentage (70)
        $filterType = $request->get('filter_type', 'date');
        $selectedDate = $request->get('transaction_date', ''); // Default empty, no calculation
        $selectedMonth = $request->get('transaction_month', ''); // For month filter

        // Get real transaction data from database for available dates
        $allTransactions = $this->getTransactionDataFromDatabase();
        $availableDates = array_unique(array_column($allTransactions, 'date'));
        sort($availableDates);

        // Get available months from transactions (format: YYYY-MM)
        $availableMonths = [];
        foreach ($availableDates as $date) {
            $monthKey = Carbon::parse($date)->format('Y-m');
            if (!in_array($monthKey, $availableMonths)) {
                $availableMonths[] = $monthKey;
            }
        }
        sort($availableMonths);

        // Only perform calculation if a date or month is selected
        $sampleTransactions = [];
        $algorithmSteps = null;
        $hasCalculation = false;
        $analysisSaved = false; // Tambahan variabel untuk tracking penyimpanan
        $savedRulesCount = 0; // Jumlah rules yang berhasil disimpan

        if (!empty($selectedDate) || !empty($selectedMonth)) {
            $hasCalculation = true;

            // Start timing execution
            $startTime = microtime(true);

            // Filter transactions based on filter type
            if ($filterType === 'month' && !empty($selectedMonth)) {
                // Filter by month - expect format YYYY-MM from month input
                $sampleTransactions = array_filter($allTransactions, function ($transaction) use ($selectedMonth) {
                    $transactionDate = Carbon::parse($transaction['date']);
                    // Direct comparison with Y-m format (no need to parse selectedMonth)
                    return $transactionDate->format('Y-m') === $selectedMonth;
                });
                // Reset array keys after filtering
                $sampleTransactions = array_values($sampleTransactions);
                $selectedDate = $selectedMonth; // For display and saving
            } elseif ($selectedDate === 'all') {
                $sampleTransactions = $allTransactions;
            } else {
                // Filter by specific date
                $sampleTransactions = array_filter($allTransactions, function ($transaction) use ($selectedDate) {
                    // Use Carbon for proper date comparison
                    $transactionDate = Carbon::parse($transaction['date'])->format('Y-m-d');
                    $filterDate = Carbon::parse($selectedDate)->format('Y-m-d');
                    return $transactionDate === $filterDate;
                });
                // Reset array keys after filtering
                $sampleTransactions = array_values($sampleTransactions);
            }

            Log::info('FP-Growth transactions filtering result', [
                'filter_type' => $filterType,
                'selected_date' => $selectedDate,
                'selected_month' => $selectedMonth,
                'total_transactions' => count($allTransactions),
                'filtered_transactions' => count($sampleTransactions)
            ]);

            // If no transactions found for selected date, show message
            if (empty($sampleTransactions)) {
                $algorithmSteps = $this->getEmptyAlgorithmSteps($minSupport, $minConfidence);
            } else {
                // Simulate FP-Growth algorithm steps - pass original percentage values for display
                $algorithmSteps = $this->simulateFpGrowthSteps(
                    $sampleTransactions,
                    $minSupport,
                    $minConfidence,
                );

                // Calculate execution time
                $endTime = microtime(true);
                $executionTimeMs = round(($endTime - $startTime) * 1000, 2);

                // Add execution time to algorithm steps summary
                $algorithmSteps['summary']['execution_time_ms'] = $executionTimeMs;
                $algorithmSteps['summary']['execution_time_start'] = $startTime;
                $algorithmSteps['summary']['execution_time_end'] = $endTime;

                // Save analysis results to database (satu per satu association rule)
                try {
                    $savedCount = FpGrowthAnalysis::saveAnalysis(
                        $algorithmSteps,
                        $selectedDate,
                        $minSupport,
                        $minConfidence,
                        $sampleTransactions,
                        Auth::user()->name ?? 'System'
                    );

                    if ($savedCount && $savedCount > 0) {
                        $analysisSaved = true; // Set true jika berhasil disimpan
                        $savedRulesCount = $savedCount; // Simpan jumlah rules
                        Log::info('FP-Growth analysis saved successfully', [
                            'rules_saved' => $savedCount,
                            'selected_date' => $selectedDate
                        ]);
                    } else {
                        Log::info('FP-Growth analysis not saved - no association rules generated', [
                            'selected_date' => $selectedDate,
                            'min_support' => $minSupport,
                            'min_confidence' => $minConfidence
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error('Failed to save FP-Growth analysis', [
                        'error' => $e->getMessage(),
                        'selected_date' => $selectedDate,
                        'min_support' => $minSupport,
                        'min_confidence' => $minConfidence
                    ]);
                }
            }
        }

        // Get product data with images for modal
        $products = Item::select('item_name', 'image_path', 'id')
            ->get()
            ->keyBy('item_name')
            ->toArray();

        return view('analysis.fpgrowth-process', compact(
            'sampleTransactions',
            'algorithmSteps',
            'selectedDate',
            'availableDates',
            'availableMonths',
            'products',
            'hasCalculation',
            'analysisSaved',
            'savedRulesCount'
        ))->with([
            'minSupport' => $minSupport, // Send as percentage for display
            'minConfidence' => $minConfidence // Send as percentage for display
        ]);
    }

    /**
     * Simulate FP-Growth algorithm steps using real transaction data
     */
    private function simulateFpGrowthSteps($transactions, $minSupport, $minConfidence)
    {
        $stepTimings = [];
        $algorithmStart = microtime(true);

        $totalTransactions = count($transactions);
        if ($totalTransactions === 0) {
            return $this->getEmptyAlgorithmSteps($minSupport, $minConfidence);
        }

        // Step 1: Count individual items and sort by frequency (descending)
        $step1Start = microtime(true);
        $itemCounts = [];
        foreach ($transactions as $transaction) {
            foreach ($transaction['items'] as $item) {
                $itemCounts[$item] = ($itemCounts[$item] ?? 0) + 1;
            }
        }

        // Sort items by count descending for FP-Tree construction
        arsort($itemCounts);

        // Step 1 data: Individual item counts
        $step1Data = [];
        foreach ($itemCounts as $item => $count) {
            $support = round(($count / $totalTransactions) * 100, 1);
            $step1Data[] = [
                'item' => $item,
                'count' => $count,
                'total' => $totalTransactions,
                'support' => $support
            ];
        }
        $stepTimings['step1'] = round((microtime(true) - $step1Start) * 1000, 2);

        // Step 2: Prune infrequent items (create F-List)
        $step2Start = microtime(true);
        $frequentItems = [];
        $step2Data = [];
        foreach ($itemCounts as $item => $count) {
            $support = round(($count / $totalTransactions) * 100, 1);
            $status = $support >= $minSupport ? 'kept' : 'pruned';
            if ($status === 'kept') {
                $frequentItems[$item] = $count; // Keep with count for ordering
            }
            $step2Data[] = [
                'item' => $item,
                'support' => $support,
                'status' => $status
            ];
        }
        $stepTimings['step2'] = round((microtime(true) - $step2Start) * 1000, 2);

        // Step 3: Sort transactions by frequent item order
        $step3Start = microtime(true);
        $sortedTransactions = [];
        $step3Data = [];

        foreach ($transactions as $transaction) {
            // Filter only frequent items from transaction
            $filteredItems = array_filter($transaction['items'], function ($item) use ($frequentItems) {
                return isset($frequentItems[$item]);
            });

            // Sort filtered items by frequency order (as in F-List)
            usort($filteredItems, function ($a, $b) use ($frequentItems) {
                return $frequentItems[$b] <=> $frequentItems[$a]; // Descending by count
            });

            if (!empty($filteredItems)) {
                $sortedTransactions[] = $filteredItems;

                // Add itemset key for view compatibility - show the sorted transaction
                $step3Data[] = [
                    'itemset' => implode(' → ', $filteredItems),  // For view display
                    'original' => implode(', ', $transaction['items']),
                    'sorted' => implode(' → ', $filteredItems),
                    'count' => count($filteredItems)
                ];
            }
        }
        $stepTimings['step3'] = round((microtime(true) - $step3Start) * 1000, 2);

        // Step 4: Build FP-Tree (simplified representation as paths)
        $step4Start = microtime(true);
        $fpTreePaths = [];
        $step4Data = [];

        foreach ($sortedTransactions as $index => $items) {
            $path = implode(' → ', $items);
            if (!isset($fpTreePaths[$path])) {
                $fpTreePaths[$path] = 0;
            }
            $fpTreePaths[$path]++;
        }

        foreach ($fpTreePaths as $path => $count) {
            $support = round(($count / $totalTransactions) * 100, 1);
            $step4Data[] = [
                'itemset' => $path,  // Add itemset key for view compatibility
                'path' => $path,
                'count' => $count,
                'support' => $support,
                'status' => 'kept'  // FP-Tree paths are always kept
            ];
        }
        $stepTimings['step4'] = round((microtime(true) - $step4Start) * 1000, 2);

        // Step 5: Mine frequent patterns using FP-Tree
        $step5Start = microtime(true);
        $frequentPatterns = [];

        // Find all 2-itemsets (pairs) from sorted transactions
        foreach ($sortedTransactions as $items) {
            for ($i = 0; $i < count($items); $i++) {
                for ($j = $i + 1; $j < count($items); $j++) {
                    $pair = [$items[$i], $items[$j]];
                    sort($pair);
                    $pairKey = implode(', ', $pair);

                    if (!isset($frequentPatterns[$pairKey])) {
                        $frequentPatterns[$pairKey] = 0;
                    }
                    $frequentPatterns[$pairKey]++;
                }
            }
        }

        $step5Data = [];
        $validFrequentPairs = [];
        foreach ($frequentPatterns as $pattern => $count) {
            $support = round(($count / $totalTransactions) * 100, 1);
            $status = $support >= $minSupport ? 'kept' : 'pruned';

            if ($status === 'kept') {
                $validFrequentPairs[$pattern] = explode(', ', $pattern);
            }

            $step5Data[] = [
                'itemset' => '{' . $pattern . '}',  // Use itemset for view compatibility
                'pattern' => '{' . $pattern . '}',
                'count' => $count,
                'support' => $support,
                'status' => $status
            ];
        }
        $stepTimings['step5'] = round((microtime(true) - $step5Start) * 1000, 2);

        // Generate association rules from frequent patterns
        $rulesStart = microtime(true);
        $associationRules = [];

        foreach ($validFrequentPairs as $pairKey => $pair) {
            $pairCount = $frequentPatterns[$pairKey];
            $pairSupport = round(($pairCount / $totalTransactions) * 100, 1);

            // Rule: A → B
            $antecedentCount1 = $itemCounts[$pair[0]];
            $confidence1 = round(($pairCount / $antecedentCount1) * 100, 1);
            $expectedSupport1 = ($itemCounts[$pair[0]] / $totalTransactions) * ($itemCounts[$pair[1]] / $totalTransactions);
            $actualSupport1 = $pairCount / $totalTransactions;
            $lift1 = round($actualSupport1 / $expectedSupport1, 2);

            // Rule: B → A
            $antecedentCount2 = $itemCounts[$pair[1]];
            $confidence2 = round(($pairCount / $antecedentCount2) * 100, 1);
            $expectedSupport2 = ($itemCounts[$pair[1]] / $totalTransactions) * ($itemCounts[$pair[0]] / $totalTransactions);
            $actualSupport2 = $pairCount / $totalTransactions;
            $lift2 = round($actualSupport2 / $expectedSupport2, 2);

            // Add rule with higher confidence
            if ($confidence1 >= $confidence2) {
                $associationRules[] = [
                    'rule' => $pair[0] . ' → ' . $pair[1],
                    'description' => 'Jika membeli ' . $pair[0] . ' maka membeli ' . $pair[1],
                    'support' => $pairSupport,
                    'confidence' => $confidence1,
                    'lift' => $lift1,
                    'status' => $confidence1 >= $minConfidence ? 'strong' : 'weak'
                ];
            } else {
                $associationRules[] = [
                    'rule' => $pair[1] . ' → ' . $pair[0],
                    'description' => 'Jika membeli ' . $pair[1] . ' maka membeli ' . $pair[0],
                    'support' => $pairSupport,
                    'confidence' => $confidence2,
                    'lift' => $lift2,
                    'status' => $confidence2 >= $minConfidence ? 'strong' : 'weak'
                ];
            }
        }
        $stepTimings['rules'] = round((microtime(true) - $rulesStart) * 1000, 2);

        $algorithmEnd = microtime(true);
        $totalAlgorithmTime = round(($algorithmEnd - $algorithmStart) * 1000, 2);

        // Build steps array for FP-Growth (Step D - Build FP-Tree dihapus untuk simplifikasi UI)
        $steps = [
            [
                'step' => 'A',
                'title' => 'Scan & Count Items',
                'description' => 'Count frequency of each item in database',
                'data' => $step1Data
            ],
            [
                'step' => 'B',
                'title' => 'Create F-List',
                'description' => 'Keep frequent items with support ≥ ' . $minSupport . '% (sorted by frequency)',
                'data' => $step2Data
            ],
            [
                'step' => 'C',
                'title' => 'Sort Transactions',
                'description' => 'Reorder items in each transaction by F-List order',
                'data' => $step3Data
            ],
            [
                'step' => 'D',
                'title' => 'Mine Patterns',
                'description' => 'Extract frequent patterns from sorted transactions',
                'data' => $step5Data
            ],
        ];

        return [
            'steps' => $steps,
            'association_rules' => $associationRules,
            'summary' => [
                'total_transactions' => $totalTransactions,
                'frequent_items' => count($frequentItems),
                'fp_tree_paths' => count($fpTreePaths),
                'frequent_patterns' => count($validFrequentPairs),
                'strong_rules' => count(array_filter($associationRules, fn($rule) => $rule['status'] === 'strong')),
                'step_timings' => $stepTimings,
                'algorithm_time_ms' => $totalAlgorithmTime
            ]
        ];
    }


    /**
     * Display apriori algorithm process page
     */
    public function aprioriProcess(Request $request)
    {
        // Get algorithm parameters - keep as percentage
        $minSupport = $request->get('min_support', 50); // Keep as percentage (50)
        $minConfidence = $request->get('min_confidence', 70); // Keep as percentage (70)
        $filterType = $request->get('filter_type', 'date');
        $selectedDate = $request->get('transaction_date', ''); // Default empty, no calculation
        $selectedMonth = $request->get('transaction_month', ''); // For month filter

        // Get real transaction data from database for available dates
        $allTransactions = $this->getTransactionDataFromDatabase();
        $availableDates = array_unique(array_column($allTransactions, 'date'));
        sort($availableDates);

        // Get available months from transactions (format: YYYY-MM)
        $availableMonths = [];
        foreach ($availableDates as $date) {
            $monthKey = Carbon::parse($date)->format('Y-m');
            if (!in_array($monthKey, $availableMonths)) {
                $availableMonths[] = $monthKey;
            }
        }
        sort($availableMonths);

        // Only perform calculation if a date or month is selected
        $sampleTransactions = [];
        $algorithmSteps = null;
        $hasCalculation = false;
        $analysisSaved = false; // Tambahan variabel untuk tracking penyimpanan
        $savedRulesCount = 0; // Jumlah rules yang berhasil disimpan

        if (!empty($selectedDate) || !empty($selectedMonth)) {
            $hasCalculation = true;

            // Start timing execution
            $startTime = microtime(true);

            // Filter transactions based on filter type
            if ($filterType === 'month' && !empty($selectedMonth)) {
                // Filter by month - expect format YYYY-MM from month input
                $sampleTransactions = array_filter($allTransactions, function ($transaction) use ($selectedMonth) {
                    $transactionDate = Carbon::parse($transaction['date']);
                    // Direct comparison with Y-m format (no need to parse selectedMonth)
                    return $transactionDate->format('Y-m') === $selectedMonth;
                });
                // Reset array keys after filtering
                $sampleTransactions = array_values($sampleTransactions);
                $selectedDate = $selectedMonth; // For display and saving
            } elseif ($selectedDate === 'all') {
                $sampleTransactions = $allTransactions;
            } else {
                // Filter by specific date
                $sampleTransactions = array_filter($allTransactions, function ($transaction) use ($selectedDate) {
                    // Use Carbon for proper date comparison
                    $transactionDate = Carbon::parse($transaction['date'])->format('Y-m-d');
                    $filterDate = Carbon::parse($selectedDate)->format('Y-m-d');
                    return $transactionDate === $filterDate;
                });
                // Reset array keys after filtering
                $sampleTransactions = array_values($sampleTransactions);
            }

            Log::info('Apriori transactions filtering result', [
                'filter_type' => $filterType,
                'selected_date' => $selectedDate,
                'selected_month' => $selectedMonth,
                'total_transactions' => count($allTransactions),
                'filtered_transactions' => count($sampleTransactions)
            ]);

            // If no transactions found for selected date, show message
            if (empty($sampleTransactions)) {
                $algorithmSteps = $this->getEmptyAlgorithmSteps($minSupport, $minConfidence);
            } else {
                // Simulate apriori algorithm steps - pass original percentage values for display
                $algorithmSteps = $this->simulateAprioriSteps(
                    $sampleTransactions,
                    $minSupport,
                    $minConfidence,
                );

                // Calculate execution time
                $endTime = microtime(true);
                $executionTimeMs = round(($endTime - $startTime) * 1000, 2);

                // Add execution time to algorithm steps summary
                $algorithmSteps['summary']['execution_time_ms'] = $executionTimeMs;
                $algorithmSteps['summary']['execution_time_start'] = $startTime;
                $algorithmSteps['summary']['execution_time_end'] = $endTime;

                // Save analysis results to database (satu per satu association rule)
                try {
                    $savedCount = AprioriAnalysis::saveAnalysis(
                        $algorithmSteps,
                        $selectedDate,
                        $minSupport,
                        $minConfidence,
                        $sampleTransactions,
                        Auth::user()->name ?? 'System'
                    );

                    if ($savedCount && $savedCount > 0) {
                        $analysisSaved = true; // Set true jika berhasil disimpan
                        $savedRulesCount = $savedCount; // Simpan jumlah rules
                        Log::info('Apriori analysis saved successfully', [
                            'rules_saved' => $savedCount,
                            'selected_date' => $selectedDate
                        ]);
                    } else {
                        Log::info('Apriori analysis not saved - no association rules generated', [
                            'selected_date' => $selectedDate,
                            'min_support' => $minSupport,
                            'min_confidence' => $minConfidence
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error('Failed to save Apriori analysis', [
                        'error' => $e->getMessage(),
                        'selected_date' => $selectedDate,
                        'min_support' => $minSupport,
                        'min_confidence' => $minConfidence
                    ]);
                }
            }
        }

        // Get product data with images for modal
        $products = Item::select('item_name', 'image_path', 'id')
            ->get()
            ->keyBy('item_name')
            ->toArray();

        return view('analysis.apriori-process', compact(
            'sampleTransactions',
            'algorithmSteps',
            'selectedDate',
            'availableDates',
            'availableMonths',
            'products',
            'hasCalculation',
            'analysisSaved',
            'savedRulesCount'
        ))->with([
            'minSupport' => $minSupport, // Send as percentage for display
            'minConfidence' => $minConfidence // Send as percentage for display
        ]);
    }

    /**
     * Simulate Apriori algorithm steps using real transaction data
     */
    private function simulateAprioriSteps($transactions, $minSupport, $minConfidence)
    {
        $stepTimings = [];
        $algorithmStart = microtime(true);

        $totalTransactions = count($transactions);
        if ($totalTransactions === 0) {
            return $this->getEmptyAlgorithmSteps($minSupport, $minConfidence);
        }

        // Step 1: Count individual items (1-itemsets)
        $step1Start = microtime(true);
        $itemCounts = [];
        foreach ($transactions as $transaction) {
            foreach ($transaction['items'] as $item) {
                $itemCounts[$item] = ($itemCounts[$item] ?? 0) + 1;
            }
        }

        // Sort items by count descending
        arsort($itemCounts);

        // Step 1 data: Individual item counts
        $step1Data = [];
        foreach ($itemCounts as $item => $count) {
            $support = round(($count / $totalTransactions) * 100, 1);
            $step1Data[] = [
                'item' => $item,
                'count' => $count,
                'total' => $totalTransactions,
                'support' => $support
            ];
        }
        $stepTimings['step1'] = round((microtime(true) - $step1Start) * 1000, 2);

        // Step 2: Prune infrequent items
        $step2Start = microtime(true);
        $frequentItems = [];
        $step2Data = [];
        foreach ($itemCounts as $item => $count) {
            $support = round(($count / $totalTransactions) * 100, 1);
            $status = $support >= $minSupport ? 'kept' : 'pruned';
            if ($status === 'kept') {
                $frequentItems[] = $item;
            }
            $step2Data[] = [
                'item' => $item,
                'support' => $support,
                'status' => $status
            ];
        }
        $stepTimings['step2'] = round((microtime(true) - $step2Start) * 1000, 2);

        // Step 3: Generate 2-itemsets
        $step3Start = microtime(true);
        $pairCombinations = [];
        $step3Data = [];
        for ($i = 0; $i < count($frequentItems); $i++) {
            for ($j = $i + 1; $j < count($frequentItems); $j++) {
                $pair = [$frequentItems[$i], $frequentItems[$j]];
                sort($pair); // Consistent ordering
                $pairKey = implode(', ', $pair);
                $pairCombinations[$pairKey] = $pair;
                $step3Data[] = [
                    'itemset' => '{' . $pairKey . '}',
                    'generated' => true
                ];
            }
        }
        $stepTimings['step3'] = round((microtime(true) - $step3Start) * 1000, 2);

        // Step 4: Count and prune 2-itemsets
        $step4Start = microtime(true);
        $pairCounts = [];
        foreach ($pairCombinations as $pairKey => $pair) {
            $count = 0;
            foreach ($transactions as $transaction) {
                if (count(array_intersect($pair, $transaction['items'])) === 2) {
                    $count++;
                }
            }
            $pairCounts[$pairKey] = $count;
        }

        $frequentPairs = [];
        $step4Data = [];
        foreach ($pairCounts as $pairKey => $count) {
            $support = round(($count / $totalTransactions) * 100, 1);
            $status = $support >= $minSupport ? 'kept' : 'pruned';
            if ($status === 'kept') {
                $frequentPairs[$pairKey] = $pairCombinations[$pairKey];
            }
            $step4Data[] = [
                'itemset' => '{' . $pairKey . '}',
                'count' => $count,
                'support' => $support,
                'status' => $status
            ];
        }
        $stepTimings['step4'] = round((microtime(true) - $step4Start) * 1000, 2);

        // Step 5: Generate 3-itemsets (if any frequent pairs exist)
        $step5Start = microtime(true);
        $step5Data = [];
        $frequentTriplets = [];
        if (count($frequentPairs) >= 2) {
            // Generate all possible triplets from frequent pairs
            $tripletCombinations = [];
            $pairKeys = array_keys($frequentPairs);
            for ($i = 0; $i < count($pairKeys); $i++) {
                for ($j = $i + 1; $j < count($pairKeys); $j++) {
                    $items1 = $frequentPairs[$pairKeys[$i]];
                    $items2 = $frequentPairs[$pairKeys[$j]];
                    $combined = array_unique(array_merge($items1, $items2));
                    if (count($combined) === 3) {
                        sort($combined);
                        $tripletKey = implode(', ', $combined);
                        $tripletCombinations[$tripletKey] = $combined;
                    }
                }
            }

            // Count triplets
            foreach ($tripletCombinations as $tripletKey => $triplet) {
                $count = 0;
                foreach ($transactions as $transaction) {
                    if (count(array_intersect($triplet, $transaction['items'])) === 3) {
                        $count++;
                    }
                }

                $support = round(($count / $totalTransactions) * 100, 1);
                $status = $support >= $minSupport ? 'kept' : 'pruned';

                if ($status === 'kept') {
                    $frequentTriplets[$tripletKey] = $triplet;
                }

                $step5Data[] = [
                    'itemset' => '{' . $tripletKey . '}',
                    'count' => $count,
                    'total' => $totalTransactions,
                    'support' => $support,
                    'status' => $status
                ];
            }
        }

        if (empty($step5Data)) {
            $step5Data[] = ['note' => 'No valid 3-itemsets can be generated from frequent 2-itemsets'];
        }
        $stepTimings['step5'] = round((microtime(true) - $step5Start) * 1000, 2);

        // Generate association rules from frequent 2-itemsets
        $rulesStart = microtime(true);
        $associationRules = [];
        foreach ($frequentPairs as $pairKey => $pair) {
            $pairCount = $pairCounts[$pairKey];
            $pairSupport = round(($pairCount / $totalTransactions) * 100, 1);

            // Rule: A → B
            $antecedentCount1 = $itemCounts[$pair[0]];
            $confidence1 = round(($pairCount / $antecedentCount1) * 100, 1);
            $expectedSupport1 = ($itemCounts[$pair[0]] / $totalTransactions) * ($itemCounts[$pair[1]] / $totalTransactions);
            $actualSupport1 = $pairCount / $totalTransactions;
            $lift1 = round($actualSupport1 / $expectedSupport1, 2);

            // Rule: B → A
            $antecedentCount2 = $itemCounts[$pair[1]];
            $confidence2 = round(($pairCount / $antecedentCount2) * 100, 1);
            $expectedSupport2 = ($itemCounts[$pair[1]] / $totalTransactions) * ($itemCounts[$pair[0]] / $totalTransactions);
            $actualSupport2 = $pairCount / $totalTransactions;
            $lift2 = round($actualSupport2 / $expectedSupport2, 2);

            // Only add the rule with higher confidence to avoid duplication
            if ($confidence1 >= $confidence2) {
                $associationRules[] = [
                    'rule' => $pair[0] . ' → ' . $pair[1],
                    'description' => 'Jika membeli ' . $pair[0] . ' maka membeli ' . $pair[1],
                    'support' => $pairSupport,
                    'confidence' => $confidence1,
                    'lift' => $lift1,
                    'status' => $confidence1 >= $minConfidence ? 'strong' : 'weak'
                ];
            } else {
                $associationRules[] = [
                    'rule' => $pair[1] . ' → ' . $pair[0],
                    'description' => 'Jika membeli ' . $pair[1] . ' maka membeli ' . $pair[0],
                    'support' => $pairSupport,
                    'confidence' => $confidence2,
                    'lift' => $lift2,
                    'status' => $confidence2 >= $minConfidence ? 'strong' : 'weak'
                ];
            }
        }
        $stepTimings['rules'] = round((microtime(true) - $rulesStart) * 1000, 2);

        $algorithmEnd = microtime(true);
        $totalAlgorithmTime = round(($algorithmEnd - $algorithmStart) * 1000, 2);

        // Build steps array
        $steps = [
            [
                'step' => 'A',
                'title' => 'Scan & Count Singles',
                'description' => 'Count how often each individual item appears',
                'data' => $step1Data
            ],
            [
                'step' => 'B',
                'title' => 'Prune Infrequent Items',
                'description' => 'Keep only items whose support ≥ min_sup (' . $minSupport . '%)',
                'data' => $step2Data
            ],
            [
                'step' => 'D',
                'title' => 'Count & Prune 2-Itemsets',
                'description' => 'Count support for each pair, keep those ≥ ' . $minSupport . '%',
                'data' => $step4Data
            ],
            [
                'step' => 'E',
                'title' => 'Generate 3-Itemsets',
                'description' => 'Build triplets from surviving 2-itemsets',
                'data' => $step5Data
            ],
        ];

        return [
            'steps' => $steps,
            'association_rules' => $associationRules,
            'summary' => [
                'total_transactions' => $totalTransactions,
                'frequent_1_itemsets' => count($frequentItems),
                'frequent_2_itemsets' => count($frequentPairs),
                'frequent_3_itemsets' => count($frequentTriplets),
                'strong_rules' => count(array_filter($associationRules, fn($rule) => $rule['status'] === 'strong')),
                'step_timings' => $stepTimings,
                'algorithm_time_ms' => $totalAlgorithmTime
            ]
        ];
    }

    /**
     * Return empty algorithm steps when no transactions available
     */
    private function getEmptyAlgorithmSteps($minSupport, $minConfidence)
    {
        return [
            'steps' => [
                [
                    'step' => 'A',
                    'title' => 'Scan & Count Singles',
                    'description' => 'No transactions available for analysis',
                    'data' => []
                ]
            ],
            'association_rules' => [],
            'summary' => [
                'total_transactions' => 0,
                'frequent_1_itemsets' => 0,
                'frequent_2_itemsets' => 0,
                'frequent_3_itemsets' => 0,
                'strong_rules' => 0,
                'execution_time_ms' => 0,
                'algorithm_time_ms' => 0,
                'step_timings' => []
            ]
        ];
    }

    /**
     * Get real transaction data from database
     * Group by DATE to find shopping patterns per day (not by transaction_id)
     */
    private function getTransactionDataFromDatabase(): array
    {
        // Get all outgoing items grouped by DATE to analyze daily shopping patterns
        $outgoingItems = OutgoingItem::with('item')
            ->orderBy('outgoing_date')
            ->get();

        Log::info('Total outgoing items retrieved from database', ['count' => $outgoingItems->count()]);

        $transactions = [];

        // Group by DATE ONLY to find shopping patterns per day
        // This means all items purchased on the same date are treated as one transaction
        $groupedTransactions = $outgoingItems->groupBy(function ($item) {
            return Carbon::parse($item->outgoing_date)->format('Y-m-d');
        });

        Log::info('Grouped transactions by date count', ['count' => $groupedTransactions->count()]);

        foreach ($groupedTransactions as $date => $items) {
            $itemNames = $items->map(function ($item) {
                return $item->item->item_name;
            })->unique()->values()->toArray();

            // Only include transactions with at least one item
            if (count($itemNames) > 0) {
                $transactions[] = [
                    'id' => 'DATE_' . $date, // Use date as transaction ID
                    'date' => $date,
                    'customer' => 'Daily Transaction', // Not relevant anymore since we group by date
                    'items' => $itemNames,
                    'item_count' => count($itemNames)
                ];
            }
        }

        Log::info('Final transactions count', ['count' => count($transactions)]);

        return $transactions;
    }



    /**
     * Get available customers for filter dropdown
     */
    private function getAvailableCustomers(): array
    {
        $customers = OutgoingItem::whereNotNull('customer')
            ->distinct()
            ->pluck('customer')
            ->filter()
            ->sort()
            ->values()
            ->toArray();

        return array_merge(['all' => 'Semua Customer'], array_combine($customers, $customers));
    }
}
