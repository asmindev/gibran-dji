<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class IncomingItemsTemplateExport implements FromArray, WithHeadings
{
    public function array(): array
    {
        // Return sample data for template
        // ID Transaksi tidak perlu diisi, akan auto-generated oleh sistem
        return [
            [
                '1',
                '10/08/2025',
                'Barang A',
                'Kategori A',
                50,
                15000
            ],
            [
                '2',
                '11/08/2025',
                'Barang B',
                'Kategori B',
                25,
                8000
            ]
        ];
    }

    public function headings(): array
    {
        return [
            'NO',
            'TANGGAL TRANSAKSI',
            'NAMA BARANG',
            'KATEGORI',
            'JUMLAH',
            'HARGA SATUAN'
        ];
    }
}
