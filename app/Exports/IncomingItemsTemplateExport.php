<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class IncomingItemsTemplateExport implements FromArray, WithHeadings
{
    public function array(): array
    {
        // Return sample data for template
        return [
            [
                '1',
                'TRX20250810001',
                '10/08/2025',
                'Barang A',
                'Kategori A',
                50,
                15000
            ]
        ];
    }

    public function headings(): array
    {
        return [
            'NO',
            'ID TRANSAKSI',
            'TANGGAL TRANSAKSI',
            'NAMA BARANG',
            'KATEGORI',
            'JUMLAH',
            'HARGA SATUAN'
        ];
    }
}
