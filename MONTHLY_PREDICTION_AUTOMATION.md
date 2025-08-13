# Monthly Prediction Automation System

## Overview

Sistem automasi prediksi bulanan yang berjalan secara otomatis untuk melakukan prediksi penjualan dan menghitung akurasi berdasarkan data aktual.

## Database Schema (Simplified)

```sql
CREATE TABLE stock_predictions (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    prediction INT NOT NULL,           -- Hasil prediksi
    actual INT NULL,                   -- Actual terjual (diisi kemudian)
    product VARCHAR(255) NOT NULL,     -- Nama produk
    month DATE NOT NULL,               -- Bulan prediksi (YYYY-MM-01)
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```

## Workflow Automation

### 1. Awal Bulan (Tanggal 1, Jam 09:00)

**Command:** `predictions:monthly-automation --type=predict`

**Proses:**

1. Ambil data penjualan bulan lalu untuk setiap produk
2. Hitung prediksi untuk bulan ini menggunakan algoritma
3. Simpan prediksi ke database dengan `actual = null`

**Contoh:**

```
Tanggal: 1 September 2025, 09:00
Input: Data penjualan Agustus 2025
Output: Prediksi penjualan September 2025
```

### 2. Akhir Bulan (Tanggal 30, Jam 23:00)

**Command:** `predictions:monthly-automation --type=calculate`

**Proses:**

1. Ambil semua prediksi bulan ini yang belum ada `actual`
2. Hitung total penjualan aktual bulan ini
3. Update field `actual` dengan total penjualan
4. Tampilkan statistik akurasi

**Contoh:**

```
Tanggal: 30 September 2025, 23:00
Input: Prediksi September 2025
Process: Hitung actual sales September 2025
Output: Update actual data + accuracy statistics
```

## Command Usage

### Manual Commands

```bash
# Buat prediksi untuk bulan depan
php artisan predictions:monthly-automation --type=predict

# Hitung actual sales untuk bulan ini
php artisan predictions:monthly-automation --type=calculate

# Jalankan kedua proses sekaligus
php artisan predictions:monthly-automation --type=both
```

### Scheduled Commands (Automatic)

Di `routes/console.php`:

```php
// Buat prediksi setiap tanggal 1 jam 9 pagi
Schedule::command('predictions:monthly-automation --type=predict')
    ->monthlyOn(1, '09:00')
    ->timezone('Asia/Jakarta');

// Hitung actual setiap tanggal 30 jam 11 malam
Schedule::command('predictions:monthly-automation --type=calculate')
    ->monthlyOn(30, '23:00')
    ->timezone('Asia/Jakarta');
```

## Algorithm (Simplified)

```php
private function callPredictionAlgorithm($item, $prevMonthTotal)
{
    // Base prediction from previous month
    $basePredicton = $prevMonthTotal;

    // Seasonal factors
    $month = now()->addMonth()->month;
    $seasonalFactor = 1.0;

    if ($month == 12) {        // December - holiday season
        $seasonalFactor = 1.2;
    } elseif ($month == 1) {   // January - post-holiday
        $seasonalFactor = 0.8;
    }

    $prediction = round($basePredicton * $seasonalFactor);
    return max(0, $prediction);
}
```

## Example Data Flow

### Scenario: Sistem berjalan di Agustus 2025

**1. Tanggal 1 Agustus 2025, 09:00:**

```
Input Data:
- Sepatu Bola: 258 unit terjual di Juli 2025
- Jersey Mills: 190 unit terjual di Juli 2025

Algorithm:
- Sepatu Bola: 258 * 1.0 = 258 (prediksi Agustus)
- Jersey Mills: 190 * 1.0 = 190 (prediksi Agustus)

Database Insert:
┌────────────┬────────────┬────────┬──────────────┬────────────┐
│ prediction │ actual     │ product│ month        │ created_at │
├────────────┼────────────┼────────┼──────────────┼────────────┤
│ 258        │ NULL       │ Sepatu │ 2025-08-01   │ 09:00:00   │
│ 190        │ NULL       │ Jersey │ 2025-08-01   │ 09:00:00   │
└────────────┴────────────┴────────┴──────────────┴────────────┘
```

**2. Tanggal 30 Agustus 2025, 23:00:**

