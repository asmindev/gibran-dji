# Fitur Waktu Eksekusi Prediksi

Dokumentasi ini menjelaskan fitur baru yang menampilkan waktu eksekusi pada hasil prediksi stock.

## Overview

Sistem prediksi sekarang menampilkan informasi waktu eksekusi yang detail untuk membantu memantau performa model dan memberikan transparansi kepada pengguna tentang seberapa cepat prediksi diproses.

## Fitur yang Ditambahkan

### 1. **Tampilan Waktu Eksekusi Utama**

-   **Lokasi**: Card "Waktu Eksekusi" di hasil prediksi
-   **Format**: Otomatis menyesuaikan (ms/detik)
-   **Indikator Performa**: Icon dan label berdasarkan kecepatan

### 2. **Detail Performa (Jika Tersedia)**

-   **Waktu Model**: Waktu yang dibutuhkan model untuk prediksi
-   **Total Waktu**: Waktu keseluruhan termasuk overhead
-   **Overhead**: Selisih antara total waktu dengan waktu model
-   **Tipe Prediksi**: Daily atau Monthly

### 3. **Kategorisasi Performa**

| Waktu Eksekusi | Icon | Label               | Warna   |
| -------------- | ---- | ------------------- | ------- |
| < 100ms        | 🚀   | Sangat Cepat        | Hijau   |
| 100-500ms      | ⚡   | Cepat               | Hijau   |
| 500-1000ms     | ⚡   | Normal              | Kuning  |
| > 1000ms       | 🐌   | Lambat              | Orange  |
| Tidak tersedia | ❓   | Data tidak tersedia | Abu-abu |

## Implementasi Frontend

### File yang Dimodifikasi

-   `resources/views/predictions/results.blade.php`

### Fungsi JavaScript

```javascript
window.showPredictionResults = function (prediction) {
    // Memformat dan menampilkan waktu eksekusi
    const executionTime = prediction.execution_time_ms || 0;
    let executionDisplay = `${executionTime} ms`;

    // Konversi ke detik jika > 1000ms
    if (executionTime > 1000) {
        executionDisplay = `${(executionTime / 1000).toFixed(2)} detik`;
    }

    // Menampilkan indikator performa
    // ...
};
```

## Implementasi Backend

### Data yang Dikirim dari Controller

```php
// StockPredictionController.php
$result = [
    'success' => true,
    'prediction' => $prediction,
    'execution_time_ms' => $executionTime,      // Total waktu eksekusi
    'model_prediction_time_ms' => $modelTime,   // Waktu prediksi model saja
    'timestamp' => now()->toISOString(),        // Waktu prediksi dibuat
    // ... data lainnya
];
```

### Sumber Data Waktu Eksekusi

Data waktu eksekusi diperoleh dari:

1. **Python Script**: `stock_predictor.py` yang sudah diupdate untuk mengembalikan timing information
2. **Controller**: Mengekstrak data timing dari response Python
3. **Frontend**: Memformat dan menampilkan dengan UI yang user-friendly

## Contoh Tampilan

### 1. **Card Waktu Eksekusi Normal**

```
⚡ Waktu Eksekusi
250 ms
Cepat
```

### 2. **Card Waktu Eksekusi Lambat**

```
🐌 Waktu Eksekusi
1.25 detik
Lambat
```

### 3. **Detail Performa (Expanded)**

```
🔬 Detail Performa
Waktu Model:     180 ms
Total Waktu:     250 ms
Overhead:        70 ms
Tipe Prediksi:   daily
```

## Manfaat Fitur

### 1. **Transparansi Performa**

-   User dapat melihat seberapa cepat sistem memproses prediksi
-   Membantu dalam menilai responsivitas aplikasi

### 2. **Monitoring dan Debugging**

-   Developer dapat memonitor performa model
-   Membantu identifikasi bottleneck dalam proses prediksi

### 3. **User Experience**

-   Memberikan feedback visual tentang proses yang sedang berjalan
-   Indikator performa membantu user memahami kualitas response time

### 4. **Optimisasi Performa**

-   Data timing membantu dalam optimisasi model dan infrastructure
-   Dapat digunakan untuk benchmark improvements

## Konfigurasi dan Customization

### Mengubah Threshold Performa

```javascript
// Di results.blade.php, section formatting execution time
if (executionTime > 1000) {
    // Threshold untuk "Lambat"
    performanceIcon = "🐌";
    performanceText = "Lambat";
} else if (executionTime > 500) {
    // Threshold untuk "Normal"
    performanceIcon = "⚡";
    performanceText = "Normal";
} else if (executionTime > 100) {
    // Threshold untuk "Cepat"
    performanceIcon = "⚡";
    performanceText = "Cepat";
}
```

### Mengubah Format Tampilan

```javascript
// Customisasi format waktu
if (executionTime > 1000) {
    executionDisplay = `${(executionTime / 1000).toFixed(2)} detik`;
} else {
    executionDisplay = `${executionTime} ms`;
}
```

## Troubleshooting

### 1. **Waktu Eksekusi Tidak Muncul**

-   **Penyebab**: Data `execution_time_ms` tidak dikirim dari backend
-   **Solusi**: Pastikan Python script mengembalikan timing data yang benar

### 2. **Waktu Eksekusi Selalu 0**

-   **Penyebab**: Error dalam ekstraksi data timing dari Python output
-   **Solusi**: Check log Laravel untuk error dalam parsing response Python

### 3. **Format Waktu Tidak Sesuai**

-   **Penyebab**: Threshold atau formatting logic perlu disesuaikan
-   **Solusi**: Modifikasi logic di JavaScript sesuai kebutuhan

## Future Enhancements

### 1. **Historical Performance Tracking**

-   Menyimpan data timing ke database
-   Membuat grafik performa dari waktu ke waktu

### 2. **Performance Alerts**

-   Notifikasi jika waktu eksekusi melebihi threshold tertentu
-   Auto-scaling berdasarkan response time

### 3. **Detailed Breakdown**

-   Breakdown waktu per tahap (preprocessing, model inference, postprocessing)
-   Memory usage monitoring

### 4. **Comparative Analysis**

-   Perbandingan performa daily vs monthly prediction
-   Benchmark dengan different model configurations

Fitur waktu eksekusi ini memberikan insight valuable tentang performa sistem dan meningkatkan transparansi untuk end users.
