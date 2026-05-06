<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guests are redirected from products page', function () {
    $this->get(route('products.index'))
        ->assertRedirect(route('login'));
});

test('authenticated users can view products', function () {
    $user = User::factory()->create();
    $category = Category::create([
        'name' => 'Clothes',
    ]);

    Product::create([
        'sku' => 'TSHIRT001',
        'name' => 'T-Shirt',
        'category_id' => $category->id,
        'unit' => 'pcs',
        'minimum_stock' => 10,
        'is_active' => true,
    ]);

    $this->actingAs($user)
        ->get(route('products.index'))
        ->assertOk();
});

test('authenticated users can create a product', function () {
    $user = User::factory()->create();
    $category = Category::create([
        'name' => 'Clothes',
    ]);

    $this->actingAs($user)
        ->post(route('products.store'), [
            'sku' => 'TSHIRT001',
            'name' => 'T-Shirt',
            'category_id' => $category->id,
            'unit' => 'pcs',
            'minimum_stock' => 10,
            'is_active' => true,
        ])
        ->assertRedirect(route('products.index'));

    $this->assertDatabaseHas('products', [
        'sku' => 'TSHIRT001',
        'name' => 'T-Shirt',
        'category_id' => $category->id,
        'unit' => 'pcs',
        'minimum_stock' => 10,
        'is_active' => true,
    ]);
});

test('product sku is required and unique', function () {
    $user = User::factory()->create();
    $category = Category::create([
        'name' => 'Clothes',
    ]);

    Product::create([
        'sku' => 'TSHIRT001',
        'name' => 'T-Shirt',
        'category_id' => $category->id,
        'unit' => 'pcs',
        'minimum_stock' => 10,
        'is_active' => true,
    ]);

    $this->actingAs($user)
        ->post(route('products.store'), [
            'sku' => 'TSHIRT001',
            'name' => 'Another T-Shirt',
            'category_id' => $category->id,
            'unit' => 'pcs',
            'minimum_stock' => 5,
            'is_active' => true,
        ])
        ->assertSessionHasErrors('sku');
});

test('authenticated users can update a product', function () {
    $user = User::factory()->create();
    $category = Category::create([
        'name' => 'Clothes',
    ]);

    $product = Product::create([
        'sku' => 'TSHIRT001',
        'name' => 'T-Shirt',
        'category_id' => $category->id,
        'unit' => 'pcs',
        'minimum_stock' => 10,
        'is_active' => true,
    ]);

    $this->actingAs($user)
        ->put(route('products.update', $product), [
            'sku' => 'TSHIRT002',
            'name' => 'Updated T-Shirt',
            'category_id' => $category->id,
            'unit' => 'box',
            'minimum_stock' => 20,
            'is_active' => false,
        ])
        ->assertRedirect(route('products.index'));

    $this->assertDatabaseHas('products', [
        'id' => $product->id,
        'sku' => 'TSHIRT002',
        'name' => 'Updated T-Shirt',
        'category_id' => $category->id,
        'unit' => 'box',
        'minimum_stock' => 20,
        'is_active' => false,
    ]);
});

test('authenticated users can delete a product', function () {
    $user = User::factory()->create();
    $category = Category::create([
        'name' => 'Clothes',
    ]);

    $product = Product::create([
        'sku' => 'TSHIRT001',
        'name' => 'T-Shirt',
        'category_id' => $category->id,
        'unit' => 'pcs',
        'minimum_stock' => 10,
        'is_active' => true,
    ]);

    $this->actingAs($user)
        ->delete(route('products.destroy', $product))
        ->assertRedirect(route('products.index'));

    $this->assertDatabaseMissing('products', [
        'id' => $product->id,
    ]);
});
