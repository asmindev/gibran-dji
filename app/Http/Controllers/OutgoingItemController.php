<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\OutgoingItem;
use App\Http\Requests\StoreOutgoingItemRequest;
use App\Exports\OutgoingItemsExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class OutgoingItemController extends Controller
{

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = OutgoingItem::with(['item', 'item.category']);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->whereHas('item', function ($itemQuery) use ($search) {
                $itemQuery->where('item_name', 'like', "%{$search}%")
                    ->orWhere('item_code', 'like', "%{$search}%");
            })->orWhere('destination', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")
                ->orWhere('notes', 'like', "%{$search}%");
        }

        // Date range filter
        if ($request->filled('start_date')) {
            $query->where('outgoing_date', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->where('outgoing_date', '<=', $request->end_date);
        }

        // Item filter
        if ($request->filled('item_id')) {
            $query->where('item_id', $request->item_id);
        }

        $outgoingItems = $query->latest('outgoing_date')->paginate(15)->withQueryString();
        $items = Item::all();

        return view('outgoing_items.index', compact('outgoingItems', 'items'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $items = Item::all();
        return view('outgoing_items.create', compact('items'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreOutgoingItemRequest $request)
    {
        // Check if item has enough stock
        $item = Item::find($request->item_id);
        if ($item->stock < $request->quantity) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Insufficient stock. Available: ' . $item->stock);
        }

        OutgoingItem::create($request->validated());

        return redirect()->route('outgoing_items.index')
            ->with('success', 'Outgoing item recorded successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(OutgoingItem $outgoingItem)
    {
        $outgoingItem->load('item');
        return view('outgoing_items.show', compact('outgoingItem'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(OutgoingItem $outgoingItem)
    {
        $items = Item::all();
        return view('outgoing_items.edit', compact('outgoingItem', 'items'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreOutgoingItemRequest $request, OutgoingItem $outgoingItem)
    {
        // Check if item has enough stock (considering current transaction)
        $item = Item::find($request->item_id);
        $currentStock = $item->stock + $outgoingItem->quantity; // Add back current quantity

        if ($currentStock < $request->quantity) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Insufficient stock. Available: ' . $currentStock);
        }

        $outgoingItem->update($request->validated());

        return redirect()->route('outgoing_items.index')
            ->with('success', 'Outgoing item updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(OutgoingItem $outgoingItem)
    {
        $outgoingItem->delete();

        return redirect()->route('outgoing_items.index')
            ->with('success', 'Outgoing item deleted successfully.');
    }

    /**
     * Export outgoing items to Excel/CSV
     */
    public function export(Request $request)
    {
        $format = $request->get('format', 'excel');
        $filename = 'barang_keluar_' . date('Y-m-d_H-i-s');

        if ($format === 'csv') {
            return Excel::download(new OutgoingItemsExport($request), $filename . '.csv', \Maatwebsite\Excel\Excel::CSV);
        } else {
            return Excel::download(new OutgoingItemsExport($request), $filename . '.xlsx');
        }
    }

    /**
     * Download import template
     */
    public function template()
    {
        $filename = 'template_barang_keluar.xlsx';

        // Create a simple template with sample data
        $export = new OutgoingItemsExport(request());
        return Excel::download($export, $filename);
    }
}
