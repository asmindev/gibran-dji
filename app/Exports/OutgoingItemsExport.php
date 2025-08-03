<?php

namespace App\Exports;

use App\Models\OutgoingItem;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class OutgoingItemsExport implements FromCollection, WithHeadings, WithMapping
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

        return $query->orderBy('outgoing_date', 'desc')->get();
    }

    public function headings(): array
    {
        return [
            'No',
            'Transaction ID',
            'Kode Barang',
            'Nama Barang',
            'Kategori',
            'Tanggal Keluar',
            'Jumlah',
            'Catatan'
        ];
    }

    public function map($outgoingItem): array
    {
        return [
            $outgoingItem->id,
            $outgoingItem->transaction_id ?? '',
            $outgoingItem->item->item_code ?? '',
            $outgoingItem->item->item_name ?? '',
            $outgoingItem->item->category->name ?? '',
            $outgoingItem->outgoing_date ? $outgoingItem->outgoing_date->format('d/m/Y') : '',
            $outgoingItem->quantity,
            $outgoingItem->notes ?? ''
        ];
    }
}