```
Calculate Actual:
- Sepatu Bola: 245 unit (actual sales Agustus)
- Jersey Mills: 203 unit (actual sales Agustus)

Database Update:
┌────────────┬────────────┬────────┬──────────────┬────────────┐
│ prediction │ actual     │ product│ month        │ accuracy   │
├────────────┼────────────┼────────┼──────────────┼────────────┤
│ 258        │ 245        │ Sepatu │ 2025-08-01   │ 94.96%     │
│ 190        │ 203        │ Jersey │ 2025-08-01   │ 87.19%     │
└────────────┴────────────┴────────┴──────────────┴────────────┘

Accuracy Calculation:
- Sepatu: (1 - |258-245|/258) * 100 = 94.96%
- Jersey: (1 - |190-203|/203) * 100 = 87.19%
- Average: 91.08%
```

## Output Examples

### Prediction Creation Output

```
=== Monthly Prediction Automation ===
Current Date: 01 September 2025
--- Creating Monthly Predictions ---
Creating predictions for: September 2025
████████████████████████████ 8/8 100%

Prediction Results:
  Created: 8
  Skipped (already exists): 0
  Errors: 0
```

### Actual Calculation Output

```
=== Monthly Prediction Automation ===
Current Date: 30 August 2025
--- Calculating Actual Sales ---
Calculating actual sales for: August 2025
Found 8 predictions to update
████████████████████████████ 8/8 100%

Successfully updated 8 predictions

=== Accuracy Statistics ===
┌───────────────────┬─────────────┐
│ Metric            │ Value       │
├───────────────────┼─────────────┤
│ Total Predictions │ 8           │
│ Average Accuracy  │ 89.23%      │
│ Minimum Accuracy  │ 76.54%      │
│ Maximum Accuracy  │ 96.75%      │
│ Month/Year        │ August 2025 │
└───────────────────┴─────────────┘

Top 5 Most Accurate Predictions:
  Sepatu Bola Ortus: 96.75% (Predicted: 258, Actual: 245)
  Jersey Mills: 87.19% (Predicted: 190, Actual: 203)
  Kaos Kaki Avo: 84.32% (Predicted: 150, Actual: 174)
```

## API Integration

### Get Prediction History

```php
// Example API endpoint (if needed)
Route::get('/api/predictions/history', function() {
    return StockPrediction::orderBy('month', 'desc')
        ->get()
        ->map(function($pred) {
            return [
                'product' => $pred->product,
                'month' => $pred->formatted_month,
                'prediction' => $pred->prediction,
                'actual' => $pred->actual,
                'accuracy' => $pred->accuracy
            ];
        });
});
```

### Response Example

```json
[
    {
        "product": "Sepatu Bola Ortus",
        "month": "September 2025",
        "prediction": 317,
        "actual": null,
        "accuracy": null
    },
    {
        "product": "Sepatu Bola Ortus",
        "month": "August 2025",
        "prediction": 149,
        "actual": 154,
        "accuracy": 96.75
    }
]
```

## Monitoring & Maintenance

### Check Scheduler Status

```bash
# Check if scheduler is running
php artisan schedule:list

# Run scheduler manually (for testing)
php artisan schedule:run

# Check specific command
php artisan schedule:test
```

### Manual Operations

```bash
# Create predictions manually
php artisan predictions:monthly-automation --type=predict

# Calculate actual manually
php artisan predictions:monthly-automation --type=calculate

# View database records
php artisan tinker
>>> StockPrediction::latest()->take(5)->get()
```

### Performance Monitoring

```bash
# Monitor accuracy trends
SELECT
    DATE_FORMAT(month, '%Y-%m') as period,
    COUNT(*) as total_predictions,
    AVG(CASE
        WHEN actual IS NOT NULL AND prediction > 0
        THEN (1 - ABS(prediction - actual) / GREATEST(prediction, actual)) * 100
        ELSE NULL
    END) as avg_accuracy
FROM stock_predictions
WHERE actual IS NOT NULL
GROUP BY DATE_FORMAT(month, '%Y-%m')
ORDER BY period DESC;
```

## Benefits

1. **Full Automation**: Tidak perlu manual intervention
2. **Historical Tracking**: Semua prediksi tersimpan dengan akurasi
3. **Performance Monitoring**: Bisa track improvement model over time
4. **Simple Schema**: Hanya 4 field utama, mudah dimaintain
5. **Scalable**: Bisa handle multiple products secara otomatis
6. **Scheduled**: Berjalan otomatis sesuai jadwal bisnis

## Setup Cron Job (Production)

Untuk menjalankan scheduler Laravel di production server:

```bash
# Add to crontab (crontab -e)
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

System ini memberikan foundation yang solid untuk stock prediction dengan automasi penuh dan tracking akurasi yang comprehensive!
