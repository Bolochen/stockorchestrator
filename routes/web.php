<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
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
});

require __DIR__.'/settings.php';
