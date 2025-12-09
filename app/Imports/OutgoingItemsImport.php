<?php

namespace App\Imports;

use App\Models\Item;
use App\Models\OutgoingItem;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Support\Collection;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OutgoingItemsImport implements
    ToCollection,
    WithHeadingRow,
    WithChunkReading,
    WithMapping
{
    use Importable;

    private $validationErrors = [];
    private $stockValidationErrors = [];
    private $processedRows = 0;
    private $savedRows = 0;
    private $skippedRows = 0;

    public function __construct()
    {
        // Reset counters for each new import
        $this->validationErrors = [];
        $this->stockValidationErrors = [];
        $this->processedRows = 0;
        $this->savedRows = 0;
        $this->skippedRows = 0;
    }

    /**
     * @param Collection $collection
     */
    public function collection(Collection $collection)
    {
        $originalCount = $collection->count();
        Log::info('Starting OutgoingItems import', [
            'total_rows_in_file' => $originalCount,
            'memory_start' => memory_get_usage(true) / 1024 / 1024 . ' MB'
        ]);

        DB::beginTransaction();

        try {
            foreach ($collection as $index => $row) {
                // Calculate actual row number in Excel/CSV file (+2 for header and 0-based index)
                $actualRowNumber = $index + 2;
                $this->processedRows++;

                Log::debug('Processing row', [
                    'actual_row_number' => $actualRowNumber,
                    'collection_index' => $index,
                    'row_data' => $row,
                    'processed_so_far' => $this->processedRows
                ]);

                // Skip empty rows
                if (empty($row['nama_barang'])) {
                    $this->skippedRows++;
                    Log::warning('Skipping empty row - nama_barang is empty', [
                        'actual_row_number' => $actualRowNumber,
                        'row_data' => $row
                    ]);
                    continue;
                }

                // Validate required fields (id_transaksi optional; will use provided or auto-generate)
                if (empty($row['nama_barang']) || empty($row['jumlah'])) {
                    $this->validationErrors[] = [
                        'row' => $actualRowNumber,
                        'message' => 'Data tidak lengkap pada baris ' . $actualRowNumber . ': ' .
                            (empty($row['nama_barang']) ? 'nama_barang kosong ' : '') .
                            (empty($row['jumlah']) ? 'jumlah kosong' : '')
                    ];
                    $this->skippedRows++;
                    Log::warning('Validation error: incomplete data', [
                        'actual_row_number' => $actualRowNumber,
                        'missing_fields' => [
                            'nama_barang' => empty($row['nama_barang']),
                            'jumlah' => empty($row['jumlah'])
                        ],
                        'data' => $row
                    ]);
                    continue;
                }

                // Validate jumlah is numeric (not a formula like =RANDBETWEEN)
                if (!is_numeric($row['jumlah']) || strpos($row['jumlah'], '=') === 0) {
                    $this->validationErrors[] = [
                        'row' => $actualRowNumber,
                        'message' => "Jumlah pada baris {$actualRowNumber} harus berupa angka, bukan formula Excel. Nilai: '{$row['jumlah']}'"
                    ];
                    $this->skippedRows++;
                    Log::warning('Validation error: jumlah contains formula', [
                        'actual_row_number' => $actualRowNumber,
                        'jumlah_value' => $row['jumlah']
                    ]);
                    continue;
                }

                // Find item by name
                $item = Item::where('item_name', $row['nama_barang'])->first();

                if (!$item) {
                    $this->validationErrors[] = [
                        'row' => $actualRowNumber,
                        'message' => "Barang '{$row['nama_barang']}' tidak ditemukan"
                    ];
                    $this->skippedRows++;
                    Log::warning('Item not found', [
                        'actual_row_number' => $actualRowNumber,
                        'item_name' => $row['nama_barang']
                    ]);
                    continue;
                }

                // Check stock availability
                $requestedQuantity = (int)$row['jumlah'];
                // if ($item->stock < $requestedQuantity) {
                //     $this->stockValidationErrors[] = [
                //         'row' => $actualRowNumber,
                //         'item' => $row['nama_barang'],
                //         'requested' => $requestedQuantity,
                //         'available' => $item->stock,
                //         'message' => "Stok {$row['nama_barang']} tidak mencukupi. Diminta: {$requestedQuantity}, Tersedia: {$item->stock}"
                //     ];
                //     continue;
                // }

                // Parse date
                $outgoingDate = $this->parseDate($row['tanggal_transaksi'] ?? null);

                // Use provided transaction_id if supplied; otherwise generate
                $providedTransactionId = $row['id_transaksi'] ?? $row['transaction_id'] ?? null;
                $providedTransactionId = is_string($providedTransactionId) ? trim($providedTransactionId) : $providedTransactionId;

                $transactionId = $providedTransactionId ?: OutgoingItem::generateTransactionId($outgoingDate);

                // Create outgoing item
                OutgoingItem::create([
                    'transaction_id' => $transactionId,
                    'item_id' => $item->id,
                    'quantity' => $requestedQuantity,
                    'unit_cost' => $row['harga_satuan'] ?? 0,
                    'outgoing_date' => $outgoingDate->format('Y-m-d'), // Save as YYYY-MM-DD for database
                    'notes' => 'Import dari file Excel - ' . $outgoingDate->format('d/m/Y'),
                ]);

                $this->savedRows++;
                Log::debug('Successfully saved outgoing item', [
                    'actual_row_number' => $actualRowNumber,
                    'transaction_id' => $transactionId,
                    'item_name' => $row['nama_barang'],
                    'quantity' => $requestedQuantity
                ]);

                // Update stock
                $item->decrement('stock', $requestedQuantity);

                // Clear memory periodically
                if ($this->processedRows % 25 === 0) {
                    gc_collect_cycles();
                }
            }

            DB::commit();

            Log::info('OutgoingItems import completed successfully', [
                'file_summary' => [
                    'total_rows_in_file' => $originalCount,
                    'rows_after_filtering_empty' => $originalCount - $this->skippedRows,
                ],
                'processing_summary' => [
                    'processed_rows' => $this->processedRows,
                    'saved_rows' => $this->savedRows,
                    'skipped_rows' => $this->skippedRows,
                ],
                'error_summary' => [
                    'validation_errors' => count($this->validationErrors),
                    'stock_errors' => count($this->stockValidationErrors),
                    'total_errors' => count($this->validationErrors) + count($this->stockValidationErrors)
                ],
                'calculation' => [
                    'expected_saved' => $originalCount - count($this->validationErrors) - count($this->stockValidationErrors),
                    'actual_saved' => $this->savedRows,
                    'difference' => ($originalCount - count($this->validationErrors) - count($this->stockValidationErrors)) - $this->savedRows
                ],
                'memory_end' => memory_get_usage(true) / 1024 / 1024 . ' MB'
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('OutgoingItems import failed', [
                'error' => $e->getMessage(),
                'processed_rows' => $this->processedRows,
                'saved_rows' => $this->savedRows,
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);
            throw $e;
        }
    }

    public function getValidationErrors()
    {
        return $this->validationErrors;
    }

    public function getStockValidationErrors()
    {
        return $this->stockValidationErrors;
    }

    public function getImportStatistics()
    {
        return [
            'processed_rows' => $this->processedRows,
            'saved_rows' => $this->savedRows,
            'skipped_rows' => $this->skippedRows,
            'validation_errors' => count($this->validationErrors),
            'stock_errors' => count($this->stockValidationErrors)
        ];
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

            // Format Indonesia: "3 maret 2025", "15 januari 2024", etc.
            if ($this->isIndonesianDateFormat($dateString)) {
                return $this->parseIndonesianDate($dateString);
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
     * Check if date string is in Indonesian format
     */
    private function isIndonesianDateFormat($dateString): bool
    {
        $dateString = strtolower(trim($dateString));
        $months = [
            // Indonesian months
            'januari',
            'februari',
            'maret',
            'april',
            'mei',
            'juni',
            'juli',
            'agustus',
            'september',
            'oktober',
            'november',
            'desember',
            // English months
            'january',
            'february',
            'march',
            'april',
            'may',
            'june',
            'july',
            'august',
            'september',
            'october',
            'november',
            'december',
            // Short English months
            'jan',
            'feb',
            'mar',
            'apr',
            'may',
            'jun',
            'jul',
            'aug',
            'sep',
            'oct',
            'nov',
            'dec'
        ];

        foreach ($months as $month) {
            if (strpos($dateString, $month) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Parse Indonesian date format like "3 maret 2025"
     */
    private function parseIndonesianDate($dateString): Carbon
    {
        $dateString = strtolower(trim($dateString));

        // Mapping bulan Indonesia dan Inggris ke nomor bulan
        $monthMapping = [
            // Indonesian months
            'januari' => 1,
            'februari' => 2,
            'maret' => 3,
            'mei' => 5,
            'juni' => 6,
            'juli' => 7,
            'agustus' => 8,
            'oktober' => 10,
            'november' => 11,
            'desember' => 12,
            // English months
            'january' => 1,
            'february' => 2,
            'march' => 3,
            'april' => 4,
            'may' => 5,
            'june' => 6,
            'july' => 7,
            'august' => 8,
            'september' => 9,
            'october' => 10,
            'december' => 12,
            // Short English months
            'jan' => 1,
            'feb' => 2,
            'mar' => 3,
            'apr' => 4,
            'jun' => 6,
            'jul' => 7,
            'aug' => 8,
            'sep' => 9,
            'oct' => 10,
            'nov' => 11,
            'dec' => 12
        ];

        $day = null;
        $month = null;
        $year = null;

        // Pattern 1: "3 maret 2025" atau "1 july 2025"
        if (preg_match('/(\d{1,2})\s+(\w+)\s+(\d{4})/', $dateString, $matches)) {
            $day = (int)$matches[1];
            $monthName = $matches[2];
            $year = (int)$matches[3];

            // Cari nomor bulan
            foreach ($monthMapping as $monthKey => $monthNumber) {
                if (strpos($monthName, $monthKey) !== false || $monthName === $monthKey) {
                    $month = $monthNumber;
                    break;
                }
            }
        }
        // Pattern 2: "3-maret-2025" atau "1-july-2025"
        elseif (preg_match('/(\d{1,2})-(\w+)-(\d{4})/', $dateString, $matches)) {
            $day = (int)$matches[1];
            $monthName = $matches[2];
            $year = (int)$matches[3];

            // Cari nomor bulan
            foreach ($monthMapping as $monthKey => $monthNumber) {
                if (strpos($monthName, $monthKey) !== false || $monthName === $monthKey) {
                    $month = $monthNumber;
                    break;
                }
            }
        }
        // Pattern 3: "maret 3, 2025" atau "july 1, 2025"
        elseif (preg_match('/(\w+)\s+(\d{1,2}),?\s+(\d{4})/', $dateString, $matches)) {
            $monthName = $matches[1];
            $day = (int)$matches[2];
            $year = (int)$matches[3];

            // Cari nomor bulan
            foreach ($monthMapping as $monthKey => $monthNumber) {
                if (strpos($monthName, $monthKey) !== false || $monthName === $monthKey) {
                    $month = $monthNumber;
                    break;
                }
            }
        }

        // Validasi dan buat Carbon instance
        if ($month && $day >= 1 && $day <= 31 && $year >= 1900 && $year <= 2100) {
            try {
                return Carbon::create($year, $month, $day);
            } catch (\Exception $e) {
                return Carbon::today();
            }
        }

        // Fallback jika parsing gagal
        return Carbon::today();
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
        // Header mapping yang fleksibel untuk format baru (id_transaksi optional)
        $headerMapping = [
            'no' => 'no',
            'id transaksi' => 'id_transaksi',
            'transaction id' => 'id_transaksi',
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
