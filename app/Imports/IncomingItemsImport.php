<?php

namespace App\Imports;

use App\Models\Item;
use App\Models\IncomingItem;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Support\Collection;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class IncomingItemsImport implements
    ToCollection,
    WithHeadingRow,
    WithChunkReading,
    WithMapping
{
    use Importable;

    private $validationErrors = [];
    private $processedRows = 0;

    /**
     * @param Collection $collection
     */
    public function collection(Collection $collection)
    {
        DB::beginTransaction();

        try {
            foreach ($collection as $index => $row) {
                $this->processedRows++;

                // Skip empty rows
                if (empty($row['nama_barang'])) {
                    continue;
                }

                // Validate required fields
                if (empty($row['id_transaksi']) || empty($row['nama_barang']) || empty($row['jumlah'])) {
                    $this->validationErrors[] = [
                        'row' => $index + 2, // +2 because of header and 0-based index
                        'message' => 'Data tidak lengkap pada baris ' . ($index + 2)
                    ];
                    continue;
                }

                // Find item by name
                $item = Item::where('item_name', $row['nama_barang'])->first();

                if (!$item) {
                    $this->validationErrors[] = [
                        'row' => $index + 2,
                        'message' => "Barang '{$row['nama_barang']}' tidak ditemukan"
                    ];
                    continue;
                }

                // Parse date
                $incomingDate = $this->parseDate($row['tanggal_transaksi'] ?? null);

                // Create incoming item
                IncomingItem::create([
                    'transaction_id' => $row['id_transaksi'],
                    'item_id' => $item->id,
                    'quantity' => $row['jumlah'],
                    'unit_cost' => $row['harga_satuan'] ?? 0,
                    'incoming_date' => $incomingDate,
                    'notes' => 'Import dari file Excel - ID Transaksi: ' . ($row['id_transaksi'] ?? 'N/A'),
                ]);

                // Update stock
                $item->increment('stock', $row['jumlah']);

                // Clear memory periodically
                if ($this->processedRows % 25 === 0) {
                    gc_collect_cycles();
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function getValidationErrors()
    {
        return $this->validationErrors;
    }

    public function chunkSize(): int
    {
        return 25; // Smaller chunks for better memory management
    }

    /**
     * Validasi format tanggal
     */
    private function isValidDate($date): bool
    {
        if (empty($date)) {
            return false;
        }

        // Handle Excel serial number first
        if (is_numeric($date) && $date > 1 && $date < 73415) {
            return true; // Excel serial numbers are valid
        }

        // Coba format DD/MM/YYYY
        if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $date)) {
            try {
                Carbon::createFromFormat('d/m/Y', $date);
                return true;
            } catch (\Exception $e) {
                // Continue to next format
            }
        }

        // Coba format YYYY-MM-DD
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            try {
                Carbon::createFromFormat('Y-m-d', $date);
                return true;
            } catch (\Exception $e) {
                // Continue to next format
            }
        }

        // Coba format DD/M/YYYY atau D/MM/YYYY (single digit day/month)
        if (preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$/', $date)) {
            try {
                $dateParts = explode('/', $date);
                if (count($dateParts) === 3) {
                    $day = (int)$dateParts[0];
                    $month = (int)$dateParts[1];
                    $year = (int)$dateParts[2];

                    // Validate ranges
                    if ($day >= 1 && $day <= 31 && $month >= 1 && $month <= 12 && $year >= 1900 && $year <= 2100) {
                        Carbon::create($year, $month, $day);
                        return true;
                    }
                }
            } catch (\Exception $e) {
                // Continue to next format
            }
        }

        return false;
    }

    /**
     * Parse tanggal dengan handling error
     */
    private function parseDate($dateString): Carbon
    {
        if (empty($dateString)) {
            return Carbon::today();
        }

        try {
            // Handle Excel serial number first (if somehow it still gets here)
            if (is_numeric($dateString) && $dateString > 1 && $dateString < 73415) {
                return $this->parseExcelSerial($dateString);
            }

            // Format DD/MM/YYYY or D/M/YYYY
            if (strpos($dateString, '/') !== false) {
                $dateParts = explode('/', $dateString);
                if (count($dateParts) === 3) {
                    $day = (int)$dateParts[0];
                    $month = (int)$dateParts[1];
                    $year = (int)$dateParts[2];
                    return Carbon::create($year, $month, $day);
                }
                // Fallback to createFromFormat
                return Carbon::createFromFormat('d/m/Y', $dateString);
            }

            // Format YYYY-MM-DD
            if (strpos($dateString, '-') !== false) {
                return Carbon::createFromFormat('Y-m-d', $dateString);
            }

            // Fallback parsing
            return Carbon::parse($dateString);
        } catch (\Exception $e) {
            return Carbon::today();
        }
    }

    /**
     * Parse Excel serial number to Carbon date
     */
    private function parseExcelSerial($serial): Carbon
    {
        try {
            $excelEpoch = Carbon::create(1900, 1, 1);
            $days = (int)$serial - 1;
            $adjustedDays = $days > 59 ? $days - 1 : $days;
            return $excelEpoch->addDays($adjustedDays);
        } catch (\Exception $e) {
            return Carbon::today();
        }
    }

    /**
     * Map the row data to consistent keys
     */
    public function map($row): array
    {
        // Header mapping yang fleksibel untuk format baru
        $headerMapping = [
            'no' => 'no',
            'id transaksi' => 'id_transaksi',
            'tanggal transaksi' => 'tanggal_transaksi',
            'nama barang' => 'nama_barang',
            'kategori' => 'kategori',
            'jumlah' => 'jumlah',
            'harga satuan' => 'harga_satuan',
        ];

        $mappedData = [];

        foreach ($row as $key => $value) {
            $normalizedKey = strtolower(trim($key));

            // Cari mapping yang cocok
            $mapped = false;
            foreach ($headerMapping as $pattern => $mappedKey) {
                if (str_contains($normalizedKey, $pattern) || str_contains($pattern, $normalizedKey)) {
                    // Special handling for date fields - convert Excel serial number
                    if ($mappedKey === 'tanggal_transaksi' && is_numeric($value) && $value > 1 && $value < 73415) {
                        $value = $this->convertExcelSerialToDate($value);
                    }
                    $mappedData[$mappedKey] = $value;
                    $mapped = true;
                    break;
                }
            }

            // Jika tidak ditemukan mapping, gunakan key original (lowercased)
            if (!$mapped) {
                $mappedData[str_replace(' ', '_', $normalizedKey)] = $value;
            }
        }

        return $mappedData;
    }

    /**
     * Convert Excel serial number to date format
     */
    private function convertExcelSerialToDate($serial): string
    {
        try {
            // Excel serial date starts from January 1, 1900
            // But Excel incorrectly considers 1900 as a leap year, so we adjust
            $excelEpoch = Carbon::create(1900, 1, 1);
            $days = (int)$serial - 1; // Subtract 1 because Excel starts counting from 1

            // Adjust for Excel's leap year bug (day 60 = Feb 29, 1900 which doesn't exist)
            $adjustedDays = $days > 59 ? $days - 1 : $days;

            $resultDate = $excelEpoch->addDays($adjustedDays);

            // Return in DD/MM/YYYY format
            return $resultDate->format('d/m/Y');
        } catch (\Exception $e) {
            // If conversion fails, return the original value
            return (string)$serial;
        }
    }
}
