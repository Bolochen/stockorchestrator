<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Warehouse;
use App\Services\StockQueryService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function stockQueryProduct(array $overrides = []): Product
{
    $category = Category::create(['name' => 'General']);

    return Product::create(array_merge([
        'sku' => fake()->unique()->bothify('SKU###'),
        'name' => fake()->words(2, true),
        'category_id' => $category->id,
        'unit' => 'pcs',
        'minimum_stock' => 5,
        'is_active' => true,
    ], $overrides));
}

function stockQueryWarehouse(array $overrides = []): Warehouse
{
    return Warehouse::create(array_merge([
        'code' => fake()->unique()->bothify('W###'),
        'name' => fake()->unique()->bothify('Warehouse ###'),
        'location' => 'Jakarta',
    ], $overrides));
}

test('it lists current stock for active products only', function () {
    $service = app(StockQueryService::class);
    $warehouse = stockQueryWarehouse();
    $activeProduct = stockQueryProduct(['sku' => 'ACTIVE001']);
    $inactiveProduct = stockQueryProduct(['sku' => 'INACTIVE001', 'is_active' => false]);

    Stock::create([
        'warehouse_id' => $warehouse->id,
        'product_id' => $activeProduct->id,
        'quantity' => 10,
        'average_cost' => '100.00',
        'total_value' => '1000.00',
    ]);
    Stock::create([
        'warehouse_id' => $warehouse->id,
        'product_id' => $inactiveProduct->id,
        'quantity' => 10,
        'average_cost' => '100.00',
        'total_value' => '1000.00',
    ]);

    $stocks = $service->getCurrentStock();

    expect($stocks)->toHaveCount(1)
        ->and($stocks->first()->product_id)->toBe($activeProduct->id);
});

test('it finds low stock items for a warehouse', function () {
    $service = app(StockQueryService::class);
    $warehouse = stockQueryWarehouse();
    $otherWarehouse = stockQueryWarehouse();
    $product = stockQueryProduct(['minimum_stock' => 10]);

    Stock::create([
        'warehouse_id' => $warehouse->id,
        'product_id' => $product->id,
        'quantity' => 8,
        'average_cost' => '100.00',
        'total_value' => '800.00',
    ]);
    Stock::create([
        'warehouse_id' => $otherWarehouse->id,
        'product_id' => $product->id,
        'quantity' => 12,
        'average_cost' => '100.00',
        'total_value' => '1200.00',
    ]);

    $items = $service->getLowStockItems($warehouse->id);

    expect($items)->toHaveCount(1)
        ->and($items->first()->warehouse_id)->toBe($warehouse->id);
});

test('it summarizes stock by warehouse', function () {
    $service = app(StockQueryService::class);
    $warehouse = stockQueryWarehouse();
    $firstProduct = stockQueryProduct(['sku' => 'SUM001']);
    $secondProduct = stockQueryProduct(['sku' => 'SUM002']);

    Stock::create([
        'warehouse_id' => $warehouse->id,
        'product_id' => $firstProduct->id,
        'quantity' => 4,
        'average_cost' => '50.00',
        'total_value' => '200.00',
    ]);
    Stock::create([
        'warehouse_id' => $warehouse->id,
        'product_id' => $secondProduct->id,
        'quantity' => 6,
        'average_cost' => '75.00',
        'total_value' => '450.00',
    ]);

    $summary = $service->getStockSummaryByWarehouse($warehouse->id);

    expect($summary)->toMatchArray([
        'warehouse_id' => $warehouse->id,
        'total_items' => 2,
        'total_quantity' => 10,
        'total_value' => '650.00',
    ]);
});
