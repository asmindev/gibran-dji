<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Item;
use Illuminate\Support\Collection;

class AnalyzeExcelErrors extends Command
{
    protected $signature = 'analyze:excel-errors {--path= : Path to Excel files directory}';
    protected $description = 'Analyze Excel files for import errors without importing';

    private $errors = [];
    private $fileStats = [];

    public function handle()
    {
        $path = $this->option('path') ?: '/home/labubu/Downloads/gibran';

        if (!is_dir($path)) {
            $this->error("Directory not found: {$path}");
            return 1;
        }

        $this->info("Analyzing Excel files in: {$path}");

        // Analyze incoming items
        $this->analyzeFolder($path . '/BARANG MASUK', 'incoming');

        // Analyze outgoing items
        $this->analyzeFolder($path . '/BARANG KELUAR', 'outgoing');

        // Show results
        $this->showResults();

        return 0;
    }

    private function analyzeFolder($folderPath, $type)
    {
        if (!is_dir($folderPath)) {
            $this->warn("Folder not found: {$folderPath}");
            return;
        }

        $files = $this->getExcelFiles($folderPath);
        $this->info("Analyzing {$type} files: {$files->count()} files");

        $progressBar = $this->output->createProgressBar($files->count());

        foreach ($files as $file) {
            $this->analyzeFile($file, $type);
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
    }

    private function analyzeFile($filePath, $type)
    {
        try {
            // Use toArray instead of import with WithHeadingRow for better control
            $data = Excel::toArray(new class {}, $filePath)[0] ?? [];

            // Skip header row (row 0)
            $dataRows = array_slice($data, 1);

            // Count valid vs empty rows
            $validRows = [];
            $emptyRows = 0;

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
                } else {
                    $emptyRows++;
                }
            }

            // Store file statistics
            $this->fileStats[basename($filePath)] = [
                'valid_rows' => count($validRows),
                'empty_rows' => $emptyRows,
                'total_rows' => count($dataRows)
            ];

            foreach ($validRows as $rowIndex => $row) {
                // Assuming column structure: NO | ID TRANSAKSI | TANGGAL | NAMA BARANG | KATEGORI | JUMLAH
                // nama_barang should be at index 3 (0-based)
                $itemName = trim($row[3] ?? '');

                if (empty($itemName)) {
                    $this->errors[] = [
                        'type' => $type,
                        'file' => basename($filePath),
                        'error' => 'Empty item name',
                        'row' => $row,
                        'row_number' => $rowIndex + 2 // +2 because we skipped header and array is 0-based
                    ];
                    continue;
                }

                $item = Item::where('item_name', $itemName)->first();
                if (!$item) {
                    $this->errors[] = [
                        'type' => $type,
                        'file' => basename($filePath),
                        'error' => 'Item not found: ' . $itemName,
                        'row' => $row,
                        'row_number' => $rowIndex + 2
                    ];
                }
            }
        } catch (\Exception $e) {
            $this->errors[] = [
                'type' => $type,
                'file' => basename($filePath),
                'error' => 'File error: ' . $e->getMessage(),
                'row' => [],
                'row_number' => 0
            ];
        }
    }

    private function getExcelFiles($directory): Collection
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

        return $files;
    }

    private function showResults()
    {
        $this->newLine();
        $this->warn('=== ANALYSIS RESULTS ===');

        $totalErrors = count($this->errors);
        $incomingErrors = collect($this->errors)->where('type', 'incoming')->count();
        $outgoingErrors = collect($this->errors)->where('type', 'outgoing')->count();

        $this->info("Total errors found: {$totalErrors}");
        $this->info("Incoming errors: {$incomingErrors}");
        $this->info("Outgoing errors: {$outgoingErrors}");

        // Show summary of processed data
        $this->newLine();
        $this->warn('📊 DATA SUMMARY:');
        $this->info("Total files processed: " . (count($this->incomingFiles ?? []) + count($this->outgoingFiles ?? [])));
        $this->info("Files with data: " . collect($this->fileStats ?? [])->where('valid_rows', '>', 0)->count());
        $this->info("Files with empty rows: " . collect($this->fileStats ?? [])->where('empty_rows', '>', 0)->count());

        // Items not found
        $itemNotFound = collect($this->errors)->where('error', 'like', 'Item not found%');
        if ($itemNotFound->isNotEmpty()) {
            $this->newLine();
            $this->warn('📦 ITEMS NOT FOUND (Top 20):');

            $itemNames = $itemNotFound->map(function ($error) {
                if (preg_match('/Item not found: (.+)/', $error['error'], $matches)) {
                    return trim($matches[1]);
                }
                return 'Unknown';
            })->countBy()->sortDesc()->take(20);

            foreach ($itemNames as $itemName => $count) {
                $this->line("  - '{$itemName}': {$count} occurrences");
            }
        }

        // Empty item names
        $emptyNames = collect($this->errors)->where('error', 'Empty item name');
        if ($emptyNames->isNotEmpty()) {
            $this->newLine();
            $this->warn('📝 EMPTY ITEM NAMES:');
            $this->info("Rows with empty 'nama_barang': {$emptyNames->count()}");
        }

        // Files with most errors
        $this->newLine();
        $this->warn('📄 FILES WITH MOST ERRORS:');

        $fileErrors = collect($this->errors)->groupBy('file')->map(function ($group) {
            return $group->count();
        })->sortDesc()->take(10);
        foreach ($fileErrors as $file => $count) {
            $this->line("  - {$file}: {$count} errors");
        }

        // Show file statistics
        $this->newLine();
        $this->warn('📈 FILE STATISTICS:');
        if (!empty($this->fileStats)) {
            foreach ($this->fileStats as $file => $stats) {
                $this->line("  - {$file}: {$stats['valid_rows']} valid rows, {$stats['empty_rows']} empty rows");
            }
        }

        // Sample errors
        if ($totalErrors > 0) {
            $this->newLine();
            $this->warn('🔍 SAMPLE ERRORS:');

            $sampleErrors = collect($this->errors)->take(10);
            foreach ($sampleErrors as $error) {
                $this->line("Type: {$error['type']} | File: {$error['file']}");
                $this->line("Error: {$error['error']}");
                $this->line('---');
            }
        } else {
            $this->newLine();
            $this->info('✅ No errors found! All items in Excel files exist in the database.');
        }
    }
}
