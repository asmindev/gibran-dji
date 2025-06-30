<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\IncomingItem;
use App\Http\Requests\StoreIncomingItemRequest;
use Illuminate\Http\Request;

class IncomingItemController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = IncomingItem::with('item');

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

        $incomingItems = $query->latest('incoming_date')->paginate(10);
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
}
