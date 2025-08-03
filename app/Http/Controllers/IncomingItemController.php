<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\IncomingItem;
use App\Http\Requests\StoreIncomingItemRequest;
use App\Exports\IncomingItemsExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class IncomingItemController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = IncomingItem::with(['item', 'item.category']);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('notes', 'like', "%{$search}%")
                    ->orWhereHas('item', function ($itemQuery) use ($search) {
                        $itemQuery->where('item_name', 'like', "%{$search}%")
                            ->orWhere('item_code', 'like', "%{$search}%");
                    });
            });
        }

        // Date range filter
        if ($request->filled('start_date')) {
            $query->where('incoming_date', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->where('incoming_date', '<=', $request->end_date);
        }

        // Item filter
        if ($request->filled('item_id')) {
            $query->where('item_id', $request->item_id);
        }



        // Quantity range filter
        if ($request->filled('min_quantity')) {
            $query->where('quantity', '>=', $request->get('min_quantity'));
        }
        if ($request->filled('max_quantity')) {
            $query->where('quantity', '<=', $request->get('max_quantity'));
        }

        // Cost range filter
        if ($request->filled('min_cost')) {
            $query->where('unit_cost', '>=', $request->get('min_cost'));
        }
        if ($request->filled('max_cost')) {
            $query->where('unit_cost', '<=', $request->get('max_cost'));
        }

        // Sorting functionality
        $sortBy = $request->get('sort_by', 'incoming_date');
        $sortOrder = $request->get('sort_order', 'desc');

        $allowedSorts = ['incoming_date', 'quantity', 'unit_cost', 'supplier', 'created_at'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'incoming_date';
        }

        if (!in_array($sortOrder, ['asc', 'desc'])) {
            $sortOrder = 'desc';
        }

        $query->orderBy($sortBy, $sortOrder);

        $incomingItems = $query->paginate(15)->withQueryString();
        $items = Item::all();

        return view('incoming_items.index', compact('incomingItems', 'items'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $items = Item::all();
        return view('incoming_items.create', compact('items'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreIncomingItemRequest $request)
    {
        IncomingItem::create($request->validated());

        return redirect()->route('incoming_items.index')
            ->with('success', 'Incoming item recorded successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(IncomingItem $incomingItem)
    {
        $incomingItem->load('item');
        return view('incoming_items.show', compact('incomingItem'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(IncomingItem $incomingItem)
    {
        $items = Item::all();
        return view('incoming_items.edit', compact('incomingItem', 'items'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreIncomingItemRequest $request, IncomingItem $incomingItem)
    {
        $incomingItem->update($request->validated());

        return redirect()->route('incoming_items.index')
            ->with('success', 'Incoming item updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(IncomingItem $incomingItem)
    {
        $incomingItem->delete();

        return redirect()->route('incoming_items.index')
            ->with('success', 'Incoming item deleted successfully.');
    }

    /**
     * Export incoming items to Excel/CSV
     */
    public function export(Request $request)
    {
        $format = $request->get('format', 'excel');
        $filename = 'barang_masuk_' . date('Y-m-d_H-i-s');

        if ($format === 'csv') {
            return Excel::download(new IncomingItemsExport($request), $filename . '.csv', \Maatwebsite\Excel\Excel::CSV);
        } else {
            return Excel::download(new IncomingItemsExport($request), $filename . '.xlsx');
        }
    }

    /**
     * Download import template
     */
    public function template()
    {
        $filename = 'template_barang_masuk.xlsx';

        // Create a simple template with sample data
        $export = new IncomingItemsExport(request());
        return Excel::download($export, $filename);
    }
}
