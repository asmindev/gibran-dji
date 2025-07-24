<?php

namespace App\Exports;

use App\Models\OutgoingItem;
use Illuminate\Http\Request;

class OutgoingItemsExport
{
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function collection()
    {
        $query = OutgoingItem::with(['item', 'item.category']);

        // Apply same filters as index method
        if ($this->request->filled('search')) {
            $search = $this->request->get('search');
            $query->whereHas('item', function ($itemQuery) use ($search) {
                $itemQuery->where('item_name', 'like', "%{$search}%")
                    ->orWhere('item_code', 'like', "%{$search}%");
            });
        }

        if ($this->request->filled('start_date')) {
            $query->where('outgoing_date', '>=', $this->request->start_date);
        }

        if ($this->request->filled('end_date')) {
            $query->where('outgoing_date', '<=', $this->request->end_date);
        }

        if ($this->request->filled('item_id')) {
            $query->where('item_id', $this->request->item_id);
        }

        $query->orderBy('outgoing_date', 'desc');

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'No',
            'Kode Barang',
            'Nama Barang',
            'Kategori',
            'Tanggal Keluar',
            'Jumlah',
            'Satuan',
            'Tujuan',
            'Deskripsi',
            'Catatan'
        ];
    }

    public function map($outgoingItem): array
    {
        static $counter = 1;

        return [
            $counter++,
            $outgoingItem->item->item_code ?? '',
            $outgoingItem->item->item_name ?? '',
            $outgoingItem->item->category->category_name ?? '',
            $outgoingItem->outgoing_date ? $outgoingItem->outgoing_date->format('d/m/Y') : '',
            $outgoingItem->quantity,
            $outgoingItem->item->unit ?? '',
            $outgoingItem->destination ?? '',
            $outgoingItem->description ?? '',
            $outgoingItem->notes ?? ''
        ];
    }

    public function toCsv()
    {
        $data = $this->collection();

        $filename = 'barang_keluar_' . date('Y-m-d_H-i-s') . '.csv';

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
