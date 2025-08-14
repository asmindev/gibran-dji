<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Maatwebsite\Excel\Facades\Excel;
use App\Imports\OutgoingItemsImport;

echo 'Testing import with correct row numbers...' . PHP_EOL;

$import = new OutgoingItemsImport();
try {
    Excel::import($import, 'test_import.csv');

    $stats = $import->getImportStatistics();
    $errors = $import->getValidationErrors();

    echo 'Import Statistics:' . PHP_EOL;
    echo '- Processed: ' . $stats['processed_rows'] . PHP_EOL;
    echo '- Saved: ' . $stats['saved_rows'] . PHP_EOL;
    echo '- Skipped: ' . $stats['skipped_rows'] . PHP_EOL;
    echo '- Errors: ' . $stats['validation_errors'] . PHP_EOL;

    if (!empty($errors)) {
        echo PHP_EOL . 'Validation Errors:' . PHP_EOL;
        foreach ($errors as $error) {
            echo '- Baris ' . $error['row'] . ': ' . $error['message'] . PHP_EOL;
        }
    }
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
