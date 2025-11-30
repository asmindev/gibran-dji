<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class OutgoingItemsTemplateExport implements FromArray, WithHeadings
{
    public function array(): array
    {
        // Return sample data for template
        // ID Transaksi tidak perlu diisi, akan auto-generated oleh sistem
        return [
            [
                '1',
                '03/08/2025',
                'Barang A',
                'Kategori A',
                2
            ],
            [
                '2',
                '04/08/2025',
                'Barang B',
                'Kategori B',
                1
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
            'JUMLAH'
        ];
    }
}
