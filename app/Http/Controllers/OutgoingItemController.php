<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\OutgoingItem;
use App\Http\Requests\StoreOutgoingItemRequest;
use App\Exports\OutgoingItemsExport;
use App\Exports\OutgoingItemsTemplateExport;
use App\Imports\OutgoingItemsImport;
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
                $itemQuery->where('item_name', 'like', "%{$search}%");
            });
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
        return Excel::download(new OutgoingItemsTemplateExport(), $filename);
    }

    /**
     * Import outgoing items from Excel/CSV
     */    public function import(Request $request)
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
            $import = new OutgoingItemsImport();
            Excel::import($import, $request->file('file'));

            // Check for stock validation errors
            $stockErrors = $import->getStockValidationErrors();
            $hasStockErrors = !empty($stockErrors);

            if ($hasStockErrors) {
                $stockErrorDetails = [];
                foreach ($stockErrors as $error) {
                    $stockErrorDetails[] = [
                        'row' => $error['row'],
                        'error' => $error['message']
                    ];
                }

                if ($request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'errors' => ['stock' => $stockErrorDetails],
                        'error_count' => count($stockErrorDetails)
                    ]);
                }

                return redirect()->back()
                    ->withInput()
                    ->with('import_errors', ['stock' => $stockErrorDetails])
                    ->with('error_count', count($stockErrorDetails));
            }

            if ($request->ajax()) {
                // Set flash session for success message
                session()->flash('success', 'Data barang keluar berhasil diimport.');

                return response()->json([
                    'success' => true,
                    'redirect_url' => route('outgoing_items.index')
                ]);
            }

            return redirect()->route('outgoing_items.index')
                ->with('success', 'Data barang keluar berhasil diimport.');
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
                } elseif (strpos($detail['error'], 'stok') !== false) {
                    $groupedErrors['stock'][] = $detail;
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
     * Preview import data for validation
     */
    public function importPreview(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,csv|max:10240',
        ], [
            'file.required' => 'File import wajib dipilih.',
            'file.mimes' => 'File harus berformat Excel (.xlsx) atau CSV (.csv).',
            'file.max' => 'Ukuran file maksimal 10MB.',
        ]);

        try {
            $uploadedFile = $request->file('file');

            // Simpan file sementara untuk digunakan saat konfirmasi import
            $tempFileName = 'import_' . time() . '_' . uniqid() . '.' . $uploadedFile->getClientOriginalExtension();
            $tempFilePath = storage_path('app/temp/' . $tempFileName);

            // Pastikan direktori temp ada
            if (!file_exists(dirname($tempFilePath))) {
                mkdir(dirname($tempFilePath), 0755, true);
            }

            // Copy file ke temporary location
            $uploadedFile->move(dirname($tempFilePath), $tempFileName);

            // Simpan path di session
            session(['import_file_path' => $tempFilePath]);
            session(['import_file_name' => $uploadedFile->getClientOriginalName()]);

            // Read the file and validate without importing
            $data = Excel::toArray(new OutgoingItemsImport(), $tempFilePath);

            if (empty($data) || empty($data[0])) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'File kosong atau format tidak sesuai.');
            }

            $rows = $data[0]; // First sheet
            $preview = [];
            $validationErrors = [];
            $stockErrors = [];

            // Remove header row and process first 10 rows for preview
            array_shift($rows);
            $previewRows = array_slice($rows, 0, 10);

            foreach ($previewRows as $index => $row) {
                $rowNumber = $index + 2; // +2 because array is 0-indexed and we removed header

                // Map array indices to column names
                $mappedRow = [
                    'kode_barang' => $row[0] ?? '',
                    'tanggal_keluar' => $row[1] ?? '',
                    'jumlah' => $row[2] ?? '',
                    'tujuan' => $row[3] ?? '',
                    'deskripsi' => $row[4] ?? '',
                    'catatan' => $row[5] ?? '',
                ];

                // Basic validation
                $rowErrors = [];

                if (empty($mappedRow['kode_barang'])) {
                    $rowErrors[] = 'Kode barang wajib diisi';
                }

                if (empty($mappedRow['tanggal_keluar'])) {
                    $rowErrors[] = 'Tanggal keluar wajib diisi';
                }

                if (empty($mappedRow['jumlah']) || !is_numeric($mappedRow['jumlah']) || $mappedRow['jumlah'] <= 0) {
                    $rowErrors[] = 'Jumlah harus berupa angka positif';
                }

                // Check if item exists and stock availability
                if (!empty($mappedRow['nama_barang'])) {
                    $item = Item::where('item_name', $mappedRow['nama_barang'])->first();

                    if (!$item) {
                        $rowErrors[] = "Item dengan nama '{$mappedRow['nama_barang']}' tidak ditemukan";
                    } elseif (is_numeric($mappedRow['jumlah']) && $item->stock < $mappedRow['jumlah']) {
                        $stockErrors[] = "Stok tidak mencukupi untuk {$item->item_name}. Diminta: {$mappedRow['jumlah']}, Tersedia: {$item->stock}";
                    }
                }

                $preview[] = [
                    'row_number' => $rowNumber,
                    'data' => $mappedRow,
                    'errors' => $rowErrors,
                    'item' => isset($item) ? $item : null
                ];

                if (!empty($rowErrors)) {
                    $validationErrors[] = "Baris {$rowNumber}: " . implode(', ', $rowErrors);
                }
            }

            $totalRows = count($rows);
            $hasErrors = !empty($validationErrors) || !empty($stockErrors);
            $uploadedFileName = session('import_file_name', '');

            return view('outgoing_items.import-preview', compact(
                'preview',
                'validationErrors',
                'stockErrors',
                'totalRows',
                'hasErrors',
                'uploadedFileName'
            ));
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Gagal membaca file: ' . $e->getMessage());
        }
    }

    /**
     * Show import form
     */
    public function importForm()
    {
        return view('outgoing_items.import');
    }
}
