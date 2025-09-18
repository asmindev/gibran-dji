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

        // Prepare data for chart
        $predictionLabels = $stockPredictions->pluck('product')->toArray();
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
            'availablePredictionMonths',
            'selectedPredictionMonth'
        ));
    }
}
