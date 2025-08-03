# Queue Worker Management Guide

## ❓ Pertanyaan: Apakah Queue Worker Harus Jalan Terus?

**Jawaban: YA!** Queue worker adalah daemon process yang harus berjalan terus menerus agar job dapat diproses.

## 🔄 Cara Kerja Queue System

```
User Request → Job Dispatched → Queue → Worker Process → Completion
```

Jika worker tidak berjalan:

-   ❌ Job akan menumpuk di queue
-   ❌ Model training tidak akan dijalankan
-   ❌ Status akan stuck di "queued"

## 🛠️ Management Commands

### 1. Artisan Commands (Baru)

```bash
# Check status
php artisan queue:manage status

# Start worker
php artisan queue:manage start

# Stop worker
php artisan queue:manage stop

# Restart worker
php artisan queue:manage restart
```

### 2. Manual Commands

```bash
# Start worker manually
php artisan queue:work --queue=model-training --timeout=600 --sleep=3

# Start in background (Linux/Mac)
nohup php artisan queue:work --queue=model-training --timeout=600 --sleep=3 > /dev/null 2>&1 &

# Kill all workers
pkill -f "queue:work"
```

### 3. Web Interface

-   ✅ Real-time worker status di `/predictions`
-   🟢 Green = Worker Active
-   🔴 Red = Worker Inactive
-   🟡 Yellow = Status Unknown

## 📋 Development Workflow

### Option 1: Manual (Terminal Terpisah)

```bash
# Terminal 1: Laravel Server
php artisan serve

# Terminal 2: Queue Worker
php artisan queue:work --queue=model-training --timeout=600 --sleep=3
```

### Option 2: Background Process

```bash
# Start worker in background
php artisan queue:manage start

# Continue development
php artisan serve

# Stop when done
php artisan queue:manage stop
```

## 🚀 Production Setup

### Option 1: Supervisor (Recommended)

```bash
# Install supervisor
sudo apt install supervisor

# Create config file
sudo nano /etc/supervisor/conf.d/laravel-worker.conf
```

**Config content:**

```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/project/artisan queue:work --queue=model-training --sleep=3 --tries=3 --timeout=600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/path/to/project/storage/logs/worker.log
```

**Supervisor commands:**

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-worker:*
sudo supervisorctl status
```

### Option 2: Systemd Service

```bash
# Create service file
sudo nano /etc/systemd/system/laravel-worker.service
```

**Service content:**

```ini
[Unit]
Description=Laravel queue worker
After=network.target

[Service]
User=www-data
Group=www-data
Restart=always
ExecStart=/usr/bin/php /path/to/project/artisan queue:work --queue=model-training --sleep=3 --tries=3 --timeout=600

[Install]
WantedBy=multi-user.target
```

**Systemd commands:**

```bash
sudo systemctl daemon-reload
sudo systemctl enable laravel-worker
sudo systemctl start laravel-worker
sudo systemctl status laravel-worker
```

## 📊 Monitoring & Troubleshooting

### Check Status

```bash
# Via command
php artisan queue:manage status

# Via API
curl http://localhost:8000/predictions/worker-status

# Manual check
ps aux | grep queue:work
```

### Common Issues

#### 1. Worker Died

**Symptoms:** Jobs stuck in queue, no processing
**Solution:**

```bash
php artisan queue:manage restart
```

#### 2. Memory Issues

**Symptoms:** Worker stops after some time
**Solution:** Add memory limit

```bash
php artisan queue:work --memory=512 --queue=model-training
```

#### 3. Long Running Jobs

**Symptoms:** Worker timeout
**Solution:** Increase timeout

```bash
php artisan queue:work --timeout=1200 --queue=model-training
```

### Logs Monitoring

```bash
# Laravel logs
tail -f storage/logs/laravel.log | grep -E "(queue|training)"

# Worker logs (if using supervisor)
tail -f storage/logs/worker.log

# System logs
sudo journalctl -u laravel-worker -f
```

## ⚡ Quick Setup untuk Development

```bash
# 1. Start worker in background
php artisan queue:manage start

# 2. Verify it's running
php artisan queue:manage status

# 3. Start Laravel server
php artisan serve

# 4. Test model training di browser
# http://localhost:8000/predictions

# 5. Stop worker when done
php artisan queue:manage stop
```

## 🔧 Best Practices

### 1. Always Monitor

-   ✅ Use web interface status widget
-   ✅ Set up alerts for worker downtime
-   ✅ Monitor queue size

### 2. Graceful Restart

```bash
# Instead of kill -9
php artisan queue:restart
```

### 3. Resource Management

```bash
# Limit memory usage
--memory=512

# Limit job execution time
--timeout=600

# Limit worker lifetime
--max-time=3600
```

### 4. Error Handling

```bash
# Retry failed jobs
php artisan queue:retry all

# Clear failed jobs
php artisan queue:flush --failed

# Monitor failed jobs
php artisan queue:failed
```

## 📱 Quick Reference

| Action             | Command                                         |
| ------------------ | ----------------------------------------------- |
| **Check Status**   | `php artisan queue:manage status`               |
| **Start Worker**   | `php artisan queue:manage start`                |
| **Stop Worker**    | `php artisan queue:manage stop`                 |
| **Restart Worker** | `php artisan queue:manage restart`              |
| **Manual Start**   | `php artisan queue:work --queue=model-training` |
| **Kill All**       | `pkill -f "queue:work"`                         |
| **Check Queue**    | `php artisan queue:size model-training`         |
| **Web Status**     | `/predictions/worker-status`                    |

**🎯 Bottom Line:** Queue worker HARUS berjalan terus untuk memproses job. Gunakan supervisor/systemd untuk production, atau command management untuk development.
