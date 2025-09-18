<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\StockPrediction;
use App\Models\Item;

echo "Testing database structure and prediction save...\n";

// Check table structure
echo "\n1. Checking stock_predictions table structure:\n";
try {
    $columns = DB::select('DESCRIBE stock_predictions');
    foreach ($columns as $column) {
        echo "- {$column->Field}: {$column->Type}\n";
        if ($column->Field === 'prediction_type') {
            echo "  ✅ Found prediction_type column with type: {$column->Type}\n";
        }
    }
} catch (Exception $e) {
    echo "❌ Error checking table structure: " . $e->getMessage() . "\n";
}

// Test saving a prediction
echo "\n2. Testing prediction save:\n";
try {
    // Get first item
    $item = Item::first();
    if (!$item) {
        echo "❌ No items found in database. Please add some items first.\n";
        exit;
    }
    
    echo "Testing with item: {$item->name} (ID: {$item->id})\n";
    
    // Test data
    $testData = [
        'item_id' => $item->id,
        'product' => $item->name,
        'prediction' => 15.75,
        'prediction_type' => 'sales', // This should work now
        'month' => now()->startOfMonth(),
        'created_at' => now(),
        'updated_at' => now()
    ];
    
    echo "Attempting to save prediction with type: '{$testData['prediction_type']}'\n";
    
    $prediction = StockPrediction::create($testData);
    
    echo "✅ Prediction saved successfully!\n";
    echo "- ID: {$prediction->id}\n";
    echo "- Product: {$prediction->product}\n";
    echo "- Prediction Type: {$prediction->prediction_type}\n";
    echo "- Prediction Value: {$prediction->prediction}\n";
    
    // Clean up test data
    $prediction->delete();
    echo "✅ Test data cleaned up.\n";
    
} catch (Exception $e) {
    echo "❌ Error saving prediction: " . $e->getMessage() . "\n";
    echo "Error details: " . $e->getTraceAsString() . "\n";
}

echo "\n3. Testing with restock type:\n";
try {
    $item = Item::first();
    
    $testData = [
        'item_id' => $item->id,
        'product' => $item->name,
        'prediction' => 25.50,
        'prediction_type' => 'restock', // Test restock type too
        'month' => now()->startOfMonth(),
        'created_at' => now(),
        'updated_at' => now()
    ];
    
    echo "Attempting to save prediction with type: '{$testData['prediction_type']}'\n";
    
    $prediction = StockPrediction::create($testData);
    
    echo "✅ Restock prediction saved successfully!\n";
    echo "- ID: {$prediction->id}\n";
    echo "- Product: {$prediction->product}\n";
    echo "- Prediction Type: {$prediction->prediction_type}\n";
    echo "- Prediction Value: {$prediction->prediction}\n";
    
    // Clean up test data
    $prediction->delete();
    echo "✅ Test data cleaned up.\n";
    
} catch (Exception $e) {
    echo "❌ Error saving restock prediction: " . $e->getMessage() . "\n";
}

echo "\n✅ Database test completed!\n";