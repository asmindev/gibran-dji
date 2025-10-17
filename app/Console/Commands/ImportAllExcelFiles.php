<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Item;
use App\Models\IncomingItem;
use App\Models\OutgoingItem;
use App\Models\Category;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ImportAllExcelFiles extends Command
{
    protected $signature = 'import:all-excel {--path= : Path to Excel files directory} {--force : Force import even if data exists}';
    protected $description = 'Import all Excel files from directory to database';

    private $stats = [
        'files_processed' => 0,
        'items_created' => 0,
        'incoming_created' => 0,
        'outgoing_created' => 0,
        'errors' => 0
    ];

    public function handle()
    {
        $path = $this->option('path') ?: '/home/labubu/Downloads/gibran';

        if (!is_dir($path)) {
            $this->error("Directory not found: {$path}");
            return 1;
        }

        $this->info("🔍 Scanning Excel files in: {$path}");

        $files = $this->getAllExcelFiles($path);
        $this->info("📄 Found {$files->count()} Excel files to process");

        if ($files->isEmpty()) {
            $this->warn("No Excel files found!");
            return 0;
        }

        if (!$this->option('force') && $this->hasExistingData()) {
            $this->warn("⚠️  Database already contains data. Use --force to overwrite.");
            if (!$this->confirm('Continue with import?')) {
                return 0;
            }
        }

        $this->newLine();
        $this->info("🚀 Starting mass import...");

        $progressBar = $this->output->createProgressBar($files->count());
        $progressBar->setFormat('verbose');

        foreach ($files as $file) {
            $this->processFile($file);
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->showResults();

        return 0;
    }

    private function hasExistingData(): bool
    {
        return Item::count() > 0 || IncomingItem::count() > 0 || OutgoingItem::count() > 0;
    }

    private function getAllExcelFiles($directory): Collection
    {
        $files = collect();

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && in_array(strtolower($file->getExtension()), ['xlsx', 'xls'])) {
                $files->push($file->getPathname());
            }
        }

        return $files->sort();
    }

    private function processFile($filePath)
    {
        try {
            $this->stats['files_processed']++;

            $fileName = basename($filePath);
            $isIncoming = str_contains(strtolower($fileName), 'masuk');
            $isOutgoing = str_contains(strtolower($fileName), 'keluar');

            if (!$isIncoming && !$isOutgoing) {
                $this->warn("Skipping file (not recognized as incoming/outgoing): {$fileName}");
                return;
            }

            $this->info("Processing: {$fileName}");

            $data = Excel::toArray(new class {}, $filePath)[0] ?? [];

            if (empty($data)) {
                $this->warn("Empty file: {$fileName}");
                return;
            }

            // Skip header row and filter empty rows
            $dataRows = array_slice($data, 1);
            $validRows = [];

            foreach ($dataRows as $row) {
                $hasData = false;
                foreach ($row as $cell) {
                    if (!is_null($cell) && trim((string)$cell) !== '') {
                        $hasData = true;
                        break;
                    }
                }
                if ($hasData) {
                    $validRows[] = $row;
                }
            }

            $this->info("  → Found " . count($validRows) . " valid rows");

            if ($isIncoming) {
                $this->importIncomingItems($validRows, $fileName);
            } elseif ($isOutgoing) {
                $this->importOutgoingItems($validRows, $fileName);
            }
        } catch (\Exception $e) {
            $this->stats['errors']++;
            $this->error("Error processing {$fileName}: " . $e->getMessage());
        }
    }

    private function importIncomingItems($rows, $fileName)
    {
        DB::beginTransaction();
        try {
            foreach ($rows as $row) {
                // Column structure: NO | ID TRANSAKSI | TANGGAL | NAMA BARANG | KATEGORI | JUMLAH MASUK
                $transactionId = trim($row[1] ?? '');
                $date = $this->parseDate($row[2] ?? '');
                $itemName = trim($row[3] ?? '');
                $categoryName = trim($row[4] ?? '');
                $quantity = (int)($row[5] ?? 0);

                if (empty($itemName) || $quantity <= 0) {
                    continue;
                }

                // Create/update category
                $category = null;
                if (!empty($categoryName)) {
                    $category = Category::firstOrCreate(
                        ['name' => $categoryName],
                        ['created_at' => now(), 'updated_at' => now()]
                    );
                }

                // Create/update item
                $item = Item::firstOrCreate(
                    ['item_name' => $itemName],
                    [
                        'category_id' => $category ? $category->id : null,
                        'stock' => 0,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]
                );

                // Create incoming transaction
                IncomingItem::create([
                    'item_id' => $item->id,
                    'transaction_id' => $transactionId ?: 'AUTO_' . time() . '_' . rand(1000, 9999),
                    'incoming_date' => $date,
                    'quantity' => $quantity,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                // Update stock
                $item->increment('stock', $quantity);

                $this->stats['incoming_created']++;
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->stats['errors']++;
            $this->error("Transaction failed for incoming items: " . $e->getMessage());
        }
    }

    private function importOutgoingItems($rows, $fileName)
    {
        DB::beginTransaction();
        try {
            foreach ($rows as $row) {
                // Column structure: NO | ID TRANSAKSI | TANGGAL | NAMA BARANG | KATEGORI | JUMLAH KELUAR
                $transactionId = trim($row[1] ?? '');
                $date = $this->parseDate($row[2] ?? '');
                $itemName = trim($row[3] ?? '');
                $categoryName = trim($row[4] ?? '');
                $quantity = (int)($row[5] ?? 0);

                if (empty($itemName) || $quantity <= 0) {
                    continue;
                }

                // Create/update category
                $category = null;
                if (!empty($categoryName)) {
                    $category = Category::firstOrCreate(
                        ['name' => $categoryName],
                        ['created_at' => now(), 'updated_at' => now()]
                    );
                }

                // Create/update item
                $item = Item::firstOrCreate(
                    ['item_name' => $itemName],
                    [
                        'category_id' => $category ? $category->id : null,
                        'stock' => 0,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]
                );

                // Create outgoing transaction
                OutgoingItem::create([
                    'item_id' => $item->id,
                    'transaction_id' => $transactionId ?: 'AUTO_' . time() . '_' . rand(1000, 9999),
                    'outgoing_date' => $date,
                    'quantity' => $quantity,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                // Update stock
                $item->decrement('stock', $quantity);

                $this->stats['outgoing_created']++;
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->stats['errors']++;
            $this->error("Transaction failed for outgoing items: " . $e->getMessage());
        }
    }

    private function parseDate($dateValue): ?Carbon
    {
        if (empty($dateValue)) {
            return now();
        }

        // Handle Excel date format (numeric)
        if (is_numeric($dateValue)) {
            try {
                return Carbon::createFromFormat('Y-m-d', '1900-01-01')->addDays($dateValue - 2);
            } catch (\Exception $e) {
                return now();
            }
        }

        // Handle Indonesian date format like "1 Agustus 2024"
        $indonesianMonths = [
            'januari' => 'January',
            'februari' => 'February',
            'maret' => 'March',
            'april' => 'April',
            'mei' => 'May',
            'juni' => 'June',
            'juli' => 'July',
            'agustus' => 'August',
            'september' => 'September',
            'oktober' => 'October',
            'november' => 'November',
            'desember' => 'December'
        ];

        $dateString = strtolower(trim($dateValue));

        // Replace Indonesian month names with English
        foreach ($indonesianMonths as $indo => $english) {
            $dateString = str_replace($indo, $english, $dateString);
        }

        try {
            return Carbon::parse($dateString);
        } catch (\Exception $e) {
            // Try other formats
            try {
                return Carbon::createFromFormat('d/m/Y', $dateValue);
            } catch (\Exception $e2) {
                try {
                    return Carbon::createFromFormat('Y-m-d', $dateValue);
                } catch (\Exception $e3) {
                    return now();
                }
            }
        }
    }

    private function showResults()
    {
        $this->newLine();
        $this->warn('=== IMPORT RESULTS ===');

        $this->info("📄 Files processed: {$this->stats['files_processed']}");
        $this->info("📦 Items created/updated: {$this->stats['items_created']}");
        $this->info("📥 Incoming transactions: {$this->stats['incoming_created']}");
        $this->info("📤 Outgoing transactions: {$this->stats['outgoing_created']}");
        $this->info("❌ Errors: {$this->stats['errors']}");

        $totalTransactions = $this->stats['incoming_created'] + $this->stats['outgoing_created'];
        $this->info("📊 Total transactions imported: {$totalTransactions}");

        if ($this->stats['errors'] > 0) {
            $this->warn("⚠️  Some errors occurred. Check logs above.");
        } else {
            $this->info("✅ All files imported successfully!");
        }

        // Show final database stats
        $this->newLine();
        $this->warn('=== FINAL DATABASE STATUS ===');
        $this->info("Items: " . Item::count());
        $this->info("Categories: " . Category::count());
        $this->info("Incoming transactions: " . IncomingItem::count());
        $this->info("Outgoing transactions: " . OutgoingItem::count());
    }
}
