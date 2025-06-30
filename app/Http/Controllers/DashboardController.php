<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Category;
use App\Models\IncomingItem;
use App\Models\OutgoingItem;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $totalItems = Item::count();
        $totalCategories = Category::count();
        $lowStockItems = Item::where('stock', '<=', 10)->get();
        $latestItems = Item::with('category')->latest()->take(5)->get();
        $recentIncoming = IncomingItem::with('item')->latest()->take(5)->get();
        $recentOutgoing = OutgoingItem::with('item')->latest()->take(5)->get();

        return view('dashboard.index', compact(
            'totalItems',
            'totalCategories',
            'lowStockItems',
            'latestItems',
            'recentIncoming',
            'recentOutgoing'
        ));
    }
}
