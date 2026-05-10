<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\WarehouseController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::inertia('/', 'welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard', [
            'productCount' => 120,
        ]);
    })->name('dashboard');

    Route::resource('warehouses', WarehouseController::class);
    Route::resource('categories', CategoryController::class);
    Route::resource('products', ProductController::class);
    Route::get('stocks/warehouse/{warehouseId}', [StockController::class, 'warehouse'])->name('stocks.warehouse');
    Route::get('stocks/product/{productId}', [StockController::class, 'product'])->name('stocks.product');
    Route::get('stocks/low-stock', [StockController::class, 'lowStock'])->name('stocks.low-stock');
    Route::get('stocks/summary/{warehouseId}', [StockController::class, 'summary'])->name('stocks.summary');
    Route::post('stocks/movements/{movement}/post', [StockController::class, 'postMovement'])->name('stocks.movements.post');
    Route::post('stocks/movements/{movement}/cancel', [StockController::class, 'cancelMovement'])->name('stocks.movements.cancel');
    Route::post('stocks/transfers', [StockController::class, 'transfer'])->name('stocks.transfers.store');
    Route::post('stocks/valuation-preview', [StockController::class, 'valuationPreview'])->name('stocks.valuation-preview');
    Route::resource('stocks', StockController::class);
});

require __DIR__.'/settings.php';
