<?php

namespace App\Http\Controllers;

use App\Models\InventoryRecommendation;
use App\Models\StockPrediction;
use App\Models\Item;
use App\Models\OutgoingItem;
use App\Services\InventoryAnalysisService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AnalysisController extends Controller
{
    private InventoryAnalysisService $analysisService;

    public function __construct(InventoryAnalysisService $analysisService)
    {
        $this->analysisService = $analysisService;
    }

    /**
     * Display apriori algorithm process page
     */
    public function aprioriProcess(Request $request)
    {
        // Get algorithm parameters - keep as percentage
        $minSupport = $request->get('min_support', 50); // Keep as percentage (50)
        $minConfidence = $request->get('min_confidence', 70); // Keep as percentage (70)
        $selectedDate = $request->get('transaction_date', 'all'); // Default all dates

        // Get real transaction data from database
        $allTransactions = $this->getTransactionDataFromDatabase();

        // Filter transactions by selected date if specified
        $sampleTransactions = $allTransactions;
        if ($selectedDate !== 'all') {
            $sampleTransactions = array_filter($allTransactions, function ($transaction) use ($selectedDate) {
                // Use Carbon for proper date comparison
                $transactionDate = Carbon::parse($transaction['date'])->format('Y-m-d');
                $filterDate = Carbon::parse($selectedDate)->format('Y-m-d');
                return $transactionDate === $filterDate;
            });
            // Reset array keys after filtering
            $sampleTransactions = array_values($sampleTransactions);
        }

        // If no transactions found for selected date, use all transactions
        if (empty($sampleTransactions) && $selectedDate !== 'all') {
            $sampleTransactions = $allTransactions;
            $selectedDate = 'all'; // Indicate all dates are being used
        }

        // Get product data with images for modal
        $products = Item::select('item_name', 'image_path', 'item_code')
            ->get()
            ->keyBy('item_name')
            ->toArray();

        // Simulate apriori algorithm steps - pass original percentage values for display
        $algorithmSteps = $this->simulateAprioriSteps(
            $sampleTransactions,
            $minSupport,
            $minConfidence,
        );

        // Get available dates for dropdown
        $availableDates = array_unique(array_column($allTransactions, 'date'));
        sort($availableDates);

        return view('analysis.apriori-process', compact(
            'sampleTransactions',
            'algorithmSteps',
            'selectedDate',
            'availableDates',
            'products'
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
        $totalTransactions = count($transactions);
        if ($totalTransactions === 0) {
            return $this->getEmptyAlgorithmSteps($minSupport, $minConfidence);
        }

        // Step 1: Count individual items (1-itemsets)
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

        // Step 2: Prune infrequent items
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

        // Step 3: Generate 2-itemsets
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

        // Step 4: Count and prune 2-itemsets
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

        // Step 5: Generate 3-itemsets (if any frequent pairs exist)
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
            // [
            //     'step' => 'C',
            //     'title' => 'Generate 2-Itemsets',
            //     'description' => 'Form every possible pair of the remaining items',
            //     'data' => $step3Data
            // ],
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
            // [
            //     'step' => 'F',
            //     'title' => 'Algorithm Termination',
            //     'description' => 'Algorithm halts when no k-itemsets meet min_sup',
            //     'data' => [
            //         ['result' => 'Largest frequent itemset: k = ' . (count($frequentTriplets) > 0 ? '3' : (count($frequentPairs) > 0 ? '2' : '1'))],
            //         ['result' => 'Analysis with ' . $totalTransactions . ' transactions completed']
            //     ]
            // ]
        ];

        // Generate association rules from frequent 2-itemsets
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

        return [
            'steps' => $steps,
            'association_rules' => $associationRules,
            'summary' => [
                'total_transactions' => $totalTransactions,
                'frequent_1_itemsets' => count($frequentItems),
                'frequent_2_itemsets' => count($frequentPairs),
                'frequent_3_itemsets' => count($frequentTriplets),
                'strong_rules' => count(array_filter($associationRules, fn($rule) => $rule['status'] === 'strong'))
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
                'strong_rules' => 0
            ]
        ];
    }

    /**
     * Get real transaction data from database
     */
    private function getTransactionDataFromDatabase(): array
    {
        // Group outgoing items by date and customer to form transactions
        $outgoingItems = OutgoingItem::with('item')
            ->orderBy('outgoing_date')
            ->orderBy('customer')
            ->get();

        $transactions = [];
        $transactionId = 1;

        // Group by date and customer to form unique transactions
        $groupedTransactions = $outgoingItems->groupBy(function ($item) {
            // Use string casting for the date
            return (string)$item->outgoing_date . '|' . $item->customer;
        });

        foreach ($groupedTransactions as $key => $items) {
            $parts = explode('|', $key);
            $date = $parts[0];
            $customer = $parts[1];

            $itemNames = $items->map(function ($item) {
                return $item->item->item_name;
            })->unique()->values()->toArray();

            // Include all transactions (even single items) for complete analysis
            $transactions[] = [
                'id' => $transactionId++,
                'date' => $date,
                'customer' => $customer,
                'items' => $itemNames,
                'item_count' => count($itemNames)
            ];
        }

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
