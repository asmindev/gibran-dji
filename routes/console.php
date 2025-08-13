<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule model training every day at 3:50 PM (for immediate testing)
Schedule::command('model:train')->dailyAt('15:52')->name('daily-model-training');

// Monthly Prediction Automation
// Create predictions for next month (run on 1st day of month at 9:00 AM)
Schedule::command('predictions:monthly-automation --type=predict')
    ->monthlyOn(1, '09:00')
    ->name('monthly-prediction-create')
    ->timezone('Asia/Jakarta');

// Calculate actual sales for current month (run on last day of month at 11:00 PM)
Schedule::command('predictions:monthly-automation --type=calculate')
    ->monthlyOn(30, '23:00')  // Run on 30th day
    ->name('monthly-prediction-calculate')
    ->timezone('Asia/Jakarta');

// Alternative: Run both operations on 1st of month
// Schedule::command('predictions:monthly-automation --type=both')
//     ->monthlyOn(1, '10:00')
//     ->name('monthly-prediction-both')
//     ->timezone('Asia/Jakarta');

// Original schedule (15:48)
// Schedule::command('model:train')->dailyAt('15:48')->name('daily-model-training');

// Alternative schedules (uncomment as needed):
// Schedule::command('model:train')->weeklyOn(0, '01:00')->name('weekly-model-training');
// Schedule::command('model:train')->monthlyOn(1, '01:00')->name('monthly-model-training');
