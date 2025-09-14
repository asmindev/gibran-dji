<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\IncomingItemController;
use App\Http\Controllers\OutgoingItemController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StockPredictionController;
use App\Http\Controllers\AuthController;

// Authentication Routes
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Protected Routes (require authentication)
Route::middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Categories
    Route::resource('categories', CategoryController::class);

    // Items
    Route::resource('items', ItemController::class);

    // Incoming Items
    Route::get('incoming_items/export', [IncomingItemController::class, 'export'])->name('incoming_items.export');
    Route::get('incoming_items/template', [IncomingItemController::class, 'template'])->name('incoming_items.template');
    Route::get('incoming_items/import', [IncomingItemController::class, 'importForm'])->name('incoming_items.import.form');
    Route::post('incoming_items/import', [IncomingItemController::class, 'import'])->name('incoming_items.import');
    Route::resource('incoming_items', IncomingItemController::class);

    // Outgoing Items
    Route::get('outgoing_items/export', [OutgoingItemController::class, 'export'])->name('outgoing_items.export');
    Route::get('outgoing_items/template', [OutgoingItemController::class, 'template'])->name('outgoing_items.template');
    Route::get('outgoing_items/import', [OutgoingItemController::class, 'importForm'])->name('outgoing_items.import.form');
    Route::post('outgoing_items/import-preview', [OutgoingItemController::class, 'importPreview'])->name('outgoing_items.import.preview');
    Route::post('outgoing_items/import', [OutgoingItemController::class, 'import'])->name('outgoing_items.import');
    Route::resource('outgoing_items', OutgoingItemController::class);

    // Stock Predictions
    Route::prefix('predictions')->name('predictions.')->group(function () {
        Route::get('/', [StockPredictionController::class, 'index'])->name('index');
        Route::post('/predict', [StockPredictionController::class, 'predict'])->name('predict');
        Route::post('/generate-model', [StockPredictionController::class, 'generateModel'])->name('generate-model');
        Route::get('/training-status', [StockPredictionController::class, 'getTrainingStatus'])->name('training-status');
        Route::get('/worker-status', [StockPredictionController::class, 'getWorkerStatus'])->name('worker-status');
        Route::post('/update-actual', [StockPredictionController::class, 'updateActualData'])->name('update-actual');
        Route::get('/history', [StockPredictionController::class, 'getPredictionHistory'])->name('history');
        Route::delete('/{prediction}', [StockPredictionController::class, 'destroy'])->name('destroy');
    });

    // Reports
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('/incoming-items', [ReportController::class, 'incomingReport'])->name('incoming');
        Route::get('/outgoing-items', [ReportController::class, 'outgoingReport'])->name('outgoing');
        Route::get('/stock-report', [ReportController::class, 'stockReport'])->name('stock');
        Route::get('/summary', [ReportController::class, 'summaryReport'])->name('summary');
    });
}); // End of protected routes middleware group
