<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\IncomingItem;
use App\Http\Requests\StoreIncomingItemRequest;
use App\Http\Requests\UpdateIncomingItemRequest;
use App\Exports\IncomingItemsExport;
use App\Exports\IncomingItemsTemplateExport;
use App\Imports\IncomingItemsImport;
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
                        $itemQuery->where('item_name', 'like', "%{$search}%");
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
        // add item stock increment logic here
        $item = Item::find($request->item_id);
        if ($item) {
            $data = $request->validated();

            // Auto-generate transaction_id jika tidak ada
            if (empty($data['transaction_id'])) {
                $data['transaction_id'] = IncomingItem::generateTransactionId($data['incoming_date'] ?? null);
            }

            IncomingItem::create($data);
            $item->increment('stock', $request->quantity);
            return redirect()->route('incoming_items.index')
                ->with('success', 'Incoming item recorded successfully.');
        } else {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Item not found. Stock increment failed.');
        }
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
    public function update(UpdateIncomingItemRequest $request, IncomingItem $incomingItem)
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
        return Excel::download(new IncomingItemsTemplateExport(), $filename);
    }

    /**
     * Import incoming items from Excel/CSV
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,csv|max:10240', // Max 10MB
        ], [
            'file.required' => 'File import wajib dipilih.',
            'file.mimes' => 'File harus berformat Excel (.xlsx) atau CSV (.csv).',
            'file.max' => 'Ukuran file maksimal 10MB.',
        ]);

        // Increase memory limit and execution time for import
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', 300); // 5 minutes

        try {
            $import = new IncomingItemsImport();
            Excel::import($import, $request->file('file'));

            // Check for validation errors
            $validationErrors = $import->getValidationErrors();
            $hasValidationErrors = !empty($validationErrors);

            if ($hasValidationErrors) {
                $errorDetails = [];
                foreach ($validationErrors as $error) {
                    $errorDetails[] = [
                        'row' => $error['row'],
                        'error' => $error['message']
                    ];
                }

                if ($request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'errors' => ['validation' => $errorDetails],
                        'error_count' => count($errorDetails)
                    ]);
                }

                return redirect()->back()
                    ->withInput()
                    ->with('import_errors', ['validation' => $errorDetails])
                    ->with('error_count', count($errorDetails));
            }

            if ($request->ajax()) {
                // Set flash session for success message
                session()->flash('success', 'Data barang masuk berhasil diimport.');

                return response()->json([
                    'success' => true,
                    'redirect_url' => route('incoming_items.index')
                ]);
            }

            return redirect()->route('incoming_items.index')
                ->with('success', 'Data barang masuk berhasil diimport.');
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $failures = $e->failures();
            $errorDetails = [];

            foreach ($failures as $failure) {
                $row = $failure->row();
                $errors = $failure->errors();

                foreach ($errors as $error) {
                    $errorDetails[] = [
                        'row' => $row,
                        'error' => $error
                    ];
                }
            }

            // Group errors by type for better display
            $groupedErrors = [];
            foreach ($errorDetails as $detail) {
                if (strpos($detail['error'], 'tidak ditemukan') !== false) {
                    $groupedErrors['not_found'][] = $detail;
                } else {
                    $groupedErrors['validation'][] = $detail;
                }
            }

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'errors' => $groupedErrors,
                    'error_count' => count($errorDetails)
                ]);
            }

            return redirect()->back()
                ->withInput()
                ->with('import_errors', $groupedErrors)
                ->with('error_count', count($errorDetails));
        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'errors' => ['general' => 'Import gagal: ' . $e->getMessage()],
                    'error_count' => 1
                ]);
            }

            return redirect()->back()
                ->withInput()
                ->with('error', 'Import gagal: ' . $e->getMessage());
        }
    }

    /**
     * Show import form
     */
    public function importForm()
    {
        return view('incoming_items.import');
    }
}
