# Laravel Queue System untuk Model Training

## Overview

Sistem ini menggunakan Laravel Queue untuk menjalankan training model AI di background, memungkinkan:

-   Training non-blocking (user tidak perlu menunggu)
-   Scheduling otomatis (contoh: jam 1 malam setiap hari)
-   Retry mechanism jika gagal
-   Status monitoring real-time

## Komponen Utama

### 1. Job: `TrainStockPredictionModel`

**File**: `app/Jobs/TrainStockPredictionModel.php`

**Fitur**:

-   Timeout: 10 menit
-   Retry: 3 kali dengan backoff 30 detik
-   Queue khusus: `model-training`
-   Status tracking via Cache

**Proses**:

1. Export data ke CSV
2. Training model Python
3. Update status di Cache
4. Logging lengkap

### 2. Console Command: `model:train`

**File**: `app/Console/Commands/TrainModelCommand.php`

**Usage**:

```bash
# Training normal
php artisan model:train

# Force training (skip status check)
php artisan model:train --force
```

### 3. Scheduled Training

**File**: `routes/console.php`

**Default**: Setiap hari jam 1 pagi

```php
Schedule::command('model:train')->dailyAt('01:00');
```

**Alternatives**:

-   Weekly: `weeklyOn(0, '01:00')` (Minggu jam 1)
-   Monthly: `monthlyOn(1, '01:00')` (Tanggal 1 jam 1)

### 4. Status Monitoring

**Endpoint**: `GET /predictions/training-status`

**Status Values**:

-   `idle`: Belum ada training
-   `in_progress`: Training sedang berjalan
-   `completed`: Training selesai sukses
-   `failed`: Training gagal

### 5. Frontend Integration

**Real-time polling** setiap 2 detik untuk update status

## Setup dan Deployment

### 1. Queue Worker

```bash
# Development
php artisan queue:work --queue=model-training --timeout=600

# Production (dengan supervisor)
sudo supervisorctl start laravel-worker
```

### 2. Scheduler (Cron)

```bash
# Tambahkan ke crontab
* * * * * cd /path/to/laravel && php artisan schedule:run >> /dev/null 2>&1
```

### 3. Supervisor Configuration

**File**: `/etc/supervisor/conf.d/laravel-worker.conf`

```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/laravel/artisan queue:work --queue=model-training --sleep=3 --tries=3 --timeout=600
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/path/to/laravel/storage/logs/worker.log
```

## API Endpoints

### 1. Generate Model (Queue)

```http
POST /predictions/generate-model
Content-Type: application/json
X-CSRF-TOKEN: {token}

Response:
{
  "success": true,
  "message": "Model training telah dimulai di background...",
  "status": "queued"
}
```

### 2. Training Status

```http
GET /predictions/training-status

Response (in_progress):
{
  "status": "in_progress",
  "message": "Model training sedang berjalan di background",
  "elapsed_minutes": 2,
  "started_at": "2025-08-03T06:18:00Z"
}

Response (completed):
{
  "status": "completed",
  "message": "Model training telah selesai dengan sukses",
  "completed_at": "2025-08-03T06:20:12Z",
  "result": { ... }
}
```

## Cache Keys

-   `model_training_status`: Current status
-   `model_training_started_at`: Start timestamp
-   `model_training_completed_at`: Completion timestamp
-   `model_training_failed_at`: Failure timestamp
-   `model_training_result`: Success result data
-   `model_training_error`: Error message

## Monitoring dan Troubleshooting

### 1. Check Queue Status

```bash
# List failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all

# Clear failed jobs
php artisan queue:flush
```

### 2. Logs

```bash
# Laravel logs
tail -f storage/logs/laravel.log

# Queue worker logs (if using supervisor)
tail -f storage/logs/worker.log
```

### 3. Manual Testing

```bash
# Test command directly
php artisan model:train

# Test job processing
php artisan queue:work --queue=model-training --once
```

## Benefits

### ✅ Non-blocking

-   User tidak perlu menunggu training selesai
-   UI tetap responsive

### ✅ Schedulable

-   Training otomatis setiap hari/minggu/bulan
-   Tidak perlu manual intervention

### ✅ Reliable

-   Retry mechanism jika gagal
-   Status tracking untuk monitoring

### ✅ Scalable

-   Bisa dijalankan di worker terpisah
-   Multiple workers untuk load balancing

### ✅ User Friendly

-   Real-time status updates
-   Clear progress indication
