<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Category;
use App\Models\IncomingItem;
use App\Models\OutgoingItem;
use App\Models\AprioriAnalysis;

use App\Models\StockPrediction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {


        // Get month filter parameter, default to first available month for Apriori or current month

        // Get prediction month filter parameter, default to current month
        $selectedPredictionMonth = $request->get('prediction_month', now()->format('Y-m'));        // Basic Statistics
        $totalItems = Item::count();
        $totalCategories = Category::count();
        $lowStockItems = Item::whereRaw('stock <= minimum_stock')->get();
        $latestItems = Item::with('category')->latest()->take(5)->get();

        // calculate total incoming items and outgoing items in selected month
        $totalIncoming = IncomingItem::whereRaw("DATE_FORMAT(incoming_date, '%Y-%m') = ?", [$selectedPredictionMonth])
            ->sum('quantity');
        $totalOutgoing = OutgoingItem::whereRaw("DATE_FORMAT(outgoing_date, '%Y-%m') = ?", [$selectedPredictionMonth])
            ->sum('quantity');

        // Stock Predictions Data for Chart (selected month)
        $stockPredictions = StockPrediction::whereRaw("DATE_FORMAT(month, '%Y-%m') = ?", [$selectedPredictionMonth])
            ->orderBy('product')
            ->get();

        // Get all unique products for consistent labels
        $allProducts = $stockPredictions->pluck('product')->unique()->sort()->values();

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
            $predictionData[] = ($salesPrediction ? $salesPrediction->prediction : 0) + ($restockPrediction ? $restockPrediction->prediction : 0);
            $salesData[] = $actualSales;
            $restockData[] = $actualRestock;
        }

        // Prepare data for multi-line chart
        $lineChartData = [
            'labels' => $chartLabels,
            'datasets' => [
                [
                    'label' => 'Total Prediksi',
                    'data' => $predictionData,
                    'borderColor' => '#3b82f6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                ],
                [
                    'label' => 'Sales Aktual',
                    'data' => $salesData,
                    'borderColor' => '#ef4444',
                    'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                ],
                [
                    'label' => 'Restock Aktual',
                    'data' => $restockData,
                    'borderColor' => '#10b981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                ]
            ]
        ];

        // Calculate totals for summary cards
        $totalPrediction = array_sum($predictionData);

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


        // Get available months for filter dropdown (use the already computed data)

        // Stock Predictions Data for Chart (selected month)
        $stockPredictions = StockPrediction::whereRaw("DATE_FORMAT(month, '%Y-%m') = ?", [$selectedPredictionMonth])
            ->orderBy('product')
            ->get();

        // Separate predictions by type
        $salesPredictions = $stockPredictions->where('prediction_type', 'sales');
        $restockPredictions = $stockPredictions->where('prediction_type', 'restock');

        // Get all unique products for consistent labels
        $allProducts = $stockPredictions->pluck('product')->unique()->sort()->values();

        // Prepare data for chart
        $predictionLabels = $allProducts->toArray();

        // Create sales data array with proper indexing
        $salesData = [];
        foreach ($allProducts as $product) {
            $salesPrediction = $salesPredictions->where('product', $product)->first();
            $salesData[] = $salesPrediction ? $salesPrediction->prediction : 0;
        }

        // Create restock data array with proper indexing
        $restockData = [];
        foreach ($allProducts as $product) {
            $restockPrediction = $restockPredictions->where('product', $product)->first();
            $restockData[] = $restockPrediction ? $restockPrediction->prediction : 0;
        }

        // Keep backward compatibility
        $predictionData = $stockPredictions->pluck('prediction')->toArray();

        // Get available months for prediction filter
        $availablePredictionMonths = StockPrediction::distinct()
            ->orderBy('month', 'desc')
            ->get(['month'])
            ->map(function ($prediction) {
                $monthFormat = \Carbon\Carbon::parse($prediction->month)->format('Y-m');
                return [
                    'value' => $monthFormat,
                    'label' => \Carbon\Carbon::parse($prediction->month)->format('F Y')
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
            'predictionLabels',
            'predictionData',
            'salesData',
            'restockData',
            'availablePredictionMonths',
            'selectedPredictionMonth',
            'totalIncoming',
            'totalOutgoing',
            'lineChartData',
            'totalPrediction'
        ));
    }
}
