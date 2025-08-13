<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Category;
use App\Models\IncomingItem;
use App\Models\OutgoingItem;
use App\Models\AprioriAnalysis;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // Get month filter parameter, default to current month
        $selectedMonth = $request->get('month', now()->format('Y-m'));

        // Basic Statistics
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

        // Apriori Analysis Data for Chart (filtered by month)
        $aprioriData = AprioriAnalysis::select('rules', 'confidence', 'support')
            ->whereRaw("DATE_FORMAT(transaction_date, '%Y-%m') = ?", [$selectedMonth])
            ->orderBy('confidence', 'desc')
            ->limit(5) // Ambil 5 data teratas berdasarkan confidence
            ->get()
            ->map(function ($item) {
                // Format label: "Jersey Mills + Sepatu Bola Ortus"
                $label = implode(' + ', $item->rules);
                return [
                    'label' => $label,
                    'confidence' => $item->confidence,
                    'support' => $item->support
                ];
            });

        // Get available months for filter dropdown
        $availableMonths = AprioriAnalysis::selectRaw("DATE_FORMAT(transaction_date, '%Y-%m') as month")
            ->distinct()
            ->orderBy('month', 'desc')
            ->pluck('month')
            ->map(function ($month) {
                return [
                    'value' => $month,
                    'label' => \Carbon\Carbon::parse($month . '-01')->format('F Y')
                ];
            });

        return view('dashboard.index', compact(
            'totalItems',
            'totalCategories',
            'lowStockItems',
            'latestItems',
            'stockByCategory',
            'monthlyIncoming',
            'monthlyOutgoing',
            'aprioriData',
            'selectedMonth',
            'availableMonths'
        ));
    }
}
