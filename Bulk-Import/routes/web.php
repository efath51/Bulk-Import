<?php

use App\Http\Controllers\BulkImportController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');


Route::prefix('bulk-import')->name('bulk-import.')->group(function () {
    Route::get('/', [BulkImportController::class, 'index'])->name('index');
    Route::post('/upload-dataset', [BulkImportController::class, 'uploadDataset'])->name('upload');
    Route::get('/wizard',        [BulkImportController::class, 'wizard'])->name('wizard');
    Route::get('/current-item', [BulkImportController::class, 'getCurrentItem'])->name('current-item');
    Route::post('/cancel', [BulkImportController::class, 'cancel'])->name('cancel');
    Route::get('/wizard/item/{index}', [BulkImportController::class, 'getItemByIndex'])->name('item');
    Route::post('/load-dataset',    [BulkImportController::class, 'loadDataset'])->name('load');

    Route::post('/save-item', [BulkImportController::class, 'saveItem'])->name('save-item');

    // datasets
    Route::get('/datasets', [BulkImportController::class, 'datasets'])->name('datasets');
    Route::delete('/datasets/{filename}', [BulkImportController::class, 'deleteDataset'])->name('datasets.delete');
    Route::get('/datasets/{filename}/preview', [BulkImportController::class, 'previewDataset'])->name('datasets.preview');


    Route::get('/parser-config',  [BulkImportController::class, 'getParserConfigs'])->name('parser-config.index');
    Route::post('/parser-config', [BulkImportController::class, 'saveParserConfig'])->name('parser-config.save');
});
