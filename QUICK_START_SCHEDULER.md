# Quick Start Guide - Monthly Prediction Scheduler

## 🚀 Setup Cepat (5 Menit)

### Step 1: Verifikasi Data

```bash
# 1. Check apakah ada data produk dan penjualan
php artisan tinker --execute="
echo 'Items: ' . App\Models\Item::count() . PHP_EOL;
echo 'Sales: ' . App\Models\OutgoingItem::count() . PHP_EOL;
echo 'Predictions: ' . App\Models\StockPrediction::count() . PHP_EOL;
"
```

### Step 2: Test Manual

```bash
# Test buat prediksi
php artisan predictions:monthly-automation --type=predict

# Test hitung actual (jika ada prediksi bulan ini)
php artisan predictions:monthly-automation --type=calculate
```

### Step 3: Setup Cron (Production)

```bash
# Edit crontab
crontab -e

# Tambahkan line ini (ganti /path/to/project dengan path real):
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1

# Save dan exit (Ctrl+X, Y, Enter)
```

### Step 4: Verifikasi Scheduler

```bash
# Check schedule list
php artisan schedule:list

# Harus muncul 3 scheduled tasks:
# 52 15 * * *  model:train (daily model training)
# 0  9  1  * *  predictions:monthly-automation --type=predict
# 0  23 30 * *  predictions:monthly-automation --type=calculate
```

## ✅ Validation Checklist

**Setelah Setup:**

-   [ ] Cron job terinstall: `crontab -l`
-   [ ] Schedule terdaftar: `php artisan schedule:list`
-   [ ] Test command berhasil: `php artisan predictions:monthly-automation --type=both`
-   [ ] Database ada data: Check items & outgoing_items tables

**Setiap Bulan:**

-   [ ] Tanggal 1: Prediksi bulan depan dibuat otomatis
-   [ ] Tanggal 30: Actual sales bulan ini dihitung otomatis
-   [ ] Check accuracy: Average > 70% = Good, < 50% = Review algorithm

## 📅 Calendar Schedule

```
Contoh Timeline untuk Setiap Bulan:

Tanggal 01 (09:00 WIB): CREATE PREDICTIONS
├─ Input: Data penjualan bulan lalu
├─ Output: Prediksi untuk bulan depan
└─ Database: Insert new records

Tanggal 02-29: BUSINESS AS USUAL
├─ Sistem tracking penjualan normal
└─ Data terakumulasi di outgoing_items

Tanggal 30 (23:00 WIB): CALCULATE ACTUAL
├─ Input: Prediksi bulan ini (dibuat bulan lalu)
├─ Process: Hitung total penjualan actual
├─ Output: Update actual + accuracy stats
└─ Database: Update existing records
```

## 🔧 Troubleshooting Cepat

**Problem: Command tidak jalan otomatis**

```bash
# Check cron service
sudo systemctl status cron

# Jika stopped, start service
sudo systemctl start cron
sudo systemctl enable cron
```

**Problem: Permission denied**

```bash
# Fix artisan permission
chmod +x artisan

# Fix storage permission
chmod -R 755 storage
chmod -R 755 bootstrap/cache
```

**Problem: Timezone salah**

```bash
# Check .env file
grep TIMEZONE .env

# Harus: APP_TIMEZONE=Asia/Jakarta
# Jika tidak ada, tambahkan ke .env
echo "APP_TIMEZONE=Asia/Jakarta" >> .env
```

## 📊 Monitoring Commands

```bash
# Check log realtime
tail -f storage/logs/laravel.log

# Check predictions terbaru
php artisan tinker --execute="
\$recent = App\Models\StockPrediction::latest()->take(5)->get();
foreach (\$recent as \$p) {
    echo \$p->product . ' | ' . \$p->month . ' | P:' . \$p->prediction . ' A:' . (\$p->actual ?? 'null') . PHP_EOL;
}
"

# Check accuracy stats
php artisan tinker --execute="
\$predictions = App\Models\StockPrediction::whereNotNull('actual')->get();
\$avg = \$predictions->avg('accuracy');
echo 'Average Accuracy: ' . round(\$avg, 2) . '%' . PHP_EOL;
echo 'Total Completed: ' . \$predictions->count() . PHP_EOL;
"
```

## 🎯 Expected Results

**Setelah Tanggal 1 (Prediction Created):**

```
Database akan memiliki records baru:
┌─────────────┬────────┬─────────────────┬────────────┐
│ prediction  │ actual │ product         │ month      │
├─────────────┼────────┼─────────────────┼────────────┤
│ 250         │ NULL   │ Sepatu Bola     │ 2025-09-01 │
│ 180         │ NULL   │ Jersey Mills    │ 2025-09-01 │
│ 320         │ NULL   │ Kaos Kaki       │ 2025-09-01 │
└─────────────┴────────┴─────────────────┴────────────┘
```

**Setelah Tanggal 30 (Actual Calculated):**

```
Database akan terupdate:
┌─────────────┬────────┬─────────────────┬────────────┬──────────┐
│ prediction  │ actual │ product         │ month      │ accuracy │
├─────────────┼────────┼─────────────────┼────────────┼──────────┤
│ 250         │ 245    │ Sepatu Bola     │ 2025-08-01 │ 98.00%   │
│ 180         │ 195    │ Jersey Mills    │ 2025-08-01 │ 92.31%   │
│ 320         │ 298    │ Kaos Kaki       │ 2025-08-01 │ 93.13%   │
└─────────────┴────────┴─────────────────┴────────────┴──────────┘
```

## 🚨 Emergency Procedures

**Stop Scheduler:**

```bash
# Disable cron temporarily
crontab -r

# Re-enable later
crontab -e
# Add: * * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

**Manual Override:**

```bash
# Force create predictions untuk bulan tertentu
php artisan tinker --execute="
use App\Models\StockPrediction;
use App\Models\Item;

// Create manual prediction
StockPrediction::create([
    'prediction' => 200,
    'actual' => null,
    'product' => 'Sepatu Bola',
    'month' => '2025-09-01'
]);
echo 'Manual prediction created';
"

# Force calculate actual untuk bulan tertentu
php artisan predictions:monthly-automation --type=calculate
```

## 📞 Support Contacts

**Ketika Ada Masalah:**

1. Check logs: `tail -f storage/logs/laravel.log`
2. Check cron: `sudo systemctl status cron`
3. Test manual: `php artisan predictions:monthly-automation --type=both`
4. Check database: Count records di stock_predictions table

**Monthly Health Check:**

-   [ ] Scheduler running: `php artisan schedule:list`
-   [ ] Predictions created: Check database for current month + 1
-   [ ] Actual calculated: Check database for current month with actual != null
-   [ ] Accuracy acceptable: Average > 70%

**System ini dirancang untuk berjalan otomatis tanpa maintenance!** 🎉
