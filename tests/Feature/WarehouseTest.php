<?php

use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guests are redirected from warehouses page', function () {
    $this->get(route('warehouses.index'))
        ->assertRedirect(route('login'));
});

test('authenticated users can view warehouses', function () {
    $user = User::factory()->create();

    Warehouse::create([
        'code' => 'WH001',
        'name' => 'Main Warehouse',
        'location' => 'Jakarta',
    ]);

    $this->actingAs($user)
        ->get(route('warehouses.index'))
        ->assertOk();
});

test('authenticated users can create a warehouse', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('warehouses.store'), [
            'code' => 'WH001',
            'name' => 'Main Warehouse',
            'location' => 'Jakarta',
        ])
        ->assertRedirect(route('warehouses.index'));

    $this->assertDatabaseHas('warehouses', [
        'code' => 'WH001',
        'name' => 'Main Warehouse',
        'location' => 'Jakarta',
    ]);
});

test('warehouse code is required and unique', function() {
    $user = User::factory()->create();

    Warehouse::create([
        'code' => 'WH001',
        'name' => 'Main Warehouse',
        'location' => 'Jakarta'
    ]);

    $this->actingAs($user)
        ->post(route('warehouses.store'), [
            'code' => 'WH001',
            'name' => 'Main Warehouse',
            'location' => 'Jakarta',
        ])
        ->assertSessionHasErrors('code');
});

test('authenticated users can update a warehouse', function() {
    $user = User::factory()->create();

    $warehouse = Warehouse::create([
        'code' => 'WH001',
        'name' => 'Main Warehouse',
        'location' => 'Jakarta'
    ]);

    $this->actingAs($user)
        ->put(route('warehouses.update', $warehouse), [
          'code' => 'WH001',
          'name' => 'Warehouse 1',
          'location' => 'Jakarta'
        ])
        ->assertRedirect(route('warehouses.index'));

    
    $this->assertDatabaseHas('warehouses', [
        'id' => $warehouse->id,
        'code' => 'WH001',
        'name' => 'Warehouse 1',
        'location' => 'Jakarta'
    ]);
});

test('authenticated users can delete a warehouse', function() {
    $user = User::factory()->create();

    $warehouse = Warehouse::create([
        'code' => 'WH001',
        'name' => 'Main Warehouse',
        'location' => 'Jakarta'
    ]);

    $this->actingAs($user)
        ->delete(route('warehouses.destroy', $warehouse))
        ->assertRedirect(route('warehouses.index'));

    $this->assertDatabaseMissing('warehouses',[
        'id' => $warehouse->id
    ]);
});