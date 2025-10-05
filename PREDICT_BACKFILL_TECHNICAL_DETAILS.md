# Predict Backfill - Technical Details

## Apakah Command Ini Memanggil predict.py?

**✅ YA**, command `predict:backfill` memanggil script Python `scripts/predict.py` untuk setiap prediksi.

### Cara Kerja:

```php
// Build Python command dengan virtual environment support
$scriptsPath = base_path('scripts');
$args = [
    '--product', $item->id,
    '--type', $predictionType,  // 'sales' atau 'restock'
    '--avg-monthly', $avgMonthly
];

$pythonCommandInfo = PlatformCompatibilityService::buildPythonCommand(
    $scriptsPath,
    'predict.py',
    $args
);

// Execute command
$result = PlatformCompatibilityService::executeCommand(
    $pythonCommandInfo['command'],
    $pythonCommandInfo['workingDirectory']
);
```

---

## Apakah Sudah Mendukung Virtual Environment?

**✅ YA**, command ini sudah menggunakan `PlatformCompatibilityService` yang mendukung virtual environment.

### Urutan Prioritas Python:

1. **Virtual Environment Python (Prioritas Tertinggi)**

    - Linux/Mac: `scripts/.venv/bin/python`
    - Windows: `scripts\.venv\Scripts\python.exe`

2. **Virtual Environment dengan Aktivasi**

    - Linux/Mac: `source scripts/.venv/bin/activate && python predict.py`
    - Windows: `scripts\.venv\Scripts\activate.bat && python predict.py`

3. **System Python (Fallback)**
    - Linux/Mac: `python3`
    - Windows: `python`

### Cara Membuat Virtual Environment:

```bash
# Linux/Mac
cd scripts
python3 -m venv .venv
source .venv/bin/activate
pip install -r requirements.txt

# Windows
cd scripts
python -m venv .venv
.venv\Scripts\activate
pip install -r requirements.txt
```

---

## Apakah Sudah Mendukung Windows?

**✅ YA**, command ini sudah mendukung Windows melalui `PlatformCompatibilityService`.

### Fitur Cross-Platform:

| Fitur                 | Linux/Mac                       | Windows                         |
| --------------------- | ------------------------------- | ------------------------------- |
| **Python Command**    | `python3`                       | `python`                        |
| **Path Separator**    | `/`                             | `\`                             |
| **Virtual Env Path**  | `.venv/bin/python`              | `.venv\Scripts\python.exe`      |
| **Activation Script** | `source .venv/bin/activate`     | `.venv\Scripts\activate.bat`    |
| **Command Execution** | `exec()` dengan proper escaping | `exec()` dengan proper escaping |

### Deteksi Platform Otomatis:

```php
PlatformCompatibilityService::isWindows()  // true jika Windows
PlatformCompatibilityService::isUnix()     // true jika Linux/Mac
PlatformCompatibilityService::getOSFamily() // 'Windows', 'Linux', atau 'Darwin'
```

---

## Apakah Hasilnya Disimpan ke Database?

**✅ YA**, semua hasil prediksi disimpan ke database tabel `stock_predictions`.

### Struktur Data yang Disimpan:

```php
StockPrediction::updateOrCreate(
    [
        'item_id' => $item->id,              // ID produk
        'product' => $item->item_name,       // Nama produk
        'month' => $monthDate->format('Y-m-01'), // Bulan prediksi (format: 2025-01-01)
        'prediction_type' => $predictionType,     // 'sales' atau 'restock'
    ],
    [
        'prediction' => round($predictionResult['prediction'], 2), // Nilai prediksi
        'actual' => $actual,                                       // Nilai aktual (jika ada)
    ]
);
```

### Tabel Database: `stock_predictions`

| Kolom             | Tipe               | Keterangan                        |
| ----------------- | ------------------ | --------------------------------- |
| `id`              | bigint             | Primary key                       |
| `item_id`         | bigint             | Foreign key ke tabel `items`      |
| `product`         | string             | Nama produk                       |
| `month`           | date               | Bulan prediksi (selalu tanggal 1) |
| `prediction_type` | enum               | 'sales' atau 'restock'            |
| `prediction`      | decimal            | Nilai prediksi dari model         |
| `actual`          | decimal (nullable) | Nilai aktual transaksi            |
| `created_at`      | timestamp          | Waktu dibuat                      |
| `updated_at`      | timestamp          | Waktu terakhir diupdate           |

### Data Aktual Otomatis Diambil:

Command ini juga mengambil data aktual dari transaksi yang sudah terjadi:

```php
// Untuk sales prediction
$actual = OutgoingItem::where('item_id', $item->id)
    ->whereYear('outgoing_date', $monthDate->year)
    ->whereMonth('outgoing_date', $monthDate->month)
    ->sum('quantity');

// Untuk restock prediction
$actual = IncomingItem::where('item_id', $item->id)
    ->whereYear('incoming_date', $monthDate->year)
    ->whereMonth('incoming_date', $monthDate->month)
    ->sum('quantity');
