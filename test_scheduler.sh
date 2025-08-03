#!/bin/bash

# Test Scheduler - Simulate scheduled execution
cd /home/labubu/Projects/gibran

echo "=== TESTING SCHEDULED MODEL TRAINING ==="
echo "Current time: $(date)"
echo ""

echo "1. Force running today's schedule (simulate 15:48):"
php artisan schedule:run --force
echo ""

echo "2. Check if model training runs:"
php artisan model:train
echo ""

echo "3. Monitor queue processing:"
echo "Starting queue worker for 10 seconds..."
timeout 10s php artisan queue:work || echo "Queue worker stopped"
echo ""

echo "4. Check recent logs:"
echo "--- Last 10 lines of Laravel log ---"
tail -10 storage/logs/laravel.log 2>/dev/null || echo "No log file found"
echo ""

echo "=== TEST COMPLETED ==="
echo "Schedule is set for daily at 15:48 (3:48 PM)"
echo "Next execution: Tomorrow at 15:48"
