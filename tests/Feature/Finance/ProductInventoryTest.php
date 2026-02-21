<?php

use App\Models\InventoryItem;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->finance = User::factory()->finance()->create();
    $this->actingAs($this->finance);
});

test('finance product inventory page renders product price catalog', function () {
    InventoryItem::factory()->create([
        'name' => 'School Uniform (Male - Small)',
        'type' => 'uniform',
        'price' => 450,
    ]);
    InventoryItem::factory()->create([
        'name' => 'Mathematics 7 Textbook',
        'type' => 'book',
        'price' => 1200,
    ]);

    $this->get('/finance/product-inventory')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('finance/product-inventory/index')
            ->has('product_items', 2)
            ->where('product_items.0.name', 'Mathematics 7 Textbook')
            ->where('product_items.1.type', 'uniform')
        );
});

test('finance can create update and delete product price items', function () {
    $this->post('/finance/product-inventory', [
        'name' => 'PE Shirt (Small)',
        'type' => 'uniform',
        'price' => 280,
    ])->assertRedirect();

    $item = InventoryItem::query()->first();

    expect($item)->not->toBeNull();
    expect($item->name)->toBe('PE Shirt (Small)');
    expect($item->type)->toBe('uniform');
    expect((float) $item->price)->toBe(280.0);

    $this->patch("/finance/product-inventory/{$item->id}", [
        'name' => 'School ID Lace',
        'type' => 'other',
        'price' => 65.5,
    ])->assertRedirect();

    $item->refresh();

    expect($item->name)->toBe('School ID Lace');
    expect($item->type)->toBe('other');
    expect((float) $item->price)->toBe(65.5);

    $this->delete("/finance/product-inventory/{$item->id}")
        ->assertRedirect();

    expect(InventoryItem::query()->whereKey($item->id)->exists())->toBeFalse();
});
