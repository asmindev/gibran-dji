# Windows PowerShell Support

## Overview

Aplikasi sekarang mendukung eksekusi script Python di Windows PowerShell selain Linux/Unix.

## Perubahan yang Dilakukan

### 1. Method `callNewPythonPredict`

-   ✅ **Deteksi OS**: Menggunakan `PHP_OS` untuk mendeteksi Windows vs Linux
-   ✅ **Windows Command**: Menggunakan PowerShell dengan sintaks yang benar
-   ✅ **Virtual Environment**: Support untuk `.venv/Scripts/Activate.ps1` di Windows
-   ✅ **Fallback**: Jika venv tidak ada, gunakan system Python

**Windows Command Format:**

```powershell
powershell -Command "& { . 'path/to/.venv/Scripts/Activate.ps1'; cd 'scripts'; python predict.py --product 1 --type sales --avg-monthly 10 }"
```

**Linux Command Format:**

```bash
cd /path/scripts && source .venv/bin/activate && python predict.py --product 1 --type sales --avg-monthly 10
```

### 2. Method `callPythonTrain`

-   ✅ **Deteksi OS**: Same OS detection logic
-   ✅ **Windows Execution**: Menggunakan `exec()` untuk Windows (lebih simple)
-   ✅ **Linux Execution**: Tetap menggunakan `proc_open()` dengan timeout
-   ✅ **Virtual Environment**: Support untuk Windows dan Linux

**Windows Command Format:**

```powershell
powershell -Command "& { . 'path/to/.venv/Scripts/Activate.ps1'; cd 'scripts'; python -u train_model.py }"
```

## Struktur Virtual Environment

### Linux/Unix

```
scripts/
├── .venv/
│   ├── bin/
│   │   └── activate
│   └── ...
```

### Windows

```
scripts/
├── .venv/
│   ├── Scripts/
│   │   └── Activate.ps1
│   └── ...
```

## Testing

### Untuk testing di Windows:

1. Pastikan PowerShell dapat menjalankan script
2. Set execution policy jika diperlukan: `Set-ExecutionPolicy -ExecutionPolicy RemoteSigned -Scope CurrentUser`
3. Test predict: buka halaman prediksi dan jalankan prediksi
4. Test training: klik tombol "Train Model"

### Untuk testing di Linux:

Tidak ada perubahan, semua command tetap sama seperti sebelumnya.

## Log Output

Sistem akan mencatat OS yang terdeteksi dalam log:

-   `Using Python virtual environment on Windows: ...`
-   `Using Python virtual environment on Linux: ...`
-   `Virtual environment not found on Windows/Linux, using system Python`

## Error Handling

-   Jika PowerShell tidak tersedia di Windows, akan ada error execution
-   Jika Python tidak ada di PATH, akan ada error command not found
-   Semua error akan dicatat di Laravel log dengan detail lengkap
