# Laravel Scheduler - Monthly Prediction Automation Documentation

## Overview

Dokumentasi lengkap untuk sistem scheduler Laravel yang menjalankan automasi prediksi bulanan secara otomatis. Sistem ini akan berjalan di background tanpa intervensi manual.

## Table of Contents

1. [Persyaratan System](#persyaratan-system)
2. [Cara Kerja Scheduler](#cara-kerja-scheduler)
3. [Alur Automasi](#alur-automasi)
4. [Setup Production](#setup-production)
5. [Monitoring &amp; Troubleshooting](#monitoring--troubleshooting)
6. [Testing &amp; Validation](#testing--validation)

---

## Persyaratan System

### 1. **Server Requirements**

```bash
# Minimum Requirements
- PHP >= 8.1
- Laravel >= 11.0
- Database: MySQL/MariaDB
- Cron service (Linux/Unix)
- Timezone: Asia/Jakarta
```

### 2. **Data Requirements**

```sql
-- Database harus memiliki data:
- items table: Produk dengan item_name
- outgoing_items table: Data penjualan historis minimal 1 bulan
- stock_predictions table: Tabel untuk menyimpan prediksi
```

### 3. **Permission Requirements**

```bash
# File permissions
- storage/logs: writable (755)
- storage/framework: writable (755)
- artisan: executable (755)
```

---

## Cara Kerja Scheduler

### 1. **Schedule Configuration**

File: `/routes/console.php`

```php
// Training model setiap hari jam 15:52 (3:52 PM)
Schedule::command('model:train')->dailyAt('15:52')->name('daily-model-training');

// Buat prediksi setiap tanggal 1 bulan, jam 09:00
Schedule::command('predictions:monthly-automation --type=predict')
    ->monthlyOn(1, '09:00')
    ->name('monthly-prediction-create')
    ->timezone('Asia/Jakarta');

// Hitung actual sales setiap tanggal 30 bulan, jam 23:00
Schedule::command('predictions:monthly-automation --type=calculate')
    ->monthlyOn(30, '23:00')
    ->name('monthly-prediction-calculate')
    ->timezone('Asia/Jakarta');
```

### 2. **Command Details**

| Command            | Frequency | Time  | Description                              | Example                             |
| ------------------ | --------- | ----- | ---------------------------------------- | ----------------------------------- |
| `model:train`      | Daily     | 15:52 | Melatih ulang model prediksi setiap hari | Training dengan data terbaru        |
| `--type=predict`   | Monthly   | 09:00 | Membuat prediksi untuk bulan depan       | September prediksi dibuat 1 Agustus |
| `--type=calculate` | Monthly   | 23:00 | Menghitung actual sales bulan ini        | Agustus actual dihitung 30 Agustus  |
| `--type=both`      | Manual    | -     | Jalankan predict + calculate             | Untuk testing/manual run            |

---

## Alur Automasi

### **Timeline Example: Agustus 2025**

```
┌─────────────────────────────────────────────────────────┐
│                    AGUSTUS 2025                         │
├─────────────────────────────────────────────────────────┤
│ 01 Agt 09:00 │ CREATE PREDICTIONS untuk Sept 2025     │
│              │ Input: Data penjualan Juli 2025         │
│              │ Output: Prediksi Sept di database       │
├─────────────────────────────────────────────────────────┤
│ 02-29 Agt    │ PERIODE PENJUALAN                       │
│              │ Business as usual, data terakumulasi    │
├─────────────────────────────────────────────────────────┤
│ 30 Agt 23:00 │ CALCULATE ACTUAL untuk Agt 2025        │
│              │ Input: Prediksi Agt (dibuat 1 Juli)    │
│              │ Process: Hitung actual sales Agustus    │
│              │ Output: Update actual + accuracy stats  │
└─────────────────────────────────────────────────────────┘
```

### **Step-by-Step Process**

#### **Step 1: Create Predictions (Tanggal 1, 09:00)**

1. **Trigger**: Cron job menjalankan command otomatis
2. **Process**:

    ```bash
    # Command yang dijalankan otomatis
    php artisan predictions:monthly-automation --type=predict
    ```

3. **Algorithm**:

    ```php
    // Untuk setiap produk:
    $lastMonthSales = July2025_Sales;
    $seasonalFactor = getSeasonalFactor(September); // 1.0 normal, 1.2 Dec, 0.8 Jan
    $prediction = $lastMonthSales * $seasonalFactor;

    // Save ke database
    StockPrediction::create([
        'prediction' => $prediction,
        'actual' => null,
        'product' => $productName,
        'month' => '2025-09-01'
    ]);
    ```

4. **Output Expected**:

    ```
    === Monthly Prediction Automation ===
    Current Date: 01 August 2025
    --- Creating Monthly Predictions ---
    Creating predictions for: September 2025
    ████████████████████████████ 8/8 100%

    Prediction Results:
      Created: 8
      Skipped (already exists): 0
      Errors: 0
    ```

#### **Step 2: Calculate Actual (Tanggal 30, 23:00)**

1. **Trigger**: Cron job menjalankan command otomatis
2. **Process**:

    ```bash
    # Command yang dijalankan otomatis
    php artisan predictions:monthly-automation --type=calculate
    ```

3. **Algorithm**:

    ```php
    // Cari prediksi bulan ini yang belum ada actual data
    $predictions = StockPrediction::whereMonth('month', 8)
        ->whereYear('month', 2025)
        ->whereNull('actual')
        ->get();

    foreach ($predictions as $prediction) {
        // Hitung actual sales dari outgoing_items
        $actualSales = OutgoingItem::where('item_id', $item->id)
            ->whereMonth('outgoing_date', 8)
            ->whereYear('outgoing_date', 2025)
            ->sum('quantity');

        // Update database
        $prediction->update(['actual' => $actualSales]);
    }
    ```

4. **Output Expected**:

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
      Sepatu Bola: 96.75% (Predicted: 258, Actual: 245)
      Jersey Mills: 87.19% (Predicted: 190, Actual: 203)
    ```

---

## Setup Production

### **1. Install Cron Job (WAJIB untuk Production)**

```bash
# Login ke server sebagai user yang menjalankan aplikasi
ssh user@your-server.com

# Edit crontab
crontab -e

# Tambahkan line ini:
* * * * * cd /path/to/your/laravel/project && php artisan schedule:run >> /dev/null 2>&1

# Contoh real path:
* * * * * cd /var/www/gibran && php artisan schedule:run >> /dev/null 2>&1
```

### **2. Verify Cron Installation**

```bash
# Check apakah cron service berjalan
sudo systemctl status cron

# Jika tidak aktif, start cron service
sudo systemctl start cron
sudo systemctl enable cron

# Verify crontab entry
crontab -l
```

### **3. Test Scheduler**

```bash
# 1. Check scheduled commands
php artisan schedule:list

# Expected output:
# 0  9  1  * *  php artisan predictions:monthly-automation --type=predict
# 0  23 30 * *  php artisan predictions:monthly-automation --type=calculate

# 2. Run scheduler manually (untuk testing)
php artisan schedule:run

# 3. Test specific command
php artisan predictions:monthly-automation --type=both
```

### **4. Environment Configuration**

```bash
# .env file harus dikonfigurasi dengan benar
APP_TIMEZONE=Asia/Jakarta
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password

# Optional: Untuk logging yang lebih detail
LOG_CHANNEL=daily
LOG_LEVEL=info
```

---

## Monitoring & Troubleshooting

### **1. Log Monitoring**

```bash
# Check Laravel logs
tail -f storage/logs/laravel.log

# Check cron logs (Ubuntu/Debian)
tail -f /var/log/cron.log

# Check system logs
tail -f /var/log/syslog | grep CRON
```

### **2. Debug Commands**

```bash
# 1. Verify scheduler configuration
php artisan schedule:list

# 2. Test scheduler manually
php artisan schedule:run --verbose

# 3. Test specific automation command
php artisan predictions:monthly-automation --type=predict -v
php artisan predictions:monthly-automation --type=calculate -v

# 4. Check database records
php artisan tinker
>>> App\Models\StockPrediction::latest()->take(5)->get()
>>> App\Models\StockPrediction::whereNull('actual')->count()
```

### **3. Common Issues & Solutions**

| Issue                   | Symptom                         | Solution                                       |
| ----------------------- | ------------------------------- | ---------------------------------------------- |
| **Cron not running**    | Commands tidak execute otomatis | `sudo systemctl start cron`                    |
| **Permission denied**   | Error saat akses file/directory | `chmod 755 artisan`, check storage permissions |
| **Database connection** | Connection refused errors       | Check .env database config                     |
| **Timezone mismatch**   | Command run di waktu yang salah | Set `APP_TIMEZONE=Asia/Jakarta`                |
| **Memory limit**        | PHP memory exhausted            | Increase `memory_limit` in php.ini             |

### **4. Performance Monitoring**

```bash
# Check command execution time
time php artisan predictions:monthly-automation --type=predict

# Monitor database queries
# Enable query logging in config/database.php:
'connections' => [
    'mysql' => [
        'options' => [
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
        ],
        'dump' => [
            'log_queries' => true,
        ],
    ],
],
```

---

## Testing & Validation

### **1. Pre-Production Testing**

```bash
# 1. Test data requirements
php artisan tinker
>>> App\Models\Item::count()          // Harus > 0
>>> App\Models\OutgoingItem::count()  // Harus > 0

# 2. Test prediction creation
php artisan predictions:monthly-automation --type=predict

# 3. Test actual calculation
php artisan predictions:monthly-automation --type=calculate

# 4. Verify database records
>>> App\Models\StockPrediction::count()
>>> App\Models\StockPrediction::whereNotNull('actual')->count()
```

### **2. Monthly Validation Checklist**

**Tanggal 1 (Setelah prediction creation):**

```bash
# Check apakah prediksi dibuat untuk bulan depan
php artisan tinker
>>> $nextMonth = now()->addMonth()->format('Y-m');
>>> App\Models\StockPrediction::whereRaw("DATE_FORMAT(month, '%Y-%m') = ?", [$nextMonth])->count()
# Expected: > 0 (sesuai jumlah produk)
```

**Tanggal 30/31 (Setelah actual calculation):**

```bash
# Check apakah actual data sudah diupdate untuk bulan ini
php artisan tinker
>>> $currentMonth = now()->format('Y-m');
>>> App\Models\StockPrediction::whereRaw("DATE_FORMAT(month, '%Y-%m') = ?", [$currentMonth])
...     ->whereNotNull('actual')->count()
# Expected: > 0 (sesuai prediksi yang ada untuk bulan ini)
```

### **3. Accuracy Monitoring**

```sql
-- Query untuk monitor performa prediksi
SELECT
    DATE_FORMAT(month, '%Y-%m') as period,
    COUNT(*) as total_predictions,
    COUNT(CASE WHEN actual IS NOT NULL THEN 1 END) as completed_predictions,
    AVG(CASE
        WHEN actual IS NOT NULL AND prediction > 0
        THEN (1 - ABS(prediction - actual) / GREATEST(prediction, actual)) * 100
        ELSE NULL
    END) as avg_accuracy,
    MIN(CASE
        WHEN actual IS NOT NULL AND prediction > 0
        THEN (1 - ABS(prediction - actual) / GREATEST(prediction, actual)) * 100
        ELSE NULL
    END) as min_accuracy,
    MAX(CASE
        WHEN actual IS NOT NULL AND prediction > 0
        THEN (1 - ABS(prediction - actual) / GREATEST(prediction, actual)) * 100
        ELSE NULL
    END) as max_accuracy
FROM stock_predictions
GROUP BY DATE_FORMAT(month, '%Y-%m')
ORDER BY period DESC;
```

---

## Quick Reference

### **Emergency Commands**

```bash
# Stop all scheduled jobs (disable cron)
crontab -r

# Re-enable scheduling
crontab -e
# Add: * * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1

# Force run prediction for specific month
php artisan tinker
>>> Artisan::call('predictions:monthly-automation', ['--type' => 'predict']);

# Manual cleanup (jika ada duplikasi)
>>> App\Models\StockPrediction::where('month', '2025-09-01')->delete();
```

### **Status Check Commands**

```bash
# Scheduler status
php artisan schedule:list

# Database status
php artisan tinker
>>> App\Models\StockPrediction::selectRaw('
    YEAR(month) as year,
    MONTH(month) as month,
    COUNT(*) as total,
    COUNT(actual) as completed
')->groupBy('year', 'month')->orderBy('year', 'desc')->orderBy('month', 'desc')->get();

# Cron status
sudo systemctl status cron
```

---

## Support & Maintenance

### **Monthly Checklist**

-   [ ] Verify cron job berjalan (check logs)
-   [ ] Check accuracy statistics
-   [ ] Monitor storage/logs untuk errors
-   [ ] Backup stock_predictions table
-   [ ] Review prediction vs actual trends

### **Alert Thresholds**

-   Average accuracy < 70% → Review algorithm
-   No predictions created → Check data availability
-   Cron not executing → Check server status
-   High error rate → Check logs immediately

**System ini akan berjalan otomatis 24/7 setelah setup production selesai!** 🚀
