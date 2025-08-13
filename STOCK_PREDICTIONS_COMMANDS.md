# Stock Predictions Management Commands

Setelah database dikosongkan dan Anda memiliki data OutgoingItem dan IncomingItem dari bulan Mei hingga Agustus, berikut adalah command-command yang tersedia untuk mengelola prediksi stok:

## 1. Backfill Stock Predictions

Command untuk mengisi data prediksi historis:

```bash
# Mengisi data prediksi dari Mei hingga Agustus 2025 (default)
php artisan predictions:backfill

# Mengisi data untuk periode tertentu
php artisan predictions:backfill --start-month=5 --start-year=2025 --end-month=8 --end-year=2025

# Dry run - melihat apa yang akan dilakukan tanpa menyimpan data
php artisan predictions:backfill --dry-run

# Force overwrite data yang sudah ada
php artisan predictions:backfill --force

# Contoh untuk bulan spesifik
php artisan predictions:backfill --start-month=6 --end-month=6 --start-year=2025 --end-year=2025
```

### Fitur Command Backfill:

-   **Perhitungan Prediksi**: Menggunakan rata-rata penjualan 3 bulan sebelumnya dengan faktor musiman
-   **Perhitungan Actual**: Mengambil data penjualan aktual dari OutgoingItems
-   **Progress Bar**: Menampilkan progress saat pemrosesan
-   **Error Handling**: Menangani error dan melanjutkan proses
-   **Statistics**: Menampilkan statistik lengkap setelah selesai

## 2. Prediction Report

Command untuk melihat laporan analisis prediksi:

```bash
# Laporan lengkap semua data
php artisan predictions:report

# Laporan bulan tertentu
php artisan predictions:report --month=8 --year=2025

# Laporan tahun tertentu
php artisan predictions:report --year=2025

# Laporan produk tertentu
php artisan predictions:report --product="Jersey"

# Mengurutkan hasil
php artisan predictions:report --sort=accuracy  # berdasarkan akurasi (default)
php artisan predictions:report --sort=product   # berdasarkan nama produk
php artisan predictions:report --sort=month     # berdasarkan bulan

# Membatasi hasil
php artisan predictions:report --limit=10
```

### Fitur Laporan:

-   **Summary Statistics**: Total prediksi, actual, akurasi rata-rata
-   **Detailed Table**: Data lengkap per produk per bulan
-   **Accuracy Distribution**: Distribusi akurasi dalam rentang persentase
-   **Product Performance**: Ranking performa per produk

## 3. Monthly Automation (Existing)

Command untuk automasi bulanan:

```bash
# Membuat prediksi untuk bulan depan
php artisan predictions:monthly-automation --type=predict

# Menghitung penjualan aktual bulan ini
php artisan predictions:monthly-automation --type=calculate

# Kedua operasi sekaligus
php artisan predictions:monthly-automation --type=both
```

## Contoh Workflow

### Setelah Database Dikosongkan dan Data Seed:

1. **Isi data prediksi historis**:

    ```bash
    php artisan predictions:backfill
    ```

2. **Lihat laporan keseluruhan**:

    ```bash
    php artisan predictions:report
    ```

3. **Analisis bulan tertentu**:

    ```bash
    php artisan predictions:report --month=8 --year=2025
    ```

4. **Lihat performa produk tertentu**:
    ```bash
    php artisan predictions:report --product="Jersey" --sort=accuracy
    ```

## Hasil yang Diperoleh

Berdasarkan data yang telah diisi (Mei-Agustus 2025):

-   **Total Predictions**: 32 records (8 produk × 4 bulan)
-   **Overall Accuracy**: 44.28%
-   **Best Performing Product**: Tali Sepatu Kipzkapz (56.6% accuracy)
-   **Worst Performing Month**: Agustus 2025 (30.84% accuracy)

### Insight dari Data:

1. **Prediksi Mei**: Akurasi rendah karena tidak ada data sebelumnya (menggunakan default 5)
2. **Prediksi Juni-Juli**: Akurasi lebih baik setelah ada data historis
3. **Prediksi Agustus**: Akurasi menurun, kemungkinan karena faktor musiman

## Scheduled Tasks

Command automasi sudah terjadwal di `routes/console.php`:

-   **Tanggal 1 pukul 09:00**: Membuat prediksi untuk bulan depan
-   **Tanggal 30 pukul 23:00**: Menghitung penjualan aktual bulan ini

## Tips Penggunaan

1. **Gunakan dry-run** sebelum menjalankan backfill untuk data besar
2. **Analisis laporan** secara berkala untuk memahami pola prediksi
3. **Monitor akurasi** per produk untuk perbaikan model prediksi
4. **Backup data** sebelum menggunakan `--force` option
