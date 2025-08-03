#!/bin/bash

echo "=== LARAVEL SCHEDULER STATUS CHECK ==="
echo "Current time: $(date)"
echo ""

cd /home/labubu/Projects/gibran

echo "1. Scheduled Tasks List:"
php artisan schedule:list
echo ""

echo "2. Running Schedule (manually):"
php artisan schedule:run
echo ""

echo "3. Queue Status:"
php artisan queue:work --once
echo ""

echo "4. Recent Laravel Logs (last 20 lines):"
tail -20 storage/logs/laravel.log 2>/dev/null || echo "No Laravel log found"
echo ""

echo "5. Check if cron is running:"
ps aux | grep cron | grep -v grep
echo ""

echo "6. Check crontab entries:"
crontab -l 2>/dev/null || echo "No crontab entries found"
echo ""

echo "=== END CHECK ==="
