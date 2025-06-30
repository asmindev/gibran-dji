<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\IncomingItemController;
use App\Http\Controllers\OutgoingItemController;
use App\Http\Controllers\ReportController;

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

// Categories
Route::resource('categories', CategoryController::class);

// Items
Route::resource('items', ItemController::class);

// Incoming Items
Route::resource('incoming_items', IncomingItemController::class);

// Outgoing Items
Route::resource('outgoing_items', OutgoingItemController::class);

// Reports
Route::prefix('reports')->name('reports.')->group(function () {
    Route::get('/', [ReportController::class, 'index'])->name('index');
    Route::get('/incoming-items', [ReportController::class, 'incomingReport'])->name('incoming');
    Route::get('/outgoing-items', [ReportController::class, 'outgoingReport'])->name('outgoing');
    Route::get('/stock-report', [ReportController::class, 'stockReport'])->name('stock');
    Route::get('/summary', [ReportController::class, 'summaryReport'])->name('summary');
});
