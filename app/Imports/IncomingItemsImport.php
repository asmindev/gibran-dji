<?php

namespace App\Imports;

use App\Models\Item;
use App\Models\IncomingItem;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Validators\Failure;
use Illuminate\Validation\Rule;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class IncomingItemsImport implements
    ToModel,
    WithHeadingRow,
    WithValidation,
    WithChunkReading,
    WithMapping
{
    use Importable;

    private $validationErrors = [];
    private $processedRows = 0;

    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        $this->processedRows++;

        // Find item by name
        $item = Item::where('item_name', $row['nama_barang'])->first();

        if (!$item) {
            // Item tidak ditemukan, akan di-handle oleh validation rules
            return null;
        }

        // Parse date dengan validasi
        $incomingDate = $this->parseDate($row['tanggal_transaksi'] ?? null);

        // Buat incoming item
        $incomingItem = IncomingItem::create([
            'transaction_id' => $row['id_transaksi'],
            'item_id' => $item->id,
            'quantity' => $row['jumlah'],
            'unit_cost' => $row['harga_satuan'] ?? 0,
            'incoming_date' => $incomingDate,
            'notes' => 'Import dari file Excel - ID Transaksi: ' . ($row['id_transaksi'] ?? 'N/A'),
        ]);

        // Manual increment stok untuk memastikan (karena observer mungkin tidak dipanggil dalam import)
        $item->refresh(); // Refresh item data
        $item->increment('stock', $row['jumlah']);

        return $incomingItem;
    }

    public function rules(): array
    {
        return [
            'id_transaksi' => [
                'required',
                'string',
                'max:255',
                'unique:incoming_items,transaction_id'
            ],
            'nama_barang' => [
                'required',
                'string',
                'max:255',
                'exists:items,item_name'
            ],
            'tanggal_transaksi' => [
                'required'
            ],
            'jumlah' => [
                'required',
                'numeric',
                'min:1',
                'max:999999'
            ],
            'harga_satuan' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999999999'
            ],
            'kategori' => 'nullable|string|max:255',
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            'id_transaksi.required' => 'ID Transaksi wajib diisi.',
            'id_transaksi.unique' => 'ID Transaksi ":input" sudah ada dalam database.',
            'id_transaksi.max' => 'ID Transaksi maksimal 255 karakter.',
            'nama_barang.required' => 'Nama barang wajib diisi.',
            'nama_barang.exists' => 'Nama barang ":input" tidak ditemukan dalam database.',
            'nama_barang.max' => 'Nama barang maksimal 255 karakter.',
            'tanggal_transaksi.required' => 'Tanggal transaksi wajib diisi.',
            'jumlah.required' => 'Jumlah wajib diisi.',
            'jumlah.numeric' => 'Jumlah harus berupa angka.',
            'jumlah.min' => 'Jumlah minimal 1.',
            'jumlah.max' => 'Jumlah maksimal 999,999.',
            'harga_satuan.numeric' => 'Harga satuan harus berupa angka.',
            'harga_satuan.min' => 'Harga satuan minimal 0.',
            'harga_satuan.max' => 'Harga satuan maksimal 999,999,999.',
            'kategori.max' => 'Kategori maksimal 255 karakter.',
        ];
    }

    public function getValidationErrors()
    {
        return $this->validationErrors;
    }

    public function chunkSize(): int
    {
        return 100; // Read in chunks of 100
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
