<?php

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guests are redirected from categories page', function () {
    $this->get(route('categories.index'))
        ->assertRedirect(route('login'));
});

test('authenticated users can view categories', function () {
    $user = User::factory()->create();

    Category::create([
        'name' => 'Clothes',
    ]);

    $this->actingAs($user)
        ->get(route('categories.index'))
        ->assertOk();
});

test('authenticated users can create a category', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('categories.store'), [
            'name' => 'Clothes',
        ])
        ->assertRedirect(route('categories.index'));

    $this->assertDatabaseHas('categories', [
        'name' => 'Clothes',
    ]);
});

test('authenticated users can update a category', function() {
    $user = User::factory()->create();

    $category = category::create([
        'name' => 'Clothes',
    ]);

    $this->actingAs($user)
        ->put(route('categories.update', $category), [
            'name' => 'Clothes2',
        ])
        ->assertRedirect(route('categories.index'));

    
    $this->assertDatabaseHas('categories', [
        'id' => $category->id,
        'name' => 'Clothes2',
    ]);
});

test('authenticated users can delete a category', function() {
    $user = User::factory()->create();

    $category = category::create([
        'name' => 'Clothes',
    ]);

    $this->actingAs($user)
        ->delete(route('categories.destroy', $category))
        ->assertRedirect(route('categories.index'));

    $this->assertDatabaseMissing('categories',[
        'id' => $category->id
    ]);
});