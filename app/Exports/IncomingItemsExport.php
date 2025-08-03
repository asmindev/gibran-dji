<?php

namespace App\Exports;

use App\Models\IncomingItem;
use Illuminate\Http\Request;

class IncomingItemsExport
{
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function collection()
    {
        $query = IncomingItem::with(['item', 'item.category']);

        // Apply same filters as index method
        if ($this->request->filled('search')) {
            $search = $this->request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('supplier', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhereHas('item', function ($itemQuery) use ($search) {
                        $itemQuery->where('item_name', 'like', "%{$search}%")
                            ->orWhere('item_code', 'like', "%{$search}%");
                    });
            });
        }

        if ($this->request->filled('start_date')) {
            $query->where('incoming_date', '>=', $this->request->start_date);
        }

        if ($this->request->filled('end_date')) {
            $query->where('incoming_date', '<=', $this->request->end_date);
        }

        if ($this->request->filled('item_id')) {
            $query->where('item_id', $this->request->item_id);
        }

        if ($this->request->filled('supplier')) {
            $query->where('supplier', 'like', "%{$this->request->supplier}%");
        }

        if ($this->request->filled('min_quantity')) {
            $query->where('quantity', '>=', $this->request->get('min_quantity'));
        }
        if ($this->request->filled('max_quantity')) {
            $query->where('quantity', '<=', $this->request->get('max_quantity'));
        }

        if ($this->request->filled('min_cost')) {
            $query->where('unit_cost', '>=', $this->request->get('min_cost'));
        }
        if ($this->request->filled('max_cost')) {
            $query->where('unit_cost', '<=', $this->request->get('max_cost'));
        }

        $sortBy = $this->request->get('sort_by', 'incoming_date');
        $sortOrder = $this->request->get('sort_order', 'desc');

        $allowedSorts = ['incoming_date', 'quantity', 'unit_cost', 'supplier', 'created_at'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'incoming_date';
        }

        if (!in_array($sortOrder, ['asc', 'desc'])) {
            $sortOrder = 'desc';
        }

        $query->orderBy($sortBy, $sortOrder);

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'No',
            'Kode Barang',
            'Nama Barang',
            'Kategori',
            'Tanggal Masuk',
            'Jumlah',
            'Satuan',
            'Harga Satuan',
            'Total Harga',
            'Supplier',
            'Deskripsi',
            'Catatan'
        ];
    }

    public function map($incomingItem): array
    {
        static $counter = 1;

        return [
            $counter++,
            $incomingItem->item->item_code ?? '',
            $incomingItem->item->item_name ?? '',
            $incomingItem->item->category->name ?? '',
            $incomingItem->incoming_date ? $incomingItem->incoming_date->format('d/m/Y') : '',
            $incomingItem->quantity,
            number_format($incomingItem->unit_cost, 0, ',', '.'),
            number_format($incomingItem->quantity * $incomingItem->unit_cost, 0, ',', '.'),
            $incomingItem->notes ?? ''
        ];
    }

    public function toCsv()
    {
        $data = $this->collection();

        $filename = 'barang_masuk_' . date('Y-m-d_H-i-s') . '.csv';

        $handle = fopen('php://output', 'w');

        // Add BOM for proper UTF-8 encoding in Excel
        fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // Add headings
        fputcsv($handle, $this->headings(), ';');

        // Add data
        foreach ($data as $item) {
            fputcsv($handle, $this->map($item), ';');
        }

        fclose($handle);

        return $filename;
    }

    public function toExcel()
    {
        // For now, we'll return CSV format
        // This can be extended to use PhpSpreadsheet later
        return $this->toCsv();
    }
}
