# Predict Backfill Command Guide

## Deskripsi

Command `predict:backfill` digunakan untuk melakukan prediksi secara otomatis untuk semua produk pada semua bulan yang memiliki data transaksi (incoming atau outgoing items).

## Penggunaan

### Command Dasar

```bash
php artisan predict:backfill
```

Command ini akan:

1. Mencari semua bulan yang memiliki data transaksi
2. Untuk setiap bulan dan setiap produk:
    - Menghitung rata-rata transaksi bulanan sebelum bulan tersebut
    - Menjalankan prediksi menggunakan model Python
    - Menyimpan hasil prediksi (sales & restock)
    - Menambahkan data aktual jika tersedia
3. Skip prediksi yang sudah ada (untuk menghindari duplikasi)

### Opsi: Force Re-prediction

```bash
php artisan predict:backfill --force
```

Opsi `--force` akan:

-   Menimpa prediksi yang sudah ada
-   Berguna jika Anda ingin memperbarui prediksi dengan model yang sudah di-retrain
-   Berguna jika data historis berubah

## Contoh Output

```
Starting backfill prediction process...
Found 6 month(s) with transaction data.
Found 15 item(s) to process.

 180/180 [============================] 100%

Backfill prediction completed!
+----------------------------+-------+
| Status                     | Count |
+----------------------------+-------+
| New Predictions            | 150   |
| Skipped (Already exists)   | 30    |
| Failed                     | 0     |
| Total Processed            | 180   |
+----------------------------+-------+
```

## Workflow

### 1. Persiapan Data

Pastikan Anda memiliki:

-   Item/produk di database
-   Data incoming items (transaksi masuk)
-   Data outgoing items (transaksi keluar)
-   Model Python yang sudah di-train

### 2. Jalankan Backfill

```bash
# Prediksi untuk bulan-bulan yang belum memiliki data prediksi
php artisan predict:backfill

# Atau untuk re-prediksi semua
php artisan predict:backfill --force
```

### 3. Hasil

Setelah command selesai, data prediksi akan tersimpan di tabel `stock_predictions` dengan kolom:

-   `item_id` - ID produk
-   `product` - Nama produk
-   `month` - Bulan prediksi
-   `prediction_type` - Tipe prediksi (sales/restock)
-   `prediction` - Nilai prediksi
-   `actual` - Nilai aktual (jika tersedia)

## Kapan Menggunakan Command Ini?

### Scenario 1: Initial Setup

Ketika pertama kali setup sistem prediksi dan memiliki data historis:

```bash
php artisan predict:backfill
```

### Scenario 2: Setelah Re-training Model

Ketika Anda sudah melatih ulang model dengan data terbaru:

```bash
php artisan predict:backfill --force
```

### Scenario 3: Menambah Data Historis Baru

Ketika Anda menambahkan data transaksi untuk bulan-bulan sebelumnya:

```bash
php artisan predict:backfill
```

## Integrasi dengan Dashboard

Setelah menjalankan command ini, dashboard akan menampilkan:

1. **Chart Analisis Stok Bulanan**

    - Total Prediksi (line chart biru)
    - Sales Aktual (line chart merah)

2. **Summary Cards**

    - Total Prediksi (unit)
    - Total Penjualan Aktual (unit)

3. **Akurasi Keseluruhan** (NEW!)
    - Persentase akurasi prediksi vs aktual
    - Label kualitas: Sangat Baik, Baik, Cukup, Perlu Ditingkatkan
    - Hanya ditampilkan jika data aktual tersedia

## Perhitungan Akurasi

Akurasi dihitung dengan formula:

```
accuracy = (1 - (|prediction - actual| / max(prediction, actual))) × 100
overall_accuracy = average(all_accuracies)
```

Interpretasi:

-   **≥ 85%**: Sangat Baik (Excellent)
-   **70-84%**: Baik (Good)
-   **50-69%**: Cukup (Fair)
-   **< 50%**: Perlu Ditingkatkan (Poor)

## Troubleshooting

### Command gagal dengan error "No transaction data found"

**Solusi**: Pastikan ada data di tabel `incoming_items` atau `outgoing_items`

### Command gagal dengan error "No items found"

**Solusi**: Pastikan ada data di tabel `items`

### Banyak predictions yang failed

**Solusi**:

1. Cek apakah model Python sudah di-train: `python scripts/train_model.py`
2. Cek apakah Python dependencies sudah terinstall: `pip install -r scripts/requirements.txt`
3. Cek log Python di `scripts/logs/`

### Akurasi tidak muncul di dashboard

**Solusi**:

1. Pastikan prediksi sudah di-generate untuk bulan yang dipilih
2. Pastikan ada data aktual (outgoing items) untuk bulan tersebut
3. Jalankan `predict:backfill` untuk generate prediksi

## Tips & Best Practices

1. **Jalankan secara periodik**: Setelah menambah data transaksi baru
2. **Re-train model secara berkala**: Untuk meningkatkan akurasi prediksi
3. **Monitor akurasi**: Gunakan dashboard untuk tracking performa model
4. **Backup sebelum --force**: Data prediksi lama akan ditimpa

## Hubungan dengan Command Lain

```bash
# 1. Train model terlebih dahulu
python scripts/train_model.py

# 2. Generate predictions untuk semua bulan historis
php artisan predict:backfill

# 3. Lihat hasil di dashboard
# Buka browser ke /dashboard
```

## FAQ

**Q: Apakah command ini mempengaruhi data produk atau transaksi?**
A: Tidak. Command ini hanya membaca data transaksi dan menulis ke tabel `stock_predictions`.

**Q: Berapa lama waktu yang dibutuhkan?**
A: Tergantung jumlah produk dan bulan. Sekitar 1-2 detik per prediksi.
Contoh: 15 produk × 6 bulan × 2 tipe = 180 prediksi ≈ 3-6 menit

**Q: Apakah bisa di-schedule untuk berjalan otomatis?**
A: Ya, tambahkan ke Laravel Scheduler di `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    // Jalankan setiap hari pukul 01:00
    $schedule->command('predict:backfill')
             ->dailyAt('01:00')
             ->withoutOverlapping();
}
```

**Q: Data prediksi disimpan dimana?**
A: Di tabel `stock_predictions` dengan struktur:

-   `id`, `item_id`, `product`, `month`, `prediction_type`, `prediction`, `actual`, `created_at`, `updated_at`
