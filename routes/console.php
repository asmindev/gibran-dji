<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule model training every day at 3:50 PM (for immediate testing)
Schedule::command('model:train')->dailyAt('15:52')->name('daily-model-training');

// Original schedule (15:48)
// Schedule::command('model:train')->dailyAt('15:48')->name('daily-model-training');

// Alternative schedules (uncomment as needed):
// Schedule::command('model:train')->weeklyOn(0, '01:00')->name('weekly-model-training');
// Schedule::command('model:train')->monthlyOn(1, '01:00')->name('monthly-model-training');
