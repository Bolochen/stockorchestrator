<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Services\StockMovementService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function stockMovementProduct(array $overrides = []): Product
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

function stockMovementWarehouse(array $overrides = []): Warehouse
{
    return Warehouse::create(array_merge([
        'code' => fake()->unique()->bothify('W###'),
        'name' => fake()->unique()->bothify('Warehouse ###'),
        'location' => 'Jakarta',
    ], $overrides));
}

test('restock creates stock and updates average cost', function () {
    $service = app(StockMovementService::class);
    $warehouse = stockMovementWarehouse();
    $product = stockMovementProduct();

    $movement = $service->createDraft([
        'movement_number' => 'RST001',
        'type' => 'RESTOCK',
        'warehouse_id' => $warehouse->id,
    ]);

    $service->addDetail($movement, [
        'product_id' => $product->id,
        'quantity' => 5,
        'unit_cost' => '120.00',
    ]);

    $posted = $service->post($movement);
    $stock = Stock::where('warehouse_id', $warehouse->id)
        ->where('product_id', $product->id)
        ->first();

    expect($posted->status)->toBe('POSTED')
        ->and($stock->quantity)->toBe(5)
        ->and($stock->average_cost)->toBe('120.00')
        ->and($stock->total_value)->toBe('600.00');
});

test('sale decreases stock using average cost', function () {
    $service = app(StockMovementService::class);
    $warehouse = stockMovementWarehouse();
    $product = stockMovementProduct();

    Stock::create([
        'warehouse_id' => $warehouse->id,
        'product_id' => $product->id,
        'quantity' => 10,
        'average_cost' => '100.00',
        'total_value' => '1000.00',
    ]);

    $movement = $service->createDraft([
        'movement_number' => 'SAL001',
        'type' => 'SALE',
        'warehouse_id' => $warehouse->id,
    ]);

    $detail = $service->addDetail($movement, [
        'product_id' => $product->id,
        'quantity' => 3,
    ]);

    $service->post($movement);
    $stock = Stock::where('warehouse_id', $warehouse->id)
        ->where('product_id', $product->id)
        ->first();

    expect($stock->quantity)->toBe(7)
        ->and($stock->total_value)->toBe('700.00')
        ->and($detail->fresh()->unit_cost)->toBe('100.00')
        ->and($detail->fresh()->total_cost)->toBe('300.00');
});

test('sale fails when stock is not enough', function () {
    $service = app(StockMovementService::class);
    $warehouse = stockMovementWarehouse();
    $product = stockMovementProduct();

    Stock::create([
        'warehouse_id' => $warehouse->id,
        'product_id' => $product->id,
        'quantity' => 2,
        'average_cost' => '100.00',
        'total_value' => '200.00',
    ]);

    $movement = $service->createDraft([
        'movement_number' => 'SAL002',
        'type' => 'SALE',
        'warehouse_id' => $warehouse->id,
    ]);

    $service->addDetail($movement, [
        'product_id' => $product->id,
        'quantity' => 3,
    ]);

    expect(fn () => $service->post($movement))
        ->toThrow(InvalidArgumentException::class, 'Stock quantity is not enough.');

    expect($movement->fresh()->status)->toBe('DRAFT');
});

test('transfer decreases source and increases destination', function () {
    $service = app(StockMovementService::class);
    $sourceWarehouse = stockMovementWarehouse(['code' => 'SRC']);
    $destinationWarehouse = stockMovementWarehouse(['code' => 'DST']);
    $product = stockMovementProduct();

    Stock::create([
        'warehouse_id' => $sourceWarehouse->id,
        'product_id' => $product->id,
        'quantity' => 10,
        'average_cost' => '100.00',
        'total_value' => '1000.00',
    ]);

    $movements = $service->transfer($sourceWarehouse->id, $destinationWarehouse->id, [
        [
            'product_id' => $product->id,
            'quantity' => 4,
        ],
    ]);

    $sourceStock = Stock::where('warehouse_id', $sourceWarehouse->id)
        ->where('product_id', $product->id)
        ->first();
    $destinationStock = Stock::where('warehouse_id', $destinationWarehouse->id)
        ->where('product_id', $product->id)
        ->first();

    expect($movements['out']->type)->toBe('TRANSFER_OUT')
        ->and($movements['in']->type)->toBe('TRANSFER_IN')
        ->and($sourceStock->quantity)->toBe(6)
        ->and($destinationStock->quantity)->toBe(4)
        ->and($destinationStock->average_cost)->toBe('100.00');
});

test('posted movement cannot be posted twice', function () {
    $service = app(StockMovementService::class);
    $warehouse = stockMovementWarehouse();
    $product = stockMovementProduct();

    $movement = $service->createDraft([
        'movement_number' => 'RST002',
        'type' => 'RESTOCK',
        'warehouse_id' => $warehouse->id,
    ]);

    $service->addDetail($movement, [
        'product_id' => $product->id,
        'quantity' => 1,
        'unit_cost' => '10.00',
    ]);

    $service->post($movement);

    expect(fn () => $service->post($movement))
        ->toThrow(Exception::class, 'Stock movement is already processed.');
});

test('cancelled movement cannot be posted', function () {
    $service = app(StockMovementService::class);
    $warehouse = stockMovementWarehouse();

    $movement = StockMovement::create([
        'movement_number' => 'CAN001',
        'type' => 'RESTOCK',
        'warehouse_id' => $warehouse->id,
        'status' => 'CANCELLED',
    ]);

    expect(fn () => $service->post($movement))
        ->toThrow(Exception::class, 'Stock movement is already processed.');
});
