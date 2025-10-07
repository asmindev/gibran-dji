<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Category;
use App\Models\IncomingItem;
use App\Models\OutgoingItem;
use App\Models\StockPrediction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // Get prediction month filter parameter, default to current month
        $selectedPredictionMonth = $request->get('prediction_month', now()->format('Y-m'));

        // Basic Statistics
        $totalItems = Item::count();
        $totalCategories = Category::count();
        $lowStockItems = Item::whereRaw('stock <= minimum_stock')->get();
        $latestItems = Item::with('category')->latest()->take(5)->get();

        // Calculate total incoming and outgoing items in selected month
        $totalIncoming = IncomingItem::whereRaw("DATE_FORMAT(incoming_date, '%Y-%m') = ?", [$selectedPredictionMonth])
            ->sum('quantity');
        $totalOutgoing = OutgoingItem::whereRaw("DATE_FORMAT(outgoing_date, '%Y-%m') = ?", [$selectedPredictionMonth])
            ->sum('quantity');

        // Stock Predictions Data for Chart (selected month)
        $stockPredictions = StockPrediction::whereRaw("DATE_FORMAT(month, '%Y-%m') = ?", [$selectedPredictionMonth])
            ->orderBy('product')
            ->get();

        // Get all unique products from StockPrediction, IncomingItem, and OutgoingItem
        $allProducts = collect()
            ->merge($stockPredictions->pluck('product'))
            ->merge(
                IncomingItem::join('items', 'incoming_items.item_id', '=', 'items.id')
                    ->whereRaw("DATE_FORMAT(incoming_date, '%Y-%m') = ?", [$selectedPredictionMonth])
                    ->pluck('items.item_name')
            )
            ->merge(
                OutgoingItem::join('items', 'outgoing_items.item_id', '=', 'items.id')
                    ->whereRaw("DATE_FORMAT(outgoing_date, '%Y-%m') = ?", [$selectedPredictionMonth])
                    ->pluck('items.item_name')
            )
            ->unique()
            ->sort()
            ->values();

        // Prepare chart data by product
        $chartLabels = $allProducts->toArray();

        // Initialize arrays for each dataset
        $predictionData = [];
        $salesData = [];
        $restockData = [];

        foreach ($allProducts as $product) {
            // Get predictions for this product
            $salesPrediction = $stockPredictions->where('product', $product)->where('prediction_type', 'sales')->first();
            $restockPrediction = $stockPredictions->where('product', $product)->where('prediction_type', 'restock')->first();

            // Get actual data for this product in selected month
            $actualSales = OutgoingItem::join('items', 'outgoing_items.item_id', '=', 'items.id')
                ->where('items.item_name', $product)
                ->whereRaw("DATE_FORMAT(outgoing_date, '%Y-%m') = ?", [$selectedPredictionMonth])
                ->sum('outgoing_items.quantity');

            $actualRestock = IncomingItem::join('items', 'incoming_items.item_id', '=', 'items.id')
                ->where('items.item_name', $product)
                ->whereRaw("DATE_FORMAT(incoming_date, '%Y-%m') = ?", [$selectedPredictionMonth])
                ->sum('incoming_items.quantity');

            // Add to arrays
            // For prediction data, only use sales prediction (not sales + restock)
            // because we're comparing with sales actual, not total activity
            $predictionData[] = ($salesPrediction ? $salesPrediction->prediction : 0);
            $salesData[] = $actualSales;
            $restockData[] = $actualRestock;
        }

        // Prepare data for multi-line chart
        $lineChartData = [
            'labels' => $chartLabels,
            'datasets' => [
                [
                    'label' => 'Prediksi Penjualan',
                    'data' => $predictionData,
                    'borderColor' => '#3b82f6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                ],
                [
                    'label' => 'Penjualan Aktual',
                    'data' => $salesData,
                    'borderColor' => '#ef4444',
                    'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                ]
            ]
        ];

        // Calculate totals for summary cards
        $totalPrediction = array_sum($predictionData);

        // Calculate overall accuracy
        $overallAccuracy = $this->calculateOverallAccuracy($selectedPredictionMonth);

        // Stock by Category for Chart
        $stockByCategory = Category::select('categories.name')
            ->selectRaw('COALESCE(SUM(items.stock), 0) as total_stock')
            ->leftJoin('items', 'categories.id', '=', 'items.category_id')
            ->groupBy('categories.id', 'categories.name')
            ->orderBy('total_stock', 'desc')
            ->get();

        // Monthly Transactions for Chart
        $currentMonth = now()->format('Y-m');
        $monthlyIncoming = IncomingItem::whereRaw("DATE_FORMAT(incoming_date, '%Y-%m') = ?", [$currentMonth])
            ->sum('quantity');
        $monthlyOutgoing = OutgoingItem::whereRaw("DATE_FORMAT(outgoing_date, '%Y-%m') = ?", [$currentMonth])
            ->sum('quantity');

        // Get available months for prediction filter
        $availablePredictionMonths = DB::table('incoming_items')
            ->selectRaw("DATE_FORMAT(incoming_date, '%Y-%m') as month")
            ->union(
                DB::table('outgoing_items')->selectRaw("DATE_FORMAT(outgoing_date, '%Y-%m') as month")
            )
            ->distinct()
            ->orderBy('month', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'value' => $item->month,
                    'label' => \Carbon\Carbon::createFromFormat('Y-m', $item->month)->format('F Y')
                ];
            })
            ->unique('value')
            ->values();

        return view('dashboard.index', compact(
            'totalItems',
            'totalCategories',
            'lowStockItems',
            'latestItems',
            'stockByCategory',
            'monthlyIncoming',
            'monthlyOutgoing',
            'stockPredictions',
            'lineChartData',
            'availablePredictionMonths',
            'selectedPredictionMonth',
            'totalIncoming',
            'totalOutgoing',
            'totalPrediction',
            'overallAccuracy'
        ));
    }

    /**
     * Calculate overall accuracy for predictions in selected month
     */
    private function calculateOverallAccuracy($selectedMonth)
    {
        // Get all predictions with actual data for the selected month
        $predictions = StockPrediction::whereRaw("DATE_FORMAT(month, '%Y-%m') = ?", [$selectedMonth])
            ->whereNotNull('actual')
            ->where('prediction_type', 'sales') // Only calculate for sales predictions
            ->get();

        if ($predictions->isEmpty()) {
            return null; // No data available
        }

        $totalAccuracy = 0;
        $count = 0;

        foreach ($predictions as $prediction) {
            if ($prediction->prediction > 0 || $prediction->actual > 0) {
                $difference = abs($prediction->prediction - $prediction->actual);
                $maxValue = max($prediction->prediction, $prediction->actual);

                if ($maxValue > 0) {
                    $accuracy = (1 - ($difference / $maxValue)) * 100;
                    $totalAccuracy += max(0, $accuracy);
                    $count++;
                }
            }
        }

        return $count > 0 ? round($totalAccuracy / $count, 2) : null;
    }
}
