<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Category;
use App\Models\IncomingItem;
use App\Models\OutgoingItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
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

        return view('dashboard.index', compact(
            'totalItems',
            'totalCategories',
            'lowStockItems',
            'latestItems',
            'stockByCategory',
            'monthlyIncoming',
            'monthlyOutgoing'
        ));
    }
}
