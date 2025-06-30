<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Category;
use App\Models\IncomingItem;
use App\Models\OutgoingItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function index()
    {
        return view('reports.index');
    }

    public function incomingReport(Request $request)
    {
        $categories = Category::all();
        $query = IncomingItem::with(['item.category']);

        // Apply filters
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('category_id')) {
            $query->whereHas('item', function ($q) use ($request) {
                $q->where('category_id', $request->category_id);
            });
        }

        $incomingItems = $query->latest()->paginate(20);

        // Calculate summary data
        $summary = [
            'total_transactions' => $query->count(),
            'total_items' => $query->sum('quantity'),
            'total_value' => $query->whereNotNull('unit_cost')->sum(DB::raw('quantity * unit_cost')),
            'avg_per_transaction' => $query->count() > 0 ? $query->whereNotNull('unit_cost')->sum(DB::raw('quantity * unit_cost')) / $query->count() : 0,
        ];

        return view('reports.incoming_items', compact('incomingItems', 'categories', 'summary'));
    }

    public function outgoingReport(Request $request)
    {
        $categories = Category::all();
        $query = OutgoingItem::with(['item.category']);

        // Apply filters
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('category_id')) {
            $query->whereHas('item', function ($q) use ($request) {
                $q->where('category_id', $request->category_id);
            });
        }

        if ($request->filled('purpose')) {
            $query->where('purpose', $request->purpose);
        }

        $outgoingItems = $query->latest()->paginate(20);

        // Calculate summary data
        $summary = [
            'total_transactions' => $query->count(),
            'total_items' => $query->sum('quantity'),
            'total_value' => $query->whereNotNull('unit_price')->sum(DB::raw('quantity * unit_price')),
            'avg_per_transaction' => $query->count() > 0 ? $query->whereNotNull('unit_price')->sum(DB::raw('quantity * unit_price')) / $query->count() : 0,
        ];

        return view('reports.outgoing_items', compact('outgoingItems', 'categories', 'summary'));
    }

    public function stockReport()
    {
        $items = Item::with('category')->get();
        $lowStockItems = Item::whereRaw('stock <= minimum_stock')->with('category')->get();

        return view('reports.stock_report', compact('items', 'lowStockItems'));
    }

    public function summaryReport(Request $request)
    {
        // Apply date filters if provided
        $dateFrom = $request->filled('date_from') ? Carbon::parse($request->date_from) : Carbon::now()->startOfMonth();
        $dateTo = $request->filled('date_to') ? Carbon::parse($request->date_to) : Carbon::now();

        // Basic totals
        $totalItems = Item::count();
        $totalStock = Item::sum('stock');
        $totalStockValue = Item::sum(DB::raw('stock * selling_price'));

        // Transaction data for the period
        $incomingQuery = IncomingItem::whereBetween('created_at', [$dateFrom, $dateTo]);
        $outgoingQuery = OutgoingItem::whereBetween('created_at', [$dateFrom, $dateTo]);

        $incomingTransactions = $incomingQuery->count();
        $outgoingTransactions = $outgoingQuery->count();
        $totalIncoming = $incomingQuery->sum('quantity');
        $totalOutgoing = $outgoingQuery->sum('quantity');
        $incomingValue = $incomingQuery->whereNotNull('unit_cost')->sum(DB::raw('quantity * unit_cost'));
        $outgoingValue = $outgoingQuery->whereNotNull('unit_price')->sum(DB::raw('quantity * unit_price'));

        // Category breakdown
        $categoryBreakdown = Category::with('items')->get()->map(function ($category) {
            $totalStock = $category->items->sum('stock');
            $stockValue = $category->items->reduce(function ($carry, $item) {
                return $carry + ($item->stock * $item->selling_price);
            }, 0);
            $lowStockItems = $category->items->filter(function ($item) {
                return $item->stock <= $item->minimum_stock;
            })->count();

            return [
                'name' => $category->name,
                'total_items' => $category->items->count(),
                'current_stock' => $totalStock,
                'stock_value' => $stockValue,
                'low_stock_items' => $lowStockItems,
            ];
        });

        // Top items
        $topIncomingItems = Item::with('category')
            ->select('items.id', 'items.item_name', 'items.item_code', 'items.category_id', 'items.stock', 'items.selling_price', 'items.image_path', DB::raw('SUM(incoming_items.quantity) as incoming_total'))
            ->join('incoming_items', 'items.id', '=', 'incoming_items.item_id')
            ->whereBetween('incoming_items.created_at', [$dateFrom, $dateTo])
            ->groupBy('items.id', 'items.item_name', 'items.item_code', 'items.category_id', 'items.stock', 'items.selling_price', 'items.image_path')
            ->orderBy('incoming_total', 'desc')
            ->limit(5)
            ->get();

        $topOutgoingItems = Item::with('category')
            ->select('items.id', 'items.item_name', 'items.item_code', 'items.category_id', 'items.stock', 'items.selling_price', 'items.image_path', DB::raw('SUM(outgoing_items.quantity) as outgoing_total'))
            ->join('outgoing_items', 'items.id', '=', 'outgoing_items.item_id')
            ->whereBetween('outgoing_items.created_at', [$dateFrom, $dateTo])
            ->groupBy('items.id', 'items.item_name', 'items.item_code', 'items.category_id', 'items.stock', 'items.selling_price', 'items.image_path')
            ->orderBy('outgoing_total', 'desc')
            ->limit(5)
            ->get();

        $summary = [
            'total_items' => $totalItems,
            'total_incoming' => $totalIncoming,
            'total_outgoing' => $totalOutgoing,
            'total_value' => $totalStockValue,
            'incoming_transactions' => $incomingTransactions,
            'outgoing_transactions' => $outgoingTransactions,
            'total_transactions' => $incomingTransactions + $outgoingTransactions,
            'incoming_value' => $incomingValue,
            'outgoing_value' => $outgoingValue,
            'net_movement' => $incomingValue - $outgoingValue,
            'category_breakdown' => $categoryBreakdown,
            'top_incoming_items' => $topIncomingItems,
            'top_outgoing_items' => $topOutgoingItems,
        ];

        return view('reports.summary', compact('summary'));
    }

    // Legacy methods for backward compatibility
    public function incomingItems(Request $request)
    {
        return $this->incomingReport($request);
    }

    public function outgoingItems(Request $request)
    {
        return $this->outgoingReport($request);
    }

    public function summary(Request $request)
    {
        return $this->summaryReport($request);
    }
}
