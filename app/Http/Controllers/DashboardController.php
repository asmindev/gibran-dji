<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Category;
use App\Models\IncomingItem;
use App\Models\OutgoingItem;
use App\Models\AprioriAnalysis;
use App\Models\FpGrowthAnalysis;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // Basic Statistics
        $totalItems = Item::count();
        $totalCategories = Category::count();
        $lowStockItems = Item::whereRaw('stock <= minimum_stock')->get();
        $latestItems = Item::with('category')->latest()->take(5)->get();

        // Get Apriori Analysis Data (top 10 by support)
        $aprioriData = AprioriAnalysis::orderBy('support', 'desc')
            ->orderBy('confidence', 'desc')
            ->limit(10)
            ->get();

        // Get FP-Growth Analysis Data (top 10 by support)
        $fpGrowthData = FpGrowthAnalysis::orderBy('support', 'desc')
            ->orderBy('confidence', 'desc')
            ->limit(10)
            ->get();

        // Prepare Apriori Chart Data
        $aprioriChartData = [
            'labels' => $aprioriData->map(function ($item) {
                return $item->antecedent . ' → ' . $item->consequent;
            })->toArray(),
            'support' => $aprioriData->pluck('support')->map(function ($value) {
                return (float) $value;
            })->toArray(),
            'confidence' => $aprioriData->pluck('confidence')->map(function ($value) {
                return (float) $value;
            })->toArray(),
        ];

        // Prepare FP-Growth Chart Data
        $fpGrowthChartData = [
            'labels' => $fpGrowthData->map(function ($item) {
                return $item->antecedent . ' → ' . $item->consequent;
            })->toArray(),
            'support' => $fpGrowthData->pluck('support')->map(function ($value) {
                return (float) $value;
            })->toArray(),
            'confidence' => $fpGrowthData->pluck('confidence')->map(function ($value) {
                return (float) $value;
            })->toArray(),
            'lift' => $fpGrowthData->pluck('lift')->map(function ($value) {
                return $value ? (float) $value : 0;
            })->toArray(),
        ];

        // Calculate summary statistics
        $totalAprioriRules = AprioriAnalysis::count();
        $totalFpGrowthRules = FpGrowthAnalysis::count();
        $avgAprioriConfidence = AprioriAnalysis::avg('confidence');
        $avgFpGrowthConfidence = FpGrowthAnalysis::avg('confidence');

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

        return view('dashboard.index', compact(
            'totalItems',
            'totalCategories',
            'lowStockItems',
            'latestItems',
            'stockByCategory',
            'monthlyIncoming',
            'monthlyOutgoing',
            'aprioriChartData',
            'fpGrowthChartData',
            'totalAprioriRules',
            'totalFpGrowthRules',
            'avgAprioriConfidence',
            'avgFpGrowthConfidence'
        ));
    }
}
