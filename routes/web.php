<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\IncomingItemController;
use App\Http\Controllers\OutgoingItemController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\AnalysisController;

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

// Categories
Route::resource('categories', CategoryController::class);

// Items
Route::resource('items', ItemController::class);

// Incoming Items
Route::get('incoming_items/export', [IncomingItemController::class, 'export'])->name('incoming_items.export');
Route::get('incoming_items/template', [IncomingItemController::class, 'template'])->name('incoming_items.template');
Route::resource('incoming_items', IncomingItemController::class);

// Outgoing Items
Route::get('outgoing_items/export', [OutgoingItemController::class, 'export'])->name('outgoing_items.export');
Route::get('outgoing_items/template', [OutgoingItemController::class, 'template'])->name('outgoing_items.template');
Route::resource('outgoing_items', OutgoingItemController::class);

// Analysis routes
Route::prefix('analysis')->name('analysis.')->group(function () {
    Route::get('/apriori-process', [AnalysisController::class, 'aprioriProcess'])->name('apriori-process');
});

// Reports
Route::prefix('reports')->name('reports.')->group(function () {
    Route::get('/', [ReportController::class, 'index'])->name('index');
    Route::get('/incoming-items', [ReportController::class, 'incomingReport'])->name('incoming');
    Route::get('/outgoing-items', [ReportController::class, 'outgoingReport'])->name('outgoing');
    Route::get('/stock-report', [ReportController::class, 'stockReport'])->name('stock');
    Route::get('/summary', [ReportController::class, 'summaryReport'])->name('summary');
});