```

---

## Contoh Flow Lengkap

### 1. User Menjalankan Command:

```bash
php artisan predict:backfill
```

### 2. Command Mengambil Data:

-   Bulan-bulan yang ada transaksi: `['2025-01', '2025-02', '2025-03']`
-   Produk: `['Produk A', 'Produk B', 'Produk C']`
-   Total prediksi yang akan dibuat: 3 bulan × 3 produk × 2 tipe = **18 prediksi**

### 3. Untuk Setiap Kombinasi (Produk + Bulan + Tipe):

```
Bulan: 2025-01
Produk: Produk A (ID: 1)
Tipe: sales

1. Hitung avg_monthly = AVG(quantity) dari outgoing_items sebelum 2025-01
   → Hasil: 150.5

2. Panggil Python:
   Linux:   .venv/bin/python scripts/predict.py --product 1 --type sales --avg-monthly 150.5
   Windows: .venv\Scripts\python.exe scripts\predict.py --product 1 --type sales --avg-monthly 150.5

3. Python mengembalikan:
   PREDICTION_RESULT: {"success": true, "prediction": 145.32, ...}

4. Parse hasil dan ambil actual data:
   actual = SUM(quantity) dari outgoing_items di bulan 2025-01 untuk produk ID 1
   → Hasil: 142

5. Simpan ke database:
   INSERT INTO stock_predictions (
       item_id=1,
       product='Produk A',
       month='2025-01-01',
       prediction_type='sales',
       prediction=145.32,
       actual=142
   )
```

### 4. Output Command:

```
Starting backfill prediction process...
Found 3 month(s) with transaction data.
Found 3 item(s) to process.

 18/18 [============================] 100%

Backfill prediction completed!
+----------------------------+-------+
| Status                     | Count |
+----------------------------+-------+
| New Predictions            | 18    |
| Skipped (Already exists)   | 0     |
| Failed                     | 0     |
| Total Processed            | 18    |
+----------------------------+-------+
```

---

## Keunggulan Implementasi

### ✅ Cross-Platform

-   Otomatis deteksi OS (Windows/Linux/Mac)
-   Path separator yang benar
-   Command yang sesuai platform

### ✅ Virtual Environment Support

-   Prioritas menggunakan venv jika ada
-   Fallback ke system Python
-   Logging untuk debugging

### ✅ Data Persistence

-   Semua hasil tersimpan ke database
-   Update jika sudah ada (dengan `updateOrCreate`)
-   Data actual otomatis di-link

### ✅ Error Handling

-   Skip jika tidak ada data historis
-   Skip jika prediction gagal
-   Progress bar untuk monitoring
-   Summary report di akhir

### ✅ Performance

-   Batch processing semua bulan dan produk
-   Skip prediction yang sudah ada (kecuali --force)
-   Efficient database queries

---

## Verifikasi Data di Database

### Query untuk Cek Hasil:

```sql
-- Lihat semua prediksi yang tersimpan
SELECT
    id,
    product,
    month,
    prediction_type,
    prediction,
    actual,
    created_at
FROM stock_predictions
ORDER BY month DESC, product ASC;

-- Hitung akurasi per produk
SELECT
    product,
    prediction_type,
    AVG(
        CASE
            WHEN actual IS NOT NULL AND (prediction > 0 OR actual > 0)
            THEN (1 - ABS(prediction - actual) / GREATEST(prediction, actual)) * 100
            ELSE NULL
        END
    ) as avg_accuracy
FROM stock_predictions
WHERE actual IS NOT NULL
GROUP BY product, prediction_type;

-- Lihat prediksi vs aktual untuk bulan tertentu
SELECT
    product,
    prediction_type,
    prediction,
    actual,
    ROUND(
        (1 - ABS(prediction - actual) / GREATEST(prediction, actual)) * 100,
        2
    ) as accuracy_percent
FROM stock_predictions
WHERE month = '2025-01-01'
AND actual IS NOT NULL
ORDER BY product, prediction_type;
```

---

## Testing

### Test di Linux/Mac:

```bash
# Dengan virtual environment
cd scripts
python3 -m venv .venv
source .venv/bin/activate
pip install -r requirements.txt
cd ..
php artisan predict:backfill

# Cek database
php artisan tinker
>>> StockPrediction::count()
>>> StockPrediction::latest()->first()
```

### Test di Windows:

```cmd
# Dengan virtual environment
cd scripts
python -m venv .venv
.venv\Scripts\activate
pip install -r requirements.txt
cd ..
php artisan predict:backfill

# Cek database
php artisan tinker
>>> StockPrediction::count()
>>> StockPrediction::latest()->first()
```

---

## Kesimpulan

| Pertanyaan                         | Jawaban                             |
| ---------------------------------- | ----------------------------------- |
| **Apakah memanggil predict.py?**   | ✅ YA                               |
| **Mendukung virtual environment?** | ✅ YA                               |
| **Mendukung Windows?**             | ✅ YA                               |
| **Hasil disimpan ke database?**    | ✅ YA, ke tabel `stock_predictions` |
| **Data actual otomatis diambil?**  | ✅ YA, dari incoming/outgoing items |
| **Cross-platform?**                | ✅ YA, Linux/Mac/Windows            |
