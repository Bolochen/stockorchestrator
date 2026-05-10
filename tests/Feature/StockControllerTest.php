<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\Stock;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

function stockControllerProduct(array $overrides = []): Product
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

function stockControllerWarehouse(array $overrides = []): Warehouse
{
    return Warehouse::create(array_merge([
        'code' => fake()->unique()->bothify('W###'),
        'name' => fake()->unique()->bothify('Warehouse ###'),
        'location' => 'Jakarta',
    ], $overrides));
}

test('guests are redirected from stock page', function () {
    $this->get(route('stocks.index'))
        ->assertRedirect(route('login'));
});

test('authenticated users can view stock index page', function () {
    $user = User::factory()->create();
    $warehouse = stockControllerWarehouse();
    $product = stockControllerProduct();

    Stock::create([
        'warehouse_id' => $warehouse->id,
        'product_id' => $product->id,
        'quantity' => 10,
        'average_cost' => '100.00',
        'total_value' => '1000.00',
    ]);

    $this->actingAs($user)
        ->get(route('stocks.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('stocks/index')
            ->has('stocks', 1)
            ->has('warehouses', 1)
            ->has('products', 1));
});

test('authenticated users can open stock movement create page with transfer option', function () {
    $user = User::factory()->create();
    stockControllerWarehouse();
    stockControllerProduct();

    $this->actingAs($user)
        ->get(route('stocks.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('stocks/create')
            ->where('movementTypes', ['RESTOCK', 'SALE', 'TRANSFER', 'ADJUSTMENT'])
            ->has('warehouses', 1)
            ->has('products', 1));
});

test('authenticated users can create and post restock movement from stock controller', function () {
    $user = User::factory()->create();
    $warehouse = stockControllerWarehouse();
    $product = stockControllerProduct();

    $this->withoutMiddleware(ValidateCsrfToken::class)
        ->actingAs($user)
        ->post(route('stocks.store'), [
            'post' => true,
            'movement' => [
                'movement_number' => 'WEBRST001',
                'type' => 'RESTOCK',
                'warehouse_id' => $warehouse->id,
                'reference_number' => 'PO001',
            ],
            'details' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 5,
                    'unit_cost' => '120.00',
                ],
            ],
        ])
        ->assertRedirect(route('stocks.index'));

    $this->assertDatabaseHas('stock_movements', [
        'movement_number' => 'WEBRST001',
        'type' => 'RESTOCK',
        'status' => 'POSTED',
    ]);
    $this->assertDatabaseHas('stocks', [
        'warehouse_id' => $warehouse->id,
        'product_id' => $product->id,
        'quantity' => 5,
        'average_cost' => '120.00',
        'total_value' => '600.00',
    ]);
});

test('authenticated users can create transfer from stock controller', function () {
    $user = User::factory()->create();
    $sourceWarehouse = stockControllerWarehouse(['code' => 'SRC']);
    $destinationWarehouse = stockControllerWarehouse(['code' => 'DST']);
    $product = stockControllerProduct();

    Stock::create([
        'warehouse_id' => $sourceWarehouse->id,
        'product_id' => $product->id,
        'quantity' => 10,
        'average_cost' => '100.00',
        'total_value' => '1000.00',
    ]);

    $this->withoutMiddleware(ValidateCsrfToken::class)
        ->actingAs($user)
        ->post(route('stocks.transfers.store'), [
            'movement' => [
                'warehouse_id' => $sourceWarehouse->id,
                'destination_warehouse_id' => $destinationWarehouse->id,
            ],
            'details' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 4,
                ],
            ],
        ])
        ->assertRedirect(route('stocks.index'));

    $this->assertDatabaseHas('stock_movements', [
        'type' => 'TRANSFER_OUT',
        'warehouse_id' => $sourceWarehouse->id,
        'destination_warehouse_id' => $destinationWarehouse->id,
        'status' => 'POSTED',
    ]);
    $this->assertDatabaseHas('stock_movements', [
        'type' => 'TRANSFER_IN',
        'warehouse_id' => $destinationWarehouse->id,
        'destination_warehouse_id' => $sourceWarehouse->id,
        'status' => 'POSTED',
    ]);
    $this->assertDatabaseHas('stocks', [
        'warehouse_id' => $sourceWarehouse->id,
        'product_id' => $product->id,
        'quantity' => 6,
    ]);
    $this->assertDatabaseHas('stocks', [
        'warehouse_id' => $destinationWarehouse->id,
        'product_id' => $product->id,
        'quantity' => 4,
        'average_cost' => '100.00',
    ]);
});

test('stock show page renders valuation', function () {
    $user = User::factory()->create();
    $warehouse = stockControllerWarehouse();
    $product = stockControllerProduct();
    $stock = Stock::create([
        'warehouse_id' => $warehouse->id,
        'product_id' => $product->id,
        'quantity' => 3,
        'average_cost' => '25.00',
        'total_value' => '75.00',
    ]);

    $this->actingAs($user)
        ->get(route('stocks.show', $stock))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('stocks/show')
            ->where('valuation.total_value', '75.00'));
});
