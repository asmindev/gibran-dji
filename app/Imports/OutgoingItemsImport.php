<?php

namespace App\Imports;

use App\Models\Item;
use App\Models\OutgoingItem;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Validators\Failure;
use Illuminate\Validation\Rule;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class OutgoingItemsImport implements
    ToModel,
    WithHeadingRow,
    WithValidation,
    WithBatchInserts,
    WithChunkReading,
    WithMapping
{
    use Importable;

    private $stockValidationErrors = [];
    private $processedRows = 0;

    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        $this->processedRows++;

        // Find item by code
        $item = Item::where('item_code', $row['kode_barang'])->first();

        if (!$item) {
            // Item tidak ditemukan, akan di-handle oleh validation rules
            return null;
        }

        // Parse date dengan validasi
        $outgoingDate = $this->parseDate($row['tanggal_keluar'] ?? null);

        $outgoingItem = new OutgoingItem([
            'transaction_id' => $row['transaksi_id'] ?? null,
            'item_id' => $item->id,
            'quantity' => $row['jumlah'],
            'outgoing_date' => $outgoingDate,
            'notes' => $row['catatan'] ?? '',
        ]);

        return $outgoingItem;
    }

    public function rules(): array
    {
        return [
            'transaksi_id' => [
                'nullable',
                'string',
                'max:50'
            ],
            'kode_barang' => [
                'required',
                'string',
                'max:50',
                'exists:items,item_code'
            ],
            'tanggal_keluar' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    if (!$this->isValidDate($value)) {
                        $fail('Format tanggal tidak valid. Gunakan format DD/MM/YYYY atau YYYY-MM-DD.');
                    }
                }
            ],
            'jumlah' => [
                'required',
                'numeric',
                'min:1',
                'max:999999'
            ],
            'catatan' => 'nullable|string|max:1000',
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            'transaksi_id.max' => 'Transaksi ID maksimal 50 karakter.',
            'transaksi_id.unique' => 'Transaksi ID ":input" sudah digunakan.',
            'kode_barang.required' => 'Kode barang wajib diisi.',
            'kode_barang.exists' => 'Kode barang ":input" tidak ditemukan dalam database.',
            'kode_barang.max' => 'Kode barang maksimal 50 karakter.',
            'tanggal_keluar.required' => 'Tanggal keluar wajib diisi.',
            'jumlah.required' => 'Jumlah wajib diisi.',
            'jumlah.numeric' => 'Jumlah harus berupa angka.',
            'jumlah.min' => 'Jumlah minimal 1.',
            'jumlah.max' => 'Jumlah maksimal 999,999.',
            'catatan.max' => 'Catatan maksimal 1000 karakter.',
        ];
    }

    public function getStockValidationErrors()
    {
        return $this->stockValidationErrors;
    }

    public function batchSize(): int
    {
        return 100; // Process in batches of 100
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
            // Format DD/MM/YYYY
            if (strpos($dateString, '/') !== false) {
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
     * Map the row data to consistent keys
     */
    public function map($row): array
    {
        // Header mapping yang fleksibel
        $headerMapping = [
            'no' => 'no',
            'transaksi id' => 'transaksi_id',
            'transaction id' => 'transaksi_id',
            'kode barang' => 'kode_barang',
            'nama barang' => 'nama_barang',
            'kategori' => 'kategori',
            'tanggal keluar' => 'tanggal_keluar',
            'jumlah' => 'jumlah',
            'catatan' => 'catatan',
        ];

        $mappedData = [];

        foreach ($row as $key => $value) {
            $normalizedKey = strtolower(trim($key));

            // Cari mapping yang cocok
            $mapped = false;
            foreach ($headerMapping as $pattern => $mappedKey) {
                if (str_contains($normalizedKey, $pattern) || str_contains($pattern, $normalizedKey)) {
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
}
