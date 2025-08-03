<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class OutgoingItemsTemplateExport implements FromArray, WithHeadings
{
    public function array(): array
    {
        // Return sample data for template
        return [
            [
                '1',
                'TRX20250803001', // ID dapat dikosongkan (auto-generate) atau diisi manual
                'BRG001',
                'Barang A',
                'Kategori A',
                '03/08/2025',
                2,
                'Kondisi baik'
            ]
        ];
    }

    public function headings(): array
    {
        return [
            'No',
            'Transaksi ID',
            'Kode Barang',
            'Nama Barang',
            'Kategori',
            'Tanggal Keluar',
            'Jumlah',
            'Catatan'
        ];
    }
}
